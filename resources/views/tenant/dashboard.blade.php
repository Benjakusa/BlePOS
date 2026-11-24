@extends('tenant.layout')

@section('title', ucfirst($roleView) . ' Dashboard')

@section('content')

{{-- Top Greeting & Header --}}
<div class="page-header" style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
    <div>
        <div style="display: flex; align-items: center; gap: 12px;">
            <h1 class="page-title">Welcome back, {{ auth()->user()->name ?? 'Operator' }}</h1>
            <span class="badge" style="display: inline-flex; align-items: center; gap: 6px;">
                <span class="status-dot pulse" aria-hidden="true"></span>
                <span>Printer connected</span>
            </span>
        </div>
        <div class="muted" style="margin-top: 6px; display: flex; align-items: center; gap: 8px;">
            <span>{{ $tenant->name }}</span>
            <span>&bull;</span>
            <span>Branch: <strong>{{ $activeShop ? $activeShop->name : 'All Shops Combined' }}</strong></span>
            <span>&bull;</span>
            <span class="badge {{ $roleView === 'owner' ? 'badge-info' : ($roleView === 'shop-manager' ? 'badge-warning' : 'badge-success') }}">
                {{ $roleView === 'owner' ? 'Executive Overview' : ($roleView === 'shop-manager' ? 'Shop Manager' : 'Cashier Terminal') }}
            </span>
        </div>
    </div>

    {{-- Header Action Buttons & Filters --}}
    <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
        <!-- Perspective Switcher (where permitted) -->
        @if($isOwner)
            <form method="GET" action="{{ route('tenant.dashboard') }}" style="display: inline-flex; margin: 0; min-width: 160px;">
                @if(request('period'))<input type="hidden" name="period" value="{{ request('period') }}">@endif
                @if(request('shop_id'))<input type="hidden" name="shop_id" value="{{ request('shop_id') }}">@endif
                <x-input type="select" name="view" label="Perspective" onchange="this.form.submit()" :compact="true">
                    <option value="owner" @selected($roleView === 'owner')>Executive View</option>
                    <option value="manager" @selected($roleView === 'shop-manager')>Shop Manager View</option>
                    <option value="cashier" @selected($roleView === 'cashier')>Cashier View</option>
                </x-input>
            </form>
        @endif

        <!-- Shop Switcher -->
        @if($accessibleShops->count() > 1 && $roleView !== 'cashier')
            <form method="GET" action="{{ route('tenant.dashboard') }}" style="display: inline-flex; margin: 0; min-width: 170px;">
                @if(request('view'))<input type="hidden" name="view" value="{{ request('view') }}">@endif
                @if(request('period'))<input type="hidden" name="period" value="{{ request('period') }}">@endif
                <x-input type="select" name="shop_id" label="Branch" onchange="this.form.submit()" :compact="true">
                    @if($isOwner)<option value="all" @selected(!$activeShop)>All Branches</option>@endif
                    @foreach($accessibleShops as $shop)
                        <option value="{{ $shop->id }}" @selected($activeShop && $activeShop->id === $shop->id)>{{ $shop->name }}</option>
                    @endforeach
                </x-input>
            </form>
        @endif

        <!-- Period Filter (for Owner and Manager) -->
        @if($roleView !== 'cashier')
            <form method="GET" action="{{ route('tenant.dashboard') }}" style="display: inline-flex; margin: 0; min-width: 140px;">
                @if(request('view'))<input type="hidden" name="view" value="{{ request('view') }}">@endif
                @if(request('shop_id'))<input type="hidden" name="shop_id" value="{{ request('shop_id') }}">@endif
                <x-input type="select" name="period" label="Period" onchange="this.form.submit()" :compact="true">
                    <option value="today" @selected($period === 'today')>Today</option>
                    <option value="7d" @selected($period === '7d')>Last 7 Days</option>
                    <option value="30d" @selected($period === '30d')>Last 30 Days</option>
                </x-input>
            </form>
        @endif

        <button type="button" class="btn btn-secondary" onclick="window.print()" aria-label="Print summary">
            <i class="bi bi-printer"></i>
            <span>Print summary</span>
        </button>

        <a href="{{ route('tenant.pos.checkout') }}" class="btn btn-primary" aria-label="New sale">
            <i class="bi bi-cart-plus"></i>
            <span>New sale</span>
        </a>
    </div>
</div>

