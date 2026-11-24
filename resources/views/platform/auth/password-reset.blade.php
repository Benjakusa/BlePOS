@extends('layouts.auth')

@section('title', 'Set New Password')

@section('content')
<h1 class="ath-heading">New Password</h1>
<p class="ath-subhead">Use at least 14 characters with uppercase, lowercase, a number, and a symbol.</p>

<form method="post" action="{{ route('platform.password.update') }}">
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">

    <x-input
        type="email"
        name="email"
        id="email"
        label="Email address"
        leadingIcon="bi-envelope"
        :value="$email"
        :required="true"
    />

    <x-input
        type="password"
        name="password"
        id="password"
        label="New password"
        leadingIcon="bi-lock"
        autocomplete="new-password"
        :required="true"
    />

    <x-input
        type="password"
        name="password_confirmation"
        id="password_confirmation"
        label="Confirm password"
        leadingIcon="bi-lock-fill"
        autocomplete="new-password"
        :required="true"
    />

    <button type="submit" class="ath-submit">
        <i class="bi bi-check-lg" style="font-size:17px;"></i>
        <span>Reset Password</span>
    </button>
</form>

<a href="{{ route('platform.login') }}" class="ath-back">&larr; Back to Sign In</a>
@endsection
