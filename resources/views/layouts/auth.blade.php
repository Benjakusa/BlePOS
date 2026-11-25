<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Welcome') · BlePOS</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')


</head>
<body class="auth-page">

<div class="ath-wrap">
    {{-- LEFT: Login form --}}
    <div class="ath-form-side">
        <div class="ath-form-inner">
            <div class="ath-auth-card">
                <a class="ath-brand" href="{{ route('platform.login') }}" aria-label="BlePOS sign in">
                    <span class="ath-brand-mark" aria-hidden="true"><i class="bi bi-bluetooth"></i></span>
                    <span>BlePOS</span>
                </a>

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
    </div>

    {{-- RIGHT: Inset hero image card --}}
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
            el.classList.add('motion-closing');
            setTimeout(function () { el.remove(); }, window.BleMotion.duration('--dur-base'));
        }, window.BleMotion.duration('--delay-toast-dismiss'));
    });
})();
</script>
</body>
</html>
