<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Platform Console') · BlePOS</title>
    <style>
        :root { color-scheme: light; font-family: Inter, ui-sans-serif, system-ui, sans-serif; color: #17231f; background: #f3f6f4; }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; }
        a { color: #146b4d; }
        .shell { min-height: 100vh; display: grid; grid-template-columns: 230px 1fr; }
        aside { background: #102b22; color: #eaf4ef; padding: 24px 18px; }
        .brand { font-weight: 750; font-size: 20px; letter-spacing: -.03em; margin-bottom: 32px; }
        .brand small { display: block; color: #a9c9b9; font-size: 12px; font-weight: 500; margin-top: 4px; letter-spacing: 0; }
        nav a { display: block; color: #eaf4ef; padding: 10px 12px; text-decoration: none; border-radius: 6px; }
        nav a.active, nav a:hover { background: #204b3b; }
        main { min-width: 0; padding: 30px clamp(18px, 4vw, 54px); }
        header { display: flex; justify-content: space-between; align-items: center; gap: 20px; margin-bottom: 28px; }
        h1 { font-size: 27px; margin: 0; letter-spacing: -.025em; }
        .muted { color: #63736b; }
        .panel { background: #fff; border: 1px solid #dce5df; border-radius: 8px; padding: 22px; max-width: 760px; }
        .form { display: grid; gap: 14px; max-width: 430px; }
        label { display: grid; gap: 6px; font-weight: 600; }
        input { border: 1px solid #b9c9bf; border-radius: 5px; padding: 11px 12px; font: inherit; width: 100%; }
        button { background: #146b4d; color: #fff; border: 0; border-radius: 5px; padding: 11px 15px; font: inherit; font-weight: 650; cursor: pointer; }
        .secondary { background: transparent; color: #d4e7dc; padding: 8px; }
        .error { color: #a32121; font-size: 14px; }
        .notice { background: #e7f4eb; border: 1px solid #b9ddc4; padding: 12px 14px; border-radius: 5px; margin: 0 0 16px; }
        .qr { width: 200px; height: 200px; border: 1px solid #dce5df; padding: 8px; }
        code { overflow-wrap: anywhere; }
        @media (max-width: 700px) { .shell { grid-template-columns: 1fr; } aside { padding: 14px 18px; } .brand { margin: 0; } nav { display: flex; gap: 6px; margin-top: 12px; } main { padding-top: 22px; } }
    </style>
</head>
<body>
<div class="shell">
    <aside>
        <div class="brand">BlePOS<small>Platform control plane</small></div>
        @auth('platform')
            <nav aria-label="Platform navigation">
                @if(auth('platform')->user()->hasPermission('platform.dashboard.view'))
                    <a class="{{ request()->routeIs('platform.dashboard') ? 'active' : '' }}" href="{{ route('platform.dashboard') }}">Dashboard</a>
                    <a class="{{ request()->routeIs('platform.analytics.*') ? 'active' : '' }}" href="{{ route('platform.analytics.index') }}">Analytics & Reports</a>
                @endif
                @if(auth('platform')->user()->hasPermission('platform.tenants.view'))
                    <a class="{{ request()->routeIs('platform.tenants.*') ? 'active' : '' }}" href="{{ route('platform.tenants.index') }}">Tenants</a>
                @endif
                @if(auth('platform')->user()->hasPermission('platform.provisioning.view'))
                    <a class="{{ request()->routeIs('platform.provisioning.*') ? 'active' : '' }}" href="{{ route('platform.provisioning.index') }}">Provisioning</a>
                @endif
                @if(auth('platform')->user()->hasPermission('platform.infrastructure.view'))
                    <a class="{{ request()->routeIs('platform.infrastructure.*') ? 'active' : '' }}" href="{{ route('platform.infrastructure.index') }}">Infrastructure</a>
                @endif
                @if(auth('platform')->user()->hasPermission('platform.billing.view'))
                    <a class="{{ request()->routeIs('platform.plans.*') ? 'active' : '' }}" href="{{ route('platform.plans.index') }}">Plans</a>
                    <a class="{{ request()->routeIs('platform.invoices.*') ? 'active' : '' }}" href="{{ route('platform.invoices.index') }}">Invoices & Billing</a>
                    <a class="{{ request()->routeIs('platform.dunning.*') ? 'active' : '' }}" href="{{ route('platform.dunning.index') }}">Dunning</a>
                @endif
                @if(auth('platform')->user()->hasPermission('platform.modules.view'))
                    <a class="{{ request()->routeIs('platform.modules.*') ? 'active' : '' }}" href="{{ route('platform.modules.index') }}">Module Catalogue</a>
                @endif
                @if(auth('platform')->user()->hasPermission('platform.modules.view'))
                    <a class="{{ request()->routeIs('platform.templates.*') ? 'active' : '' }}" href="{{ route('platform.templates.index') }}">Industry Templates</a>
                @endif
                @if(auth('platform')->user()->hasPermission('platform.security.users.view'))
                    <a class="{{ request()->routeIs('platform.users.*') ? 'active' : '' }}" href="{{ route('platform.users.index') }}">Platform users</a>
                @endif
                @if(auth('platform')->user()->hasPermission('platform.security.roles.manage'))
                    <a class="{{ request()->routeIs('platform.roles.*') ? 'active' : '' }}" href="{{ route('platform.roles.index') }}">Roles & permissions</a>
                @endif
                @if(auth('platform')->user()->hasPermission('platform.security.approvals.review'))
                    <a class="{{ request()->routeIs('platform.approvals.*') ? 'active' : '' }}" href="{{ route('platform.approvals.index') }}">Approvals</a>
                @endif
                @if(auth('platform')->user()->hasPermission('platform.security.sessions.revoke'))
                    <a class="{{ request()->routeIs('platform.sessions.*') ? 'active' : '' }}" href="{{ route('platform.sessions.index') }}">Sessions</a>
                @endif
                @if(auth('platform')->user()->hasPermission('platform.security.view'))
                    <a class="{{ request()->routeIs('platform.security.index') ? 'active' : '' }}" href="{{ route('platform.security.index') }}">Security Centre</a>
                    <a class="{{ request()->routeIs('platform.security.events.*') ? 'active' : '' }}" href="{{ route('platform.security.events.index') }}">Security events</a>
                @endif
                @if(auth('platform')->user()->hasPermission('platform.audit.view'))
                    <a class="{{ request()->routeIs('platform.audit.*') ? 'active' : '' }}" href="{{ route('platform.audit.index') }}">Audit log</a>
                @endif
                @if(auth('platform')->user()->hasPermission('platform.support.view'))
                    <a class="{{ request()->routeIs('platform.support.*') ? 'active' : '' }}" href="{{ route('platform.support.cases.index') }}">Support Centre</a>
                @endif
                @if(auth('platform')->user()->hasPermission('platform.compliance.view'))
                    <a class="{{ request()->routeIs('platform.compliance.*') ? 'active' : '' }}" href="{{ route('platform.compliance.index') }}">Compliance & eTIMS</a>
                @endif
            </nav>
        @endauth
    </aside>
    <main>
        @if(auth('platform')->check())
            <header>
                <div><h1>@yield('title', 'Platform Console')</h1><div class="muted">{{ auth('platform')->user()->name }}</div></div>
                <form method="post" action="{{ route('platform.logout') }}">@csrf<button class="secondary" type="submit">Sign out</button></form>
            </header>
        @else
            <header><div><h1>@yield('title', 'Platform Console')</h1></div></header>
        @endif
        @if (session('status')) <p class="notice">{{ session('status') }}</p> @endif
        @yield('content')
    </main>
</div>
@stack('scripts')
</body>
</html>
