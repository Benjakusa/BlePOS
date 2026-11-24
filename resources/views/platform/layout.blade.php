<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Platform Console') · BlePOS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            color-scheme: light;
            /* Neutrals */
            --color-neutral-0: #FFFFFF;
            --color-neutral-50: #F5F7F9;
            --color-neutral-100: #EFF1F3;
            --color-neutral-150: #E9ECEF;
            --color-neutral-200: #E4E7EB;
            --color-neutral-250: #DCE0E5;
            --color-neutral-300: #D3D8DF;
            --color-neutral-350: #B4BCC6;
            --color-neutral-400: #9AA3AF;
            --color-neutral-500: #7B8491;
            --color-neutral-600: #606A76;
            --color-neutral-700: #4B535E;
            --color-neutral-800: #333942;
            --color-neutral-900: #1D2127;
            --color-neutral-950: #0B0C0E;

            /* Inks */
            --color-ink-950: #0A0B0C;
            --color-ink-900: #1A1D20;
            --color-ink-800: #2C3136;
            --color-ink-700: #424951;

            /* Vibrant Greens */
            --color-primary-green: #15803d;
            --color-primary-green-hover: #166534;
            --color-primary-green-active: #14532d;

            /* Radii */
            --radius-btn: 10px;
            --radius-card: 12px;
            --radius-badge: 7px;
            --radius-sm: 6px;

            /* Shadows */
            --shadow-card: 0 1px 3px rgba(0, 0, 0, 0.04), 0 1px 2px rgba(0, 0, 0, 0.02);
            --shadow-raised: 0 1px 3px rgba(0, 0, 0, 0.06), 0 1px 2px rgba(0, 0, 0, 0.04);
            --shadow-popover: 0 10px 15px -3px rgba(0, 0, 0, 0.08), 0 4px 6px -4px rgba(0, 0, 0, 0.04);
            --shadow-input: 0 1px 3px rgba(0, 0, 0, 0.07), 0 1px 2px rgba(0, 0, 0, 0.04);
            --shadow-input-focus: 0 0 0 2px var(--color-primary-green), 0 2px 6px rgba(21, 128, 61, 0.18);

            /* Transitions */
            --transition-base: all 180ms cubic-bezier(0.16, 1, 0.3, 1);
            --transition-colors: background-color 180ms cubic-bezier(0.16, 1, 0.3, 1), border-color 180ms cubic-bezier(0.16, 1, 0.3, 1), color 180ms cubic-bezier(0.16, 1, 0.3, 1);
            --transition-transform: transform 180ms cubic-bezier(0.16, 1, 0.3, 1);
        }

        /* 10 Typography Styles */
        .text-kpi-value { font-weight: 600; font-size: 31.5px; line-height: 38px; letter-spacing: -0.4px; }
        .text-heading-page { font-weight: 600; font-size: 23.5px; line-height: 30px; letter-spacing: -0.3px; }
        .text-heading-brand { font-weight: 600; font-size: 16px; line-height: 22px; letter-spacing: -0.25px; }
        .text-body-large { font-weight: 400; font-size: 16px; line-height: 22px; letter-spacing: 0; }
        .text-body-medium { font-weight: 500; font-size: 14px; line-height: 20px; letter-spacing: -0.25px; }
        .text-body-regular { font-weight: 400; font-size: 14px; line-height: 20px; letter-spacing: -0.1px; }
        .text-body-small { font-weight: 400; font-size: 12.5px; line-height: 18px; letter-spacing: 0; }
        .text-label-tab { font-weight: 500; font-size: 14px; line-height: 20px; letter-spacing: -0.15px; }
        .text-label-score { font-weight: 500; font-size: 13px; line-height: 18px; letter-spacing: 0; }
        .text-label-caps { font-weight: 500; font-size: 12px; line-height: 16px; letter-spacing: +0.5px; text-transform: uppercase; }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            font-feature-settings: "cv11", "ss01";
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            background: var(--color-neutral-100);
            color: var(--color-ink-950);
        }

        a { color: var(--color-ink-900); text-decoration: none; transition: var(--transition-colors); }
        a:hover { text-decoration: underline; }

        .shell { min-height: 100vh; display: grid; grid-template-columns: 240px 1fr; }
        aside {
            background: var(--color-neutral-50);
            border-right: 1px solid var(--color-neutral-200);
            color: var(--color-neutral-700);
            padding: 24px 16px;
        }

        .brand {
            font-weight: 600;
            font-size: 16px;
            line-height: 22px;
            letter-spacing: -0.25px;
            margin-bottom: 28px;
            color: var(--color-ink-950);
        }
        .brand small {
            display: block;
            color: var(--color-neutral-500);
            font-size: 12.5px;
            line-height: 18px;
            font-weight: 400;
            margin-top: 2px;
            letter-spacing: 0;
        }

        nav { display: grid; gap: 3px; }
        nav a {
            display: flex;
            align-items: center;
            height: 38px;
            color: var(--color-neutral-700);
            padding: 0 12px;
            text-decoration: none;
            border-radius: var(--radius-btn);
            font-size: 14px;
            line-height: 20px;
            font-weight: 500;
            letter-spacing: -0.15px;
            transition: var(--transition-base);
        }
        nav a:hover {
            background: var(--color-neutral-150);
            color: var(--color-ink-900);
            text-decoration: none;
        }
        nav a.active {
            background: var(--color-neutral-0);
            color: var(--color-ink-950);
            font-weight: 600;
            box-shadow: var(--shadow-raised);
        }

        main {
            min-width: 0;
            padding: 28px clamp(16px, 3vw, 40px);
            background: var(--color-neutral-100);
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            margin-bottom: 28px;
        }

        h1 {
            font-size: 23.5px;
            line-height: 30px;
            margin: 0;
            letter-spacing: -0.3px;
            font-weight: 600;
            color: var(--color-ink-950);
        }
        .muted { color: var(--color-neutral-600); font-size: 14px; line-height: 20px; }

        .panel {
            background: var(--color-neutral-0);
            border: 1px solid var(--color-neutral-200);
            border-radius: var(--radius-card);
            padding: 22px;
            max-width: 760px;
            box-shadow: var(--shadow-card);
            transition: var(--transition-base);
        }

        .form { display: grid; gap: 14px; max-width: 430px; }
        label { display: grid; gap: 6px; font-weight: 500; font-size: 14px; line-height: 20px; color: var(--color-ink-900); }
        input {
            border: 0;
            border-radius: var(--radius-btn);
            padding: 10px 14px;
            font: inherit;
            font-size: 14px;
            line-height: 20px;
            width: 100%;
            background: var(--color-neutral-0);
            color: var(--color-ink-950);
            box-shadow: var(--shadow-input);
            transition: var(--transition-base);
        }
        input::placeholder {
            color: var(--color-neutral-400);
            font-size: 13.5px;
        }
        input:focus {
            outline: none;
            box-shadow: var(--shadow-input-focus);
            background: var(--color-neutral-0);
        }

        button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: var(--color-primary-green);
            color: #ffffff;
            border: 0;
            border-radius: var(--radius-btn);
            padding: 8px 16px;
            font: inherit;
            font-size: 14px;
            line-height: 20px;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            transition: var(--transition-base);
            box-shadow: 0 1px 2px rgba(0,0,0,0.06);
        }
        button:hover {
            background: var(--color-primary-green-hover);
            box-shadow: 0 2px 5px rgba(21, 128, 61, 0.25);
        }
        button:active {
            background: var(--color-primary-green-active);
            transform: scale(0.98);
        }

        .secondary {
            background: var(--color-neutral-0);
            border: 0;
            color: var(--color-neutral-700);
            box-shadow: var(--shadow-input);
        }
        .secondary:hover {
            background: var(--color-neutral-50);
            color: var(--color-primary-green);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .error { color: #b91c1c; font-size: 14px; line-height: 20px; }
        .notice {
            background: #ebf2fe;
            border: 1px solid #c7d9fc;
            color: #1d4ed8;
            padding: 12px 14px;
            border-radius: var(--radius-btn);
            margin: 0 0 16px;
            font-size: 14px;
            line-height: 20px;
        }
        .qr { width: 200px; height: 200px; border: 1px solid var(--color-neutral-200); padding: 8px; border-radius: var(--radius-sm); background: #fff; }
        code { overflow-wrap: anywhere; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: 13px; }

        @media (max-width: 1024px) and (min-width: 769px) {
            .shell { grid-template-columns: 200px 1fr; }
            aside { padding: 20px 12px; }
        }

        @media (max-width: 768px) {
            .shell { grid-template-columns: 1fr; }
            aside { padding: 14px 18px; border-right: none; border-bottom: 1px solid var(--color-neutral-200); }
            .brand { margin-bottom: 12px; }
            nav { display: flex; flex-wrap: wrap; gap: 6px; }
            main { padding: 20px 16px; }
        }

        @media (prefers-reduced-motion: reduce) {
            *, ::before, ::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
                scroll-behavior: auto !important;
            }
        }
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
