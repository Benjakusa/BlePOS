<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Platform Console') · BlePOS</title>
    {{-- Global design system (includes bundled Bootstrap Icons) --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body>

{{-- ── App Shell ───────────────────────────────────────────────── --}}
<div class="app-shell" id="app">

    {{-- ── Sidebar ─────────────────────────────────────────────── --}}
    @auth('platform')
    <aside class="sidebar" id="sidebar" aria-label="Platform navigation">

        {{-- Brand --}}
        <div class="sidebar-brand">
            <div class="sidebar-brand-content">
                <div class="sidebar-brand-logo" aria-hidden="true">
                    <i class="bi bi-broadcast-pin"></i>
                </div>
                <div class="sidebar-brand-text">
                    <strong>BlePOS</strong>
                    <span>Platform Console</span>
                </div>
            </div>
            <button type="button" class="sidebar-hamburger-btn" id="sidebar-hamburger" aria-label="Toggle sidebar" title="Toggle sidebar">
                <i class="bi bi-list"></i>
            </button>
        </div>

        {{-- Nav --}}
        <nav class="sidebar-nav" aria-label="Platform navigation">

            @auth('platform')

            {{-- Overview --}}
            <span class="nav-section">Overview</span>
            @if(auth('platform')->user()->hasPermission('platform.dashboard.view'))
                <a href="{{ route('platform.dashboard') }}"
                   class="nav-link {{ request()->routeIs('platform.dashboard') ? 'active' : '' }}"
                   aria-label="Dashboard">
                    <i class="bi bi-speedometer2"></i>
                    <span class="nav-label">Dashboard</span>
                </a>
                <a href="{{ route('platform.analytics.index') }}"
                   class="nav-link {{ request()->routeIs('platform.analytics.*') ? 'active' : '' }}"
                   aria-label="Analytics">
                    <i class="bi bi-bar-chart-line"></i>
                    <span class="nav-label">Analytics</span>
                </a>
            @endif

            {{-- Tenants --}}
            <span class="nav-section">Tenants</span>
            @if(auth('platform')->user()->hasPermission('platform.tenants.view'))
                <a href="{{ route('platform.tenants.index') }}"
                   class="nav-link {{ request()->routeIs('platform.tenants.*') ? 'active' : '' }}"
                   aria-label="Tenants">
                    <i class="bi bi-buildings"></i>
                    <span class="nav-label">Tenants</span>
                </a>
            @endif
            @if(auth('platform')->user()->hasPermission('platform.provisioning.view'))
                <a href="{{ route('platform.provisioning.index') }}"
                   class="nav-link {{ request()->routeIs('platform.provisioning.*') ? 'active' : '' }}"
                   aria-label="Provisioning">
                    <i class="bi bi-gear-wide-connected"></i>
                    <span class="nav-label">Provisioning</span>
                </a>
            @endif
            @if(auth('platform')->user()->hasPermission('platform.infrastructure.view'))
                <a href="{{ route('platform.infrastructure.index') }}"
                   class="nav-link {{ request()->routeIs('platform.infrastructure.*') ? 'active' : '' }}"
                   aria-label="Infrastructure">
                    <i class="bi bi-server"></i>
                    <span class="nav-label">Infrastructure</span>
                </a>
            @endif

            {{-- Billing --}}
            @if(auth('platform')->user()->hasPermission('platform.billing.view'))
                <span class="nav-section">Billing</span>
                <a href="{{ route('platform.plans.index') }}"
                   class="nav-link {{ request()->routeIs('platform.plans.*') ? 'active' : '' }}"
                   aria-label="Plans">
                    <i class="bi bi-credit-card"></i>
                    <span class="nav-label">Plans</span>
                </a>
                <a href="{{ route('platform.invoices.index') }}"
                   class="nav-link {{ request()->routeIs('platform.invoices.*') ? 'active' : '' }}"
                   aria-label="Invoices">
                    <i class="bi bi-receipt"></i>
                    <span class="nav-label">Invoices</span>
                </a>
                <a href="{{ route('platform.dunning.index') }}"
                   class="nav-link {{ request()->routeIs('platform.dunning.*') ? 'active' : '' }}"
                   aria-label="Dunning">
                    <i class="bi bi-exclamation-circle"></i>
                    <span class="nav-label">Dunning</span>
                </a>
            @endif

            {{-- Modules --}}
            @if(auth('platform')->user()->hasPermission('platform.modules.view'))
                <span class="nav-section">Modules</span>
                <a href="{{ route('platform.modules.index') }}"
                   class="nav-link {{ request()->routeIs('platform.modules.*') ? 'active' : '' }}"
                   aria-label="Module Catalogue">
                    <i class="bi bi-box-seam"></i>
                    <span class="nav-label">Module Catalogue</span>
                </a>
                <a href="{{ route('platform.templates.index') }}"
                   class="nav-link {{ request()->routeIs('platform.templates.*') ? 'active' : '' }}"
                   aria-label="Industry Templates">
                    <i class="bi bi-layout-text-window-reverse"></i>
                    <span class="nav-label">Industry Templates</span>
                </a>
            @endif

            {{-- Security --}}
            <span class="nav-section">Security</span>
            @if(auth('platform')->user()->hasPermission('platform.security.users.view'))
                <a href="{{ route('platform.users.index') }}"
                   class="nav-link {{ request()->routeIs('platform.users.*') ? 'active' : '' }}"
                   aria-label="Platform Users">
                    <i class="bi bi-people"></i>
                    <span class="nav-label">Platform Users</span>
                </a>
            @endif
            @if(auth('platform')->user()->hasPermission('platform.security.roles.manage'))
                <a href="{{ route('platform.roles.index') }}"
                   class="nav-link {{ request()->routeIs('platform.roles.*') ? 'active' : '' }}"
                   aria-label="Roles">
                    <i class="bi bi-shield-lock"></i>
                    <span class="nav-label">Roles & Perms</span>
                </a>
            @endif
            @if(auth('platform')->user()->hasPermission('platform.security.approvals.review'))
                <a href="{{ route('platform.approvals.index') }}"
                   class="nav-link {{ request()->routeIs('platform.approvals.*') ? 'active' : '' }}"
                   aria-label="Approvals">
                    <i class="bi bi-check2-square"></i>
                    <span class="nav-label">Approvals</span>
                </a>
            @endif
            @if(auth('platform')->user()->hasPermission('platform.security.sessions.revoke'))
                <a href="{{ route('platform.sessions.index') }}"
                   class="nav-link {{ request()->routeIs('platform.sessions.*') ? 'active' : '' }}"
                   aria-label="Sessions">
                    <i class="bi bi-clock-history"></i>
                    <span class="nav-label">Sessions</span>
                </a>
            @endif
            @if(auth('platform')->user()->hasPermission('platform.security.view'))
                <a href="{{ route('platform.security.index') }}"
                   class="nav-link {{ request()->routeIs('platform.security.index') ? 'active' : '' }}"
                   aria-label="Security Centre">
                    <i class="bi bi-shield-exclamation"></i>
                    <span class="nav-label">Security Centre</span>
                </a>
                <a href="{{ route('platform.security.events.index') }}"
                   class="nav-link {{ request()->routeIs('platform.security.events.*') ? 'active' : '' }}"
                   aria-label="Security Events">
                    <i class="bi bi-activity"></i>
                    <span class="nav-label">Security Events</span>
                </a>
            @endif
            @if(auth('platform')->user()->hasPermission('platform.audit.view'))
                <a href="{{ route('platform.audit.index') }}"
                   class="nav-link {{ request()->routeIs('platform.audit.*') ? 'active' : '' }}"
                   aria-label="Audit Log">
                    <i class="bi bi-journal-text"></i>
                    <span class="nav-label">Audit Log</span>
                </a>
            @endif

            {{-- Support & Compliance --}}
            @if(auth('platform')->user()->hasPermission('platform.support.view'))
                <span class="nav-section">Support</span>
                <a href="{{ route('platform.support.cases.index') }}"
                   class="nav-link {{ request()->routeIs('platform.support.*') ? 'active' : '' }}"
                   aria-label="Support Centre">
                    <i class="bi bi-headset"></i>
                    <span class="nav-label">Support Centre</span>
                </a>
            @endif
            @if(auth('platform')->user()->hasPermission('platform.compliance.view'))
                <a href="{{ route('platform.compliance.index') }}"
                   class="nav-link {{ request()->routeIs('platform.compliance.*') ? 'active' : '' }}"
                   aria-label="Compliance">
                    <i class="bi bi-patch-check"></i>
                    <span class="nav-label">Compliance</span>
                </a>
            @endif

            @endauth

        </nav>

        {{-- User footer --}}
        @auth('platform')
        <div class="sidebar-footer">
            <div class="sidebar-user">
                <div class="sidebar-avatar" aria-hidden="true">
                    {{ strtoupper(substr(auth('platform')->user()->name ?? 'U', 0, 1)) }}
                </div>
                <div>
                    <div class="user-name">{{ auth('platform')->user()->name }}</div>
                    <div class="user-email">{{ auth('platform')->user()->email }}</div>
                </div>
            </div>
        </div>
        @endauth

    </aside>
    @endauth

    {{-- ── Main Area ───────────────────────────────────────────── --}}
    <div class="main-content">

        {{-- Top Bar --}}
        @auth('platform')
        <div class="top-bar" role="banner">
            <button class="top-bar-toggle" id="sidebar-toggle"
                    aria-label="Toggle sidebar" aria-expanded="true" aria-controls="sidebar">
                <i class="bi bi-list" style="font-size:20px;"></i>
            </button>

            <span class="top-bar-title">@yield('title', 'Platform Console')</span>

            <div class="top-bar-actions">
                <a href="{{ route('platform.notifications.index') }}" class="top-bar-btn" aria-label="Notifications" data-tooltip="Notifications" style="position:relative">
                    <i class="bi bi-bell" style="font-size:17px;"></i>
                    @if(auth('platform')->user()->unreadNotifications()->count())<span class="notif-dot" aria-label="Unread notifications"></span>@endif
                </a>

                <form method="post" action="{{ route('platform.logout') }}" style="margin:0;">
                    @csrf
                    <button type="submit" class="btn-secondary btn btn-sm"
                            aria-label="Sign out">
                        <i class="bi bi-box-arrow-right"></i>
                        <span class="nav-label">Sign out</span>
                    </button>
                </form>
            </div>
        </div>
        @endauth

        {{-- Page body --}}
        <main class="page-body" id="main-content" tabindex="-1">

            {{-- Flash messages --}}
            @if(session('success') || session('status'))
                <div class="alert alert-success" role="alert">
                    <i class="bi bi-check-circle-fill"></i>
                    {{ session('success') ?? session('status') }}
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
            @if(session('info') || session('notice'))
                <div class="alert alert-info" role="alert">
                    <i class="bi bi-info-circle-fill"></i>
                    {{ session('info') ?? session('notice') }}
                </div>
            @endif
            @if($errors->any())
                <div class="alert alert-danger" role="alert">
                    <i class="bi bi-exclamation-circle-fill"></i>
                    <div>
                        <strong>Please fix the following errors:</strong>
                        <ul style="margin:6px 0 0; padding-left:16px;">
                            @foreach($errors->all() as $e)
                                <li>{{ $e }}</li>
                            @endforeach
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
    const key       = 'ble_sidebar_collapsed';

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

    // ── Auto-dismiss flash alerts after 6 s ───────────────────
    document.querySelectorAll('.alert').forEach(function (el) {
        setTimeout(function () {
            el.style.transition = 'opacity 400ms ease, transform 400ms ease';
            el.style.opacity = '0';
            el.style.transform = 'translateY(-8px)';
            setTimeout(function () { el.remove(); }, 420);
        }, 6000);
    });

    // ── Count-up for stat values ───────────────────────────────
    document.querySelectorAll('[data-count-up]').forEach(function (el) {
        const target = parseFloat(el.getAttribute('data-count-up'));
        const dur    = 1200;
        const start  = performance.now();
        const isInt  = Number.isInteger(target);
        (function step(now) {
            const p = Math.min((now - start) / dur, 1);
            const eased = 1 - Math.pow(1 - p, 3);
            const val = target * eased;
            el.textContent = isInt ? Math.round(val).toLocaleString() : val.toFixed(2);
            if (p < 1) requestAnimationFrame(step);
        })(start);
    });
})();
</script>
</body>
</html>