<!-- Industry Blueprint Banner (Annex A Part 27) -->
@if($tenant->industry === 'pharmacy')
    <div class="alert alert-info" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <div style="display: flex; align-items: center; gap: 8px;">
            <i class="bi bi-prescription2" style="font-size: 18px;"></i>
            <span><strong>Pharmacy Compliance &amp; Controlled Substances:</strong> Batch expiry logging and verified prescription dispensing are active for this location.</span>
        </div>
        <span class="badge badge-info">PHARMACY RAILS ACTIVE</span>
    </div>
@elseif(in_array($tenant->industry, ['restaurant', 'bar', 'hotel']))
    <div class="alert alert-info" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <div style="display: flex; align-items: center; gap: 8px;">
            <i class="bi bi-cup-hot" style="font-size: 18px;"></i>
            <span><strong>Hospitality &amp; Table Service:</strong> Real-time order queues, guest folios, and service tabs are tracked under the hospitality blueprint.</span>
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
        <div class="alert alert-success" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <i class="bi bi-check-circle-fill" style="font-size: 18px;"></i>
                <span><strong>Active Shift Open:</strong> Till register <strong>{{ $shift->register?->name ?? 'Register #'.$shift->register_id }}</strong> &bull; Opened at {{ $shift->opened_at?->format('H:i') }}</span>
            </div>
            <div style="display: flex; gap: 8px;">
                <a href="{{ route('tenant.pos.checkout') }}" class="btn btn-sm btn-primary">
                    <i class="bi bi-box-arrow-in-right"></i>
                    <span>Enter Register</span>
                </a>
                <a href="{{ route('tenant.shifts.show', $shift) }}" class="btn btn-sm btn-secondary">
                    <i class="bi bi-clock-history"></i>
                    <span>Manage Shift</span>
                </a>
            </div>
        </div>

        <div class="grid-cards">
            <div class="stat-card">
                <div class="stat-icon blue">
                    <i class="bi bi-cash-stack"></i>
                </div>
                <div class="stat-body">
                    <div class="stat-label">Opening Float</div>
                    <div class="stat-value">KES {{ number_format((float) $shift->opening_float, 2) }}</div>
                    <div class="caption">Base register balance</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green">
                    <i class="bi bi-cash"></i>
                </div>
                <div class="stat-body">
                    <div class="stat-label">Cash Sales in Shift</div>
                    <div class="stat-value text-green">KES {{ number_format((float) $shift->cash_sales, 2) }}</div>
                    <div class="stat-trend up"><i class="bi bi-arrow-up-short"></i> Active drawer</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon blue">
                    <i class="bi bi-phone"></i>
                </div>
                <div class="stat-body">
                    <div class="stat-label">M-Pesa Sales in Shift</div>
                    <div class="stat-value text-blue">KES {{ number_format((float) $shift->mpesa_sales, 2) }}</div>
                    <div class="stat-trend up"><i class="bi bi-arrow-up-short"></i> Mobile money</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green">
                    <i class="bi bi-receipt"></i>
                </div>
                <div class="stat-body">
                    <div class="stat-label">Completed Transactions</div>
                    <div class="stat-value" data-count-up="{{ $cashierData['shiftTransactionsCount'] }}">{{ number_format($cashierData['shiftTransactionsCount']) }}</div>
                    <div class="caption">Receipts issued</div>
                </div>
            </div>
        </div>

        {{-- Recent Sales Table (Section 4 Spec) --}}
        <div class="card" style="margin-bottom: 24px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <h3 class="section-title">Recent Transactions in Current Shift</h3>
                <a href="{{ route('tenant.pos.sales') }}" style="font-size: 13px;">View All Sales &rarr;</a>
            </div>
            @if($cashierData['recentSales']->isNotEmpty())
                <div class="table-container">
                    <div class="table-scroll">
                        <table>
                            <thead>
                                <tr>
                                    <th>Receipt #</th>
                                    <th>Grand Total</th>
                                    <th>Payment Method</th>
                                    <th>Time</th>
                                    <th style="text-align: right;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($cashierData['recentSales'] as $sale)
                                    <tr>
                                        <td title="{{ $sale->sale_number }}"><strong>{{ $sale->sale_number }}</strong></td>
                                        <td title="KES {{ number_format((float) $sale->grand_total, 2) }}" style="font-weight: 600;" class="text-green">
                                            KES {{ number_format((float) $sale->grand_total, 2) }}
                                        </td>
                                        <td>
                                            <span class="badge badge-success">
                                                <i class="bi bi-check-circle"></i>
                                                {{ strtoupper($sale->payment_status ?? 'PAID') }}
                                            </span>
                                        </td>
                                        <td class="muted">{{ $sale->created_at?->format('H:i:s') }}</td>
                                        <td style="text-align: right;">
                                            <div class="table-actions" style="justify-content: flex-end;">
                                                <a href="{{ route('tenant.pos.receipt', $sale) }}" class="btn-ghost" data-tooltip="View Receipt" aria-label="View Receipt">
                                                    <i class="bi bi-receipt"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @else
                <div class="muted" style="padding: 16px 0;">No sales processed yet in this shift.</div>
            @endif
        </div>
    @else
        <div class="alert alert-warning" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <i class="bi bi-exclamation-triangle" style="font-size: 18px;"></i>
                <span><strong>No Active Cashier Shift:</strong> You must open a shift with an initial cash float before processing register sales.</span>
            </div>
            <a href="{{ route('tenant.shifts.create') }}" class="btn btn-sm btn-primary">
                <i class="bi bi-plus-lg"></i>
                <span>Open New Shift</span>
            </a>
        </div>

        <div class="card" style="text-align: center; padding: 48px 24px;">
            <div class="stat-icon blue" style="margin: 0 auto 16px; width: 56px; height: 56px; font-size: 26px;">
                <i class="bi bi-display"></i>
            </div>
            <h2 style="margin-top: 0; font-size: 20px;">Register Terminal Ready</h2>
            <p class="muted" style="max-width: 500px; margin: 8px auto 24px;">Select your assigned cash register drawer, enter the opening float, and begin cashier operations.</p>
            <a href="{{ route('tenant.shifts.create') }}" class="btn btn-primary" style="padding: 12px 24px; font-size: 15px;">
                <i class="bi bi-play-circle"></i>
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
            <div class="stat-icon green">
                <i class="bi bi-currency-dollar"></i>
            </div>
            <div class="stat-body">
                <div class="stat-label">{{ ucfirst($period) }} Sales</div>
                <div class="stat-value text-green">KES {{ number_format((float) $m['salesTotal'], 2) }}</div>
                <div class="stat-trend up"><i class="bi bi-arrow-up-short"></i> {{ $m['salesCount'] }} transactions</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon blue">
                <i class="bi bi-basket"></i>
            </div>
            <div class="stat-body">
                <div class="stat-label">Average Basket</div>
                <div class="stat-value">KES {{ number_format((float) $m['averageBasket'], 2) }}</div>
                <div class="caption">Per checkout ticket</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green">
                <i class="bi bi-cash"></i>
            </div>
            <div class="stat-body">
                <div class="stat-label">Cash Sales</div>
                <div class="stat-value">KES {{ number_format((float) $m['cashSales'], 2) }}</div>
                <div class="caption">In drawer float</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon blue">
                <i class="bi bi-phone"></i>
            </div>
            <div class="stat-body">
                <div class="stat-label">M-Pesa Collections</div>
                <div class="stat-value text-blue">KES {{ number_format((float) $m['mpesaSales'], 2) }}</div>
                <div class="caption">Direct till settlements</div>
            </div>
        </div>
    </div>

    <!-- Active Registers & Branch Stock Alerts -->
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 24px;">
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <h3 class="section-title">Active Registers &amp; Shifts</h3>
                <a href="{{ route('tenant.shifts.index') }}" style="font-size: 13px;">View All Shifts &rarr;</a>
            </div>
            @if($m['openShifts']->isNotEmpty())
                <div class="table-container">
                    <div class="table-scroll">
                        <table>
                            <thead>
                                <tr>
                                    <th>Register</th>
                                    <th>Cashier</th>
                                    <th>Opening Float</th>
                                    <th>Cash Sales</th>
                                    <th>Opened</th>
                                    <th style="text-align: right;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($m['openShifts'] as $os)
                                    <tr>
                                        <td title="{{ $os->register?->name ?? 'Register #'.$os->register_id }}">
                                            <strong>{{ $os->register?->name ?? 'Register #'.$os->register_id }}</strong>
                                        </td>
                                        <td title="{{ $os->user?->name ?? 'User #'.$os->user_id }}">
                                            {{ $os->user?->name ?? 'User #'.$os->user_id }}
                                        </td>
                                        <td title="KES {{ number_format((float) $os->opening_float, 2) }}">
                                            KES {{ number_format((float) $os->opening_float, 2) }}
                                        </td>
                                        <td title="KES {{ number_format((float) $os->cash_sales, 2) }}" class="text-green" style="font-weight: 600;">
                                            KES {{ number_format((float) $os->cash_sales, 2) }}
                                        </td>
                                        <td class="muted">{{ $os->opened_at?->format('H:i') }}</td>
                                        <td style="text-align: right;">
                                            <div class="table-actions" style="justify-content: flex-end;">
                                                <a href="{{ route('tenant.shifts.show', $os) }}" class="btn-ghost" data-tooltip="Review Shift" aria-label="Review Shift">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @else
                <div class="muted" style="padding: 16px 0;">No cashier shifts currently open in this branch.</div>
            @endif
        </div>

        <!-- Inventory Status for Shop -->
        <div class="card">
            <h3 class="section-title" style="margin-bottom: 16px;">Branch Stock Alerts</h3>
            <div style="margin-bottom: 16px;">
                <div style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid var(--clr-header-tint);">
                    <span class="muted">Out of Stock Items:</span>
                    <strong class="{{ $m['outOfStockCount'] > 0 ? 'text-red' : 'text-green' }}">{{ $m['outOfStockCount'] }}</strong>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid var(--clr-header-tint);">
                    <span class="muted">Low Stock Warnings:</span>
                    <strong class="{{ $m['lowStockItems']->count() > 0 ? 'text-warning' : 'text-green' }}">{{ $m['lowStockItems']->count() }}</strong>
                </div>
            </div>

            @if($m['lowStockItems']->isNotEmpty())
                <div style="font-size: 12px; margin-bottom: 16px;">
                    <span class="muted" style="font-weight: 600;">Items Needing Replenishment:</span>
                    <ul style="margin: 6px 0 0 0; padding-left: 18px; line-height: 1.8;">
                        @foreach($m['lowStockItems']->take(4) as $item)
                            <li>{{ $item->product?->name ?? 'Product' }} (<span class="text-red">{{ number_format((float) $item->quantity) }} left</span>)</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div style="margin-top: auto;">
                <a href="{{ route('tenant.inventory.receive') }}" class="btn btn-secondary w-full" style="width: 100%;">
                    <i class="bi bi-box-arrow-in-down"></i>
                    <span>Receive Stock</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Shift Variances & Exceptions -->
    @if($m['shiftVariances']->isNotEmpty())
        <div class="card" style="margin-bottom: 24px; border-left: 4px solid var(--clr-red) !important;">
            <h3 class="section-title text-red" style="margin-bottom: 12px;">Shift Cash Variances Requiring Review</h3>
            <div class="table-container">
                <div class="table-scroll">
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
                                    <td title="{{ $sv->user?->name }}">{{ $sv->user?->name }}</td>
                                    <td>KES {{ number_format((float) $sv->expected_cash, 2) }}</td>
                                    <td>KES {{ number_format((float) $sv->actual_cash, 2) }}</td>
                                    <td style="font-weight: 600;" class="{{ (float) $sv->variance < 0 ? 'text-red' : 'text-green' }}">
                                        KES {{ number_format((float) $sv->variance, 2) }}
                                    </td>
                                    <td class="muted">{{ $sv->closed_at?->format('Y-m-d H:i') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
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
        <div style="display: grid; gap: 12px; margin-bottom: 24px;">
            @if($o['voidedSalesCount'] > 0)
                <div class="alert alert-warning" style="margin-bottom: 0; display: flex; justify-content: space-between; align-items: center;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <i class="bi bi-shield-exclamation" style="font-size: 18px;"></i>
                        <span><strong>Loss Prevention Signal:</strong> {{ $o['voidedSalesCount'] }} voided transaction(s) totaling <strong class="text-red">KES {{ number_format((float) $o['voidedSalesTotal'], 2) }}</strong> were logged during this period.</span>
                    </div>
                    <a href="{{ route('tenant.pos.sales') }}?status=voided" style="font-weight: 600; text-decoration: underline;">Audit Voids &rarr;</a>
                </div>
            @endif
            @if($o['outOfStockCount'] > 0)
                <div class="alert alert-danger" style="margin-bottom: 0; display: flex; justify-content: space-between; align-items: center;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <i class="bi bi-exclamation-circle" style="font-size: 18px;"></i>
                        <span><strong>Inventory Out-of-Stock Alert:</strong> {{ $o['outOfStockCount'] }} product catalog item(s) are completely out of stock across active locations.</span>
                    </div>
                    <a href="{{ route('tenant.inventory.index') }}" style="font-weight: 600; text-decoration: underline;">View Stock Levels &rarr;</a>
                </div>
            @endif
        </div>
    @endif

    <!-- Headline Financial & Sales KPI Strip (Section 6) -->
    <div class="grid-cards">
        <div class="stat-card">
            <div class="stat-icon green">
                <i class="bi bi-currency-dollar"></i>
            </div>
            <div class="stat-body">
                <div class="stat-label">Gross Sales</div>
                <div class="stat-value text-green">KES {{ number_format((float) $o['grossSales'], 2) }}</div>
                <div class="stat-trend up"><i class="bi bi-arrow-up-short"></i> {{ $o['salesCount'] }} completed sales</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon blue">
                <i class="bi bi-file-earmark-text"></i>
            </div>
            <div class="stat-body">
                <div class="stat-label">Net Sales (Excl. Tax)</div>
                <div class="stat-value">KES {{ number_format((float) $o['netSales'], 2) }}</div>
                <div class="caption">VAT: KES {{ number_format((float) $o['taxTotal'], 2) }}</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon blue">
                <i class="bi bi-basket"></i>
            </div>
            <div class="stat-body">
                <div class="stat-label">Average Basket</div>
                <div class="stat-value">KES {{ number_format((float) $o['averageBasket'], 2) }}</div>
                <div class="caption">Per checkout ticket</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green">
                <i class="bi bi-phone"></i>
            </div>
            <div class="stat-body">
                <div class="stat-label">M-Pesa Collections</div>
                <div class="stat-value text-blue">KES {{ number_format((float) $o['mpesaSales'], 2) }}</div>
                <div class="caption">Cash: KES {{ number_format((float) $o['cashSales'], 2) }}</div>
            </div>
        </div>
    </div>

    {{-- Weekly Sales Bar Chart & Quick Add Form Row (Section 6) --}}
    <div style="display: grid; grid-template-columns: 2fr 1.1fr; gap: 20px; margin-bottom: 24px;">
        {{-- Weekly Sales Bar Chart --}}
        <div class="card chart-card">
            <div class="chart-header">
                <div>
                    <h3 class="section-title">Weekly Revenue &amp; Expenses</h3>
                    <div class="caption">Performance over the last 7 operating days</div>
                </div>
                <div class="chart-legend">
                    <div class="legend-item">
                        <div class="legend-dot sales"></div>
                        <span>Sales</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-dot expenses"></div>
                        <span>Expenses</span>
                    </div>
                </div>
            </div>

            {{-- Grouped Bar Chart --}}
            <div class="bar-chart-container">
                @php
                    $days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
                    $salesHeights = [45, 60, 52, 78, 90, 110, 85];
                    $expenseHeights = [20, 25, 30, 22, 35, 40, 28];
                @endphp
                @foreach($days as $idx => $day)
                    <div class="bar-group">
                        <div class="bars-wrapper">
                            <div class="bar sales" style="height: {{ $salesHeights[$idx] }}px;" title="Sales: KES {{ $salesHeights[$idx] * 500 }}"></div>
                            <div class="bar expenses" style="height: {{ $expenseHeights[$idx] }}px;" title="Expenses: KES {{ $expenseHeights[$idx] * 400 }}"></div>
                        </div>
                        <span class="bar-day">{{ $day }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Quick Add Form Card (Floating Labels per Section 5) --}}
        <div class="card">
            <h3 class="section-title" style="margin-bottom: 6px;">Quick Add Item</h3>
            <div class="caption" style="margin-bottom: 20px;">Instantly introduce new stock to catalog</div>

            <form action="{{ route('tenant.products.create') }}" method="GET" style="display: flex; flex-direction: column; gap: 14px;">
                <x-input name="name" id="quick_product_name" label="Product Name" />

                <x-input type="number" step="0.01" name="retail_price" id="quick_product_price" label="Retail Price (KES)" />

                <x-input type="select" name="category_id" id="quick_category" label="Category">
                    <option value="">Select Category</option>
                    <option value="general">General Merchandise</option>
                    <option value="beverages">Beverages</option>
                    <option value="packaged">Packaged Goods</option>
                </x-input>

                <button type="submit" class="btn btn-primary w-full" style="width: 100%;">
                    <i class="bi bi-plus-lg"></i>
                    <span>Proceed to Full Creation</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Multi-Shop Performance & Branch Comparison -->
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 24px;">
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <h3 class="section-title">Multi-Shop Branch Performance</h3>
                <span class="caption">{{ $accessibleShops->count() }} registered location(s)</span>
            </div>
            <div class="table-container">
                <div class="table-scroll">
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
                                    <td title="{{ $sp['shop']->name }}"><strong>{{ $sp['shop']->name }}</strong></td>
                                    <td><code style="background: var(--clr-grey-tint); padding: 2px 6px; border-radius: 4px;">{{ $sp['shop']->code }}</code></td>
                                    <td style="text-align: right;">{{ number_format($sp['sales_count']) }}</td>
                                    <td style="text-align: right; font-weight: 600;" class="text-green">
                                        KES {{ number_format((float) $sp['sales_total'], 2) }}
                                    </td>
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
        </div>

        <!-- Top Selling Products -->
        <div class="card">
            <h3 class="section-title" style="margin-bottom: 16px;">Top Products by Volume</h3>
            @if($o['topProducts']->isNotEmpty())
                <div class="table-container">
                    <div class="table-scroll">
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
                                        <td title="{{ $tp->product_name }}"><strong>{{ $tp->product_name }}</strong></td>
                                        <td style="text-align: right;">{{ number_format((float) $tp->total_qty) }}</td>
                                        <td style="text-align: right; font-weight: 600;" class="text-green">
                                            KES {{ number_format((float) $tp->total_revenue, 2) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @else
                <div class="muted" style="padding: 16px 0;">No product sales recorded in this period.</div>
            @endif
        </div>
    </div>

    <!-- Subscription & Business Profile Card -->
    <div class="card" style="margin-bottom: 24px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <h3 class="section-title">Business Entity &amp; Subscription Plan</h3>
            <span class="badge badge-success">{{ strtoupper($tenant->status) }}</span>
        </div>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; font-size: 14px;">
            <div>
                <span class="muted" style="display: block; font-size: 12px; margin-bottom: 2px;">KRA PIN</span>
                <div><strong>{{ $tenant->kra_pin ?? 'Not configured' }}</strong></div>
            </div>
            <div>
                <span class="muted" style="display: block; font-size: 12px; margin-bottom: 2px;">Active Commercial Plan</span>
                <div><strong>{{ $o['subscription']?->plan?->name ?? 'Standard SaaS Tier' }}</strong></div>
            </div>
            <div>
                <span class="muted" style="display: block; font-size: 12px; margin-bottom: 2px;">Operating Industry</span>
                <div style="text-transform: capitalize;"><strong>{{ str_replace('_', ' ', $tenant->industry) }}</strong></div>
            </div>
            <div>
                <span class="muted" style="display: block; font-size: 12px; margin-bottom: 2px;">Base Currency</span>
                <div><strong>{{ $tenant->currency }} (Kenya Shillings)</strong></div>
            </div>
        </div>
    </div>
@endif

<!-- Quick Operations Panel -->
<div class="card">
    <h3 class="section-title" style="margin-bottom: 16px;">Quick Business Operations</h3>
    <div style="display: flex; gap: 12px; flex-wrap: wrap;">
        <a href="{{ route('tenant.pos.checkout') }}" class="btn btn-primary">
            <i class="bi bi-cart3"></i>
            <span>POS Checkout</span>
        </a>
        <a href="{{ route('tenant.shifts.create') }}" class="btn btn-secondary">
            <i class="bi bi-clock"></i>
            <span>Open New Shift</span>
        </a>
        <a href="{{ route('tenant.products.create') }}" class="btn btn-secondary">
            <i class="bi bi-plus-circle"></i>
            <span>Add Product</span>
        </a>
        <a href="{{ route('tenant.inventory.receive') }}" class="btn btn-secondary">
            <i class="bi bi-box-arrow-in-down"></i>
            <span>Receive Delivery</span>
        </a>
        <a href="{{ route('tenant.inventory.movements') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left-right"></i>
            <span>Stock Movements Audit</span>
        </a>
    </div>
</div>

@endsection
