@extends('platform.layout')

@section('title', $tenant->name)

@section('content')
<section class="panel" style="max-width: 100%; margin-bottom: 24px;">
    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
        <div>
            <h2>{{ $tenant->name }} (<code>{{ $tenant->code }}</code>)</h2>
            <p class="muted">Industry: {{ ucfirst(str_replace('_', ' ', $tenant->industry)) }} · Registered: {{ $tenant->created_at->format('M d, Y H:i') }}</p>
        </div>
        <div>
            <span style="padding: 6px 12px; border-radius: 4px; font-weight: 600; {{ $tenant->status === 'active' ? 'background: #e7f4eb; color: #146b4d;' : ($tenant->status === 'suspended' ? 'background: #fde8e8; color: #a32121;' : 'background: #fff8e1; color: #b78103;') }}">
                Status: {{ ucfirst($tenant->status) }}
            </span>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-top: 18px;">
        <div style="border: 1px solid #dce5df; padding: 14px; border-radius: 6px;">
            <div class="muted" style="font-size: 13px;">Owner Contact</div>
            <strong>{{ $tenant->owner_name }}</strong>
            <div>{{ $tenant->owner_email }}</div>
        </div>
        <div style="border: 1px solid #dce5df; padding: 14px; border-radius: 6px;">
            <div class="muted" style="font-size: 13px;">KRA PIN</div>
            <strong>{{ $tenant->kra_pin ?? 'Not Registered' }}</strong>
        </div>
        <div style="border: 1px solid #dce5df; padding: 14px; border-radius: 6px;">
            <div class="muted" style="font-size: 13px;">Branch Count</div>
            <strong>{{ $tenant->shops->count() }} Shops</strong> ({{ $tenant->shops->flatMap->registers->count() }} Registers)
        </div>
        <div style="border: 1px solid #dce5df; padding: 14px; border-radius: 6px;">
            <div class="muted" style="font-size: 13px;">Domains</div>
            @foreach($tenant->domains as $d)
                <div><code>{{ $d->domain }}</code></div>
            @endforeach
        </div>
    </div>
</section>

<section class="panel" style="max-width: 100%; margin-bottom: 24px;">
    <h3>Branch Locations & Registers</h3>
    <table style="width: 100%; border-collapse: collapse; text-align: left; margin-top: 10px;">
        <thead>
            <tr style="border-bottom: 2px solid #dce5df; color: #63736b;">
                <th style="padding: 8px 12px;">Shop Name</th>
                <th style="padding: 8px 12px;">Shop Code</th>
                <th style="padding: 8px 12px;">Registers</th>
                <th style="padding: 8px 12px;">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($tenant->shops as $shop)
                <tr style="border-bottom: 1px solid #eef2ef;">
                    <td style="padding: 10px 12px; font-weight: 600;">{{ $shop->name }}</td>
                    <td style="padding: 10px 12px;"><code>{{ $shop->code }}</code></td>
                    <td style="padding: 10px 12px;">{{ $shop->registers->pluck('name')->join(', ') ?: 'None' }}</td>
                    <td style="padding: 10px 12px;">{{ $shop->is_active ? 'Active' : 'Inactive' }}</td>
                </tr>
            @empty
                <tr><td colspan="4" style="padding: 14px; color: #63736b;">No shops provisioned yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</section>

