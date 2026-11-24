@extends('layouts.auth')

@section('title', 'Welcome')

@section('content')
<div class="auth-header">
    <h1>Welcome</h1>
    <p>Welcome back! Please enter your details.</p>
</div>

<form method="post" action="{{ route('platform.login.store') }}">
    @csrf

    <div class="field has-icon {{ $errors->has('email') ? 'has-error' : '' }}">
        <i class="bi bi-envelope field-prefix-icon" aria-hidden="true"></i>
        <input name="email" id="email" type="email" autocomplete="username" value="{{ old('email') }}" placeholder="Enter your email" required autofocus>
        <label for="email">Email</label>
        @error('email')
            <div class="field-error">
                <i class="bi bi-exclamation-circle"></i>
                <span>{{ $message }}</span>
            </div>
        @enderror
    </div>

    <div class="field has-icon {{ $errors->has('password') ? 'has-error' : '' }}" style="margin-bottom: 12px;">
        <i class="bi bi-lock field-prefix-icon" aria-hidden="true"></i>
        <input name="password" id="password" type="password" autocomplete="current-password" placeholder="••••••••" required>
        <label for="password">Password</label>
        @error('password')
            <div class="field-error">
                <i class="bi bi-exclamation-circle"></i>
                <span>{{ $message }}</span>
            </div>
        @enderror
    </div>

    <div class="auth-recovery-wrap">
        <a href="{{ route('platform.password.request') }}" class="auth-recovery-link">Recovery Password</a>
    </div>

    <button type="submit" class="btn auth-submit-btn">
        <span>Sign In</span>
    </button>
</form>

<div class="auth-divider">
    <span>Or continue with</span>
</div>

<div class="auth-social-row">
    <a href="#" class="auth-social-btn google" aria-label="Sign in with Google" title="Sign in with Google" onclick="event.preventDefault();">
        <i class="bi bi-google"></i>
    </a>
    <a href="#" class="auth-social-btn apple" aria-label="Sign in with Apple" title="Sign in with Apple" onclick="event.preventDefault();">
        <i class="bi bi-apple"></i>
    </a>
    <a href="#" class="auth-social-btn facebook" aria-label="Sign in with Facebook" title="Sign in with Facebook" onclick="event.preventDefault();">
        <i class="bi bi-facebook"></i>
    </a>
</div>
@endsection
