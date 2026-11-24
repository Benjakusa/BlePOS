@extends('layouts.auth')

@section('title', 'Verify Your Identity')

@section('content')
<div class="auth-card">
    <div style="text-align: center; margin-bottom: 28px;">
        <div class="stat-icon blue" style="margin: 0 auto 14px; width: 50px; height: 50px; font-size: 22px;">
            <i class="bi bi-shield-check"></i>
        </div>
        <h1 style="font-size: 22px; font-weight: 600; color: var(--clr-text); margin: 0 0 6px;">Two-Factor Authentication</h1>
        <p class="muted" style="margin: 0; font-size: 13px;">Enter the 6-digit code from your authenticator app or a recovery code.</p>
    </div>

    <form method="post" action="{{ route('platform.mfa.verify') }}">
        @csrf

        <div class="field has-icon {{ $errors->has('code') ? 'has-error' : '' }}">
            <i class="bi bi-key field-prefix-icon" aria-hidden="true"></i>
            <input name="code" id="code" autocomplete="one-time-code" maxlength="32" placeholder="123456" required autofocus>
            <label for="code">Authenticator or Recovery Code</label>
            @error('code')
                <div class="field-error">
                    <i class="bi bi-exclamation-circle"></i>
                    <span>{{ $message }}</span>
                </div>
            @enderror
        </div>

        <button type="submit" class="btn btn-primary w-full" style="width: 100%; padding: 12px; margin-top: 8px; justify-content: center;">
            <i class="bi bi-check-circle"></i>
            <span>Verify &amp; Continue</span>
        </button>
    </form>
</div>
@endsection
