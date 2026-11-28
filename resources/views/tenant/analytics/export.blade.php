@extends('tenant.layout')

@section('title', 'Data Export Center')

@section('content')
<div class="page-header" style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
    <div>
        <div style="display: flex; align-items: center; gap: 12px;">
            <h1 class="page-title">Data Export Center</h1>
            <span class="badge badge-success"><i class="bi bi-shield-check"></i> Formula-Injection Safe</span>
        </div>
        <p class="muted" style="margin-top: 4px;">
            Export audited business, financial, inventory, and operational datasets into CSV format for external reporting, accounting, or audits.
        </p>
    </div>

    <a href="{{ route('tenant.analytics.index') }}" class="btn btn-secondary" style="text-decoration: none;">
        <i class="bi bi-arrow-left"></i> Back to Analytics
    </a>
</div>

{{-- Global Export Filters Card --}}
<div class="card" style="padding: 20px; margin-bottom: 24px;">
    <h3 style="font-size: 1.05rem; font-weight: 600; margin-bottom: 12px;">
        <i class="bi bi-funnel"></i> Target Export Parameters
    </h3>
    <p class="muted" style="font-size: 0.85rem; margin-bottom: 16px;">
        Select the date range and branch you wish to filter for your exported datasets. These parameters apply when clicking any of the export actions below.
    </p>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
        <div>
            <label class="form-label" style="font-size: 0.825rem; font-weight: 600;">From Date</label>
            <input type="date" id="filter_from_date" class="form-control" value="{{ now()->subDays(30)->format('Y-m-d') }}">
        </div>
        <div>
            <label class="form-label" style="font-size: 0.825rem; font-weight: 600;">To Date</label>
            <input type="date" id="filter_to_date" class="form-control" value="{{ now()->format('Y-m-d') }}">
        </div>
        <div>
            <label class="form-label" style="font-size: 0.825rem; font-weight: 600;">Branch / Shop Location</label>
            <select id="filter_shop_id" class="form-control">
                <option value="">All Branches</option>
                @foreach($shops as $shop)
                    <option value="{{ $shop->id }}">{{ $shop->name }} ({{ $shop->code }})</option>
                @endforeach
            </select>
        </div>
    </div>
</div>

