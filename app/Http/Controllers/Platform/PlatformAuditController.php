<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\PlatformAuditLog;
use App\Services\PlatformAuditRecorder;
use App\Services\PlatformAuditVerifier;
use App\Services\PlatformPermissionAuthorizer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PlatformAuditController extends Controller
{
    public function index(Request $request, PlatformAuditVerifier $verifier, PlatformPermissionAuthorizer $authorizer): View
    {
        $authorizer->ensure($request->user('platform'), 'platform.audit.view');

        $filters = $request->validate([
            'actor_id' => ['nullable', 'integer', 'exists:platform_users,id'],
            'action' => ['nullable', 'string', 'max:160'],
            'target_type' => ['nullable', 'string', 'max:120'],
            'target_id' => ['nullable', 'string', 'max:120'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        return view('platform.security.audit', [
            'entries' => $this->filteredQuery($filters)->paginate(50)->withQueryString(),
            'integrity' => $verifier->verify(),
            'filters' => $filters,
        ]);
    }

    public function export(Request $request, PlatformAuditRecorder $audit, PlatformPermissionAuthorizer $authorizer): StreamedResponse
    {
        $authorizer->ensure($request->user('platform'), 'platform.audit.export');

        $filters = $request->validate([
            'actor_id' => ['nullable', 'integer', 'exists:platform_users,id'],
            'action' => ['nullable', 'string', 'max:160'],
            'target_type' => ['nullable', 'string', 'max:120'],
            'target_id' => ['nullable', 'string', 'max:120'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);
        $audit->record($request->user('platform')->id, 'platform.audit.exported', PlatformAuditLog::class, null, ['filters' => $filters]);
        $filename = 'blepos-platform-audit-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($filters): void {
            $output = fopen('php://output', 'w');
            fputcsv($output, ['ID', 'Occurred at', 'Actor email', 'Action', 'Target type', 'Target ID', 'Details', 'Previous hash', 'Entry hash']);

            foreach ($this->filteredQuery($filters)->reorder('id')->with('actor')->lazyById(500) as $entry) {
                fputcsv($output, [
                    $entry->id,
                    $entry->occurred_at->toISOString(),
                    $this->safeCsvValue($entry->actor?->email),
                    $this->safeCsvValue($entry->action),
                    $this->safeCsvValue($entry->target_type),
                    $this->safeCsvValue($entry->target_id),
                    $this->safeCsvValue(json_encode($entry->details, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) ?: ''),
                    $entry->previous_hash,
                    $entry->entry_hash,
                ]);
            }

            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** @param array<string, mixed> $filters */
    private function filteredQuery(array $filters): Builder
    {
        $query = PlatformAuditLog::query()->orderByDesc('id');
        foreach (['actor_id', 'action', 'target_type', 'target_id'] as $field) {
            if (isset($filters[$field])) {
                $query->where($field, $filters[$field]);
            }
        }
        if (isset($filters['from'])) {
            $query->where('occurred_at', '>=', $filters['from']);
        }
        if (isset($filters['to'])) {
            $query->where('occurred_at', '<=', $filters['to']);
        }

        return $query;
    }

    private function safeCsvValue(?string $value): string
    {
        if ($value !== null && preg_match('/^[\s]*[=+\-@]/u', $value) === 1) {
            return "'".$value;
        }

        return $value ?? '';
    }
}
