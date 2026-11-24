<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Welcome') · BlePOS</title>
    {{-- Bootstrap Icons CDN --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    {{-- Global design system --}}
    @vite(['resources/css/app.css'])
    @stack('styles')
</head>
<body class="auth-body">

<div class="auth-split-layout">
    {{-- Left Side: Clean Centered Login Form --}}
    <div class="auth-form-panel">
        <div class="auth-form-box">
            @if(session('status'))
                <div class="alert alert-info" role="alert" style="margin-bottom: 20px;">
                    <i class="bi bi-info-circle-fill"></i>
                    <span>{{ session('status') }}</span>
                </div>
            @endif
            @if(session('success'))
                <div class="alert alert-success" role="alert" style="margin-bottom: 20px;">
                    <i class="bi bi-check-circle-fill"></i>
                    <span>{{ session('success') }}</span>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger" role="alert" style="margin-bottom: 20px;">
                    <i class="bi bi-exclamation-circle-fill"></i>
                    <span>{{ session('error') }}</span>
                </div>
            @endif
            @if($errors->has('throttle'))
                <div class="alert alert-danger" role="alert" style="margin-bottom: 20px;">
                    <i class="bi bi-shield-slash-fill"></i>
                    <span>{{ $errors->first('throttle') }}</span>
                </div>
            @endif

            @yield('content')
        </div>
    </div>

    {{-- Right Side: Full Width/Height Image (/assets/login.jpg) --}}
    <div class="auth-image-panel" aria-hidden="true">
        <img src="{{ asset('assets/login.jpg') }}" alt="BlePOS Workspace" class="auth-hero-img">
        <div class="auth-hero-overlay">
            <h2 class="auth-hero-overlay-text">Finally, your workspace in one place</h2>
        </div>
    </div>
</div>

@stack('scripts')
<script>
(function () {
    'use strict';
    document.querySelectorAll('.alert').forEach(function (el) {
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
