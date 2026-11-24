@extends('layouts.auth')

@section('title', 'Recovery Password')

@section('content')
<h1 class="ath-heading">Recovery Password</h1>
<p class="ath-subhead">Enter your email address and we'll send you a reset link if the account exists.</p>

<form method="post" action="{{ route('platform.password.email') }}">
    @csrf

    <div class="ath-field {{ $errors->has('email') ? 'has-error' : '' }}">
        <label for="email">Email address</label>
        <div class="ath-input-wrap">
            <i class="bi bi-envelope ath-icon" aria-hidden="true"></i>
            <input type="email" name="email" id="email"
                   autocomplete="email"
                   value="{{ old('email') }}"
                   placeholder="name@example.com"
                   required autofocus>
        </div>
        @error('email')
            <div class="ath-field-error">
                <i class="bi bi-exclamation-circle"></i>
                <span>{{ $message }}</span>
            </div>
        @enderror
    </div>

    <button type="submit" class="ath-submit">
        <i class="bi bi-send" style="font-size:16px;"></i>
        <span>Send Reset Link</span>
    </button>
</form>

<a href="{{ route('platform.login') }}" class="ath-back">&larr; Back to Sign In</a>
@endsection
