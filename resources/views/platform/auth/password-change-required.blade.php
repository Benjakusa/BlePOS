@extends('platform.layout')

@section('title', 'Password Change Required')

@section('content')
<div style="max-width: 440px; margin: 40px auto;">
    <div class="card" style="padding: 32px;">
        <div style="text-align: center; margin-bottom: 24px;">
            <div class="stat-icon red" style="margin: 0 auto 12px; width: 48px; height: 48px; font-size: 22px;">
                <i class="bi bi-shield-lock"></i>
            </div>
            <h2 class="section-title" style="font-size: 20px;">Password Change Required</h2>
            <p class="muted" style="margin-top: 6px; font-size: 13px;">An administrator requires a password change before you can use the platform console.</p>
        </div>

        <form method="post" action="{{ route('platform.password.change.store') }}">
            @csrf

            <div class="field {{ $errors->has('current_password') ? 'has-error' : '' }}">
                <input name="current_password" id="current_password" type="password" autocomplete="current-password" placeholder=" " required autofocus>
                <label for="current_password">Current password</label>
                @error('current_password')
                    <div class="field-error">
                        <i class="bi bi-exclamation-circle"></i>
                        <span>{{ $message }}</span>
                    </div>
                @enderror
            </div>

            <div class="field {{ $errors->has('password') ? 'has-error' : '' }}">
                <input name="password" id="password" type="password" autocomplete="new-password" placeholder=" " required>
                <label for="password">New password</label>
                @error('password')
                    <div class="field-error">
                        <i class="bi bi-exclamation-circle"></i>
                        <span>{{ $message }}</span>
                    </div>
                @enderror
            </div>

            <div class="field">
                <input name="password_confirmation" id="password_confirmation" type="password" autocomplete="new-password" placeholder=" " required>
                <label for="password_confirmation">Confirm password</label>
            </div>

            <p class="muted" style="margin-bottom: 20px; font-size: 12px;">Use at least 14 characters with upper and lower case letters, a number, and a symbol.</p>

            <button type="submit" class="btn btn-primary w-full" style="width: 100%; padding: 12px;">
                <i class="bi bi-check-lg"></i>
                <span>Change Password &amp; Continue</span>
            </button>
        </form>
    </div>
</div>
@endsection
