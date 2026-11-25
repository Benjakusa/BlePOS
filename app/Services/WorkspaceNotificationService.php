<?php

namespace App\Services;

use App\Models\PlatformAnnouncement;
use App\Models\PlatformInvoice;
use App\Models\PlatformUser;
use App\Models\SupportCase;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\WorkspaceAlert;

class WorkspaceNotificationService
{
    public function tenantUsers(int $tenantId, array $data): void
    {
        User::withoutGlobalScopes()->where('tenant_id', $tenantId)->each(
            fn (User $user) => $user->notify(new WorkspaceAlert($data))
        );
    }

    public function supportAgents(array $data): void
    {
        PlatformUser::query()->where('status', 'active')
            ->whereHas('roles.permissions', fn ($query) => $query->where('name', 'platform.support.view'))
            ->each(fn (PlatformUser $user) => $user->notify(new WorkspaceAlert($data)));
    }

    public function caseCreated(SupportCase $case): void
    {
        $this->supportAgents([
            'title' => 'New tenant support ticket',
            'message' => "{$case->case_number}: {$case->subject}",
            'url' => route('platform.support.cases.show', $case),
            'kind' => 'support',
        ]);
    }

    public function caseReply(SupportCase $case, bool $fromTenant): void
    {
        if ($fromTenant) {
            $this->supportAgents([
                'title' => 'Tenant replied to a support ticket',
                'message' => "{$case->case_number}: {$case->subject}",
                'url' => route('platform.support.cases.show', $case),
                'kind' => 'support',
            ]);

            return;
        }

        $this->tenantUsers($case->tenant_id, [
            'title' => 'Platform replied to your support ticket',
            'message' => "{$case->case_number}: {$case->subject}",
            'url' => route('tenant.support.cases.show', $case),
            'kind' => 'support',
        ]);
    }

    public function invoiceIssued(PlatformInvoice $invoice): void
    {
        $this->tenantUsers($invoice->tenant_id, [
            'title' => 'New invoice from BlePOS',
            'message' => "{$invoice->invoice_number} is due {$invoice->due_date->format('d M Y')}.",
            'url' => route('tenant.subscription.index'),
            'kind' => 'invoice',
        ]);
    }

    public function announcementPublished(PlatformAnnouncement $announcement): void
    {
        $tenants = Tenant::query()->where('status', '!=', 'deleted');
        match ($announcement->target_type) {
            'tenant' => $tenants->whereKey($announcement->target_value),
            'industry' => $tenants->where('industry', $announcement->target_value),
            'plan' => $tenants->whereHas('subscriptions.plan', function ($query) use ($announcement): void {
                $query->where('id', $announcement->target_value)->orWhere('slug', $announcement->target_value);
            }),
            default => null,
        };
        $tenants->pluck('id')->each(fn ($tenantId) => $this->tenantUsers((int) $tenantId, [
            'title' => 'Platform announcement: '.$announcement->title,
            'message' => $announcement->content,
            'url' => route('tenant.announcements.show', $announcement),
            'kind' => 'announcement',
        ]));
    }
}