<section class="panel" style="max-width: 100%; margin-bottom: 24px;">
    <h3>Provisioning Runs</h3>
    <table style="width: 100%; border-collapse: collapse; text-align: left; margin-top: 10px;">
        <thead>
            <tr style="border-bottom: 2px solid #dce5df; color: #63736b;">
                <th style="padding: 8px 12px;">Run Identifier</th>
                <th style="padding: 8px 12px;">Progress</th>
                <th style="padding: 8px 12px;">Status</th>
                <th style="padding: 8px 12px;">Started</th>
                <th style="padding: 8px 12px;">Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse($provisioningRuns as $run)
                <tr style="border-bottom: 1px solid #eef2ef;">
                    <td style="padding: 10px 12px;"><code>{{ $run->run_identifier }}</code></td>
                    <td style="padding: 10px 12px;">Step {{ $run->current_step }} of {{ $run->total_steps }}</td>
                    <td style="padding: 10px 12px;">
                        <span style="font-weight: 600; {{ $run->status === 'completed' ? 'color: #146b4d;' : 'color: #a32121;' }}">
                            {{ ucfirst($run->status) }}
                        </span>
                    </td>
                    <td style="padding: 10px 12px;">{{ $run->created_at->format('Y-m-d H:i') }}</td>
                    <td style="padding: 10px 12px;">
                        <a href="{{ route('platform.provisioning.show', $run) }}">Inspect Steps</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" style="padding: 14px; color: #63736b;">No provisioning runs recorded.</td></tr>
            @endforelse
        </tbody>
    </table>
</section>

<section class="panel" style="max-width: 100%; margin-bottom: 24px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
        <h3 style="margin: 0;">Subscription & Plan</h3>
        @if($subscription)
            <span class="badge {{ $subscription->isActive() ? 'badge-success' : 'badge-warning' }}">
                Status: {{ ucfirst(str_replace('_', ' ', $subscription->status)) }}
            </span>
        @endif
    </div>

    @if($subscription)
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px; margin-bottom: 16px;">
            <div style="border: 1px solid #dce5df; padding: 12px; border-radius: 6px;">
                <div class="muted" style="font-size: 12px;">Current Plan</div>
                <strong>{{ $subscription->plan->name }}</strong> (<code>{{ $subscription->plan->code }}</code>)
            </div>
            <div style="border: 1px solid #dce5df; padding: 12px; border-radius: 6px;">
                <div class="muted" style="font-size: 12px;">Billing Cycle</div>
                <strong>{{ ucfirst($subscription->billing_cycle) }}</strong>
                ({{ $subscription->plan->currency }} {{ number_format((float) ($subscription->billing_cycle === 'annual' ? $subscription->plan->annual_price : $subscription->plan->monthly_price), 2) }})
            </div>
            <div style="border: 1px solid #dce5df; padding: 12px; border-radius: 6px;">
                <div class="muted" style="font-size: 12px;">Renews At</div>
                <strong>{{ $subscription->renews_at ? $subscription->renews_at->format('M d, Y') : '—' }}</strong>
            </div>
            <div style="border: 1px solid #dce5df; padding: 12px; border-radius: 6px;">
                <div class="muted" style="font-size: 12px;">Quotas</div>
                <small class="muted">
                    Shops: <strong>{{ $subscription->plan->max_shops }}</strong> |
                    Users: <strong>{{ $subscription->plan->max_users }}</strong> |
                    Tills: <strong>{{ $subscription->plan->max_registers }}</strong>
                </small>
            </div>
        </div>

        @if(auth('platform')->user()->hasPermission('platform.billing.subscriptions.manage'))
            <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 16px; border-top: 1px solid #e2e8f0; padding-top: 12px;">
                <span class="muted" style="font-size: 13px; align-self: center;">Status Transition:</span>
                <form action="{{ route('platform.tenants.subscription.transition', [$tenant, $subscription]) }}" method="POST" style="display: inline;">
                    @csrf
                    <input type="hidden" name="status" value="active">
                    <input type="hidden" name="reason" value="Subscription activated by platform operator">
                    <button type="submit" class="btn btn-secondary btn-sm" {{ $subscription->status === 'active' ? 'disabled' : '' }}>Activate</button>
                </form>
                <form action="{{ route('platform.tenants.subscription.transition', [$tenant, $subscription]) }}" method="POST" style="display: inline;">
                    @csrf
                    <input type="hidden" name="status" value="grace_period">
                    <input type="hidden" name="reason" value="Granted 7-day payment grace period">
                    <button type="submit" class="btn btn-secondary btn-sm" {{ $subscription->status === 'grace_period' ? 'disabled' : '' }}>Grant Grace</button>
                </form>
                <form action="{{ route('platform.tenants.subscription.transition', [$tenant, $subscription]) }}" method="POST" style="display: inline;">
                    @csrf
                    <input type="hidden" name="status" value="past_due">
                    <input type="hidden" name="reason" value="Payment overdue">
                    <button type="submit" class="btn btn-secondary btn-sm" {{ $subscription->status === 'past_due' ? 'disabled' : '' }}>Mark Past Due</button>
                </form>
                <form action="{{ route('platform.tenants.subscription.transition', [$tenant, $subscription]) }}" method="POST" style="display: inline;">
                    @csrf
                    <input type="hidden" name="status" value="suspended">
                    <input type="hidden" name="reason" value="Suspended for non-payment">
                    <button type="submit" class="btn btn-danger btn-sm" {{ $subscription->status === 'suspended' ? 'disabled' : '' }}>Suspend Sub</button>
                </form>
            </div>
        @endif
    @else
        <p class="muted">No subscription assigned yet.</p>
    @endif

    @if(auth('platform')->user()->hasPermission('platform.billing.subscriptions.manage'))
        <div style="border-top: 1px solid #e2e8f0; padding-top: 14px;">
            <h4 style="margin: 0 0 10px; font-size: 14px;">{{ $subscription ? 'Change Subscription Plan' : 'Assign Subscription Plan' }}</h4>
            <form action="{{ route('platform.tenants.subscription.assign', $tenant) }}" method="POST" style="display: flex; gap: 10px; flex-wrap: wrap; align-items: flex-start;">
                @csrf
                <div style="min-width: 220px;">
                    <x-input type="select" name="plan_id" label="Plan" :required="true">
                        <option value="">— Select Plan —</option>
                        @foreach($availablePlans as $p)
                            <option value="{{ $p->id }}" {{ ($subscription && $subscription->plan_id == $p->id) ? 'selected' : '' }}>
                                {{ $p->name }} ({{ $p->currency }} {{ number_format((float) $p->monthly_price, 2) }}/mo)
                            </option>
                        @endforeach
                    </x-input>
                </div>
                <div style="min-width: 140px;">
                    <x-input type="select" name="billing_cycle" label="Billing Cycle" :required="true">
                        <option value="monthly" {{ ($subscription && $subscription->billing_cycle === 'monthly') ? 'selected' : '' }}>Monthly</option>
                        <option value="annual" {{ ($subscription && $subscription->billing_cycle === 'annual') ? 'selected' : '' }}>Annual</option>
                    </x-input>
                </div>
                <div style="min-width: 130px;">
                    <x-input type="select" name="status" label="Status" :required="true">
                        <option value="trial" {{ ($subscription && $subscription->status === 'trial') ? 'selected' : '' }}>Trial</option>
                        <option value="active" {{ ($subscription && $subscription->status === 'active') ? 'selected' : '' }}>Active</option>
                    </x-input>
                </div>
                <button type="submit" class="btn btn-secondary" style="height: 40px;">{{ $subscription ? 'Update Plan' : 'Assign Plan' }}</button>
            </form>
        </div>
    @endif
