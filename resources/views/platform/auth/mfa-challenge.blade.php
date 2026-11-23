@extends('platform.layout')

@section('title', 'Verify your identity')

@section('content')
    <section class="panel">
        <p>Enter the six-digit code from your authenticator app. A recovery code also works and will require MFA setup again.</p>
        <form class="form" method="post" action="{{ route('platform.mfa.verify') }}">
            @csrf
            <label>Authenticator or recovery code
                <input name="code" autocomplete="one-time-code" maxlength="32" required autofocus>
                @error('code')<span class="error">{{ $message }}</span>@enderror
            </label>
            <button type="submit">Verify</button>
        </form>
    </section>
@endsection
