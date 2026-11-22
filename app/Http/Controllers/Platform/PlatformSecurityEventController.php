<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\PlatformSecurityEvent;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlatformSecurityEventController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'severity' => ['nullable', 'in:info,warning,critical'],
            'search' => ['nullable', 'string', 'max:120'],
        ]);
        $events = PlatformSecurityEvent::query()
            ->with('user:id,name,email')
            ->when($filters['severity'] ?? null, fn ($query, string $severity) => $query->where('severity', $severity))
            ->when($filters['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('event_type', 'like', '%'.$search.'%')
                        ->orWhere('ip_address', 'like', '%'.$search.'%')
                        ->orWhereHas('user', function ($query) use ($search): void {
                            $query->where('name', 'like', '%'.$search.'%')
                                ->orWhere('email', 'like', '%'.$search.'%');
                        });
                });
            })
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->paginate(50)
            ->withQueryString();

        return view('platform.security.events', [
            'events' => $events,
            'filters' => $filters,
        ]);
    }
}
