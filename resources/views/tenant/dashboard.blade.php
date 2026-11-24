@extends('tenant.layout')

@section('title', ucfirst($roleView) . ' Dashboard')

@section('content')
<header style="margin-bottom: 20px;">
    <div>
        <div style="display: flex; align-items: center; gap: 10px;">
            <h1 style="margin: 0;">Dashboard</h1>
            <span class="badge {{ $roleView === 'owner' ? 'badge-info' : ($roleView === 'shop-manager' ? 'badge-warning' : 'badge-success') }}">
                {{ $roleView === 'owner' ? 'Executive Overview' : ($roleView === 'shop-manager' ? 'Shop Manager' : 'Cashier Terminal') }}
            </span>
        </div>
        <div class="muted" style="margin-top: 4px;">
            {{ $tenant->name }} &bull; Branch: <strong>{{ $activeShop ? $activeShop->name : 'All Shops Combined' }}</strong>
        </div>
    </div>

    <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
        <!-- Perspective Switcher (where permitted) -->
        @if($isOwner)
            <form method="GET" action="{{ route('tenant.dashboard') }}" style="display: flex; gap: 6px; align-items: center;">
                @if(request('period'))<input type="hidden" name="period" value="{{ request('period') }}">@endif
                @if(request('shop_id'))<input type="hidden" name="shop_id" value="{{ request('shop_id') }}">@endif
                <select name="view" onchange="this.form.submit()" style="padding: 6px 10px; font-size: 13px;">
                    <option value="owner" @selected($roleView === 'owner')>Executive View</option>
                    <option value="manager" @selected($roleView === 'shop-manager')>Shop Manager View</option>
                    <option value="cashier" @selected($roleView === 'cashier')>Cashier View</option>
                </select>
            </form>
        @endif

        <!-- Shop Switcher -->
        @if($accessibleShops->count() > 1 && $roleView !== 'cashier')
            <form method="GET" action="{{ route('tenant.dashboard') }}" style="display: flex; gap: 6px; align-items: center;">
                @if(request('view'))<input type="hidden" name="view" value="{{ request('view') }}">@endif
                @if(request('period'))<input type="hidden" name="period" value="{{ request('period') }}">@endif
                <select name="shop_id" onchange="this.form.submit()" style="padding: 6px 10px; font-size: 13px;">
                    @if($isOwner)<option value="all" @selected(!$activeShop)>All Branches</option>@endif
                    @foreach($accessibleShops as $shop)
                        <option value="{{ $shop->id }}" @selected($activeShop && $activeShop->id === $shop->id)>{{ $shop->name }}</option>
                    @endforeach
                </select>
            </form>
        @endif

        <!-- Period Filter (for Owner and Manager) -->
        @if($roleView !== 'cashier')
            <form method="GET" action="{{ route('tenant.dashboard') }}" style="display: flex; gap: 6px; align-items: center;">
                @if(request('view'))<input type="hidden" name="view" value="{{ request('view') }}">@endif
                @if(request('shop_id'))<input type="hidden" name="shop_id" value="{{ request('shop_id') }}">@endif
                <select name="period" onchange="this.form.submit()" style="padding: 6px 10px; font-size: 13px;">
                    <option value="today" @selected($period === 'today')>Today</option>
                    <option value="7d" @selected($period === '7d')>Last 7 Days</option>
                    <option value="30d" @selected($period === '30d')>Last 30 Days</option>
                </select>
            </form>
        @endif

        <a href="{{ route('tenant.pos.checkout') }}" class="btn">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="14" x="2" y="3" rx="2"/><line x1="8" x2="16" y1="21" y2="21"/><line x1="12" x2="12" y1="17" y2="21"/></svg>
            <span>Open POS Terminal</span>
        </a>
    </div>
</header>

<!-- Industry Blueprint Banner (Annex A Part 27) -->
@if($tenant->industry === 'pharmacy')
    <div class="alert alert-info" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <div>
            <strong>Pharmacy Compliance &amp; Controlled Substances:</strong> Batch expiry logging and verified prescription dispensing are active for this pharmacy location.
        </div>
        <span class="badge badge-info">PHARMACY RAILS ACTIVE</span>
    </div>
