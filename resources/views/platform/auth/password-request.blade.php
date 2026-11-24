@extends('layouts.auth')

@section('title', 'Recovery Password')

@section('content')
<h1 class="ath-heading">Recovery Password</h1>
<p class="ath-subhead">Enter your email address and we'll send you a reset link if the account exists.</p>

<form method="post" action="{{ route('platform.password.email') }}">
    @csrf

    <x-input
        type="email"
        name="email"
        id="email"
        label="Email address"
        leadingIcon="bi-envelope"
        autocomplete="email"
        :required="true"
        :autofocus="true"
    />

    <button type="submit" class="ath-submit">
        <i class="bi bi-send" style="font-size:16px;"></i>
        <span>Send Reset Link</span>
    </button>
</form>

<a href="{{ route('platform.login') }}" class="ath-back">&larr; Back to Sign In</a>
@endsection
