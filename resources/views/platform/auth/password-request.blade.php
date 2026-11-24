@extends('layouts.auth')

@section('title', 'Reset Password')

@section('content')
<div class="auth-header">
    <h1>Reset Password</h1>
    <p>Enter your email address to receive a recovery password link.</p>
</div>

<form method="post" action="{{ route('platform.password.email') }}">
    @csrf

    <div class="field has-icon {{ $errors->has('email') ? 'has-error' : '' }}">
        <i class="bi bi-envelope field-prefix-icon" aria-hidden="true"></i>
        <input name="email" id="email" type="email" autocomplete="email" value="{{ old('email') }}" placeholder="Enter your email" required autofocus>
        <label for="email">Email address</label>
        @error('email')
            <div class="field-error">
                <i class="bi bi-exclamation-circle"></i>
                <span>{{ $message }}</span>
            </div>
        @enderror
    </div>

    <button type="submit" class="btn auth-submit-btn">
        <i class="bi bi-send"></i>
        <span>Send Reset Link</span>
    </button>
</form>

<div style="text-align: center; margin-top: 24px;">
    <a href="{{ route('platform.login') }}" class="auth-recovery-link">&larr; Back to Sign In</a>
</div>
@endsection
