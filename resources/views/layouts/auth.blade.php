<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Welcome') · BlePOS</title>
    @vite(['resources/css/app.css'])
    @stack('styles')

    {{-- Critical auth layout — guaranteed inline so layout works even before Vite CSS loads --}}
    <style>
        /* ── Reset & Base ───────────────────────────────────────── */
        *, *::before, *::after { box-sizing: border-box; }

        html, body {
            margin: 0; padding: 0; height: 100%;
            font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
            background: #ffffff;
        }

        /* ── Outer split container ─────────────────────────────── */
        .ath-wrap {
            display: flex;
            flex-direction: row;
            height: 100vh;
            width: 100%;
            overflow: hidden;
        }

        /* ── LEFT: form panel ──────────────────────────────────── */
        .ath-form-side {
            flex: 0 0 50%;
            width: 50%;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #ffffff;
            padding: 48px 40px;
            overflow-y: auto;
        }

        .ath-form-inner {
            width: 100%;
            max-width: 400px;
        }

        /* ── RIGHT: image panel ────────────────────────────────── */
        .ath-img-side {
            flex: 0 0 50%;
            width: 50%;
            height: 100vh;
            position: relative;
            overflow: hidden;
            background: #EAEEF3;
        }

        .ath-img-side img {
            display: block;
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center center;
        }

        /* ── Typography ────────────────────────────────────────── */
        .ath-heading {
            font-size: 32px;
            font-weight: 700;
            color: #0F172A;
            margin: 0 0 6px;
            letter-spacing: -0.5px;
            line-height: 1.2;
        }

        .ath-subhead {
            font-size: 14px;
            color: #64748B;
            margin: 0 0 32px;
        }

        /* ── Form fields ───────────────────────────────────────── */
        .ath-field {
            position: relative;
            margin-bottom: 18px;
        }

        .ath-field label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            color: #0F172A;
            margin-bottom: 6px;
        }

        .ath-input-wrap {
            position: relative;
        }

        .ath-input-wrap .ath-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #64748B;
            font-size: 16px;
            pointer-events: none;
            line-height: 1;
        }

        .ath-input-wrap input {
            width: 100%;
            height: 48px;
            padding: 0 14px 0 42px;
            font-family: inherit;
            font-size: 14px;
            color: #0F172A;
            background: #ffffff;
            border: 1.5px solid #CBD5E1;
            border-radius: 10px;
            outline: none;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }

        .ath-input-wrap input::placeholder {
            color: #94A3B8;
        }

        .ath-input-wrap input:focus {
            border-color: #16A34A;
            box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.08);
        }

        .ath-field.has-error .ath-input-wrap input {
            border-color: #DC2626;
        }

        .ath-field-error {
            display: flex;
            align-items: center;
            gap: 5px;
            margin-top: 5px;
            font-size: 12px;
            color: #DC2626;
        }

        /* ── Recovery link row ─────────────────────────────────── */
        .ath-recover-row {
            display: flex;
            justify-content: flex-end;
            margin: -6px 0 20px;
        }

        .ath-recover-row a {
            font-size: 13px;
            color: #64748B;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.15s;
        }

        .ath-recover-row a:hover { color: #16A34A; }

        /* ── Submit button ─────────────────────────────────────── */
        .ath-submit {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            height: 48px;
            font-family: inherit;
            font-size: 15px;
            font-weight: 600;
            color: #ffffff;
            background: #16A34A;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            margin-top: 4px;
            transition: background 0.15s ease, box-shadow 0.15s ease, transform 0.1s ease;
            box-shadow: 0 4px 14px rgba(22, 163, 74, 0.25);
        }

        .ath-submit:hover {
            background: #15803D;
            box-shadow: 0 6px 20px rgba(22, 163, 74, 0.35);
        }

        .ath-submit:active { transform: scale(0.97); }

        /* ── Divider ───────────────────────────────────────────── */
        .ath-divider {
            display: flex;
            align-items: center;
            margin: 28px 0 20px;
        }

        .ath-divider::before,
        .ath-divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid #E2E8F0;
        }

        .ath-divider span {
            padding: 0 14px;
            font-size: 12px;
            color: #94A3B8;
            font-weight: 500;
            white-space: nowrap;
        }

        /* ── Social buttons ────────────────────────────────────── */
        .ath-social-row {
            display: flex;
            gap: 12px;
        }

        .ath-social-btn {
            flex: 1;
            height: 46px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: #0F172A;
            background: #ffffff;
            border: 1.5px solid #E2E8F0;
            border-radius: 10px;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.15s ease;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }

        .ath-social-btn:hover {
            background: #F6F8FB;
            border-color: #CBD5E1;
            transform: translateY(-1px);
            box-shadow: 0 4px 10px rgba(0,0,0,0.08);
        }

        .ath-social-btn.google:hover  { color: #EA4335; }
        .ath-social-btn.apple:hover   { color: #111111; }
        .ath-social-btn.facebook:hover{ color: #1877F2; }

        /* ── Alerts ────────────────────────────────────────────── */
        .ath-alert {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 13px;
            margin-bottom: 20px;
        }

        .ath-alert.info    { background: #DBEAFE; color: #1D4ED8; }
        .ath-alert.success { background: #EAEEF3; color: #16A34A; }
        .ath-alert.danger  { background: #EAEEF3; color: #DC2626; }

        /* ── Back link ─────────────────────────────────────────── */
        .ath-back {
            display: block;
            text-align: center;
            margin-top: 24px;
            font-size: 13px;
            color: #64748B;
            text-decoration: none;
            transition: color 0.15s;
        }
        .ath-back:hover { color: #16A34A; }

        /* ── Mobile: stack vertically ──────────────────────────── */
        @media (max-width: 900px) {
            .ath-wrap    { flex-direction: column; height: auto; }
            .ath-form-side { flex: none; width: 100%; height: auto;
                            min-height: 100vh; padding: 40px 24px; }
            .ath-img-side  { display: none; }
        }
    </style>
</head>
<body>

<div class="ath-wrap">
    {{-- LEFT: Login form --}}
    <div class="ath-form-side">
        <div class="ath-form-inner">

            {{-- Flash alerts --}}
            @if(session('status'))
                <div class="ath-alert info" role="alert">
                    <i class="bi bi-info-circle-fill"></i>
                    <span>{{ session('status') }}</span>
                </div>
            @endif
            @if(session('success'))
                <div class="ath-alert success" role="alert">
                    <i class="bi bi-check-circle-fill"></i>
                    <span>{{ session('success') }}</span>
                </div>
            @endif
            @if(session('error'))
                <div class="ath-alert danger" role="alert">
                    <i class="bi bi-exclamation-circle-fill"></i>
                    <span>{{ session('error') }}</span>
                </div>
            @endif
            @if($errors->has('throttle'))
                <div class="ath-alert danger" role="alert">
                    <i class="bi bi-shield-slash-fill"></i>
                    <span>{{ $errors->first('throttle') }}</span>
                </div>
            @endif

            @yield('content')
        </div>
    </div>

    {{-- RIGHT: Full-height hero image --}}
    <div class="ath-img-side" aria-hidden="true">
        <img src="{{ asset('assets/login.jpg') }}" alt="BlePOS workspace">
    </div>
</div>

@stack('scripts')
<script nonce="{{ request()->attributes->get('csp_nonce') }}">
(function () {
    'use strict';
    document.querySelectorAll('.ath-alert').forEach(function (el) {
        setTimeout(function () {
            el.style.transition = 'opacity 400ms ease, transform 400ms ease';
            el.style.opacity = '0';
            el.style.transform = 'translateY(-8px)';
            setTimeout(function () { el.remove(); }, 420);
        }, 6000);
    });
})();
</script>
</body>
</html>
