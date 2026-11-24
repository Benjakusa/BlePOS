<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') · {{ $tenant->name ?? 'BlePOS' }}</title>
    @vite(['resources/css/app.css'])
    @stack('styles')
</head>
<body>

<div class="app-shell" id="app">

    {{-- ── Tenant Sidebar ──────────────────────────────────────── --}}
    @auth
    <aside class="sidebar" id="sidebar" aria-label="Tenant navigation">

        {{-- Brand --}}
        <div class="sidebar-brand">
            <div class="sidebar-brand-content">
                <div class="sidebar-brand-logo" aria-hidden="true">
                    <i class="bi bi-bluetooth"></i>
                </div>
                <div class="sidebar-brand-text">
                    <strong>{{ $tenant->name ?? 'BlePOS' }}</strong>
                    @if(\App\Services\ShopContext::hasShop())
                        <span>{{ \App\Services\ShopContext::getShop()->name }}</span>
                    @else
                        <span>Tenant workspace</span>
                    @endif
                </div>
            </div>
            <button type="button" class="sidebar-hamburger-btn" id="sidebar-hamburger" aria-label="Toggle sidebar" title="Toggle sidebar">
                <i class="bi bi-list"></i>
            </button>
        </div>

        {{-- Nav --}}
        <nav class="sidebar-nav">

            {{-- Dashboard --}}
            <a href="{{ route('tenant.dashboard') }}"
               class="nav-link {{ request()->routeIs('tenant.dashboard') ? 'active' : '' }}"
               data-tooltip="Dashboard"
               aria-label="Dashboard">
                <i class="bi bi-speedometer2"></i>
                <span class="nav-label">Dashboard</span>
            </a>

            {{-- POS --}}
            <span class="nav-section">Point of Sale</span>
            <a href="{{ route('tenant.pos.checkout') }}"
               class="nav-link {{ request()->routeIs('tenant.pos.checkout') ? 'active' : '' }}"
               data-tooltip="POS Terminal"
               aria-label="POS Terminal">
                <i class="bi bi-display"></i>
                <span class="nav-label">POS Terminal</span>
            </a>
            <a href="{{ route('tenant.shifts.index') }}"
               class="nav-link {{ request()->routeIs('tenant.shifts.*') ? 'active' : '' }}"
               data-tooltip="Cashier Shifts"
               aria-label="Cashier Shifts">
                <i class="bi bi-clock-history"></i>
                <span class="nav-label">Cashier Shifts</span>
            </a>
            <a href="{{ route('tenant.pos.sales') }}"
               class="nav-link {{ request()->routeIs('tenant.pos.sales') ? 'active' : '' }}"
               data-tooltip="Sales History"
               aria-label="Sales History">
                <i class="bi bi-receipt"></i>
                <span class="nav-label">Sales History</span>
            </a>

            {{-- Catalog --}}
            <span class="nav-section">Catalog</span>
            <a href="{{ route('tenant.products.index') }}"
               class="nav-link {{ request()->routeIs('tenant.products.*') ? 'active' : '' }}"
               data-tooltip="Products"
               aria-label="Products">
                <i class="bi bi-box-seam"></i>
                <span class="nav-label">Products</span>
            </a>
            <a href="{{ route('tenant.categories.index') }}"
               class="nav-link {{ request()->routeIs('tenant.categories.*') ? 'active' : '' }}"
               data-tooltip="Categories"
               aria-label="Categories">
                <i class="bi bi-tags"></i>
                <span class="nav-label">Categories</span>
            </a>

            {{-- Inventory --}}
            <span class="nav-section">Inventory</span>
            <a href="{{ route('tenant.inventory.index') }}"
               class="nav-link {{ request()->routeIs('tenant.inventory.index') ? 'active' : '' }}"
               data-tooltip="Stock Levels"
               aria-label="Stock Levels">
                <i class="bi bi-layers"></i>
                <span class="nav-label">Stock Levels</span>
            </a>
            <a href="{{ route('tenant.inventory.adjust') }}"
               class="nav-link {{ request()->routeIs('tenant.inventory.adjust') ? 'active' : '' }}"
               data-tooltip="Adjust Stock"
               aria-label="Adjust Stock">
                <i class="bi bi-pencil-square"></i>
                <span class="nav-label">Adjust Stock</span>
            </a>
            <a href="{{ route('tenant.inventory.receive') }}"
               class="nav-link {{ request()->routeIs('tenant.inventory.receive') ? 'active' : '' }}"
               data-tooltip="Receive Stock"
               aria-label="Receive Stock">
                <i class="bi bi-box-arrow-in-down"></i>
                <span class="nav-label">Receive Stock</span>
            </a>
            <a href="{{ route('tenant.inventory.movements') }}"
               class="nav-link {{ request()->routeIs('tenant.inventory.movements') ? 'active' : '' }}"
               data-tooltip="Audit Movements"
               aria-label="Audit Movements">
                <i class="bi bi-arrow-left-right"></i>
                <span class="nav-label">Audit Movements</span>
            </a>

            {{-- Finance --}}
            <span class="nav-section">Finance & Ledgers</span>
            <a href="{{ route('tenant.accounting.accounts.index') }}"
               class="nav-link {{ request()->routeIs('tenant.accounting.accounts.*') ? 'active' : '' }}"
               data-tooltip="Chart of Accounts"
               aria-label="Chart of Accounts">
                <i class="bi bi-journal-bookmark"></i>
                <span class="nav-label">Chart of Accounts</span>
            </a>
            <a href="{{ route('tenant.accounting.journals.index') }}"
               class="nav-link {{ request()->routeIs('tenant.accounting.journals.*') ? 'active' : '' }}"
               data-tooltip="Journal Entries"
               aria-label="Journal Entries">
                <i class="bi bi-journal-text"></i>
                <span class="nav-label">Journal Entries</span>
            </a>
            <a href="{{ route('tenant.accounting.expenses.index') }}"
               class="nav-link {{ request()->routeIs('tenant.accounting.expenses.*') ? 'active' : '' }}"
               data-tooltip="Expenses"
               aria-label="Expenses">
                <i class="bi bi-credit-card"></i>
                <span class="nav-label">Expenses</span>
            </a>
            <a href="{{ route('tenant.accounting.reports.trial-balance') }}"
               class="nav-link {{ request()->routeIs('tenant.accounting.reports.trial-balance') ? 'active' : '' }}"
               data-tooltip="Trial Balance"
               aria-label="Trial Balance">
                <i class="bi bi-bar-chart-line"></i>
                <span class="nav-label">Trial Balance</span>
            </a>
            <a href="{{ route('tenant.accounting.reports.profit-loss') }}"
               class="nav-link {{ request()->routeIs('tenant.accounting.reports.profit-loss') ? 'active' : '' }}"
               data-tooltip="Profit & Loss"
               aria-label="Profit & Loss">
                <i class="bi bi-graph-up-arrow"></i>
                <span class="nav-label">Profit & Loss</span>
            </a>
            <a href="{{ route('tenant.accounting.reports.balance-sheet') }}"
               class="nav-link {{ request()->routeIs('tenant.accounting.reports.balance-sheet') ? 'active' : '' }}"
               data-tooltip="Balance Sheet"
               aria-label="Balance Sheet">
                <i class="bi bi-clipboard-data"></i>
                <span class="nav-label">Balance Sheet</span>
            </a>
            <a href="{{ route('tenant.accounting.reports.general-ledger') }}"
               class="nav-link {{ request()->routeIs('tenant.accounting.reports.general-ledger') ? 'active' : '' }}"
               data-tooltip="General Ledger"
               aria-label="General Ledger">
                <i class="bi bi-list-columns"></i>
                <span class="nav-label">General Ledger</span>
            </a>

        </nav>

        {{-- User footer --}}
        <div class="sidebar-footer">
            <div class="sidebar-user">
                <div class="sidebar-avatar" aria-hidden="true">
                    {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                </div>
                <div>
                    <div class="user-name">{{ auth()->user()->name ?? 'Operator' }}</div>
                    <div class="user-email">{{ auth()->user()->email ?? '' }}</div>
                </div>
            </div>
        </div>

    </aside>
    @endauth

    {{-- ── Main Area ───────────────────────────────────────────── --}}
    <div class="main-content">

        {{-- Top Bar --}}
        @auth
        <div class="top-bar" role="banner">
            <button class="top-bar-toggle" id="sidebar-toggle"
                    aria-label="Toggle sidebar" aria-expanded="true" aria-controls="sidebar">
                <i class="bi bi-list" style="font-size:20px;"></i>
            </button>

            <span class="top-bar-title">{{ $tenant->name ?? 'BlePOS' }}</span>

            <div class="top-bar-actions">
                <a href="{{ route('tenant.pos.checkout') }}"
                   class="btn btn-sm"
                   aria-label="Open POS Terminal"
                   style="text-decoration:none;">
                    <i class="bi bi-cart3"></i>
                    <span class="nav-label">New Sale</span>
                </a>

                <button class="top-bar-btn" aria-label="Notifications" data-tooltip="Notifications">
                    <i class="bi bi-bell" style="font-size:17px;"></i>
                </button>

                @if(Route::has('tenant.logout'))
                <form method="post" action="{{ route('tenant.logout') }}" style="margin:0;">
                    @csrf
                    <button type="submit" class="btn-secondary btn btn-sm" aria-label="Sign out">
                        <i class="bi bi-box-arrow-right"></i>
                        <span class="nav-label">Sign out</span>
                    </button>
                </form>
                @endif
            </div>
        </div>
        @endauth

        {{-- Page body --}}
        <main class="page-body" id="main-content" tabindex="-1">

            {{-- Flash messages --}}
            @if(session('success'))
                <div class="alert alert-success" role="alert">
                    <i class="bi bi-check-circle-fill"></i>
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger" role="alert">
                    <i class="bi bi-x-circle-fill"></i>
                    {{ session('error') }}
                </div>
            @endif
            @if(session('warning'))
                <div class="alert alert-warning" role="alert">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    {{ session('warning') }}
                </div>
            @endif
            @if(session('info'))
                <div class="alert alert-info" role="alert">
                    <i class="bi bi-info-circle-fill"></i>
                    {{ session('info') }}
                </div>
            @endif
            @if($errors->any())
                <div class="alert alert-danger" role="alert">
                    <i class="bi bi-exclamation-circle-fill"></i>
                    <div>
                        <strong>Please fix the following errors:</strong>
                        <ul style="margin:6px 0 0; padding-left:16px;">
                            @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                        </ul>
                    </div>
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</div>

@stack('scripts')
<script nonce="{{ request()->attributes->get('csp_nonce') }}">
(function () {
    'use strict';
    // ── Sidebar toggle ─────────────────────────────────────────
    const sidebar   = document.getElementById('sidebar');
    const toggle    = document.getElementById('sidebar-toggle');
    const hamburger = document.getElementById('sidebar-hamburger');
    const key       = 'ble_tenant_sidebar_collapsed';

    function setSidebarCollapsed(collapsed) {
        if (!sidebar) return;
        if (collapsed) {
            sidebar.classList.add('collapsed');
            if (toggle) toggle.setAttribute('aria-expanded', 'false');
            if (hamburger) hamburger.setAttribute('aria-expanded', 'false');
        } else {
            sidebar.classList.remove('collapsed');
            if (toggle) toggle.setAttribute('aria-expanded', 'true');
            if (hamburger) hamburger.setAttribute('aria-expanded', 'true');
        }
        localStorage.setItem(key, collapsed ? '1' : '0');
    }

    if (sidebar) {
        if (localStorage.getItem(key) === '1') {
            setSidebarCollapsed(true);
        }
        function toggleSidebar() {
            setSidebarCollapsed(!sidebar.classList.contains('collapsed'));
        }
        if (toggle) toggle.addEventListener('click', toggleSidebar);
        if (hamburger) hamburger.addEventListener('click', toggleSidebar);
    }

    // ── Table horizontal scroll / slider auto-wrapper ──────────
    document.querySelectorAll('table').forEach(function (table) {
        const parent = table.parentElement;
        if (!parent.classList.contains('table-container') &&
            !parent.classList.contains('table-wrap') &&
            !parent.classList.contains('table-scroll') &&
            !parent.classList.contains('table-scroll-slider') &&
            !parent.classList.contains('overflow-x-auto')) {
            const wrapper = document.createElement('div');
            wrapper.className = 'table-container table-scroll-slider';
            parent.insertBefore(wrapper, table);
            wrapper.appendChild(table);
        } else {
            parent.classList.add('table-scroll-slider');
        }
    });
    document.querySelectorAll('.alert').forEach(function (el) {
        setTimeout(function () {
            el.style.transition = 'opacity 400ms ease, transform 400ms ease';
            el.style.opacity = '0'; el.style.transform = 'translateY(-8px)';
            setTimeout(function () { el.remove(); }, 420);
        }, 6000);
    });
    document.querySelectorAll('[data-count-up]').forEach(function (el) {
        const target = parseFloat(el.getAttribute('data-count-up'));
        const dur = 1200; const start = performance.now();
        const isInt = Number.isInteger(target);
        (function step(now) {
            const p = Math.min((now - start) / dur, 1);
            const eased = 1 - Math.pow(1 - p, 3);
            el.textContent = isInt ? Math.round(target * eased).toLocaleString() : (target * eased).toFixed(2);
            if (p < 1) requestAnimationFrame(step);
        })(start);
    });
})();
</script>
</body>
</html>
