@extends('layouts.auth')

@section('title', 'Verify your account')

@section('content')
<h1 class="ath-heading">Verify your identity</h1>
<p class="ath-subhead">Enter your authenticator code or a one-time recovery code.</p>

<form method="post" action="{{ route('tenant.mfa.verify') }}" data-otp-form>
    @csrf
    <input type="hidden" name="code" data-otp-value>

    <fieldset class="otp-fieldset" data-otp-digits>
        <legend class="otp-legend">Authenticator code</legend>
        <div class="otp-digit-row" role="group" aria-label="Six-digit authenticator code">
            @for($digit = 1; $digit <= 6; $digit++)
                <input
                    class="otp-digit"
                    type="text"
                    inputmode="numeric"
                    pattern="[0-9]*"
                    maxlength="1"
                    autocomplete="{{ $digit === 1 ? 'one-time-code' : 'off' }}"
                    aria-label="Digit {{ $digit }} of 6"
                    data-otp-digit
                    @if($digit === 1) autofocus @endif
                    required
                >
            @endfor
        </div>
    </fieldset>

    <div class="otp-recovery" data-otp-recovery hidden>
        <label class="otp-legend" for="recovery-code">Recovery code</label>
        <input id="recovery-code" class="otp-recovery-input" type="text" autocomplete="one-time-code" data-otp-recovery-input>
    </div>

    @error('code')
        <p class="otp-error" role="alert">{{ $message }}</p>
    @enderror

    <button type="button" class="otp-mode-toggle" data-otp-toggle>Use a recovery code</button>
    <button type="submit" class="ath-submit"><span>Verify</span></button>
</form>
@endsection