@elseif(in_array($tenant->industry, ['restaurant', 'bar', 'hotel']))
    <div class="alert alert-info" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <div>
            <strong>Hospitality &amp; Table Service:</strong> Real-time order queues, guest folios, and service tabs are tracked under the hospitality blueprint.
        </div>
        <span class="badge badge-info">HOSPITALITY RAILS</span>
    </div>
@endif

@if($roleView === 'cashier')
    <!-- ========================================== -->
    <!-- CASHIER PERSPECTIVE (Annex A Part 21)       -->
    <!-- ========================================== -->
    @php $shift = $cashierData['currentShift']; @endphp

    @if($shift)
        <div class="alert alert-success" style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <strong>Active Shift Open:</strong> Till register <strong>{{ $shift->register?->name ?? 'Register #'.$shift->register_id }}</strong> &bull; Opened at {{ $shift->opened_at?->format('H:i') }}
            </div>
            <div style="display: flex; gap: 8px;">
                <a href="{{ route('tenant.pos.checkout') }}" class="btn btn-sm">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
                    <span>Enter Register</span>
                </a>
                <a href="{{ route('tenant.shifts.show', $shift) }}" class="btn btn-sm btn-secondary">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    <span>Manage Shift</span>
                </a>
            </div>
        </div>

        <div class="grid-cards">
            <div class="stat-card">
                <div class="label">Opening Float</div>
                <div class="value">KES {{ number_format((float) $shift->opening_float, 2) }}</div>
            </div>
            <div class="stat-card">
                <div class="label">Cash Sales in Shift</div>
                <div class="value" style="color: #15803d;">KES {{ number_format((float) $shift->cash_sales, 2) }}</div>
            </div>
            <div class="stat-card">
                <div class="label">M-Pesa Sales in Shift</div>
                <div class="value" style="color: #0284c7;">KES {{ number_format((float) $shift->mpesa_sales, 2) }}</div>
            </div>
            <div class="stat-card">
                <div class="label">Completed Transactions</div>
                <div class="value">{{ number_format($cashierData['shiftTransactionsCount']) }}</div>
            </div>
        </div>

        <div class="panel" style="margin-bottom: 24px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                <h3 style="margin: 0; font-size: 16px;">Recent Transactions in Current Shift</h3>
                <a href="{{ route('tenant.pos.sales') }}" style="font-size: 13px;">View All Sales &rarr;</a>
            </div>
            @if($cashierData['recentSales']->isNotEmpty())
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Receipt #</th>
                                <th>Grand Total</th>
                                <th>Payment Method</th>
                                <th>Time</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($cashierData['recentSales'] as $sale)
                                <tr>
                                    <td><strong>{{ $sale->sale_number }}</strong></td>
                                    <td style="font-weight: bold; color: #15803d;">KES {{ number_format((float) $sale->grand_total, 2) }}</td>
                                    <td><span class="badge badge-secondary">{{ strtoupper($sale->payment_status ?? 'PAID') }}</span></td>
                                    <td class="muted">{{ $sale->created_at?->format('H:i:s') }}</td>
                                    <td>
                                        <a href="{{ route('tenant.pos.receipt', $sale) }}" class="btn btn-sm btn-secondary">
                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1Z"/><line x1="8" y1="8" x2="16" y2="8"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
                                            <span>Receipt</span>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="muted" style="padding: 10px 0;">No sales processed yet in this shift.</div>
            @endif
        </div>
    @else
        <div class="alert alert-warning" style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <strong>No Active Cashier Shift:</strong> You must open a shift with an initial cash float before processing register sales.
            </div>
            <a href="{{ route('tenant.shifts.create') }}" class="btn btn-sm">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                <span>Open New Shift</span>
            </a>
        </div>

        <div class="panel" style="text-align: center; padding: 40px 20px;">
            <h2 style="margin-top: 0;">Register Terminal Ready</h2>
            <p class="muted" style="max-width: 500px; margin: 0 auto 20px;">Select your assigned cash register drawer, enter the opening float, and begin cashier operations.</p>
            <a href="{{ route('tenant.shifts.create') }}" class="btn" style="padding: 12px 24px; font-size: 15px;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="14" x="2" y="3" rx="2"/><line x1="8" x2="16" y1="21" y2="21"/><line x1="12" x2="12" y1="17" y2="21"/></svg>
                <span>Open Shift &amp; Start Register</span>
            </a>
        </div>
    @endif

