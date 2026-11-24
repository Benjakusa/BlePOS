@extends('layouts.auth')

@section('title', 'Set New Password')

@section('content')
<div class="auth-header">
    <h1>New Password</h1>
    <p>Use at least 14 characters with upper and lower case letters, a number, and a symbol.</p>
</div>

<form method="post" action="{{ route('platform.password.update') }}">
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">

    <div class="field has-icon {{ $errors->has('email') ? 'has-error' : '' }}">
        <i class="bi bi-envelope field-prefix-icon" aria-hidden="true"></i>
        <input name="email" id="email" type="email" value="{{ old('email', $email) }}" placeholder="Enter your email" required>
        <label for="email">Email address</label>
        @error('email')
            <div class="field-error">
                <i class="bi bi-exclamation-circle"></i>
                <span>{{ $message }}</span>
            </div>
        @enderror
    </div>

    <div class="field has-icon {{ $errors->has('password') ? 'has-error' : '' }}">
        <i class="bi bi-lock field-prefix-icon" aria-hidden="true"></i>
        <input name="password" id="password" type="password" autocomplete="new-password" placeholder="••••••••" required>
        <label for="password">New password</label>
        @error('password')
            <div class="field-error">
                <i class="bi bi-exclamation-circle"></i>
                <span>{{ $message }}</span>
            </div>
        @enderror
    </div>

    <div class="field has-icon">
        <i class="bi bi-lock-fill field-prefix-icon" aria-hidden="true"></i>
        <input name="password_confirmation" id="password_confirmation" type="password" autocomplete="new-password" placeholder="••••••••" required>
        <label for="password_confirmation">Confirm password</label>
    </div>

    <button type="submit" class="btn auth-submit-btn">
        <i class="bi bi-check-lg"></i>
        <span>Reset Password</span>
    </button>
</form>

<div style="text-align: center; margin-top: 24px;">
    <a href="{{ route('platform.login') }}" class="auth-recovery-link">&larr; Back to Sign In</a>
</div>
@endsection