{{-- Dataset Export Cards Grid --}}
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px;">
    {{-- 1. Sales & Transactions --}}
    <div class="card" style="padding: 20px; display: flex; flex-direction: column; justify-content: space-between;">
        <div>
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                <div style="width: 36px; height: 36px; border-radius: 8px; background: rgba(59, 130, 246, 0.1); color: #3b82f6; display: flex; align-items: center; justify-content: center; font-size: 1.2rem;">
                    <i class="bi bi-receipt"></i>
                </div>
                <div>
                    <h3 style="font-size: 1.05rem; font-weight: 600; margin: 0;">Sales &amp; Orders</h3>
                    <span class="muted" style="font-size: 0.75rem;">Financial register receipts &amp; payments</span>
                </div>
            </div>
            <p class="muted" style="font-size: 0.825rem; margin-bottom: 16px;">
                Includes receipt numbers, branch, cashier, gross sales, 16% VAT, discount amounts, tender methods, and completion statuses.
            </p>
        </div>
        <button type="button" class="btn btn-primary" onclick="triggerExport('sales')">
            <i class="bi bi-file-earmark-spreadsheet"></i> Export Sales CSV
        </button>
    </div>

    {{-- 2. Cashier Shifts & Balancing --}}
    <div class="card" style="padding: 20px; display: flex; flex-direction: column; justify-content: space-between;">
        <div>
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                <div style="width: 36px; height: 36px; border-radius: 8px; background: rgba(16, 185, 129, 0.1); color: #10b981; display: flex; align-items: center; justify-content: center; font-size: 1.2rem;">
                    <i class="bi bi-clock-history"></i>
                </div>
                <div>
                    <h3 style="font-size: 1.05rem; font-weight: 600; margin: 0;">Cashier Shifts &amp; Z-Reports</h3>
                    <span class="muted" style="font-size: 0.75rem;">Opening floats, cash intake, variances</span>
                </div>
            </div>
            <p class="muted" style="font-size: 0.825rem; margin-bottom: 16px;">
                Contains shift numbers, cashier names, opening cash, cash sales totals, expected cash, actual drawer counts, and closing variances.
            </p>
        </div>
        <button type="button" class="btn btn-primary" onclick="triggerExport('shifts')">
            <i class="bi bi-file-earmark-spreadsheet"></i> Export Shifts CSV
        </button>
    </div>

    {{-- 3. Inventory & Stock Valuation --}}
    <div class="card" style="padding: 20px; display: flex; flex-direction: column; justify-content: space-between;">
        <div>
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                <div style="width: 36px; height: 36px; border-radius: 8px; background: rgba(245, 158, 11, 0.1); color: #f59e0b; display: flex; align-items: center; justify-content: center; font-size: 1.2rem;">
                    <i class="bi bi-boxes"></i>
                </div>
                <div>
                    <h3 style="font-size: 1.05rem; font-weight: 600; margin: 0;">Inventory Stock Valuation</h3>
                    <span class="muted" style="font-size: 0.75rem;">Current stock levels and valuation</span>
                </div>
            </div>
            <p class="muted" style="font-size: 0.825rem; margin-bottom: 16px;">
                Contains product SKUs, current stock balances per shop, reorder thresholds, unit prices, total stock valuation, and stock status.
            </p>
        </div>
        <button type="button" class="btn btn-primary" onclick="triggerExport('inventory')">
            <i class="bi bi-file-earmark-spreadsheet"></i> Export Inventory CSV
        </button>
    </div>

    {{-- 4. Products & Master Catalog --}}
    <div class="card" style="padding: 20px; display: flex; flex-direction: column; justify-content: space-between;">
        <div>
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                <div style="width: 36px; height: 36px; border-radius: 8px; background: rgba(139, 92, 246, 0.1); color: #8b5cf6; display: flex; align-items: center; justify-content: center; font-size: 1.2rem;">
                    <i class="bi bi-tags"></i>
                </div>
                <div>
                    <h3 style="font-size: 1.05rem; font-weight: 600; margin: 0;">Master Product Catalog</h3>
                    <span class="muted" style="font-size: 0.75rem;">All active items, SKUs, and pricing</span>
                </div>
            </div>
            <p class="muted" style="font-size: 0.825rem; margin-bottom: 16px;">
                Exports entire product catalog including barcodes, categories, cost prices, selling prices, base units, and activation status.
            </p>
        </div>
        <button type="button" class="btn btn-primary" onclick="triggerExport('products')">
            <i class="bi bi-file-earmark-spreadsheet"></i> Export Products CSV
        </button>
    </div>

    {{-- 5. Parcels & Logistics --}}
    <div class="card" style="padding: 20px; display: flex; flex-direction: column; justify-content: space-between;">
        <div>
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                <div style="width: 36px; height: 36px; border-radius: 8px; background: rgba(236, 72, 153, 0.1); color: #ec4899; display: flex; align-items: center; justify-content: center; font-size: 1.2rem;">
                    <i class="bi bi-box-seam"></i>
                </div>
                <div>
                    <h3 style="font-size: 1.05rem; font-weight: 600; margin: 0;">Parcels &amp; Freight Waybills</h3>
                    <span class="muted" style="font-size: 0.75rem;">Courier tracking, charges &amp; routing</span>
                </div>
            </div>
            <p class="muted" style="font-size: 0.825rem; margin-bottom: 16px;">
                Waybill tracking numbers, origin/destination branches, sender & recipient contacts, package weights, service levels, fees, and statuses.
            </p>
        </div>
        <button type="button" class="btn btn-primary" onclick="triggerExport('parcels')">
            <i class="bi bi-file-earmark-spreadsheet"></i> Export Parcels CSV
        </button>
    </div>

    {{-- 6. Gym Memberships & Subscriptions --}}
    <div class="card" style="padding: 20px; display: flex; flex-direction: column; justify-content: space-between;">
        <div>
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                <div style="width: 36px; height: 36px; border-radius: 8px; background: rgba(14, 165, 233, 0.1); color: #0ea5e9; display: flex; align-items: center; justify-content: center; font-size: 1.2rem;">
                    <i class="bi bi-activity"></i>
                </div>
                <div>
                    <h3 style="font-size: 1.05rem; font-weight: 600; margin: 0;">Gym Members &amp; Plans</h3>
                    <span class="muted" style="font-size: 0.75rem;">Active subscriptions and session balances</span>
                </div>
            </div>
            <p class="muted" style="font-size: 0.825rem; margin-bottom: 16px;">
                Member profiles, phone numbers, subscribed plans, subscription validity dates, credit balances, and subscription statuses.
            </p>
        </div>
        <button type="button" class="btn btn-primary" onclick="triggerExport('gym')">
            <i class="bi bi-file-earmark-spreadsheet"></i> Export Gym CSV
        </button>
    </div>

    {{-- 7. Fleet Bookings & Car Hire --}}
    <div class="card" style="padding: 20px; display: flex; flex-direction: column; justify-content: space-between;">
        <div>
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                <div style="width: 36px; height: 36px; border-radius: 8px; background: rgba(99, 102, 241, 0.1); color: #6366f1; display: flex; align-items: center; justify-content: center; font-size: 1.2rem;">
                    <i class="bi bi-car-front"></i>
                </div>
                <div>
                    <h3 style="font-size: 1.05rem; font-weight: 600; margin: 0;">Fleet Bookings &amp; Hire</h3>
                    <span class="muted" style="font-size: 0.75rem;">Vehicle rentals, deposits, and returns</span>
                </div>
            </div>
            <p class="muted" style="font-size: 0.825rem; margin-bottom: 16px;">
                Vehicle registration plates, customer contacts, pickup/return dates, agreed rates, security deposits, billing totals, and statuses.
            </p>
        </div>
        <button type="button" class="btn btn-primary" onclick="triggerExport('fleet')">
            <i class="bi bi-file-earmark-spreadsheet"></i> Export Fleet CSV
        </button>
    </div>

    {{-- 8. Auto Garage Job Cards --}}
    <div class="card" style="padding: 20px; display: flex; flex-direction: column; justify-content: space-between;">
        <div>
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                <div style="width: 36px; height: 36px; border-radius: 8px; background: rgba(234, 88, 12, 0.1); color: #ea580c; display: flex; align-items: center; justify-content: center; font-size: 1.2rem;">
                    <i class="bi bi-tools"></i>
                </div>
                <div>
                    <h3 style="font-size: 1.05rem; font-weight: 600; margin: 0;">Garage Job Cards &amp; Repairs</h3>
                    <span class="muted" style="font-size: 0.75rem;">Vehicle repairs, parts &amp; labour billing</span>
                </div>
            </div>
            <p class="muted" style="font-size: 0.825rem; margin-bottom: 16px;">
                Job numbers, customer vehicle plates, mileage in, labour totals, parts consumption, grand totals, and job completion stages.
            </p>
        </div>
        <button type="button" class="btn btn-primary" onclick="triggerExport('garage')">
            <i class="bi bi-file-earmark-spreadsheet"></i> Export Garage CSV
        </button>
    </div>
</div>

{{-- Hidden Form for Submitting Export --}}
<form id="exportForm" method="POST" action="{{ route('tenant.analytics.export') }}" style="display: none;">
    @csrf
    <input type="hidden" name="export_type" id="hidden_export_type">
    <input type="hidden" name="from_date" id="hidden_from_date">
    <input type="hidden" name="to_date" id="hidden_to_date">
    <input type="hidden" name="shop_id" id="hidden_shop_id">
</form>

<script>
function triggerExport(type) {
    document.getElementById('hidden_export_type').value = type;
    document.getElementById('hidden_from_date').value = document.getElementById('filter_from_date').value;
    document.getElementById('hidden_to_date').value = document.getElementById('filter_to_date').value;
    document.getElementById('hidden_shop_id').value = document.getElementById('filter_shop_id').value;
    document.getElementById('exportForm').submit();
}
</script>
@endsection