@elseif($roleView === 'shop-manager')
    <!-- ========================================== -->
    <!-- SHOP MANAGER PERSPECTIVE (Annex A Part 20)  -->
    <!-- ========================================== -->
    @php $m = $managerData; @endphp

    <div class="grid-cards">
        <div class="stat-card">
            <div class="label">{{ ucfirst($period) }} Sales</div>
            <div class="value" style="color: #15803d;">KES {{ number_format((float) $m['salesTotal'], 2) }}</div>
            <div class="muted" style="font-size: 12px; margin-top: 4px;">{{ $m['salesCount'] }} transactions</div>
        </div>
        <div class="stat-card">
            <div class="label">Average Basket</div>
            <div class="value">KES {{ number_format((float) $m['averageBasket'], 2) }}</div>
        </div>
        <div class="stat-card">
            <div class="label">Cash Sales</div>
            <div class="value">KES {{ number_format((float) $m['cashSales'], 2) }}</div>
        </div>
        <div class="stat-card">
            <div class="label">M-Pesa Collections</div>
            <div class="value" style="color: #0284c7;">KES {{ number_format((float) $m['mpesaSales'], 2) }}</div>
        </div>
    </div>

    <!-- Active Registers & Cashiers on Duty -->
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 16px; margin-bottom: 24px;">
        <div class="panel">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                <h3 style="margin: 0; font-size: 16px;">Active Registers &amp; Shifts</h3>
                <a href="{{ route('tenant.shifts.index') }}" style="font-size: 13px;">View All Shifts &rarr;</a>
            </div>
            @if($m['openShifts']->isNotEmpty())
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Register</th>
                                <th>Cashier</th>
                                <th>Opening Float</th>
                                <th>Cash Sales</th>
                                <th>Opened</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($m['openShifts'] as $os)
                                <tr>
                                    <td><strong>{{ $os->register?->name ?? 'Register #'.$os->register_id }}</strong></td>
                                    <td>{{ $os->user?->name ?? 'User #'.$os->user_id }}</td>
                                    <td>KES {{ number_format((float) $os->opening_float, 2) }}</td>
                                    <td style="color: #15803d; font-weight: bold;">KES {{ number_format((float) $os->cash_sales, 2) }}</td>
                                    <td class="muted">{{ $os->opened_at?->format('H:i') }}</td>
                                    <td>
                                        <a href="{{ route('tenant.shifts.show', $os) }}" class="btn btn-sm btn-secondary">
                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                                            <span>Review</span>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="muted" style="padding: 10px 0;">No cashier shifts currently open in this branch.</div>
            @endif
        </div>

        <!-- Inventory Status for Shop -->
        <div class="panel">
            <h3 style="margin: 0 0 12px 0; font-size: 16px;">Branch Stock Alerts</h3>
            <div style="margin-bottom: 12px;">
                <div style="display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid #f1f5f9;">
                    <span class="muted">Out of Stock Items:</span>
                    <strong style="color: {{ $m['outOfStockCount'] > 0 ? '#dc2626' : '#15803d' }};">{{ $m['outOfStockCount'] }}</strong>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid #f1f5f9;">
                    <span class="muted">Low Stock Warnings:</span>
                    <strong style="color: {{ $m['lowStockItems']->count() > 0 ? '#d97706' : '#15803d' }};">{{ $m['lowStockItems']->count() }}</strong>
                </div>
            </div>

            @if($m['lowStockItems']->isNotEmpty())
                <div style="font-size: 12px;">
                    <span class="muted" style="font-weight: 600;">Items Needing Replenishment:</span>
                    <ul style="margin: 6px 0 0 0; padding-left: 18px;">
                        @foreach($m['lowStockItems']->take(4) as $item)
                            <li>{{ $item->product?->name ?? 'Product' }} ({{ number_format((float) $item->quantity) }} left)</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div style="margin-top: 14px;">
                <a href="{{ route('tenant.inventory.receive') }}" class="btn btn-sm btn-secondary" style="width: 100%;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><path d="M15 18H9"/><path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.624l-3.48-4.35A1 1 0 0 0 17.52 8H14"/><circle cx="17" cy="18" r="2"/><circle cx="7" cy="18" r="2"/></svg>
                    <span>Receive Stock</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Shift Variances & Exceptions -->
    @if($m['shiftVariances']->isNotEmpty())
        <div class="panel" style="margin-bottom: 24px; border-left: 4px solid #dc2626;">
            <h3 style="margin: 0 0 10px 0; font-size: 16px; color: #991b1b;">Shift Cash Variances Requiring Review</h3>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Shift #</th>
                            <th>Cashier</th>
                            <th>Expected Cash</th>
                            <th>Actual Cash</th>
                            <th>Discrepancy</th>
                            <th>Closed At</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($m['shiftVariances'] as $sv)
                            <tr>
                                <td><a href="{{ route('tenant.shifts.show', $sv) }}">#{{ $sv->id }}</a></td>
                                <td>{{ $sv->user?->name }}</td>
                                <td>KES {{ number_format((float) $sv->expected_cash, 2) }}</td>
                                <td>KES {{ number_format((float) $sv->actual_cash, 2) }}</td>
                                <td style="font-weight: bold; color: {{ (float) $sv->variance < 0 ? '#dc2626' : '#15803d' }};">
                                    KES {{ number_format((float) $sv->variance, 2) }}
                                </td>
                                <td class="muted">{{ $sv->closed_at?->format('Y-m-d H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

@else
    <!-- ========================================== -->
    <!-- OWNER / BUSINESS MANAGER PERSPECTIVE (Part 19)-->
    <!-- ========================================== -->
    @php $o = $ownerData; @endphp

    <!-- Alerts Strip (Loss Prevention & Control Signals) -->
    @if($o['voidedSalesCount'] > 0 || $o['outOfStockCount'] > 0)
        <div style="display: grid; gap: 10px; margin-bottom: 20px;">
            @if($o['voidedSalesCount'] > 0)
                <div class="alert alert-warning" style="margin-bottom: 0; display: flex; justify-content: space-between; align-items: center;">
                    <span><strong>Loss Prevention Signal:</strong> {{ $o['voidedSalesCount'] }} voided transaction(s) totaling <strong>KES {{ number_format((float) $o['voidedSalesTotal'], 2) }}</strong> were logged during this period.</span>
                    <a href="{{ route('tenant.pos.sales') }}?status=voided" style="font-weight: bold; text-decoration: underline;">Audit Voids &rarr;</a>
                </div>
            @endif
            @if($o['outOfStockCount'] > 0)
                <div class="alert alert-danger" style="margin-bottom: 0; display: flex; justify-content: space-between; align-items: center;">
                    <span><strong>Inventory Out-of-Stock Alert:</strong> {{ $o['outOfStockCount'] }} product catalog item(s) are completely out of stock across active locations.</span>
                    <a href="{{ route('tenant.inventory.index') }}" style="font-weight: bold; text-decoration: underline;">View Stock Levels &rarr;</a>
                </div>
            @endif
        </div>
    @endif

    <!-- Headline Financial & Sales KPI Strip -->
    <div class="grid-cards">
        <div class="stat-card">
            <div class="label">Gross Sales</div>
            <div class="value" style="color: #15803d;">KES {{ number_format((float) $o['grossSales'], 2) }}</div>
            <div class="muted" style="font-size: 12px; margin-top: 4px;">{{ $o['salesCount'] }} completed sales</div>
        </div>
        <div class="stat-card">
            <div class="label">Net Sales (Excl. Tax)</div>
            <div class="value">KES {{ number_format((float) $o['netSales'], 2) }}</div>
            <div class="muted" style="font-size: 12px; margin-top: 4px;">VAT: KES {{ number_format((float) $o['taxTotal'], 2) }}</div>
        </div>
        <div class="stat-card">
            <div class="label">Average Basket</div>
            <div class="value">KES {{ number_format((float) $o['averageBasket'], 2) }}</div>
        </div>
        <div class="stat-card">
            <div class="label">M-Pesa Collections</div>
            <div class="value" style="color: #0284c7;">KES {{ number_format((float) $o['mpesaSales'], 2) }}</div>
            <div class="muted" style="font-size: 12px; margin-top: 4px;">Cash: KES {{ number_format((float) $o['cashSales'], 2) }}</div>
        </div>
    </div>

    <!-- Multi-Shop Performance & Branch Comparison -->
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 16px; margin-bottom: 24px;">
        <div class="panel">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                <h3 style="margin: 0; font-size: 16px;">Multi-Shop Branch Performance</h3>
                <span class="muted" style="font-size: 12px;">{{ $accessibleShops->count() }} registered location(s)</span>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Branch Location</th>
                            <th>Code</th>
                            <th style="text-align: right;">Sales Count</th>
                            <th style="text-align: right;">Total Revenue</th>
                            <th style="text-align: right;">Active Shifts</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($o['shopPerformance'] as $sp)
                            <tr>
                                <td><strong>{{ $sp['shop']->name }}</strong></td>
                                <td><code>{{ $sp['shop']->code }}</code></td>
                                <td style="text-align: right;">{{ number_format($sp['sales_count']) }}</td>
                                <td style="text-align: right; font-weight: bold; color: #15803d;">KES {{ number_format((float) $sp['sales_total'], 2) }}</td>
                                <td style="text-align: right;">
                                    <span class="badge {{ $sp['open_shifts'] > 0 ? 'badge-success' : 'badge-secondary' }}">
                                        {{ $sp['open_shifts'] }} open
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Top Selling Products -->
        <div class="panel">
            <h3 style="margin: 0 0 12px 0; font-size: 16px;">Top Products by Volume</h3>
            @if($o['topProducts']->isNotEmpty())
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th style="text-align: right;">Qty</th>
                                <th style="text-align: right;">Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($o['topProducts'] as $tp)
                                <tr>
                                    <td><strong>{{ $tp->product_name }}</strong></td>
                                    <td style="text-align: right;">{{ number_format((float) $tp->total_qty) }}</td>
                                    <td style="text-align: right; color: #15803d; font-weight: 600;">KES {{ number_format((float) $tp->total_revenue, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="muted" style="padding: 10px 0;">No product sales recorded in this period.</div>
            @endif
        </div>
    </div>

    <!-- Subscription & Business Profile Card -->
    <div class="panel" style="margin-bottom: 24px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
            <h3 style="margin: 0; font-size: 16px;">Business Entity &amp; Subscription Plan</h3>
            <span class="badge badge-success">{{ strtoupper($tenant->status) }}</span>
        </div>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px; font-size: 14px;">
            <div>
                <span class="muted">KRA PIN:</span>
                <div><strong>{{ $tenant->kra_pin ?? 'Not configured' }}</strong></div>
            </div>
            <div>
                <span class="muted">Active Commercial Plan:</span>
                <div><strong>{{ $o['subscription']?->plan?->name ?? 'Standard SaaS Tier' }}</strong></div>
            </div>
            <div>
                <span class="muted">Operating Industry:</span>
                <div style="text-transform: capitalize;"><strong>{{ str_replace('_', ' ', $tenant->industry) }}</strong></div>
            </div>
            <div>
                <span class="muted">Base Currency:</span>
                <div><strong>{{ $tenant->currency }} (Kenya Shillings)</strong></div>
            </div>
        </div>
    </div>
@endif

<!-- Quick Operations Panel -->
<div class="panel">
    <h3 style="font-size: 16px; margin-top: 0; margin-bottom: 12px;">Quick Business Operations</h3>
    <div style="display: flex; gap: 12px; flex-wrap: wrap;">
        <a href="{{ route('tenant.pos.checkout') }}" class="btn">POS Checkout</a>
        <a href="{{ route('tenant.shifts.create') }}" class="btn btn-secondary">Open New Shift</a>
        <a href="{{ route('tenant.products.create') }}" class="btn btn-secondary">Add Product</a>
        <a href="{{ route('tenant.inventory.receive') }}" class="btn btn-secondary">Receive Delivery</a>
        <a href="{{ route('tenant.inventory.movements') }}" class="btn btn-secondary">Stock Movements Audit</a>
    </div>
</div>
@endsection
