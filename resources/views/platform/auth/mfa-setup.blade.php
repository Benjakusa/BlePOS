@extends('platform.layout')

@section('title', 'Set up multi-factor authentication')

@section('content')
    <section class="panel">
        <p>Scan this QR code with an authenticator app, then enter the six-digit code it shows.</p>
        <img class="qr" alt="TOTP enrollment QR code" src="data:image/svg+xml;base64,{{ $qrSvg }}">
        <p>Manual setup key: <code>{{ $secret }}</code></p>
        <form class="form" method="post" action="{{ route('platform.mfa.setup.confirm') }}">
            @csrf
            <label>Authenticator code
                <input name="code" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" required autofocus>
                @error('code')<span class="error">{{ $message }}</span>@enderror
            </label>
            <button type="submit">Confirm and finish setup</button>
        </form>
    </section>
@endsection
