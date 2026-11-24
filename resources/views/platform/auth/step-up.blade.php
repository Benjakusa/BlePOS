@extends('layouts.auth')

@section('title', 'Confirm Sensitive Action')

@section('content')
<h1 class="ath-heading">Confirm Identity</h1>
<p class="ath-subhead">Enter your password and current authenticator code. This confirmation is valid for five minutes.</p>

<form method="post" action="{{ route('platform.step-up.store') }}">
    @csrf

    <div class="ath-field {{ $errors->has('password') ? 'has-error' : '' }}">
        <label for="password">Current Password</label>
        <div class="ath-input-wrap">
            <i class="bi bi-lock ath-icon" aria-hidden="true"></i>
            <input type="password" name="password" id="password"
                   autocomplete="current-password"
                   placeholder="••••••••"
                   required autofocus>
        </div>
        @error('password')
            <div class="ath-field-error">
                <i class="bi bi-exclamation-circle"></i>
                <span>{{ $message }}</span>
            </div>
        @enderror
    </div>

    <div class="ath-field {{ $errors->has('code') ? 'has-error' : '' }}">
        <label for="code">6-digit Authenticator Code</label>
        <div class="ath-input-wrap">
            <i class="bi bi-key ath-icon" aria-hidden="true"></i>
            <input name="code" id="code"
                   inputmode="numeric"
                   autocomplete="one-time-code"
                   pattern="[0-9]{6}"
                   maxlength="6"
                   placeholder="123456"
                   required>
        </div>
        @error('code')
            <div class="ath-field-error">
                <i class="bi bi-exclamation-circle"></i>
                <span>{{ $message }}</span>
            </div>
        @enderror
    </div>

    <button type="submit" class="ath-submit">
        <i class="bi bi-shield-check" style="font-size:17px;"></i>
        <span>Confirm Identity</span>
    </button>
</form>
@endsection
