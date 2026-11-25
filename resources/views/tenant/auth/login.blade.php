@extends('layouts.auth')

@section('title', 'Tenant Login')

@section('content')
<h1 class="ath-heading">Welcome</h1>
<p class="ath-subhead">Sign in to your business workspace.</p>

<form method="post" action="{{ route('tenant.login.store') }}">
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

    {{-- Sign In --}}
    <button type="submit" class="ath-submit">
        <span>Sign In</span>
    </button>
</form>

@endsection
