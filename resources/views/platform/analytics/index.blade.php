@extends('platform.layout')

@section('title', 'Platform Analytics & Reports')

@section('content')
<header>
    <div>
        <h1>Platform Analytics & Reports</h1>
        <div class="muted">Live operational intelligence, commercial aggregates, tenant lifecycle, and regulatory status (Annex A, Part 32)</div>
    </div>
    <div style="display: flex; gap: 12px; align-items: center;">
        <form method="GET" action="{{ route('platform.analytics.index') }}" style="display: flex; gap: 6px; align-items: center;">
            <label style="font-size: 13px; font-weight: normal; margin: 0;">Period:
                <select name="period" onchange="this.form.submit()" style="padding: 6px 10px; font-size: 13px; border-radius: 4px; border: 1px solid #b9c9bf;">
                    <option value="7d" @selected($period === '7d')>Last 7 Days</option>
                    <option value="30d" @selected($period === '30d')>Last 30 Days</option>
                    <option value="90d" @selected($period === '90d')>Last 90 Days</option>
                    <option value="12m" @selected($period === '12m')>Last 12 Months</option>
                </select>
            </label>
        </form>

        <form method="POST" action="{{ route('platform.analytics.export') }}" style="display: flex; gap: 6px; align-items: center;">
            @csrf
            <select name="report_type" style="padding: 6px 10px; font-size: 13px; border-radius: 4px; border: 1px solid #b9c9bf;">
                <option value="revenue">Export Revenue Report</option>
                <option value="tenants">Export Tenant Registry</option>
                <option value="modules">Export Module Adoption</option>
                <option value="growth">Export Growth Metrics</option>
            </select>
            <button type="submit" class="btn" style="padding: 6px 12px; font-size: 13px;">Download CSV</button>
        </form>
    </div>
</header>

@if(session('notice'))
    <div class="notice" role="alert">{{ session('notice') }}</div>
@endif

<!-- Key Commercial & Financial Highlights -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 24px;">
    <div class="panel" style="padding: 18px;">
        <span class="muted" style="font-size: 12px; text-transform: uppercase; font-weight: 600;">Monthly Recurring Revenue</span>
        <div style="font-size: 26px; font-weight: bold; margin-top: 4px; color: #146b4d;">KES {{ number_format((float) $revenue['mrr'], 2) }}</div>
        <div class="muted" style="font-size: 12px; margin-top: 4px;">{{ $revenue['active_subscriptions_count'] }} active subscriptions</div>
    </div>

    <div class="panel" style="padding: 18px;">
        <span class="muted" style="font-size: 12px; text-transform: uppercase; font-weight: 600;">Total Invoiced</span>
        <div style="font-size: 26px; font-weight: bold; margin-top: 4px;">KES {{ number_format((float) $revenue['total_invoiced'], 2) }}</div>
        <div class="muted" style="font-size: 12px; margin-top: 4px;">Incl. KES {{ number_format((float) $revenue['total_vat'], 2) }} VAT (16%)</div>
    </div>

    <div class="panel" style="padding: 18px;">
        <span class="muted" style="font-size: 12px; text-transform: uppercase; font-weight: 600;">Total Collected</span>
        <div style="font-size: 26px; font-weight: bold; margin-top: 4px; color: #15803d;">KES {{ number_format((float) $revenue['total_collected'], 2) }}</div>
        <div class="muted" style="font-size: 12px; margin-top: 4px;">Collection Efficiency: {{ $revenue['collection_rate'] }}%</div>
    </div>

    <div class="panel" style="padding: 18px;">
        <span class="muted" style="font-size: 12px; text-transform: uppercase; font-weight: 600;">Outstanding Receivables</span>
        <div style="font-size: 26px; font-weight: bold; margin-top: 4px; color: {{ (float) $revenue['total_due'] > 0 ? '#b91c1c' : '#146b4d' }};">
            KES {{ number_format((float) $revenue['total_due'], 2) }}
        </div>
        <div class="muted" style="font-size: 12px; margin-top: 4px;">Unsettled invoice balances</div>
    </div>
</div>

