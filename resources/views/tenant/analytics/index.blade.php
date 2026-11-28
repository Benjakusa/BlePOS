@extends('tenant.layout')

@section('title', 'Business Analytics & Insights')

@section('content')
<div class="page-header" style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
    <div>
        <div style="display: flex; align-items: center; gap: 12px;">
            <h1 class="page-title">Business Analytics &amp; Reports</h1>
            <span class="badge badge-info"><i class="bi bi-graph-up"></i> Real-time Telemetry</span>
        </div>
        <p class="muted" style="margin-top: 4px;">
            Financial summaries, revenue breakdown, transaction volumes, and operational metrics for {{ $tenant->name }}.
        </p>
    </div>

    <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
        {{-- Shop & Period Filter Form --}}
        <form method="GET" action="{{ route('tenant.analytics.index') }}" style="display: flex; gap: 8px; align-items: center;">
            <select name="period" class="form-control" onchange="this.form.submit()" style="min-width: 130px; font-size: 0.875rem;">
                <option value="7d" {{ $period === '7d' ? 'selected' : '' }}>Last 7 Days</option>
                <option value="30d" {{ $period === '30d' ? 'selected' : '' }}>Last 30 Days</option>
                <option value="90d" {{ $period === '90d' ? 'selected' : '' }}>Last 90 Days</option>
                <option value="12m" {{ $period === '12m' ? 'selected' : '' }}>Last 12 Months</option>
            </select>

            @if($shops->count() > 1)
            <select name="shop_id" class="form-control" onchange="this.form.submit()" style="min-width: 150px; font-size: 0.875rem;">
                <option value="">All Branches</option>
                @foreach($shops as $s)
                    <option value="{{ $s->id }}" {{ $shopId === $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                @endforeach
            </select>
            @endif
        </form>

        <a href="{{ route('tenant.analytics.export-center') }}" class="btn btn-primary" style="text-decoration: none;">
            <i class="bi bi-download"></i> Data Export Center
        </a>
    </div>
</div>

{{-- Metric KPI Cards --}}
<div class="metrics-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 24px;">
    <div class="card" style="padding: 18px; border-left: 4px solid var(--primary, #3b82f6);">
        <div class="muted" style="font-size: 0.825rem; font-weight: 600; text-transform: uppercase;">Gross Sales Total</div>
        <div style="font-size: 1.75rem; font-weight: 700; margin: 8px 0 4px; color: var(--text);">
            KES {{ number_format($metrics['total_sales'], 2) }}
        </div>
        <div style="font-size: 0.825rem; color: var(--text-muted);">
            Net: KES {{ number_format($metrics['net_sales'], 2) }}
        </div>
    </div>

    <div class="card" style="padding: 18px; border-left: 4px solid #10b981;">
        <div class="muted" style="font-size: 0.825rem; font-weight: 600; text-transform: uppercase;">Transactions / Orders</div>
        <div style="font-size: 1.75rem; font-weight: 700; margin: 8px 0 4px; color: var(--text);">
            {{ number_format($metrics['order_count']) }}
        </div>
        <div style="font-size: 0.825rem; color: var(--text-muted);">
            Avg Ticket: KES {{ number_format($metrics['avg_ticket'], 2) }}
        </div>
    </div>

    <div class="card" style="padding: 18px; border-left: 4px solid #f59e0b;">
        <div class="muted" style="font-size: 0.825rem; font-weight: 600; text-transform: uppercase;">16% VAT Collected</div>
        <div style="font-size: 1.75rem; font-weight: 700; margin: 8px 0 4px; color: var(--text);">
            KES {{ number_format($metrics['total_tax'], 2) }}
        </div>
        <div style="font-size: 0.825rem; color: var(--text-muted);">
            KRA eTIMS Tax Compliant
        </div>
    </div>

    <div class="card" style="padding: 18px; border-left: 4px solid #8b5cf6;">
        <div class="muted" style="font-size: 0.825rem; font-weight: 600; text-transform: uppercase;">Discounts Given</div>
        <div style="font-size: 1.75rem; font-weight: 700; margin: 8px 0 4px; color: var(--text);">
            KES {{ number_format($metrics['total_discount'], 2) }}
        </div>
        <div style="font-size: 0.825rem; color: var(--text-muted);">
            Promotional & Cashier adjustments
        </div>
    </div>
</div>

{{-- Payment Methods & Breakdown Grid --}}
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px; margin-bottom: 24px;">
    {{-- Tender Settlement Breakdown --}}
    <div class="card" style="padding: 20px;">
        <h3 style="font-size: 1.1rem; font-weight: 600; margin-bottom: 16px; display: flex; align-items: center; justify-content: space-between;">
            <span>Payment Tender Distribution</span>
            <span class="badge badge-success">KES {{ number_format($metrics['total_paid'], 2) }}</span>
        </h3>
        <div style="display: flex; flex-direction: column; gap: 12px;">
            <div>
                <div style="display: flex; justify-content: space-between; font-size: 0.875rem; margin-bottom: 4px;">
                    <span><i class="bi bi-phone" style="color: #10b981; margin-right: 6px;"></i> M-Pesa Mobile Money</span>
                    <strong>KES {{ number_format($metrics['payments']['mpesa'], 2) }}</strong>
                </div>
                @php
                    $mpesaPct = $metrics['total_paid'] > 0 ? round(($metrics['payments']['mpesa'] / $metrics['total_paid']) * 100, 1) : 0;
                @endphp
                <div style="height: 8px; width: 100%; background: var(--bg-surface-secondary, #e2e8f0); border-radius: 4px; overflow: hidden;">
                    <div style="height: 100%; width: {{ $mpesaPct }}%; background: #10b981;"></div>
                </div>
                <div style="font-size: 0.75rem; color: var(--text-muted); text-align: right; margin-top: 2px;">{{ $mpesaPct }}% of total collections</div>
            </div>

            <div>
                <div style="display: flex; justify-content: space-between; font-size: 0.875rem; margin-bottom: 4px;">
                    <span><i class="bi bi-cash-stack" style="color: #3b82f6; margin-right: 6px;"></i> Cash Tender</span>
                    <strong>KES {{ number_format($metrics['payments']['cash'], 2) }}</strong>
                </div>
                @php
                    $cashPct = $metrics['total_paid'] > 0 ? round(($metrics['payments']['cash'] / $metrics['total_paid']) * 100, 1) : 0;
                @endphp
                <div style="height: 8px; width: 100%; background: var(--bg-surface-secondary, #e2e8f0); border-radius: 4px; overflow: hidden;">
                    <div style="height: 100%; width: {{ $cashPct }}%; background: #3b82f6;"></div>
                </div>
                <div style="font-size: 0.75rem; color: var(--text-muted); text-align: right; margin-top: 2px;">{{ $cashPct }}% of total collections</div>
            </div>

            <div>
                <div style="display: flex; justify-content: space-between; font-size: 0.875rem; margin-bottom: 4px;">
                    <span><i class="bi bi-credit-card" style="color: #f59e0b; margin-right: 6px;"></i> Card / Bank POS</span>
                    <strong>KES {{ number_format($metrics['payments']['card'], 2) }}</strong>
                </div>
                @php
                    $cardPct = $metrics['total_paid'] > 0 ? round(($metrics['payments']['card'] / $metrics['total_paid']) * 100, 1) : 0;
                @endphp
                <div style="height: 8px; width: 100%; background: var(--bg-surface-secondary, #e2e8f0); border-radius: 4px; overflow: hidden;">
                    <div style="height: 100%; width: {{ $cardPct }}%; background: #f59e0b;"></div>
                </div>
            </div>

            @if($metrics['payments']['credit'] > 0)
            <div>
                <div style="display: flex; justify-content: space-between; font-size: 0.875rem; margin-bottom: 4px;">
                    <span><i class="bi bi-journal-text" style="color: #ef4444; margin-right: 6px;"></i> Store Credit / On Account</span>
                    <strong>KES {{ number_format($metrics['payments']['credit'], 2) }}</strong>
                </div>
                @php
                    $creditPct = $metrics['total_paid'] > 0 ? round(($metrics['payments']['credit'] / $metrics['total_paid']) * 100, 1) : 0;
                @endphp
                <div style="height: 8px; width: 100%; background: var(--bg-surface-secondary, #e2e8f0); border-radius: 4px; overflow: hidden;">
                    <div style="height: 100%; width: {{ $creditPct }}%; background: #ef4444;"></div>
                </div>
            </div>
            @endif
        </div>
    </div>

    {{-- Industry Operations Highlights --}}
    <div class="card" style="padding: 20px;">
        <h3 style="font-size: 1.1rem; font-weight: 600; margin-bottom: 16px;">
            <i class="bi bi-boxes"></i> Industry Operations Telemetry
        </h3>
        @if(empty($metrics['industry_stats']))
            <p class="muted" style="font-size: 0.875rem;">Standard retail point-of-sale telemetry active. Enable specialized modules for sector insights.</p>
        @else
            <div style="display: flex; flex-direction: column; gap: 14px;">
                @if(isset($metrics['industry_stats']['parcels']))
                    <div style="padding: 12px; background: var(--bg-surface-secondary, #f8fafc); border-radius: 6px;">
                        <strong style="display: block; font-size: 0.875rem; margin-bottom: 6px;"><i class="bi bi-box-seam"></i> Parcel Logistics</strong>
                        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; font-size: 0.8rem; text-align: center;">
                            <div><div class="muted">Total</div><strong>{{ $metrics['industry_stats']['parcels']['total_received'] }}</strong></div>
                            <div><div class="muted">In Transit</div><strong style="color: #f59e0b;">{{ $metrics['industry_stats']['parcels']['in_transit'] }}</strong></div>
                            <div><div class="muted">Delivered</div><strong style="color: #10b981;">{{ $metrics['industry_stats']['parcels']['delivered'] }}</strong></div>
                            <div><div class="muted">Freight Fees</div><strong>KES {{ number_format($metrics['industry_stats']['parcels']['total_fees'], 0) }}</strong></div>
                        </div>
                    </div>
                @endif

                @if(isset($metrics['industry_stats']['gym']))
                    <div style="padding: 12px; background: var(--bg-surface-secondary, #f8fafc); border-radius: 6px;">
                        <strong style="display: block; font-size: 0.875rem; margin-bottom: 6px;"><i class="bi bi-activity"></i> Gym & Fitness Club</strong>
                        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; font-size: 0.8rem; text-align: center;">
                            <div><div class="muted">Members</div><strong>{{ $metrics['industry_stats']['gym']['total_members'] }}</strong></div>
                            <div><div class="muted">Active Subs</div><strong style="color: #10b981;">{{ $metrics['industry_stats']['gym']['active_subscriptions'] }}</strong></div>
                            <div><div class="muted">Today Check-ins</div><strong style="color: #3b82f6;">{{ $metrics['industry_stats']['gym']['today_checkins'] }}</strong></div>
                        </div>
                    </div>
                @endif

                @if(isset($metrics['industry_stats']['fleet']))
                    <div style="padding: 12px; background: var(--bg-surface-secondary, #f8fafc); border-radius: 6px;">
                        <strong style="display: block; font-size: 0.875rem; margin-bottom: 6px;"><i class="bi bi-car-front"></i> Car Hire & Fleet</strong>
                        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; font-size: 0.8rem; text-align: center;">
                            <div><div class="muted">Total Fleet</div><strong>{{ $metrics['industry_stats']['fleet']['total_vehicles'] }}</strong></div>
                            <div><div class="muted">Active Rentals</div><strong style="color: #10b981;">{{ $metrics['industry_stats']['fleet']['active_rentals'] }}</strong></div>
                            <div><div class="muted">Billed Revenue</div><strong>KES {{ number_format($metrics['industry_stats']['fleet']['total_billed'], 0) }}</strong></div>
                        </div>
                    </div>
                @endif

                @if(isset($metrics['industry_stats']['garage']))
                    <div style="padding: 12px; background: var(--bg-surface-secondary, #f8fafc); border-radius: 6px;">
                        <strong style="display: block; font-size: 0.875rem; margin-bottom: 6px;"><i class="bi bi-tools"></i> Auto Garage Repair</strong>
                        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; font-size: 0.8rem; text-align: center;">
                            <div><div class="muted">Total Jobs</div><strong>{{ $metrics['industry_stats']['garage']['total_jobs'] }}</strong></div>
                            <div><div class="muted">In Progress</div><strong style="color: #f59e0b;">{{ $metrics['industry_stats']['garage']['in_progress'] }}</strong></div>
                            <div><div class="muted">Completed</div><strong style="color: #10b981;">{{ $metrics['industry_stats']['garage']['completed'] }}</strong></div>
                        </div>
                    </div>
                @endif
            </div>
        @endif
    </div>
</div>

{{-- Top Products & Cashier Ranking Tables --}}
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(360px, 1fr)); gap: 20px; margin-bottom: 24px;">
    {{-- Top Selling Products --}}
    <div class="card" style="padding: 20px;">
        <h3 style="font-size: 1.1rem; font-weight: 600; margin-bottom: 14px;">
            <i class="bi bi-trophy"></i> Top Products by Revenue
        </h3>
        <div class="table-responsive">
            <table class="table" style="width: 100%; font-size: 0.875rem;">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th style="text-align: right;">Qty Sold</th>
                        <th style="text-align: right;">Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($metrics['top_products'] as $item)
                        <tr>
                            <td>
                                <strong>{{ $item['name'] }}</strong>
                                <div class="muted" style="font-size: 0.75rem;">SKU: {{ $item['sku'] }}</div>
                            </td>
                            <td style="text-align: right;">{{ number_format($item['units_sold']) }}</td>
                            <td style="text-align: right; font-weight: 600;">KES {{ number_format($item['gross_revenue'], 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="muted" style="text-align: center; padding: 16px;">No product sales recorded in this period.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Cashier Performance --}}
    <div class="card" style="padding: 20px;">
        <h3 style="font-size: 1.1rem; font-weight: 600; margin-bottom: 14px;">
            <i class="bi bi-person-badge"></i> Cashier Shift Performance
        </h3>
        <div class="table-responsive">
            <table class="table" style="width: 100%; font-size: 0.875rem;">
                <thead>
                    <tr>
                        <th>Staff Member</th>
                        <th style="text-align: center;">Shifts</th>
                        <th style="text-align: right;">Cash Handled</th>
                        <th style="text-align: right;">Variance</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($metrics['cashier_stats'] as $cs)
                        <tr>
                            <td><strong>{{ $cs['name'] }}</strong></td>
                            <td style="text-align: center;">{{ $cs['shift_count'] }}</td>
                            <td style="text-align: right; font-weight: 600;">KES {{ number_format($cs['total_sales'], 2) }}</td>
                            <td style="text-align: right; {{ $cs['cash_variance'] < 0 ? 'color: #ef4444;' : ($cs['cash_variance'] > 0 ? 'color: #10b981;' : 'color: var(--text-muted);') }}">
                                {{ $cs['cash_variance'] > 0 ? '+' : '' }}KES {{ number_format($cs['cash_variance'], 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="muted" style="text-align: center; padding: 16px;">No cashier shifts logged in this period.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Daily Sales Trends Table --}}
<div class="card" style="padding: 20px; margin-bottom: 24px;">
    <h3 style="font-size: 1.1rem; font-weight: 600; margin-bottom: 14px;">
        <i class="bi bi-calendar3"></i> Daily Sales Breakdown ({{ ucfirst($period) }})
    </h3>
    <div class="table-responsive">
        <table class="table" style="width: 100%; font-size: 0.875rem;">
            <thead>
                <tr>
                    <th>Date</th>
                    <th style="text-align: center;">Receipts / Orders</th>
                    <th style="text-align: right;">Daily Gross (KES)</th>
                    <th>Volume Visualizer</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $maxDaily = collect($metrics['daily_trends'])->max('total') ?: 1;
                @endphp
                @forelse($metrics['daily_trends'] as $day)
                    @php
                        $dayPct = round(($day['total'] / $maxDaily) * 100);
                    @endphp
                    <tr>
                        <td><strong>{{ \Carbon\Carbon::parse($day['date'])->format('d M Y (D)') }}</strong></td>
                        <td style="text-align: center;">{{ $day['count'] }}</td>
                        <td style="text-align: right; font-weight: 600;">KES {{ number_format($day['total'], 2) }}</td>
                        <td style="width: 35%;">
                            <div style="height: 10px; width: 100%; background: var(--bg-surface-secondary, #e2e8f0); border-radius: 4px; overflow: hidden;">
                                <div style="height: 100%; width: {{ $dayPct }}%; background: var(--primary, #3b82f6);"></div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="muted" style="text-align: center; padding: 16px;">No transactions recorded for this period.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
