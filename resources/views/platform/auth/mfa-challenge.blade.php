@extends('layouts.auth')

@section('title', 'Two-Factor Authentication')

@section('content')
<h1 class="ath-heading">Verify Identity</h1>
<p class="ath-subhead">Enter the 6-digit code from your authenticator app, or use a recovery code.</p>

<form method="post" action="{{ route('platform.mfa.verify') }}">
    @csrf

    <x-input
        type="text"
        name="code"
        id="code"
        label="Authenticator or Recovery Code"
        leadingIcon="bi-key"
        autocomplete="one-time-code"
        maxlength="32"
        :required="true"
        :autofocus="true"
    />

    <button type="submit" class="ath-submit">
        <i class="bi bi-check-circle" style="font-size:17px;"></i>
        <span>Verify &amp; Continue</span>
    </button>
</form>
@endsection
