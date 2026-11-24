@extends('layouts.auth')

@section('title', 'Welcome')

@section('content')
<h1 class="ath-heading">Welcome</h1>
<p class="ath-subhead">Welcome back! Please enter your details.</p>

<form method="post" action="{{ route('platform.login.store') }}">
    @csrf

    <x-input
        type="email"
        name="email"
        id="email"
        label="Email"
        leadingIcon="bi-envelope"
        autocomplete="username"
        :required="true"
        :autofocus="true"
    />

    <x-input
        type="password"
        name="password"
        id="password"
        label="Password"
        leadingIcon="bi-lock"
        autocomplete="current-password"
        :required="true"
    />

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