<!-- Tenant Growth & Lifecycle -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-bottom: 24px;">
    <div class="panel" style="padding: 16px;">
        <span class="muted" style="font-size: 12px; text-transform: uppercase;">Total Tenants</span>
        <div style="font-size: 22px; font-weight: bold; margin-top: 4px;">{{ $growth['total_tenants'] }}</div>
        <div class="muted" style="font-size: 11px; margin-top: 2px;">+{{ $growth['new_tenants'] }} in selected period</div>
    </div>

    <div class="panel" style="padding: 16px;">
        <span class="muted" style="font-size: 12px; text-transform: uppercase;">Active Tenants</span>
        <div style="font-size: 22px; font-weight: bold; margin-top: 4px; color: #15803d;">{{ $growth['active_tenants'] }}</div>
        <div class="muted" style="font-size: 11px; margin-top: 2px;">Retention Rate: {{ $growth['retention_rate'] }}%</div>
    </div>

    <div class="panel" style="padding: 16px;">
        <span class="muted" style="font-size: 12px; text-transform: uppercase;">Suspended</span>
        <div style="font-size: 22px; font-weight: bold; margin-top: 4px; color: {{ $growth['suspended_tenants'] > 0 ? '#b91c1c' : 'inherit' }};">
            {{ $growth['suspended_tenants'] }}
        </div>
        <div class="muted" style="font-size: 11px; margin-top: 2px;">Overdue or manual</div>
    </div>

    <div class="panel" style="padding: 16px;">
        <span class="muted" style="font-size: 12px; text-transform: uppercase;">Monitored Shops</span>
        <div style="font-size: 22px; font-weight: bold; margin-top: 4px;">{{ $growth['total_shops'] }}</div>
        <div class="muted" style="font-size: 11px; margin-top: 2px;">Across all branches</div>
    </div>

    <div class="panel" style="padding: 16px;">
        <span class="muted" style="font-size: 12px; text-transform: uppercase;">POS Tills / Registers</span>
        <div style="font-size: 22px; font-weight: bold; margin-top: 4px;">{{ $growth['total_registers'] }}</div>
        <div class="muted" style="font-size: 11px; margin-top: 2px;">Active checkout terminals</div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px;">
    <!-- Plan Distribution -->
    <div class="panel" style="max-width: none;">
        <h2 style="font-size: 17px; margin-top: 0; margin-bottom: 14px;">Plan Tier Distribution & Estimated MRR</h2>
        <table cellpadding="8" cellspacing="0" border="1" style="width: 100%; border-collapse: collapse; font-size: 13px;">
            <thead>
                <tr style="background: #f8faf9;">
                    <th>Plan Tier</th>
                    <th>Monthly Price</th>
                    <th>Subscribers</th>
                    <th>Contribution (KES)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($plans as $plan)
                    <tr>
                        <td><strong>{{ $plan['name'] }}</strong></td>
                        <td>KES {{ number_format((float) $plan['price'], 2) }}</td>
                        <td>{{ $plan['active_subscribers'] }}</td>
                        <td>KES {{ number_format((float) $plan['estimated_mrr'], 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="muted" style="text-align: center; padding: 16px;">No subscription plans configured.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Industry Distribution -->
    <div class="panel" style="max-width: none;">
        <h2 style="font-size: 17px; margin-top: 0; margin-bottom: 14px;">Industry Blueprint Breakdown</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 10px;">
            @foreach($industries as $ind => $cnt)
                <div style="background: #f8faf9; border: 1px solid #e2e8f0; border-radius: 6px; padding: 12px; text-align: center;">
                    <div style="font-size: 20px; font-weight: bold; color: #146b4d;">{{ $cnt }}</div>
                    <div class="muted" style="font-size: 12px; text-transform: capitalize; margin-top: 2px;">{{ str_replace('-', ' ', $ind) }}</div>
                </div>
            @endforeach
        </div>

        <div style="margin-top: 20px; border-top: 1px solid #e2e8f0; padding-top: 14px;">
            <div style="font-size: 14px; font-weight: 600; margin-bottom: 6px;">Support SLA & Operational Reliability</div>
            <div style="display: flex; justify-content: space-between; font-size: 13px;">
                <span>Support SLA Resolution Rate:</span>
                <strong>{{ $operations['support']['sla_rate'] }}% ({{ $operations['support']['resolved'] }} of {{ $operations['support']['total'] }})</strong>
            </div>
            <div style="display: flex; justify-content: space-between; font-size: 13px; margin-top: 4px;">
                <span>Security Threats (Critical/High 30d):</span>
                <strong>{{ $operations['security']['critical_incidents_30d'] }} incidents</strong>
            </div>
            <div style="display: flex; justify-content: space-between; font-size: 13px; margin-top: 4px;">
                <span>eTIMS Regulatory Status:</span>
                <span class="badge" style="background: {{ $operations['compliance']['etims_status'] === 'CERTIFIED' ? '#dcfce7' : '#fee2e2' }}; color: {{ $operations['compliance']['etims_status'] === 'CERTIFIED' ? '#166534' : '#991b1b' }}; padding: 1px 6px; border-radius: 3px;">
                    {{ $operations['compliance']['etims_status'] }}
                </span>
            </div>
        </div>
    </div>
</div>

<!-- Module Adoption Overview -->
<div class="panel" style="max-width: none;">
    <h2 style="font-size: 17px; margin-top: 0; margin-bottom: 14px;">Module Catalogue Adoption & Entitlements</h2>
    <div class="muted" style="font-size: 13px; margin-bottom: 14px;">Adoption penetration across the versioned 25-module catalogue.</div>
    <table cellpadding="8" cellspacing="0" border="1" style="width: 100%; border-collapse: collapse; font-size: 13px;">
        <thead>
            <tr style="background: #f8faf9;">
                <th>Module Name</th>
                <th>Slug</th>
                <th>Category</th>
                <th>Type</th>
                <th>Adopting Tenants</th>
                <th>Adoption Rate</th>
            </tr>
        </thead>
        <tbody>
            @foreach($modules as $mod)
                <tr>
                    <td><strong>{{ $mod['name'] }}</strong></td>
                    <td><code>{{ $mod['slug'] }}</code></td>
                    <td><span style="text-transform: capitalize;">{{ $mod['category'] }}</span></td>
                    <td>
                        @if($mod['is_core'])
                            <span class="badge" style="background: #e0e7ff; color: #3730a3; padding: 2px 6px; border-radius: 4px;">Core</span>
                        @else
                            <span class="muted">Optional</span>
                        @endif
                    </td>
                    <td><strong>{{ $mod['enabled_tenants_count'] }}</strong> tenants</td>
                    <td>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <div style="width: 80px; height: 6px; background: #e2e8f0; border-radius: 3px; overflow: hidden;">
                                <div style="width: {{ min(100, $mod['adoption_rate']) }}%; height: 100%; background: #146b4d;"></div>
                            </div>
                            <span>{{ $mod['adoption_rate'] }}%</span>
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
