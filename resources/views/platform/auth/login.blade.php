@extends('layouts.auth')

@section('title', 'Welcome')

@section('content')
<h1 class="ath-heading">Welcome</h1>
<p class="ath-subhead">Welcome back! Please enter your details.</p>

<form method="post" action="{{ route('platform.login.store') }}">
    @csrf

    {{-- Email --}}
    <div class="ath-field {{ $errors->has('email') ? 'has-error' : '' }}">
        <label for="email">Email</label>
        <div class="ath-input-wrap">
            <i class="bi bi-envelope ath-icon" aria-hidden="true"></i>
            <input type="email" name="email" id="email"
                   autocomplete="username"
                   value="{{ old('email') }}"
                   placeholder="name@example.com"
                   required autofocus>
        </div>
        @error('email')
            <div class="ath-field-error">
                <i class="bi bi-exclamation-circle"></i>
                <span>{{ $message }}</span>
            </div>
        @enderror
    </div>

    {{-- Password --}}
    <div class="ath-field {{ $errors->has('password') ? 'has-error' : '' }}">
        <label for="password">Password</label>
        <div class="ath-input-wrap">
            <i class="bi bi-lock ath-icon" aria-hidden="true"></i>
            <input type="password" name="password" id="password"
                   autocomplete="current-password"
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

    {{-- Forgot password --}}
    <div class="ath-recover-row">
        <a href="{{ route('platform.password.request') }}">Recovery Password</a>
    </div>

    {{-- Sign In --}}
    <button type="submit" class="ath-submit">
        <span>Sign In</span>
    </button>
</form>

{{-- Social divider --}}
<div class="ath-divider"><span>Or continue with</span></div>

<div class="ath-social-row">
    <a href="#" class="ath-social-btn google" aria-label="Sign in with Google" title="Sign in with Google" onclick="event.preventDefault();">
        <i class="bi bi-google"></i>
    </a>
    <a href="#" class="ath-social-btn apple" aria-label="Sign in with Apple" title="Sign in with Apple" onclick="event.preventDefault();">
        <i class="bi bi-apple"></i>
    </a>
    <a href="#" class="ath-social-btn facebook" aria-label="Sign in with Facebook" title="Sign in with Facebook" onclick="event.preventDefault();">
        <i class="bi bi-facebook"></i>
    </a>
</div>
@endsection
