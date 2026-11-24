@extends('layouts.auth')

@section('title', 'Two-Factor Authentication')

@section('content')
<h1 class="ath-heading">Verify Identity</h1>
<p class="ath-subhead">Enter the 6-digit code from your authenticator app, or use a recovery code.</p>

<form method="post" action="{{ route('platform.mfa.verify') }}">
    @csrf

    <div class="ath-field {{ $errors->has('code') ? 'has-error' : '' }}">
        <label for="code">Authenticator or Recovery Code</label>
        <div class="ath-input-wrap">
            <i class="bi bi-key ath-icon" aria-hidden="true"></i>
            <input name="code" id="code"
                   autocomplete="one-time-code"
                   maxlength="32"
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
        <i class="bi bi-check-circle" style="font-size:17px;"></i>
        <span>Verify &amp; Continue</span>
    </button>
</form>
@endsection
