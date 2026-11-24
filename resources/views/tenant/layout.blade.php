<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') · {{ $tenant->name ?? 'BlePOS' }}</title>
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
            --color-primary-green-tint: #dcfce7;

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
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .brand {
            font-weight: 600;
            font-size: 16px;
            line-height: 22px;
            letter-spacing: -0.25px;
            margin-bottom: 24px;
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
            gap: 10px;
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
        nav a svg {
            color: var(--color-neutral-500);
            transition: var(--transition-colors);
            flex-shrink: 0;
        }
        nav a:hover {
            background: var(--color-neutral-150);
            color: var(--color-ink-900);
            text-decoration: none;
        }
        nav a:hover svg {
            color: var(--color-primary-green);
        }
        nav a.active {
            background: var(--color-neutral-0);
            color: var(--color-ink-950);
            font-weight: 600;
            box-shadow: var(--shadow-raised);
        }
        nav a.active svg {
            color: var(--color-primary-green);
        }
        nav .section-header {
            font-size: 11px;
            line-height: 16px;
            text-transform: uppercase;
            color: var(--color-neutral-500);
            font-weight: 600;
            letter-spacing: +0.5px;
            padding: 14px 12px 4px;
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
            margin-bottom: 24px;
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
            padding: 20px;
            box-shadow: var(--shadow-card);
            transition: var(--transition-base);
        }

        .grid-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: var(--color-neutral-0);
            border: 1px solid var(--color-neutral-200);
            border-radius: var(--radius-card);
            padding: 18px 20px;
            box-shadow: var(--shadow-card);
            transition: var(--transition-base);
        }
        .stat-card:hover {
            border-color: var(--color-neutral-300);
        }
        .stat-card .label {
            font-size: 12.5px;
            line-height: 18px;
            color: var(--color-neutral-600);
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: +0.3px;
        }
        .stat-card .value {
            font-size: 31.5px;
            line-height: 38px;
            font-weight: 600;
            letter-spacing: -0.4px;
            color: var(--color-ink-950);
            margin-top: 6px;
        }

        .form { display: grid; gap: 14px; max-width: 520px; }
        label { display: grid; gap: 6px; font-weight: 500; font-size: 14px; line-height: 20px; color: var(--color-ink-900); }
        input, select, textarea {
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
        input::placeholder, textarea::placeholder {
            color: var(--color-neutral-400);
            font-size: 13.5px;
            font-weight: 400;
        }
        input:focus, select:focus, textarea:focus {
            outline: none;
            box-shadow: var(--shadow-input-focus);
            background: var(--color-neutral-0);
        }

        button, .btn {
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
        button svg, .btn svg {
            flex-shrink: 0;
        }
        button:hover, .btn:hover {
            background: var(--color-primary-green-hover);
            color: #ffffff;
            text-decoration: none;
            box-shadow: 0 2px 5px rgba(21, 128, 61, 0.25);
        }
        button:active, .btn:active {
            background: var(--color-primary-green-active);
            transform: scale(0.98);
        }

        .btn-secondary {
            background: var(--color-neutral-0);
            border: 0;
            color: var(--color-ink-800);
            box-shadow: var(--shadow-input);
        }
        .btn-secondary:hover {
            background: var(--color-neutral-50);
            color: var(--color-primary-green);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .btn-danger {
            background: #fee2e2;
            border: 0;
            color: #b91c1c;
            box-shadow: var(--shadow-input);
        }
        .btn-danger:hover {
            background: #fecaca;
            color: #991b1b;
        }
        .btn-sm {
            padding: 5px 10px;
            font-size: 12.5px;
            line-height: 18px;
            border-radius: var(--radius-sm);
        }

        .alert {
            padding: 12px 16px;
            border-radius: var(--radius-btn);
            margin-bottom: 20px;
            font-size: 14px;
            line-height: 20px;
            border: 1px solid transparent;
            transition: var(--transition-base);
        }
        .alert-success { background: #dcfce7; border-color: #bbf7d0; color: #15803d; }
        .alert-danger { background: #fee2e2; border-color: #fecaca; color: #b91c1c; }
        .alert-warning { background: #fef3c7; border-color: #fde68a; color: #b45309; }
        .alert-info { background: #ebf2fe; border-color: #c7d9fc; color: #1d4ed8; }

        table { width: 100%; border-collapse: collapse; font-size: 14px; text-align: left; }
        th {
            background: var(--color-neutral-50);
            padding: 8px 14px;
            height: 33px;
            border-bottom: 1px solid var(--color-neutral-200);
            font-weight: 500;
            font-size: 12px;
            line-height: 16px;
            text-transform: uppercase;
            letter-spacing: +0.5px;
            color: var(--color-neutral-600);
        }
        td {
            padding: 14px;
            border-bottom: 1px solid var(--color-neutral-150);
            font-size: 14px;
            line-height: 20px;
            color: var(--color-ink-900);
            transition: var(--transition-colors);
        }
        tr:last-child td { border-bottom: 0; }
        tr:hover td { background: var(--color-neutral-50); }

        .badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            height: 24px;
            padding: 0 8px;
            font-size: 12px;
            line-height: 16px;
            font-weight: 500;
            border-radius: var(--radius-badge);
            text-transform: uppercase;
            letter-spacing: +0.5px;
            border: 1px solid var(--color-neutral-350);
            background: var(--color-neutral-50);
            color: var(--color-neutral-700);
            transition: var(--transition-base);
        }
        .badge-success { background: #dcfce7; border-color: #bbf7d0; color: #15803d; }
        .badge-danger { background: #fee2e2; border-color: #fecaca; color: #b91c1c; }
        .badge-warning { background: #fef3c7; border-color: #fde68a; color: #b45309; }
        .badge-info { background: #ebf2fe; border-color: #c7d9fc; color: #1d4ed8; }
        .badge-secondary { background: var(--color-neutral-100); border-color: var(--color-neutral-250); color: var(--color-neutral-700); }

        .score-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 28px;
            height: 22px;
            padding: 0 6px;
            font-size: 13px;
            line-height: 18px;
            font-weight: 500;
            border-radius: var(--radius-sm);
        }
        .score-high { background: #e8f8ee; color: #166534; }
        .score-mid { background: #fef7da; color: #854d0e; }
        .score-low { background: #fef0dc; color: #9a3412; }
        .score-critical { background: #fce8e8; color: #991b1b; }

        .actions { display: flex; gap: 8px; align-items: center; }
        .table-container {
            overflow-x: auto;
            background: var(--color-neutral-0);
            border: 1px solid var(--color-neutral-200);
            border-radius: var(--radius-card);
            box-shadow: var(--shadow-card);
        }
        .user-panel {
            font-size: 13px;
            line-height: 18px;
            color: var(--color-neutral-600);
            border-top: 1px solid var(--color-neutral-200);
            padding-top: 14px;
            margin-top: 20px;
        }
        .user-panel strong { color: var(--color-ink-950); display: block; font-weight: 600; }

        @media (max-width: 1024px) and (min-width: 769px) {
            .shell { grid-template-columns: 200px 1fr; }
            aside { padding: 20px 12px; }
        }

        @media (max-width: 768px) {
            .shell { grid-template-columns: 1fr; }
            aside { padding: 16px; border-right: none; border-bottom: 1px solid var(--color-neutral-200); }
            nav { grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); }
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
                    <span style="display: inline-block; font-size: 11px; background: var(--color-neutral-150); color: var(--color-ink-800); border: 1px solid var(--color-neutral-250); padding: 2px 8px; border-radius: var(--radius-badge); margin-top: 6px; font-weight: 500;">
                        Branch: {{ \App\Services\ShopContext::getShop()->name }}
                    </span>
                @endif
            </div>
            <nav aria-label="Tenant navigation">
                <a href="{{ route('tenant.dashboard') }}" class="{{ request()->routeIs('tenant.dashboard') ? 'active' : '' }}">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg>
                    <span>Dashboard</span>
                </a>
                
                <div class="section-header">Point of Sale</div>
                <a href="{{ route('tenant.pos.checkout') }}" class="{{ request()->routeIs('tenant.pos.checkout') ? 'active' : '' }}">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="14" x="2" y="3" rx="2"/><line x1="8" x2="16" y1="21" y2="21"/><line x1="12" x2="12" y1="17" y2="21"/></svg>
                    <span>POS Terminal</span>
                </a>
                <a href="{{ route('tenant.shifts.index') }}" class="{{ request()->routeIs('tenant.shifts.*') ? 'active' : '' }}">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    <span>Cashier Shifts</span>
                </a>
                <a href="{{ route('tenant.pos.sales') }}" class="{{ request()->routeIs('tenant.pos.sales') ? 'active' : '' }}">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1Z"/><path d="M16 8h-6a2 2 0 1 0 0 4h4a2 2 0 1 1 0 4H8"/><path d="M12 17.5v-11"/></svg>
                    <span>Sales History</span>
                </a>

                <div class="section-header">Catalog</div>
                <a href="{{ route('tenant.products.index') }}" class="{{ request()->routeIs('tenant.products.*') ? 'active' : '' }}">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg>
                    <span>Products</span>
                </a>
                <a href="{{ route('tenant.categories.index') }}" class="{{ request()->routeIs('tenant.categories.*') ? 'active' : '' }}">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2H2v10l9.29 9.29c.94.94 2.48.94 3.42 0l6.58-6.58c.94-.94.94-2.48 0-3.42L12 2Z"/><path d="M7 7h.01"/></svg>
                    <span>Categories</span>
                </a>

                <div class="section-header">Inventory</div>
                <a href="{{ route('tenant.inventory.index') }}" class="{{ request()->routeIs('tenant.inventory.index') ? 'active' : '' }}">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12.83 2.18a2 2 0 0 0-1.66 0L2.6 6.08a1 1 0 0 0 0 1.83l8.58 3.91a2 2 0 0 0 1.66 0l8.58-3.9a1 1 0 0 0 0-1.83Z"/><path d="m22 17.65-9.17 4.16a2 2 0 0 1-1.66 0L2 17.65"/><path d="m22 12.65-9.17 4.16a2 2 0 0 1-1.66 0L2 12.65"/></svg>
                    <span>Stock Levels</span>
                </a>
                <a href="{{ route('tenant.inventory.adjust') }}" class="{{ request()->routeIs('tenant.inventory.adjust') ? 'active' : '' }}">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                    <span>Adjust Stock</span>
                </a>
                <a href="{{ route('tenant.inventory.receive') }}" class="{{ request()->routeIs('tenant.inventory.receive') ? 'active' : '' }}">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><path d="M15 18H9"/><path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.624l-3.48-4.35A1 1 0 0 0 17.52 8H14"/><circle cx="17" cy="18" r="2"/><circle cx="7" cy="18" r="2"/></svg>
                    <span>Receive Stock</span>
                </a>
                <a href="{{ route('tenant.inventory.movements') }}" class="{{ request()->routeIs('tenant.inventory.movements') ? 'active' : '' }}">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                    <span>Audit Movements</span>
                </a>

                <div class="section-header">Finance & Ledgers</div>
                <a href="{{ route('tenant.accounting.accounts.index') }}" class="{{ request()->routeIs('tenant.accounting.accounts.*') ? 'active' : '' }}">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
                    <span>Chart of Accounts</span>
                </a>
                <a href="{{ route('tenant.accounting.journals.index') }}" class="{{ request()->routeIs('tenant.accounting.journals.*') ? 'active' : '' }}">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
                    <span>Journal Entries</span>
                </a>
                <a href="{{ route('tenant.accounting.expenses.index') }}" class="{{ request()->routeIs('tenant.accounting.expenses.*') ? 'active' : '' }}">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/></svg>
                    <span>Expenses</span>
                </a>
                <a href="{{ route('tenant.accounting.reports.trial-balance') }}" class="{{ request()->routeIs('tenant.accounting.reports.trial-balance') ? 'active' : '' }}">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="20" x2="12" y2="10"/><line x1="18" y1="20" x2="18" y2="4"/><line x1="6" y1="20" x2="6" y2="16"/></svg>
                    <span>Trial Balance</span>
                </a>
                <a href="{{ route('tenant.accounting.reports.profit-loss') }}" class="{{ request()->routeIs('tenant.accounting.reports.profit-loss') ? 'active' : '' }}">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="20" x2="12" y2="10"/><line x1="18" y1="20" x2="18" y2="4"/><line x1="6" y1="20" x2="6" y2="16"/></svg>
                    <span>Profit & Loss</span>
                </a>
                <a href="{{ route('tenant.accounting.reports.balance-sheet') }}" class="{{ request()->routeIs('tenant.accounting.reports.balance-sheet') ? 'active' : '' }}">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="20" x2="12" y2="10"/><line x1="18" y1="20" x2="18" y2="4"/><line x1="6" y1="20" x2="6" y2="16"/></svg>
                    <span>Balance Sheet</span>
                </a>
                <a href="{{ route('tenant.accounting.reports.general-ledger') }}" class="{{ request()->routeIs('tenant.accounting.reports.general-ledger') ? 'active' : '' }}">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="20" x2="12" y2="10"/><line x1="18" y1="20" x2="18" y2="4"/><line x1="6" y1="20" x2="6" y2="16"/></svg>
                    <span>General Ledger</span>
                </a>
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
