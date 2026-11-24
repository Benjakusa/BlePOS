@extends('platform.layout')

@section('title', 'Reset Platform Password')

@section('content')
<div style="max-width: 440px; margin: 40px auto;">
    <div class="card" style="padding: 32px;">
        <div style="text-align: center; margin-bottom: 24px;">
            <div class="stat-icon blue" style="margin: 0 auto 12px; width: 48px; height: 48px; font-size: 22px;">
                <i class="bi bi-envelope"></i>
            </div>
            <h2 class="section-title" style="font-size: 20px;">Reset Password</h2>
            <p class="muted" style="margin-top: 6px; font-size: 13px;">Enter your platform staff email address. If the account exists, we will send a password reset link.</p>
        </div>

        <form method="post" action="{{ route('platform.password.email') }}">
            @csrf

            <div class="field {{ $errors->has('email') ? 'has-error' : '' }}">
                <input name="email" id="email" type="email" autocomplete="email" value="{{ old('email') }}" placeholder=" " required autofocus>
                <label for="email">Email address</label>
                @error('email')
                    <div class="field-error">
                        <i class="bi bi-exclamation-circle"></i>
                        <span>{{ $message }}</span>
                    </div>
                @enderror
            </div>

            <button type="submit" class="btn btn-primary w-full" style="width: 100%; padding: 12px; margin-top: 8px;">
                <i class="bi bi-send"></i>
                <span>Send Reset Link</span>
            </button>
        </form>

        <div style="text-align: center; margin-top: 20px;">
            <a href="{{ route('platform.login') }}" style="font-size: 13px;">&larr; Back to sign in</a>
        </div>
    </div>
</div>
@endsection
