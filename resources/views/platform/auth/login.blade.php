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
        <a href="{{ route('platform.password.request') }}">Forgot Password?</a>
    </div>

    {{-- Sign In --}}
    <button type="submit" class="ath-submit">
        <span>Sign In</span>
    </button>
</form>

@endsection
