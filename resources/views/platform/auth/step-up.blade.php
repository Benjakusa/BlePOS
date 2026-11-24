@extends('layouts.auth')

@section('title', 'Confirm Sensitive Action')

@section('content')
<h1 class="ath-heading">Confirm Identity</h1>
<p class="ath-subhead">Enter your password and current authenticator code. This confirmation is valid for five minutes.</p>

<form method="post" action="{{ route('platform.step-up.store') }}">
    @csrf

    <x-input
        type="password"
        name="password"
        id="password"
        label="Current Password"
        leadingIcon="bi-lock"
        autocomplete="current-password"
        :required="true"
        :autofocus="true"
    />

    <x-input
        type="text"
        name="code"
        id="code"
        label="6-digit Authenticator Code"
        leadingIcon="bi-key"
        inputmode="numeric"
        autocomplete="one-time-code"
        pattern="[0-9]{6}"
        maxlength="6"
        :required="true"
    />

    <button type="submit" class="ath-submit">
        <i class="bi bi-shield-check" style="font-size:17px;"></i>
        <span>Confirm Identity</span>
    </button>
</form>
@endsection