</section>

@if(auth('platform')->user()->hasPermission('platform.modules.manage'))
    <section class="panel" style="max-width: 100%; margin-bottom: 24px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
            <div>
                <h3 style="margin: 0;">Industry Blueprint & Modules</h3>
                <span class="muted" style="font-size: 13px;">Current blueprint: <strong>{{ ucfirst(str_replace('_', ' ', $tenant->industry)) }}</strong></span>
            </div>
        </div>

        <form method="POST" action="{{ route('platform.tenants.templates.apply', $tenant) }}" id="tenant-apply-blueprint-form" style="display: flex; gap: 12px; align-items: flex-start; flex-wrap: wrap;">
            @csrf
            <div style="flex: 1; min-width: 260px;">
                <x-input type="select" name="template_id" id="blueprint-select" label="Select Blueprint to Apply" :required="true">
                    <option value="">— Choose Industry Blueprint —</option>
                    @foreach($industryTemplates as $t)
                        <option value="{{ $t->id }}">{{ $t->name }} ({{ count($t->default_modules ?? []) }} modules)</option>
                    @endforeach
                </x-input>
            </div>
            <div>
                <button type="submit" class="btn btn-primary" id="btn-apply-tenant-bp" disabled style="height: 40px;">Apply Blueprint</button>
            </div>
        </form>

        <div id="tenant-bp-preview" style="display: none; background: var(--color-neutral-50); border: 1px solid var(--color-neutral-200); border-radius: 6px; padding: 10px 14px; margin-top: 12px; font-size: 12.5px;">
            <div style="font-weight: 600; margin-bottom: 4px; color: var(--color-ink-950);">Installation Preview:</div>
            <div id="tenant-bp-will-enable" style="color: #15803d; margin-bottom: 2px;"></div>
            <div id="tenant-bp-already-enabled" style="color: var(--color-neutral-600);"></div>
        </div>

        <script nonce="{{ request()->attributes->get('csp_nonce') }}">
        document.addEventListener('DOMContentLoaded', function() {
            const bpSelect = document.getElementById('blueprint-select');
            const previewBox = document.getElementById('tenant-bp-preview');
            const willEnableDiv = document.getElementById('tenant-bp-will-enable');
            const alreadyEnabledDiv = document.getElementById('tenant-bp-already-enabled');
            const applyBtn = document.getElementById('btn-apply-tenant-bp');
            const tenantId = {{ $tenant->id }};

            if (bpSelect) {
                bpSelect.addEventListener('change', function() {
                    const templateId = bpSelect.value;
                    if (!templateId) {
                        previewBox.style.display = 'none';
                        applyBtn.disabled = true;
                        return;
                    }

                    fetch(`/platform-console/templates/${templateId}/tenants/${tenantId}/preview`)
                        .then(res => res.json())
                        .then(data => {
                            previewBox.style.display = 'block';
                            applyBtn.disabled = false;

                            if (data.will_enable && data.will_enable.length > 0) {
                                willEnableDiv.innerHTML = `<strong>+ ${data.will_enable.length} modules will be activated:</strong> ` + data.will_enable.join(', ');
                            } else {
                                willEnableDiv.innerHTML = `<em>All modules from this blueprint are already active for this tenant.</em>`;
                            }

                            if (data.already_enabled && data.already_enabled.length > 0) {
                                alreadyEnabledDiv.innerHTML = `<strong>&bull; ${data.already_enabled.length} already active:</strong> ` + data.already_enabled.join(', ');
                            } else {
                                alreadyEnabledDiv.innerHTML = '';
                            }
                        })
                        .catch(() => {
                            previewBox.style.display = 'none';
                            applyBtn.disabled = false;
                        });
                });
            }
        });
        </script>
    </section>
@endif

<section class="panel" style="max-width: 100%;">
    <h3>Lifecycle Controls (Danger Zone)</h3>
    <div style="display: flex; gap: 14px; margin-top: 14px;">
        @if($tenant->status !== 'suspended' && auth('platform')->user()->hasPermission('platform.tenants.suspend'))
            <form method="post" action="{{ route('platform.tenants.suspend', $tenant) }}" style="display: inline;">
                @csrf
                <input type="hidden" name="reason" value="Administrative suspension requested by platform operator.">
                <button type="submit" style="background: #a32121;" onclick="return confirm('Are you sure you want to suspend this business? Staff access will be immediately blocked.');">
                    Suspend Tenant
                </button>
            </form>
        @elseif($tenant->status === 'suspended' && auth('platform')->user()->hasPermission('platform.tenants.reactivate'))
            <form method="post" action="{{ route('platform.tenants.reactivate', $tenant) }}" style="display: inline;">
                @csrf
                <input type="hidden" name="reason" value="Reactivation approved following account review.">
                <button type="submit" style="background: #146b4d;">
                    Reactivate Tenant
                </button>
            </form>
        @endif
    </div>
</section>
@endsection
