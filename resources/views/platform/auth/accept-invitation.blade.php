@extends('layouts.auth')

@section('title', 'Accept Platform Invitation')

@section('content')
<h1 class="ath-heading">Accept Invitation</h1>
<p class="ath-subhead">
    Invitation for <strong>{{ $invitation->email }}</strong> as <strong>{{ $invitation->role->name }}</strong>.
    MFA enrollment is required at first sign in.
</p>

<form method="post" action="{{ route('platform.invitation.accept.store', ['token' => $token]) }}">
    @csrf

    <x-input
        type="text"
        name="name"
        id="name"
        label="Your Full Name"
        leadingIcon="bi-person"
        autocomplete="name"
        maxlength="255"
        :required="true"
        :autofocus="true"
    />

    <x-input
        type="password"
        name="password"
        id="password"
        label="Password"
        leadingIcon="bi-lock"
        autocomplete="new-password"
        :required="true"
    />

    <x-input
        type="password"
        name="password_confirmation"
        id="password_confirmation"
        label="Confirm Password"
        leadingIcon="bi-lock-fill"
        autocomplete="new-password"
        :required="true"
    />

    <p style="font-size:12px;color:#64748B;margin:0 0 20px;">Use at least 14 characters with uppercase, lowercase, a number, and a symbol.</p>

    <button type="submit" class="ath-submit">
        <i class="bi bi-check-lg" style="font-size:17px;"></i>
        <span>Create Platform Account</span>
    </button>
</form>
@endsection
