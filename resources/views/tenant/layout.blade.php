<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') · {{ $tenant->name ?? 'BlePOS' }}</title>
    <script nonce="{{ request()->attributes->get('csp_nonce') }}">
        (function(){try{var t=localStorage.getItem('blepos_theme');if(t==='light'||t==='dark'){document.documentElement.setAttribute('data-theme',t);}else if(window.matchMedia&&window.matchMedia('(prefers-color-scheme: dark)').matches){document.documentElement.setAttribute('data-theme','dark');}else{document.documentElement.setAttribute('data-theme','light');}}catch(e){}})();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
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
            @if(auth()->user()->hasPermission('pos.sale.create') || auth()->user()->hasPermission('pos.sales.view') || auth()->user()->hasPermission('shifts.view'))
            <span class="nav-section">Point of Sale</span>
            @if(auth()->user()->hasPermission('pos.sale.create'))
            <a href="{{ route('tenant.pos.checkout') }}"
               class="nav-link {{ request()->routeIs('tenant.pos.checkout') ? 'active' : '' }}"
               data-tooltip="POS Terminal"
               aria-label="POS Terminal">
                <i class="bi bi-display"></i>
                <span class="nav-label">POS Terminal</span>
            </a>
            @endif

            @if(($tenant->industry ?? null) === 'restaurant' && auth()->user()->hasPermission('pos.sale.create'))
            <a href="{{ route('tenant.tables.index') }}"
               class="nav-link {{ request()->routeIs('tenant.tables.*') ? 'active' : '' }}"
               data-tooltip="Floor Plan & Tables"
               aria-label="Floor Plan & Tables">
                <i class="bi bi-grid-3x3"></i>
                <span class="nav-label">Floor Plan & Tables</span>
            </a>
            @endif
            @if(auth()->user()->hasPermission('shifts.view'))
            <a href="{{ route('tenant.shifts.index') }}"
               class="nav-link {{ request()->routeIs('tenant.shifts.*') ? 'active' : '' }}"
               data-tooltip="Cashier Shifts"
               aria-label="Cashier Shifts">
                <i class="bi bi-clock-history"></i>
                <span class="nav-label">Cashier Shifts</span>
            </a>
            @endif
            @if(auth()->user()->hasPermission('pos.sales.view'))
            <a href="{{ route('tenant.pos.sales') }}"
               class="nav-link {{ request()->routeIs('tenant.pos.sales') ? 'active' : '' }}"
               data-tooltip="Sales History"
               aria-label="Sales History">
                <i class="bi bi-receipt"></i>
                <span class="nav-label">Sales History</span>
            </a>
            @endif
            @endif

            {{-- Support & Communication --}}
            <span class="nav-section">Support &amp; Communication</span>
            <a href="{{ route('tenant.support.cases.index') }}"
               class="nav-link {{ request()->routeIs('tenant.support.*') ? 'active' : '' }}"
               data-tooltip="Support Tickets" aria-label="Support Tickets">
                <i class="bi bi-life-preserver"></i><span class="nav-label">Support Tickets</span>
            </a>
            <a href="{{ route('tenant.announcements.index') }}"
               class="nav-link {{ request()->routeIs('tenant.announcements.*') ? 'active' : '' }}"
               data-tooltip="Platform Announcements" aria-label="Platform Announcements">
                <i class="bi bi-megaphone"></i><span class="nav-label">Announcements</span>
            </a>
            <a href="{{ route('tenant.legal.index') }}"
               class="nav-link {{ request()->routeIs('tenant.legal.*') ? 'active' : '' }}"
               data-tooltip="Legal & Compliance" aria-label="Legal & Compliance">
                <i class="bi bi-shield-check"></i><span class="nav-label">Legal &amp; Privacy</span>
            </a>

            {{-- Catalog --}}
            @if(auth()->user()->hasPermission('catalog.products.manage') || auth()->user()->hasPermission('catalog.categories.manage'))
            <span class="nav-section">Catalog</span>
            @if(auth()->user()->hasPermission('catalog.products.manage'))
            <a href="{{ route('tenant.products.index') }}"
               class="nav-link {{ request()->routeIs('tenant.products.*') ? 'active' : '' }}"
               data-tooltip="Products"
               aria-label="Products">
                <i class="bi bi-box-seam"></i>
                <span class="nav-label">Products</span>
            </a>
            @endif
            @if(auth()->user()->hasPermission('catalog.categories.manage'))
            <a href="{{ route('tenant.categories.index') }}"
               class="nav-link {{ request()->routeIs('tenant.categories.*') ? 'active' : '' }}"
               data-tooltip="Categories"
               aria-label="Categories">
                <i class="bi bi-tags"></i>
                <span class="nav-label">Categories</span>
            </a>
            @endif
            @endif

            {{-- Inventory --}}
            @if(auth()->user()->hasPermission('inventory.view'))
            <span class="nav-section">Inventory</span>
            <a href="{{ route('tenant.inventory.index') }}"
               class="nav-link {{ request()->routeIs('tenant.inventory.index') ? 'active' : '' }}"
               data-tooltip="Stock Levels"
               aria-label="Stock Levels">
                <i class="bi bi-layers"></i>
                <span class="nav-label">Stock Levels</span>
            </a>
            @if(auth()->user()->hasPermission('inventory.adjust'))
            <a href="{{ route('tenant.inventory.adjust') }}"
               class="nav-link {{ request()->routeIs('tenant.inventory.adjust') ? 'active' : '' }}"
               data-tooltip="Adjust Stock"
               aria-label="Adjust Stock">
                <i class="bi bi-pencil-square"></i>
                <span class="nav-label">Adjust Stock</span>
            </a>
            @endif
            @if(($tenant->industry ?? null) === 'pharmacy' && auth()->user()->hasPermission('inventory.view'))
            <a href="{{ route('tenant.pharmacy.index') }}" class="nav-link {{ request()->routeIs('tenant.pharmacy.*') ? 'active' : '' }}"><span>Pharmacy operations</span></a>
            @endif
            @if(auth()->user()->hasPermission('inventory.receive'))
            <a href="{{ route('tenant.inventory.receive') }}"
               class="nav-link {{ request()->routeIs('tenant.inventory.receive') ? 'active' : '' }}"
               data-tooltip="Receive Stock"
               aria-label="Receive Stock">
                <i class="bi bi-box-arrow-in-down"></i>
                <span class="nav-label">Receive Stock</span>
            </a>
            @endif
            <a href="{{ route('tenant.inventory.movements') }}"
               class="nav-link {{ request()->routeIs('tenant.inventory.movements') ? 'active' : '' }}"
               data-tooltip="Audit Movements"
               aria-label="Audit Movements">
                <i class="bi bi-arrow-left-right"></i>
                <span class="nav-label">Audit Movements</span>
            </a>
            @endif

            {{-- Finance --}}
            @if(auth()->user()->hasPermission('accounting.view'))
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
            @endif

            {{-- Team & Staff Management --}}
            @if(auth()->user()->hasPermission('users.manage'))
            <span class="nav-section">Administration</span>
            <a href="{{ route('tenant.users.index') }}"
               class="nav-link {{ request()->routeIs('tenant.users.*') ? 'active' : '' }}"
               data-tooltip="Staff Members"
               aria-label="Staff Members">
                <i class="bi bi-people"></i>
                <span class="nav-label">Staff & Team</span>
            </a>
            <a href="{{ route('tenant.activity.index') }}"
               class="nav-link {{ request()->routeIs('tenant.activity.*') ? 'active' : '' }}"
               data-tooltip="Employee Activity"
               aria-label="Employee Activity">
                <i class="bi bi-clipboard-data"></i>
                <span class="nav-label">Employee Activity</span>
            </a>
            <a href="{{ route('tenant.mpesa.index') }}"
               class="nav-link {{ request()->routeIs('tenant.mpesa.*') ? 'active' : '' }}"
               data-tooltip="M-Pesa Vault"
               aria-label="M-Pesa Vault">
                <i class="bi bi-phone"></i>
                <span class="nav-label">M-Pesa Settings</span>
            </a>
            <a href="{{ route('tenant.subscription.index') }}"
               class="nav-link {{ request()->routeIs('tenant.subscription.*') ? 'active' : '' }}"
               data-tooltip="Subscription & Billing"
               aria-label="Subscription & Billing">
                <i class="bi bi-credit-card-2-front"></i>
                <span class="nav-label">Subscription &amp; Billing</span>
            </a>
            @endif

            {{-- Industry Vertical Modules --}}
            @php
                $currentTenant = $tenant ?? \App\Services\TenantContext::getTenant();
                $moduleService = app(\App\Services\ModuleService::class);
            @endphp

            @if($currentTenant && ($moduleService->isModuleEnabled($currentTenant, 'gym') || ($currentTenant->industry ?? '') === 'gym-fitness'))
            <span class="nav-section">Gym &amp; Fitness</span>
            <a href="{{ route('tenant.gym.index') }}"
               class="nav-link {{ request()->routeIs('tenant.gym.*') ? 'active' : '' }}"
               data-tooltip="Gym Management" aria-label="Gym Management">
                <i class="bi bi-heart-pulse"></i>
                <span class="nav-label">Gym Management</span>
            </a>
            @endif

            @if($currentTenant && ($moduleService->isModuleEnabled($currentTenant, 'printing') || ($currentTenant->industry ?? '') === 'printing-branding'))
            <span class="nav-section">Printing &amp; Branding</span>
            <a href="{{ route('tenant.printing.index') }}"
               class="nav-link {{ request()->routeIs('tenant.printing.*') ? 'active' : '' }}"
               data-tooltip="Printing Management" aria-label="Printing Management">
                <i class="bi bi-printer"></i>
                <span class="nav-label">Print &amp; Branding</span>
            </a>
            @endif

            @if($currentTenant && ($moduleService->isModuleEnabled($currentTenant, 'agrovet') || ($currentTenant->industry ?? '') === 'agrovet'))
            <span class="nav-section">Agrovet</span>
            <a href="{{ route('tenant.agrovet.index') }}"
               class="nav-link {{ request()->routeIs('tenant.agrovet.*') ? 'active' : '' }}"
               data-tooltip="Agrovet Management" aria-label="Agrovet Management">
                <i class="bi bi-flower1"></i>
                <span class="nav-label">Agrovet Operations</span>
            </a>
            @endif

            @if($currentTenant && ($moduleService->isModuleEnabled($currentTenant, 'garage') || ($currentTenant->industry ?? '') === 'auto-garage'))
            <span class="nav-section">Auto Garage</span>
            <a href="{{ route('tenant.garage.index') }}"
               class="nav-link {{ request()->routeIs('tenant.garage.*') ? 'active' : '' }}"
               data-tooltip="Auto Garage" aria-label="Auto Garage">
                <i class="bi bi-tools"></i>
                <span class="nav-label">Auto Garage</span>
            </a>
            @endif

            @if($currentTenant && ($moduleService->isModuleEnabled($currentTenant, 'fleet') || ($currentTenant->industry ?? '') === 'car-hire-fleet'))
            <span class="nav-section">Fleet &amp; Car Hire</span>
            <a href="{{ route('tenant.fleet.index') }}"
               class="nav-link {{ request()->routeIs('tenant.fleet.*') ? 'active' : '' }}"
               data-tooltip="Fleet & Car Hire" aria-label="Fleet & Car Hire">
                <i class="bi bi-car-front"></i>
                <span class="nav-label">Fleet &amp; Car Hire</span>
            </a>
            @endif

            @if($currentTenant && auth()->user()->hasPermission('parcel.view') && ($moduleService->isModuleEnabled($currentTenant, 'parcel-delivery') || ($currentTenant->industry ?? '') === 'parcel-delivery'))
            <span class="nav-section">Parcel Delivery</span>
            <a href="{{ route('tenant.parcels.index') }}"
               class="nav-link {{ request()->routeIs('tenant.parcels.*') ? 'active' : '' }}"
               data-tooltip="Parcel Desk" aria-label="Parcel Desk">
                <i class="bi bi-box-seam"></i>
                <span class="nav-label">Parcel Desk</span>
            </a>
            @endif

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

                <button type="button" id="theme-toggle" class="top-bar-btn" aria-label="Toggle light/dark mode" data-tooltip="Switch to dark mode">
                    <i class="bi bi-sun" id="theme-toggle-icon" style="font-size:17px;"></i>
                </button>

                <a href="{{ route('tenant.notifications.index') }}" class="top-bar-btn" aria-label="Notifications" data-tooltip="Notifications" style="position:relative">
                    <i class="bi bi-bell" style="font-size:17px;"></i>
                    @if(auth()->user()->unreadNotifications()->count())<span class="notif-dot" aria-label="Unread notifications"></span>@endif
                </a>

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
            if (window.matchMedia('(max-width: 767px)').matches) {
                const open = sidebar.classList.toggle('mobile-open');
                if (toggle) toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
                if (hamburger) hamburger.setAttribute('aria-expanded', open ? 'true' : 'false');
                return;
            }
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
            el.classList.add('motion-closing');
            setTimeout(function () { el.remove(); }, window.BleMotion.duration('--dur-base'));
        }, window.BleMotion.duration('--delay-toast-dismiss'));
    });
})();
</script>
</body>
</html>
