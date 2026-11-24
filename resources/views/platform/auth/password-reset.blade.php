@extends('platform.layout')

@section('title', 'Choose a New Platform Password')

@section('content')
<div style="max-width: 440px; margin: 40px auto;">
    <div class="card" style="padding: 32px;">
        <div style="text-align: center; margin-bottom: 24px;">
            <div class="stat-icon green" style="margin: 0 auto 12px; width: 48px; height: 48px; font-size: 22px;">
                <i class="bi bi-key"></i>
            </div>
            <h2 class="section-title" style="font-size: 20px;">New Password</h2>
            <p class="muted" style="margin-top: 6px; font-size: 13px;">Use at least 14 characters with upper and lower case letters, a number, and a symbol.</p>
        </div>

        <form method="post" action="{{ route('platform.password.update') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <div class="field {{ $errors->has('email') ? 'has-error' : '' }}">
                <input name="email" id="email" type="email" value="{{ old('email', $email) }}" placeholder=" " required>
                <label for="email">Email address</label>
                @error('email')
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

            <button type="submit" class="btn btn-primary w-full" style="width: 100%; padding: 12px; margin-top: 8px;">
                <i class="bi bi-check-lg"></i>
                <span>Reset Password</span>
            </button>
        </form>
    </div>
</div>
@endsection
