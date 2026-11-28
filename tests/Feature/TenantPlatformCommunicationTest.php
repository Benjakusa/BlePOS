<?php

namespace Tests\Feature;

use App\Models\PlatformInvoice;
use App\Models\PlatformRole;
use App\Models\PlatformSession;
use App\Models\PlatformUser;
use App\Models\SupportCase;
use App\Models\Tenant;
use App\Models\User;
use App\Services\SupportService;
use App\Services\WorkspaceNotificationService;
use Database\Seeders\HotelTenantSeeder;
use Database\Seeders\PlatformRoleSeeder;
use Database\Seeders\RestaurantTenantSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class TenantPlatformCommunicationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PlatformRoleSeeder::class);
        $this->seed(RestaurantTenantSeeder::class);
        $this->seed(HotelTenantSeeder::class);
    }

    public function test_tenant_can_open_ticket_reply_and_only_see_its_public_conversation(): void
    {
        [$agent, $session] = $this->createPlatformUser('support-agent');
        $owner = User::where('email', 'restaurant.owner@blepos.co.ke')->firstOrFail();
        $otherOwner = User::withoutGlobalScopes()->where('email', 'hotel.owner@blepos.co.ke')->firstOrFail();

        $this->actingAs($owner)->post(route('tenant.support.cases.store'), [
            'subject' => 'Need help with reports',
            'description' => 'The monthly report is missing the tax total.',
            'category' => 'system_bug',
            'priority' => 'medium',
        ])->assertRedirect();

        $case = SupportCase::query()->where('tenant_id', $owner->tenant_id)->firstOrFail();
        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => PlatformUser::class,
            'notifiable_id' => $agent->id,
        ]);

        $case->notes()->create([
            'platform_user_id' => $agent->id,
            'author_name' => $agent->name,
            'content' => 'Internal investigation note; do not show the tenant.',
            'is_internal' => true,
        ]);

        $this->platformRequest($agent, $session)->post(route('platform.support.cases.notes.store', $case), [
            'content' => 'Please try the report again; we have fixed the issue.',
            'is_internal' => false,
        ])->assertRedirect();

        $this->actingAs($owner)->get(route('tenant.support.cases.show', $case))
            ->assertOk()
            ->assertSee('Please try the report again; we have fixed the issue.')
            ->assertDontSee('Internal investigation note');
        $this->actingAs($otherOwner)->get(route('tenant.support.cases.show', $case))->assertNotFound();
        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $owner->id,
            'data->title' => 'Platform replied to your support ticket',
        ]);

        $this->actingAs($owner)->post(route('tenant.support.cases.reply', $case), ['content' => 'That resolved it, thanks.'])
            ->assertRedirect();
        $this->assertDatabaseHas('support_case_notes', [
            'support_case_id' => $case->id,
            'tenant_user_id' => $owner->id,
            'content' => 'That resolved it, thanks.',
            'is_internal' => false,
        ]);
        $this->assertGreaterThanOrEqual(2, $agent->fresh()->unreadNotifications()->count());
    }

    public function test_platform_invoice_is_pushed_to_tenant_and_due_countdown_is_visible(): void
    {
        $owner = User::where('email', 'restaurant.owner@blepos.co.ke')->firstOrFail();
        $tenant = Tenant::findOrFail($owner->tenant_id);
        [$billingAdmin, $session] = $this->createPlatformUser('billing-administrator');
        $this->platformRequest($billingAdmin, $session)->post(route('platform.invoices.store'), [
            'tenant_id' => $tenant->id,
            'due_date' => now()->addDays(3)->toDateString(),
            'currency' => 'KES',
            'items' => [['description' => 'Platform service', 'quantity' => 1, 'unit_price' => 1250]],
        ])->assertRedirect();
        $invoice = PlatformInvoice::query()->where('tenant_id', $tenant->id)->latest('id')->firstOrFail();

        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $owner->id,
            'data->title' => 'New invoice from BlePOS',
        ]);
        $this->actingAs($owner)->get(route('tenant.subscription.index'))
            ->assertOk()
            ->assertSee($invoice->invoice_number)
            ->assertSee('Due in 3 days');

        $notification = $owner->fresh()->unreadNotifications()->firstOrFail();
        $this->actingAs($owner)->get(route('tenant.notifications.index'))->assertOk()->assertSee('New invoice from BlePOS');
        $this->actingAs($owner)->post(route('tenant.notifications.read', $notification->id))->assertRedirect(route('tenant.subscription.index'));

        $otherOwner = User::withoutGlobalScopes()->where('email', 'hotel.owner@blepos.co.ke')->firstOrFail();
        $this->actingAs($otherOwner)->get(route('tenant.subscription.index'))
            ->assertOk()
            ->assertDontSee($invoice->invoice_number);

        $invoice->update(['due_date' => now()->subDays(2)->toDateString()]);
        $this->assertSame('Overdue by 2 days', $invoice->fresh()->dueCountdown());
    }

    public function test_targeted_platform_announcement_is_visible_only_to_its_tenant(): void
    {
        $owner = User::where('email', 'restaurant.owner@blepos.co.ke')->firstOrFail();
        $otherOwner = User::withoutGlobalScopes()->where('email', 'hotel.owner@blepos.co.ke')->firstOrFail();
        $announcement = app(SupportService::class)->createAnnouncement([
            'title' => 'Restaurant maintenance window',
            'content' => 'Scheduled maintenance is at 11 PM.',
            'priority' => 'warning',
            'target_type' => 'tenant',
            'target_value' => (string) $owner->tenant_id,
        ]);
        app(WorkspaceNotificationService::class)->announcementPublished($announcement);

        $this->actingAs($owner)->get(route('tenant.announcements.index'))->assertOk()->assertSee($announcement->title);
        $this->actingAs($otherOwner)->get(route('tenant.announcements.index'))->assertOk()->assertDontSee($announcement->title);
        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $owner->id,
            'data->title' => 'Platform announcement: Restaurant maintenance window',
        ]);
    }

    public function test_support_feedback_and_announcements_render_popup_and_flashing_red_bell(): void
    {
        $owner = User::where('email', 'restaurant.owner@blepos.co.ke')->firstOrFail();

        // 1. Publish announcement -> tenant sees flashing red bell & popup modal
        $announcement = app(SupportService::class)->createAnnouncement([
            'title' => 'Urgent eTIMS update',
            'content' => 'Please refresh your eTIMS configuration before 5 PM.',
            'priority' => 'critical',
            'target_type' => 'tenant',
            'target_value' => (string) $owner->tenant_id,
        ]);
        app(WorkspaceNotificationService::class)->announcementPublished($announcement);

        $dashResponse = $this->actingAs($owner)->get(route('tenant.dashboard'));
        $dashResponse->assertOk()
            ->assertSee('notif-bell-btn has-unread', false)
            ->assertSee('notif-count-badge', false)
            ->assertSee('ble-popup-card kind-announcement', false)
            ->assertSee('Platform Announcement')
            ->assertSee('Urgent eTIMS update');

        $pollResponse = $this->actingAs($owner)->getJson(route('tenant.notifications.poll'));
        $pollResponse->assertOk()
            ->assertJsonPath('unread_count', 1)
            ->assertJsonPath('popups.0.kind', 'announcement');

        $notification = $owner->fresh()->unreadNotifications()->firstOrFail();
        $this->actingAs($owner)->postJson(route('tenant.notifications.dismiss', $notification->id))
            ->assertOk()
            ->assertJson(['ok' => true, 'unread_count' => 0]);

        $this->actingAs($owner)->get(route('tenant.dashboard'))
            ->assertOk()
            ->assertDontSee('notif-bell-btn has-unread', false)
            ->assertDontSee('class="ble-popup-card', false);

        // 2. Platform support reply -> tenant sees support ticket feedback popup & flashing bell
        $case = SupportCase::query()->create([
            'tenant_id' => $owner->tenant_id,
            'case_number' => 'SUP-POPUP-001',
            'subject' => 'Printer offline',
            'description' => 'Receipt printer stopped',
            'category' => 'hardware_pos',
            'priority' => 'high',
            'status' => 'open',
            'requester_user_id' => $owner->id,
            'requester_name' => $owner->name,
            'requester_email' => $owner->email,
        ]);
        app(WorkspaceNotificationService::class)->caseReply($case, false);

        $this->actingAs($owner)->get(route('tenant.dashboard'))
            ->assertOk()
            ->assertSee('notif-bell-btn has-unread', false)
            ->assertSee('ble-popup-card kind-support', false)
            ->assertSee('Support Ticket Feedback')
            ->assertSee('Platform replied to your support ticket');

        // 3. Tenant support reply -> platform agent sees flashing red bell & support feedback popup
        [$agent, $session] = $this->createPlatformUser('support-agent');
        app(WorkspaceNotificationService::class)->caseReply($case, true);

        $this->platformRequest($agent, $session)->get(route('platform.support.cases.index'))
            ->assertOk()
            ->assertSee('notif-bell-btn has-unread', false)
            ->assertSee('ble-popup-card kind-support', false)
            ->assertSee('Tenant replied to a support ticket');
    }

    private function createPlatformUser(string $roleSlug): array
    {
        $user = PlatformUser::factory()->create(['mfa_confirmed_at' => now()]);
        $role = PlatformRole::where('slug', $roleSlug)->firstOrFail();
        $user->roles()->attach($role->id);
        $sessionId = Str::random(40);
        PlatformSession::create([
            'platform_user_id' => $user->id,
            'session_hash' => hash('sha256', $sessionId),
            'session_identifier' => $sessionId,
            'logged_in_at' => now(),
            'last_activity_at' => now(),
            'expires_at' => now()->addHours(8),
        ]);

        return [$user, $sessionId];
    }

    private function platformRequest(PlatformUser $user, string $sessionId)
    {
        return $this->actingAs($user, 'platform')
            ->withSession([
                'platform_mfa_verified_at' => now()->timestamp,
                'platform_step_up_at' => now()->timestamp,
            ])
            ->withCookie(config('session.platform.cookie'), $sessionId);
    }
}
