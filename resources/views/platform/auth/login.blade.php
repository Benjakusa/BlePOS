@extends('platform.layout')

@section('title', 'Platform Staff Sign In')

@section('content')
<div style="max-width: 440px; margin: 40px auto;">
    <div class="card" style="padding: 32px;">
        <div style="text-align: center; margin-bottom: 24px;">
            <div class="stat-icon green" style="margin: 0 auto 12px; width: 48px; height: 48px; font-size: 22px;">
                <i class="bi bi-shield-lock"></i>
            </div>
            <h2 class="section-title" style="font-size: 20px;">Platform Staff Sign In</h2>
            <p class="muted" style="margin-top: 6px; font-size: 13px;">Sign in with your platform staff account. Tenant accounts cannot access this console.</p>
        </div>

        <form method="post" action="{{ route('platform.login.store') }}">
            @csrf

            <div class="field {{ $errors->has('email') ? 'has-error' : '' }}">
                <input name="email" id="email" type="email" autocomplete="username" value="{{ old('email') }}" placeholder=" " required autofocus>
                <label for="email">Email address</label>
                @error('email')
                    <div class="field-error">
                        <i class="bi bi-exclamation-circle"></i>
                        <span>{{ $message }}</span>
                    </div>
                @enderror
            </div>

            <div class="field {{ $errors->has('password') ? 'has-error' : '' }}">
                <input name="password" id="password" type="password" autocomplete="current-password" placeholder=" " required>
                <label for="password">Password</label>
                @error('password')
                    <div class="field-error">
                        <i class="bi bi-exclamation-circle"></i>
                        <span>{{ $message }}</span>
                    </div>
                @enderror
            </div>

            <button type="submit" class="btn btn-primary w-full" style="width: 100%; padding: 12px; margin-top: 8px;">
                <i class="bi bi-box-arrow-in-right"></i>
                <span>Continue to MFA</span>
            </button>
        </form>

        <div style="text-align: center; margin-top: 20px;">
            <a href="{{ route('platform.password.request') }}" style="font-size: 13px;">Forgot your password?</a>
        </div>
    </div>
</div>
@endsection
