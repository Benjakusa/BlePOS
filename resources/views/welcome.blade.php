<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>BlePOS · Multi-Tenant Point of Sale SaaS</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    @vite(['resources/css/app.css'])
    <style>
        .hero-section {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            background: var(--clr-bg);
        }
        .hero-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 24px clamp(16px, 5vw, 64px);
            background: var(--clr-surface);
            box-shadow: var(--shadow-sm);
        }
        .hero-body {
            max-width: 1080px;
            margin: 60px auto;
            padding: 0 24px;
            text-align: center;
        }
        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-top: 48px;
            text-align: left;
        }
    </style>
</head>
<body class="hero-section">
    <!-- Top Navigation -->
    <header class="hero-nav">
        <div style="display: flex; align-items: center; gap: 10px;">
            <div class="sidebar-brand-logo" style="width: 40px; height: 40px; font-size: 20px;">
                <i class="bi bi-broadcast-pin"></i>
            </div>
            <div>
                <strong style="font-size: 18px; color: var(--clr-text); display: block; letter-spacing: -0.3px;">BlePOS</strong>
                <span class="muted" style="font-size: 11px;">Point of Sale &amp; Enterprise Retail SaaS</span>
            </div>
        </div>

        <div style="display: flex; gap: 12px; align-items: center;">
            <a href="{{ route('platform.login') }}" class="btn btn-secondary">
                <i class="bi bi-shield-lock"></i>
                <span>Platform Console</span>
            </a>
            <a href="/tenant/dashboard" class="btn btn-primary">
                <i class="bi bi-shop"></i>
                <span>Tenant Portal</span>
            </a>
        </div>
    </header>

    <!-- Main Hero -->
    <main class="hero-body">
        <div style="display: inline-flex; align-items: center; gap: 8px; background: var(--clr-grey-tint); padding: 6px 14px; border-radius: var(--radius-badge); margin-bottom: 20px;">
            <span class="status-dot pulse"></span>
            <span style="font-size: 12px; font-weight: 600; color: var(--clr-text);">BlePOS SaaS Platform Active</span>
        </div>

        <h1 style="font-size: clamp(32px, 5vw, 48px); font-weight: 700; color: var(--clr-text); line-height: 1.15; letter-spacing: -1px; margin-bottom: 16px;">
            Modern, Resilient POS &amp; Business Management SaaS
        </h1>

        <p class="muted" style="font-size: clamp(16px, 2vw, 18px); max-width: 680px; margin: 0 auto 32px; line-height: 1.6;">
            Multi-tenant, multi-shop architecture tailored for retail, pharmacy, hospitality, and wholesale businesses across Kenya and East Africa.
        </p>

        <div style="display: flex; gap: 14px; justify-content: center; flex-wrap: wrap;">
            <a href="{{ route('platform.login') }}" class="btn btn-primary" style="padding: 14px 28px; font-size: 16px;">
                <i class="bi bi-speedometer2"></i>
                <span>Enter Super Admin Console</span>
            </a>
            <a href="/tenant/dashboard" class="btn btn-secondary" style="padding: 14px 28px; font-size: 16px;">
                <i class="bi bi-cart3"></i>
                <span>Launch POS Terminal</span>
            </a>
        </div>

        <!-- 4 Core Platform Pillars -->
        <div class="feature-grid">
            <div class="card">
                <div class="stat-icon green" style="margin-bottom: 16px;">
                    <i class="bi bi-display"></i>
                </div>
                <h3 class="section-title">POS Terminal</h3>
                <p class="muted" style="margin-top: 6px; font-size: 13px;">
                    Barcode scanning, drawer cash float tracking, cashier shifts, and thermal receipt printing.
                </p>
            </div>

            <div class="card">
                <div class="stat-icon blue" style="margin-bottom: 16px;">
                    <i class="bi bi-buildings"></i>
                </div>
                <h3 class="section-title">Tenant Isolation</h3>
                <p class="muted" style="margin-top: 6px; font-size: 13px;">
                    Multi-database architecture with envelope encryption, subdomains, and branch isolation.
                </p>
            </div>

            <div class="card">
                <div class="stat-icon green" style="margin-bottom: 16px;">
                    <i class="bi bi-phone"></i>
                </div>
                <h3 class="section-title">M-Pesa &amp; Banking</h3>
                <p class="muted" style="margin-top: 6px; font-size: 13px;">
                    Automated Daraja STK Push, Till settlements, reconciliation, and instant payment confirmations.
                </p>
            </div>

            <div class="card">
                <div class="stat-icon blue" style="margin-bottom: 16px;">
                    <i class="bi bi-patch-check"></i>
                </div>
                <h3 class="section-title">Regulatory eTIMS</h3>
                <p class="muted" style="margin-top: 6px; font-size: 13px;">
                    Built-in KRA eTIMS statutory compliance checklist, fiscal signing rails, and audit trails.
                </p>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer style="padding: 24px; text-align: center; border-top: 1px solid var(--clr-header-tint); background: var(--clr-surface); font-size: 13px;" class="muted">
        <div>&copy; {{ date('Y') }} BlePOS SaaS Platform. All rights reserved.</div>
    </footer>
</body>
</html>
