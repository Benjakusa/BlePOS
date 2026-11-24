@extends('layouts.auth')

@section('title', 'Password Change Required')

@section('content')
<h1 class="ath-heading">Change Password</h1>
<p class="ath-subhead">An administrator requires a password change before you can access the platform console.</p>

<form method="post" action="{{ route('platform.password.change.store') }}">
    @csrf

    <x-input
        type="password"
        name="current_password"
        id="current_password"
        label="Current password"
        leadingIcon="bi-lock"
        autocomplete="current-password"
        :required="true"
        :autofocus="true"
    />

    <x-input
        type="password"
        name="password"
        id="password"
        label="New password"
        leadingIcon="bi-lock-fill"
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

    <p style="font-size:12px;color:#64748B;margin:0 0 20px;">Use at least 14 characters with uppercase, lowercase, a number, and a symbol.</p>

    <button type="submit" class="ath-submit">
        <i class="bi bi-check-lg" style="font-size:17px;"></i>
        <span>Change Password &amp; Continue</span>
    </button>
</form>
@endsection
