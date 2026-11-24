@extends('platform.layout')

@section('title', 'Verify Your Identity')

@section('content')
<div style="max-width: 440px; margin: 40px auto;">
    <div class="card" style="padding: 32px;">
        <div style="text-align: center; margin-bottom: 24px;">
            <div class="stat-icon blue" style="margin: 0 auto 12px; width: 48px; height: 48px; font-size: 22px;">
                <i class="bi bi-phone"></i>
            </div>
            <h2 class="section-title" style="font-size: 20px;">Two-Factor Authentication</h2>
            <p class="muted" style="margin-top: 6px; font-size: 13px;">Enter the 6-digit code from your authenticator app. A recovery code also works and will require MFA setup again.</p>
        </div>

        <form method="post" action="{{ route('platform.mfa.verify') }}">
            @csrf

            <div class="field {{ $errors->has('code') ? 'has-error' : '' }}">
                <input name="code" id="code" autocomplete="one-time-code" maxlength="32" placeholder=" " required autofocus>
                <label for="code">Authenticator or Recovery Code</label>
                @error('code')
                    <div class="field-error">
                        <i class="bi bi-exclamation-circle"></i>
                        <span>{{ $message }}</span>
                    </div>
                @enderror
            </div>

            <button type="submit" class="btn btn-primary w-full" style="width: 100%; padding: 12px; margin-top: 8px;">
                <i class="bi bi-check-circle"></i>
                <span>Verify &amp; Continue</span>
            </button>
        </form>
    </div>
</div>
@endsection
