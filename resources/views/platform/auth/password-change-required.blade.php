@extends('layouts.auth')

@section('title', 'Password Change Required')

@section('content')
<h1 class="ath-heading">Change Password</h1>
<p class="ath-subhead">An administrator requires a password change before you can access the platform console.</p>

<form method="post" action="{{ route('platform.password.change.store') }}">
    @csrf

    <div class="ath-field {{ $errors->has('current_password') ? 'has-error' : '' }}">
        <label for="current_password">Current password</label>
        <div class="ath-input-wrap">
            <i class="bi bi-lock ath-icon" aria-hidden="true"></i>
            <input type="password" name="current_password" id="current_password"
                   autocomplete="current-password"
                   placeholder="••••••••"
                   required autofocus>
        </div>
        @error('current_password')
            <div class="ath-field-error">
                <i class="bi bi-exclamation-circle"></i>
                <span>{{ $message }}</span>
            </div>
        @enderror
    </div>

    <div class="ath-field {{ $errors->has('password') ? 'has-error' : '' }}">
        <label for="password">New password</label>
        <div class="ath-input-wrap">
            <i class="bi bi-lock-fill ath-icon" aria-hidden="true"></i>
            <input type="password" name="password" id="password"
                   autocomplete="new-password"
                   placeholder="••••••••"
                   required>
        </div>
        @error('password')
            <div class="ath-field-error">
                <i class="bi bi-exclamation-circle"></i>
                <span>{{ $message }}</span>
            </div>
        @enderror
    </div>

    <div class="ath-field">
        <label for="password_confirmation">Confirm password</label>
        <div class="ath-input-wrap">
            <i class="bi bi-lock-fill ath-icon" aria-hidden="true"></i>
            <input type="password" name="password_confirmation" id="password_confirmation"
                   autocomplete="new-password"
                   placeholder="••••••••"
                   required>
        </div>
    </div>

    <p style="font-size:12px;color:#64748B;margin:0 0 20px;">Use at least 14 characters with uppercase, lowercase, a number, and a symbol.</p>

    <button type="submit" class="ath-submit">
        <i class="bi bi-check-lg" style="font-size:17px;"></i>
        <span>Change Password &amp; Continue</span>
    </button>
</form>
@endsection
