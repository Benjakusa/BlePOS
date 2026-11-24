<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') · {{ $tenant->name ?? 'BlePOS' }}</title>
    <style>
        :root {
            color-scheme: light;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            color: #17231f;
            background: #f4f6f5;
        }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; }
        a { color: #15803d; text-decoration: none; }
        a:hover { text-decoration: underline; }
        .shell { min-height: 100vh; display: grid; grid-template-columns: 240px 1fr; }
        aside { background: #132a22; color: #eaf4ef; padding: 24px 18px; display: flex; flex-direction: column; justify-content: space-between; }
        .brand { font-weight: 750; font-size: 20px; letter-spacing: -.03em; margin-bottom: 24px; color: #fff; }
        .brand small { display: block; color: #86efac; font-size: 12px; font-weight: 500; margin-top: 4px; letter-spacing: 0; }
        nav { display: grid; gap: 4px; }
        nav a { display: block; color: #cbd5e1; padding: 9px 12px; text-decoration: none; border-radius: 6px; font-size: 14px; font-weight: 500; }
        nav a.active, nav a:hover { background: #1b4332; color: #fff; }
        nav .section-header { font-size: 11px; text-transform: uppercase; color: #6ee7b7; font-weight: 700; letter-spacing: .05em; padding: 12px 12px 4px; }
        main { min-width: 0; padding: 28px clamp(16px, 3vw, 40px); }
        header { display: flex; justify-content: space-between; align-items: center; gap: 20px; margin-bottom: 24px; }
        h1 { font-size: 24px; margin: 0; letter-spacing: -.025em; font-weight: 700; }
        .muted { color: #64748b; font-size: 14px; }
        .panel { background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); }
        .grid-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 24px; }
        .stat-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 18px; }
        .stat-card .label { font-size: 13px; color: #64748b; font-weight: 600; text-transform: uppercase; letter-spacing: .03em; }
        .stat-card .value { font-size: 28px; font-weight: 750; color: #0f172a; margin-top: 6px; }
        .form { display: grid; gap: 14px; max-width: 520px; }
        label { display: grid; gap: 6px; font-weight: 600; font-size: 14px; }
        input, select, textarea { border: 1px solid #cbd5e1; border-radius: 6px; padding: 9px 12px; font: inherit; font-size: 14px; width: 100%; background: #fff; }
        input:focus, select:focus, textarea:focus { outline: 2px solid #15803d; outline-offset: 1px; border-color: transparent; }
        button, .btn { display: inline-flex; align-items: center; justify-content: center; background: #15803d; color: #fff; border: 0; border-radius: 6px; padding: 9px 16px; font: inherit; font-size: 14px; font-weight: 600; cursor: pointer; text-decoration: none; }
        button:hover, .btn:hover { background: #166534; text-decoration: none; }
        .btn-secondary { background: #e2e8f0; color: #334155; }
        .btn-secondary:hover { background: #cbd5e1; color: #0f172a; }
        .btn-danger { background: #dc2626; color: #fff; }
        .btn-danger:hover { background: #b91c1c; }
        .btn-sm { padding: 5px 10px; font-size: 12px; border-radius: 4px; }
        .alert { padding: 12px 16px; border-radius: 6px; margin-bottom: 20px; font-size: 14px; }
        .alert-success { background: #dcfce7; border: 1px solid #bbf7d0; color: #166534; }
        .alert-danger { background: #fee2e2; border: 1px solid #fecaca; color: #991b1b; }
        .alert-warning { background: #fef3c7; border: 1px solid #fde68a; color: #92400e; }
        .alert-info { background: #e0f2fe; border: 1px solid #bae6fd; color: #075985; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; text-align: left; }
        th { background: #f8fafc; padding: 10px 14px; border-bottom: 2px solid #e2e8f0; font-weight: 650; color: #475569; }
        td { padding: 12px 14px; border-bottom: 1px solid #e2e8f0; }
        tr:hover td { background: #f8fafc; }
        .badge { display: inline-block; padding: 3px 8px; font-size: 11px; font-weight: 700; border-radius: 9999px; text-transform: uppercase; letter-spacing: .04em; }
        .badge-success { background: #dcfce7; color: #166534; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-secondary { background: #e2e8f0; color: #475569; }
        .badge-info { background: #e0f2fe; color: #0369a1; }
        .actions { display: flex; gap: 8px; align-items: center; }
        .table-container { overflow-x: auto; background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; }
        .user-panel { font-size: 13px; color: #94a3b8; border-top: 1px solid #23493c; padding-top: 14px; margin-top: 20px; }
        .user-panel strong { color: #f1f5f9; display: block; }
        @media (max-width: 800px) {
            .shell { grid-template-columns: 1fr; }
            aside { padding: 16px; }
            nav { grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); }
        }
    </style>
    @stack('styles')
</head>
<body>
<div class="shell">
    <aside>
        <div>
            <div class="brand">
                BlePOS
                <small>{{ $tenant->name ?? 'Tenant Workspace' }}</small>
                @if(\App\Services\ShopContext::hasShop())
                    <span style="display: inline-block; font-size: 11px; background: #1b4332; color: #86efac; padding: 2px 8px; border-radius: 4px; margin-top: 6px; font-weight: normal;">
                        Branch: {{ \App\Services\ShopContext::getShop()->name }}
                    </span>
                @endif
            </div>
            <nav aria-label="Tenant navigation">
                <a href="{{ route('tenant.dashboard') }}" class="{{ request()->routeIs('tenant.dashboard') ? 'active' : '' }}">Dashboard</a>
                
                <div class="section-header">Point of Sale</div>
                <a href="{{ route('tenant.pos.checkout') }}" class="{{ request()->routeIs('tenant.pos.checkout') ? 'active' : '' }}">POS Terminal</a>
                <a href="{{ route('tenant.shifts.index') }}" class="{{ request()->routeIs('tenant.shifts.*') ? 'active' : '' }}">Cashier Shifts</a>
                <a href="{{ route('tenant.pos.sales') }}" class="{{ request()->routeIs('tenant.pos.sales') ? 'active' : '' }}">Sales History</a>

                <div class="section-header">Catalog</div>
                <a href="{{ route('tenant.products.index') }}" class="{{ request()->routeIs('tenant.products.*') ? 'active' : '' }}">Products</a>
                <a href="{{ route('tenant.categories.index') }}" class="{{ request()->routeIs('tenant.categories.*') ? 'active' : '' }}">Categories</a>

                <div class="section-header">Inventory</div>
                <a href="{{ route('tenant.inventory.index') }}" class="{{ request()->routeIs('tenant.inventory.index') ? 'active' : '' }}">Stock Levels</a>
                <a href="{{ route('tenant.inventory.adjust') }}" class="{{ request()->routeIs('tenant.inventory.adjust') ? 'active' : '' }}">Adjust Stock</a>
                <a href="{{ route('tenant.inventory.receive') }}" class="{{ request()->routeIs('tenant.inventory.receive') ? 'active' : '' }}">Receive Stock</a>
                <a href="{{ route('tenant.inventory.movements') }}" class="{{ request()->routeIs('tenant.inventory.movements') ? 'active' : '' }}">Audit Movements</a>

                <div class="section-header">Finance & Ledgers</div>
                <a href="{{ route('tenant.accounting.accounts.index') }}" class="{{ request()->routeIs('tenant.accounting.accounts.*') ? 'active' : '' }}">Chart of Accounts</a>
                <a href="{{ route('tenant.accounting.journals.index') }}" class="{{ request()->routeIs('tenant.accounting.journals.*') ? 'active' : '' }}">Journal Entries</a>
                <a href="{{ route('tenant.accounting.expenses.index') }}" class="{{ request()->routeIs('tenant.accounting.expenses.*') ? 'active' : '' }}">Expenses</a>
                <a href="{{ route('tenant.accounting.reports.trial-balance') }}" class="{{ request()->routeIs('tenant.accounting.reports.trial-balance') ? 'active' : '' }}">Trial Balance</a>
                <a href="{{ route('tenant.accounting.reports.profit-loss') }}" class="{{ request()->routeIs('tenant.accounting.reports.profit-loss') ? 'active' : '' }}">Profit & Loss</a>
                <a href="{{ route('tenant.accounting.reports.balance-sheet') }}" class="{{ request()->routeIs('tenant.accounting.reports.balance-sheet') ? 'active' : '' }}">Balance Sheet</a>
                <a href="{{ route('tenant.accounting.reports.general-ledger') }}" class="{{ request()->routeIs('tenant.accounting.reports.general-ledger') ? 'active' : '' }}">General Ledger</a>
            </nav>
        </div>
        <div class="user-panel">
            <strong>{{ auth()->user()->name ?? 'Operator' }}</strong>
            <span>{{ auth()->user()->email ?? '' }}</span>
        </div>
    </aside>
    <main>
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        @if(session('warning'))
            <div class="alert alert-warning">{{ session('warning') }}</div>
        @endif
        @if(session('info'))
            <div class="alert alert-info">{{ session('info') }}</div>
        @endif

        @yield('content')
    </main>
</div>
@stack('scripts')
</body>
</html>
