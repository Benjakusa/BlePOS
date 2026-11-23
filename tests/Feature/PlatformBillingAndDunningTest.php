<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\PlatformDunningEvent;
use App\Models\PlatformInvoice;
use App\Models\PlatformPayment;
use App\Models\PlatformRole;
use App\Models\PlatformSession;
use App\Models\PlatformUser;
use App\Models\Subscription;
use App\Models\Tenant;
use Database\Seeders\PlatformRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PlatformBillingAndDunningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PlatformRoleSeeder::class);
    }

    /**
     * Helper to create a platform user with a specific role and session.
     *
     * @return array{0: PlatformUser, 1: string}
     */
    private function createPlatformAdmin(string $roleSlug = 'billing-administrator'): array
    {
        $user = PlatformUser::factory()->create(['mfa_confirmed_at' => now()]);
        $role = PlatformRole::query()->where('slug', $roleSlug)->firstOrFail();
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

    public function test_billing_admin_can_view_invoices_index_and_summary(): void
    {
        [$user, $sessionId] = $this->createPlatformAdmin();
        $tenant = Tenant::factory()->create(['status' => 'active']);

        PlatformInvoice::query()->create([
            'tenant_id' => $tenant->id,
            'invoice_number' => 'INV-TEST-001',
            'status' => 'issued',
            'currency' => 'KES',
            'subtotal' => '10000.00',
            'tax_rate' => '16.00',
            'tax_amount' => '1600.00',
            'total_amount' => '11600.00',
            'amount_paid' => '0.00',
            'amount_due' => '11600.00',
            'due_date' => now()->addDays(14)->toDateString(),
            'issued_at' => now(),
        ]);

        $response = $this->platformRequest($user, $sessionId)
            ->get(route('platform.invoices.index'));

        $response->assertOk();
        $response->assertSee('Platform Invoices');
        $response->assertSee('INV-TEST-001');
        $response->assertSee('11,600.00');
    }

    public function test_billing_admin_can_issue_invoice_with_items_vat_and_audit(): void
    {
        [$user, $sessionId] = $this->createPlatformAdmin();
        $tenant = Tenant::factory()->create(['status' => 'active']);
        $plan = Plan::query()->create([
            'code' => 'pro-retail',
            'name' => 'Pro Retail Plan',
            'currency' => 'KES',
            'monthly_price' => '8000.00',
            'annual_price' => '80000.00',
            'is_active' => true,
        ]);
        $subscription = Subscription::query()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'starts_at' => now(),
        ]);

        $response = $this->platformRequest($user, $sessionId)
            ->post(route('platform.invoices.store'), [
                'tenant_id' => $tenant->id,
                'due_date' => now()->addDays(14)->toDateString(),
                'currency' => 'KES',
                'notes' => 'Monthly subscription renewal',
                'items' => [
                    [
                        'description' => 'Pro Retail Monthly Subscription',
                        'quantity' => 1,
                        'unit_price' => '8000.00',
                    ],
                    [
                        'description' => 'Extra 2 Shop Outlets Add-on',
                        'quantity' => 2,
                        'unit_price' => '1000.00',
                    ],
                ],
            ]);

        $response->assertRedirect();

        // Subtotal = 8000 + (2 * 1000) = 10000.00
        // VAT 16% = 1600.00
        // Total = 11600.00
        $this->assertDatabaseHas('platform_invoices', [
            'tenant_id' => $tenant->id,
            'currency' => 'KES',
            'subtotal' => '10000.00',
            'tax_rate' => '16.00',
            'tax_amount' => '1600.00',
            'total_amount' => '11600.00',
            'amount_due' => '11600.00',
            'amount_paid' => '0.00',
            'status' => 'issued',
            'etims_status' => 'blocked_on_certification',
        ]);

        $this->assertDatabaseHas('platform_invoice_items', [
            'description' => 'Pro Retail Monthly Subscription',
            'quantity' => 1.00,
            'amount' => 8000.00,
        ]);

        $this->assertDatabaseHas('platform_audit_log', [
            'action' => 'platform.invoice.generate',
            'actor_id' => $user->id,
        ]);
    }

    public function test_billing_admin_can_view_invoice_show_page_with_etims_badge(): void
    {
        [$user, $sessionId] = $this->createPlatformAdmin();
        $tenant = Tenant::factory()->create(['status' => 'active']);

        $invoice = PlatformInvoice::query()->create([
            'tenant_id' => $tenant->id,
            'invoice_number' => 'INV-TEST-002',
            'status' => 'issued',
            'currency' => 'KES',
            'subtotal' => '5000.00',
            'tax_rate' => '16.00',
            'tax_amount' => '800.00',
            'total_amount' => '5800.00',
            'amount_paid' => '0.00',
            'amount_due' => '5800.00',
            'due_date' => now()->addDays(14)->toDateString(),
            'issued_at' => now(),
            'etims_status' => 'blocked_on_certification',
        ]);

        $invoice->items()->create([
            'description' => 'Standard Monthly Subscription',
            'quantity' => 1,
            'unit_price' => '5000.00',
            'amount' => '5000.00',
        ]);

        $response = $this->platformRequest($user, $sessionId)
            ->get(route('platform.invoices.show', $invoice));

        $response->assertOk();
        $response->assertSee('INV-TEST-002');
        $response->assertSee('BLOCKED-ON-CERTIFICATION');
        $response->assertSee('Standard Monthly Subscription');
    }

    public function test_recording_payment_updates_invoice_balance_restores_subscription_and_audits(): void
    {
        [$user, $sessionId] = $this->createPlatformAdmin();
        $tenant = Tenant::factory()->create(['status' => 'active']);
        $plan = Plan::query()->create([
            'code' => 'basic',
            'name' => 'Basic Plan',
            'currency' => 'KES',
            'monthly_price' => '5000.00',
            'annual_price' => '50000.00',
            'is_active' => true,
        ]);
        $subscription = Subscription::query()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'past_due',
            'billing_cycle' => 'monthly',
            'starts_at' => now()->subMonth(),
        ]);

        $invoice = PlatformInvoice::query()->create([
            'tenant_id' => $tenant->id,
            'subscription_id' => $subscription->id,
            'invoice_number' => 'INV-TEST-003',
            'status' => 'issued',
            'currency' => 'KES',
            'subtotal' => '5000.00',
            'tax_rate' => '16.00',
            'tax_amount' => '800.00',
            'total_amount' => '5800.00',
            'amount_paid' => '0.00',
            'amount_due' => '5800.00',
            'due_date' => now()->subDays(5)->toDateString(),
            'issued_at' => now()->subDays(19),
        ]);

        // Record partial payment of 3000
        $response = $this->platformRequest($user, $sessionId)
            ->post(route('platform.invoices.payment', $invoice), [
                'amount' => '3000.00',
                'payment_method' => 'mpesa',
                'gateway_reference' => 'QED1234567',
                'notes' => 'Partial M-Pesa payment',
            ]);

        $response->assertRedirectToRoute('platform.invoices.show', $invoice);

        $invoice->refresh();
        $this->assertSame('3000.00', (string) $invoice->amount_paid);
        $this->assertSame('2800.00', (string) $invoice->amount_due);
        $this->assertSame('partially_paid', $invoice->status);

        $this->assertDatabaseHas('platform_payments', [
            'platform_invoice_id' => $invoice->id,
            'payment_method' => 'mpesa',
            'amount' => '3000.00',
            'status' => 'completed',
        ]);

        // Now pay remaining balance of 2800
        $this->platformRequest($user, $sessionId)
            ->post(route('platform.invoices.payment', $invoice), [
                'amount' => '2800.00',
                'payment_method' => 'bank_transfer',
                'gateway_reference' => 'EFT998877',
            ]);

        $invoice->refresh();
        $this->assertSame('5800.00', (string) $invoice->amount_paid);
        $this->assertSame('0.00', (string) $invoice->amount_due);
        $this->assertSame('paid', $invoice->status);

        // Subscription was in past_due, must be restored to active upon full payment
        $subscription->refresh();
        $this->assertSame('active', $subscription->status);

        $this->assertDatabaseHas('platform_audit_log', [
            'action' => 'platform.payment.record',
            'actor_id' => $user->id,
        ]);
    }

    public function test_recording_payment_exceeding_due_balance_fails_validation(): void
    {
        [$user, $sessionId] = $this->createPlatformAdmin();
        $tenant = Tenant::factory()->create(['status' => 'active']);

        $invoice = PlatformInvoice::query()->create([
            'tenant_id' => $tenant->id,
            'invoice_number' => 'INV-TEST-004',
            'status' => 'issued',
            'currency' => 'KES',
            'subtotal' => '1000.00',
            'tax_rate' => '16.00',
            'tax_amount' => '160.00',
            'total_amount' => '1160.00',
            'amount_paid' => '0.00',
            'amount_due' => '1160.00',
            'due_date' => now()->addDays(7)->toDateString(),
        ]);

        $response = $this->platformRequest($user, $sessionId)
            ->post(route('platform.invoices.payment', $invoice), [
                'amount' => '2000.00', // exceeds 1160
                'payment_method' => 'mpesa',
            ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('amount');
    }

    public function test_recording_refund_updates_payment_status_and_invoice_balance(): void
    {
        [$user, $sessionId] = $this->createPlatformAdmin();
        $tenant = Tenant::factory()->create(['status' => 'active']);

        $invoice = PlatformInvoice::query()->create([
            'tenant_id' => $tenant->id,
            'invoice_number' => 'INV-TEST-005',
            'status' => 'paid',
            'currency' => 'KES',
            'subtotal' => '2000.00',
            'tax_rate' => '16.00',
            'tax_amount' => '320.00',
            'total_amount' => '2320.00',
            'amount_paid' => '2320.00',
            'amount_due' => '0.00',
            'due_date' => now()->subDay()->toDateString(),
        ]);

        $payment = PlatformPayment::query()->create([
            'platform_invoice_id' => $invoice->id,
            'tenant_id' => $tenant->id,
            'payment_reference' => 'PAY-TEST-001',
            'payment_method' => 'card',
            'status' => 'completed',
            'currency' => 'KES',
            'amount' => '2320.00',
            'paid_at' => now(),
        ]);

        $response = $this->platformRequest($user, $sessionId)
            ->post(route('platform.invoices.refund', [$invoice, $payment]), [
                'amount' => '1000.00',
                'reason' => 'Customer requested partial downgrade refund',
            ]);

        $response->assertRedirectToRoute('platform.invoices.show', $invoice);

        $this->assertDatabaseHas('platform_refunds', [
            'platform_payment_id' => $payment->id,
            'amount' => '1000.00',
            'reason' => 'Customer requested partial downgrade refund',
            'status' => 'completed',
        ]);

        $payment->refresh();
        $this->assertSame('partially_refunded', $payment->status);

        $invoice->refresh();
        $this->assertSame('1320.00', (string) $invoice->amount_paid);
        $this->assertSame('1000.00', (string) $invoice->amount_due);
        $this->assertSame('partially_paid', $invoice->status);

        $this->assertDatabaseHas('platform_audit_log', [
            'action' => 'platform.refund.record',
            'actor_id' => $user->id,
        ]);
    }

    public function test_voiding_unpaid_invoice_marks_it_voided_and_audits(): void
    {
        [$user, $sessionId] = $this->createPlatformAdmin();
        $tenant = Tenant::factory()->create(['status' => 'active']);

        $invoice = PlatformInvoice::query()->create([
            'tenant_id' => $tenant->id,
            'invoice_number' => 'INV-TEST-006',
            'status' => 'issued',
            'currency' => 'KES',
            'subtotal' => '2000.00',
            'tax_rate' => '16.00',
            'tax_amount' => '320.00',
            'total_amount' => '2320.00',
            'amount_paid' => '0.00',
            'amount_due' => '2320.00',
            'due_date' => now()->addDays(7)->toDateString(),
        ]);

        $response = $this->platformRequest($user, $sessionId)
            ->post(route('platform.invoices.void', $invoice), [
                'reason' => 'Duplicate invoice issued by mistake',
            ]);

        $response->assertRedirectToRoute('platform.invoices.show', $invoice);

        $invoice->refresh();
        $this->assertSame('voided', $invoice->status);

        $this->assertDatabaseHas('platform_audit_log', [
            'action' => 'platform.invoice.void',
            'actor_id' => $user->id,
        ]);
    }

    public function test_dunning_engine_evaluates_overdue_invoices_and_never_suspends_twice(): void
    {
        [$user, $sessionId] = $this->createPlatformAdmin();
        $tenant = Tenant::factory()->create(['status' => 'active']);
        $plan = Plan::query()->create([
            'code' => 'enterprise',
            'name' => 'Enterprise Plan',
            'currency' => 'KES',
            'monthly_price' => '15000.00',
            'annual_price' => '150000.00',
            'is_active' => true,
        ]);
        $subscription = Subscription::query()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'starts_at' => now()->subDays(60),
        ]);

        // Invoice that is 15 days overdue
        $invoice = PlatformInvoice::query()->create([
            'tenant_id' => $tenant->id,
            'subscription_id' => $subscription->id,
            'invoice_number' => 'INV-OVERDUE-001',
            'status' => 'issued',
            'currency' => 'KES',
            'subtotal' => '15000.00',
            'tax_rate' => '16.00',
            'tax_amount' => '2400.00',
            'total_amount' => '17400.00',
            'amount_paid' => '0.00',
            'amount_due' => '17400.00',
            'due_date' => now()->subDays(15)->toDateString(),
            'issued_at' => now()->subDays(30),
        ]);

        // First dunning trigger
        $response = $this->platformRequest($user, $sessionId)
            ->post(route('platform.dunning.trigger'));

        $response->assertRedirectToRoute('platform.dunning.index');

        $tenant->refresh();
        $subscription->refresh();

        $this->assertSame('suspended', $tenant->status);
        $this->assertSame('suspended', $subscription->status);

        $this->assertDatabaseHas('platform_dunning_events', [
            'tenant_id' => $tenant->id,
            'platform_invoice_id' => $invoice->id,
            'stage' => 'suspended',
            'status' => 'sent',
        ]);

        $eventCountAfterFirstRun = PlatformDunningEvent::query()->count();
        $this->assertSame(1, $eventCountAfterFirstRun);

        // Run dunning cycle AGAIN — Annex A 8.9 invariant: A tenant is never suspended twice by duplicate billing events
        $this->platformRequest($user, $sessionId)
            ->post(route('platform.dunning.trigger'));

        $this->assertSame(1, PlatformDunningEvent::query()->count(), 'No duplicate dunning event should be created on subsequent run.');
        $this->assertSame('suspended', $tenant->status);
    }

    public function test_unprivileged_roles_cannot_view_or_mutate_invoices(): void
    {
        [$user, $sessionId] = $this->createPlatformAdmin('support-agent'); // support-agent does NOT have platform.billing.view

        $response = $this->platformRequest($user, $sessionId)
            ->get(route('platform.invoices.index'));

        $response->assertForbidden();
    }
}
