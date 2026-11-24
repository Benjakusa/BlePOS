@extends('platform.layout')

@section('title', 'Set Up Multi-Factor Authentication')

@section('content')
<div style="max-width: 480px; margin: 40px auto;">
    <div class="card" style="padding: 32px; text-align: center;">
        <div class="stat-icon green" style="margin: 0 auto 12px; width: 48px; height: 48px; font-size: 22px;">
            <i class="bi bi-qr-code-scan"></i>
        </div>
        <h2 class="section-title" style="font-size: 20px;">MFA Setup</h2>
        <p class="muted" style="margin-top: 6px; font-size: 13px;">Scan this QR code with your authenticator app (Google Authenticator, 1Password, Authy), then enter the 6-digit code below.</p>

        <div style="margin: 24px auto; padding: 12px; background: #FFFFFF; display: inline-block; border-radius: var(--radius-md); box-shadow: var(--shadow-sm); border: 1px solid var(--clr-border);">
            <img class="qr" alt="TOTP enrollment QR code" src="data:image/svg+xml;base64,{{ $qrSvg }}" style="width: 200px; height: 200px; display: block;">
        </div>

        <div style="background: var(--clr-grey-tint); padding: 10px 14px; border-radius: var(--radius-md); font-size: 12px; margin-bottom: 24px;">
            <span class="muted">Manual setup key:</span>
            <code style="font-weight: 600; font-size: 13px; color: var(--clr-text); user-select: all;">{{ $secret }}</code>
        </div>

        <form method="post" action="{{ route('platform.mfa.setup.confirm') }}" style="text-align: left;">
            @csrf

            <div class="field {{ $errors->has('code') ? 'has-error' : '' }}">
                <input name="code" id="code" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" placeholder=" " required autofocus>
                <label for="code">6-digit Authenticator Code</label>
                @error('code')
                    <div class="field-error">
                        <i class="bi bi-exclamation-circle"></i>
                        <span>{{ $message }}</span>
                    </div>
                @enderror
            </div>

            <button type="submit" class="btn btn-primary w-full" style="width: 100%; padding: 12px;">
                <i class="bi bi-check-lg"></i>
                <span>Confirm &amp; Finish Setup</span>
            </button>
        </form>
    </div>
</div>
@endsection
