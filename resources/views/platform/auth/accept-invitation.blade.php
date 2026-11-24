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

    <div class="ath-field">
        <label for="name">Your Full Name</label>
        <div class="ath-input-wrap">
            <i class="bi bi-person ath-icon" aria-hidden="true"></i>
            <input name="name" id="name"
                   maxlength="255"
                   autocomplete="name"
                   placeholder="John Doe"
                   required autofocus>
        </div>
    </div>

    <div class="ath-field {{ $errors->has('password') ? 'has-error' : '' }}">
        <label for="password">Password</label>
        <div class="ath-input-wrap">
            <i class="bi bi-lock ath-icon" aria-hidden="true"></i>
            <input type="password" name="password" id="password"
                   autocomplete="new-password"
                   placeholder="••••••••"
                   required>
        </div>
        @error('password')
            <div class="ath-field-error">
                <i class="bi bi-exclamation-circle"></i>
                <span>{{ $message }}</span>
            </div>
        @enderror
    </div>

    <div class="ath-field">
        <label for="password_confirmation">Confirm Password</label>
        <div class="ath-input-wrap">
            <i class="bi bi-lock-fill ath-icon" aria-hidden="true"></i>
            <input type="password" name="password_confirmation" id="password_confirmation"
                   autocomplete="new-password"
                   placeholder="••••••••"
                   required>
        </div>
    </div>

    <p style="font-size:12px;color:#64748B;margin:0 0 20px;">Use at least 14 characters with uppercase, lowercase, a number, and a symbol.</p>

    <button type="submit" class="ath-submit">
        <i class="bi bi-check-lg" style="font-size:17px;"></i>
        <span>Create Platform Account</span>
    </button>
</form>
@endsection
