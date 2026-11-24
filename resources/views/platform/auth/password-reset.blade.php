@extends('layouts.auth')

@section('title', 'Set New Password')

@section('content')
<h1 class="ath-heading">New Password</h1>
<p class="ath-subhead">Use at least 14 characters with uppercase, lowercase, a number, and a symbol.</p>

<form method="post" action="{{ route('platform.password.update') }}">
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">

    <div class="ath-field {{ $errors->has('email') ? 'has-error' : '' }}">
        <label for="email">Email address</label>
        <div class="ath-input-wrap">
            <i class="bi bi-envelope ath-icon" aria-hidden="true"></i>
            <input type="email" name="email" id="email"
                   value="{{ old('email', $email) }}"
                   placeholder="name@example.com"
                   required>
        </div>
        @error('email')
            <div class="ath-field-error">
                <i class="bi bi-exclamation-circle"></i>
                <span>{{ $message }}</span>
            </div>
        @enderror
    </div>

    <div class="ath-field {{ $errors->has('password') ? 'has-error' : '' }}">
        <label for="password">New password</label>
        <div class="ath-input-wrap">
            <i class="bi bi-lock ath-icon" aria-hidden="true"></i>
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

    <button type="submit" class="ath-submit">
        <i class="bi bi-check-lg" style="font-size:17px;"></i>
        <span>Reset Password</span>
    </button>
</form>

<a href="{{ route('platform.login') }}" class="ath-back">&larr; Back to Sign In</a>
@endsection
