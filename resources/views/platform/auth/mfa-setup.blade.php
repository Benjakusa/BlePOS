@extends('layouts.auth')

@section('title', 'MFA Setup')

@section('content')
<h1 class="ath-heading">MFA Setup</h1>
<p class="ath-subhead">Scan this QR code with your authenticator app (Google Authenticator, Authy, 1Password), then enter the code below.</p>

{{-- QR Code --}}
<div style="display:flex;justify-content:center;margin-bottom:20px;">
    <div style="padding:12px;background:#ffffff;border-radius:12px;box-shadow:0 1px 4px rgba(0,0,0,0.1);border:1px solid #E2E8F0;display:inline-block;">
        <img class="qr" alt="TOTP enrollment QR code"
             src="data:image/svg+xml;base64,{{ $qrSvg }}"
             style="width:200px;height:200px;display:block;">
    </div>
</div>

{{-- Manual key --}}
<div style="background:#F6F8FB;padding:12px 14px;border-radius:10px;font-size:12px;margin-bottom:24px;text-align:center;">
    <span style="color:#64748B;">Manual setup key: </span>
    <code style="font-weight:600;font-size:13px;color:#0F172A;user-select:all;">{{ $secret }}</code>
</div>

<form method="post" action="{{ route('platform.mfa.setup.confirm') }}">
    @csrf

    <div class="ath-field {{ $errors->has('code') ? 'has-error' : '' }}">
        <label for="code">6-digit Authenticator Code</label>
        <div class="ath-input-wrap">
            <i class="bi bi-shield-lock ath-icon" aria-hidden="true"></i>
            <input name="code" id="code"
                   inputmode="numeric"
                   autocomplete="one-time-code"
                   pattern="[0-9]{6}"
                   maxlength="6"
                   placeholder="123456"
                   required autofocus>
        </div>
        @error('code')
            <div class="ath-field-error">
                <i class="bi bi-exclamation-circle"></i>
                <span>{{ $message }}</span>
            </div>
        @enderror
    </div>

    <button type="submit" class="ath-submit">
        <i class="bi bi-check-lg" style="font-size:17px;"></i>
        <span>Confirm &amp; Finish Setup</span>
    </button>
</form>
@endsection
