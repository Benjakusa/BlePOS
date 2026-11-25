<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\PlatformAnnouncement;
use App\Services\TenantContext;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TenantAnnouncementController extends Controller
{
    public function index(): View
    {
        return view('tenant.announcements.index', ['announcements' => $this->visibleAnnouncements()->latest()->paginate(20)]);
    }

    public function show(Request $request, PlatformAnnouncement $announcement): View
    {
        abort_unless($this->visibleAnnouncements()->whereKey($announcement->id)->exists(), 404);
        $tenant = TenantContext::getTenant();
        $announcement->reads()->firstOrCreate([
            'tenant_id' => $tenant->id,
            'user_id' => $request->user()->id,
        ], ['read_at' => now()]);

        return view('tenant.announcements.show', compact('announcement'));
    }

    private function visibleAnnouncements()
    {
        $tenant = TenantContext::getTenant();
        abort_if(! $tenant, 403);
        $planTargets = $tenant->subscriptions()->with('plan')->get()
            ->flatMap(fn ($subscription) => [$subscription->plan_id, $subscription->plan?->slug])
            ->filter()->map(fn ($value) => (string) $value)->all();

        return PlatformAnnouncement::query()->where('status', 'published')
            ->where(fn ($query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>=', now()))
            ->where(function ($query) use ($tenant, $planTargets): void {
                $query->where('target_type', 'all')
                    ->orWhere(fn ($q) => $q->where('target_type', 'tenant')->where('target_value', $tenant->id))
                    ->orWhere(fn ($q) => $q->where('target_type', 'industry')->where('target_value', $tenant->industry))
                    ->orWhere(fn ($q) => $q->where('target_type', 'plan')->whereIn('target_value', $planTargets));
            });
    }
}
