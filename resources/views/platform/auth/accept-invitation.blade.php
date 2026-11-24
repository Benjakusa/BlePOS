@extends('platform.layout')

@section('title', 'Accept Platform Invitation')

@section('content')
<div style="max-width: 460px; margin: 40px auto;">
    <div class="card" style="padding: 32px;">
        <div style="text-align: center; margin-bottom: 24px;">
            <div class="stat-icon green" style="margin: 0 auto 12px; width: 48px; height: 48px; font-size: 22px;">
                <i class="bi bi-person-plus"></i>
            </div>
            <h2 class="section-title" style="font-size: 20px;">Accept Platform Invitation</h2>
            <p class="muted" style="margin-top: 6px; font-size: 13px;">
                Invitation for <strong>{{ $invitation->email }}</strong> as <strong>{{ $invitation->role->name }}</strong>. MFA enrollment is required at first sign in.
            </p>
        </div>

        <form method="post" action="{{ route('platform.invitation.accept.store', ['token' => $token]) }}">
            @csrf

            <div class="field">
                <input name="name" id="name" maxlength="255" autocomplete="name" placeholder=" " required autofocus>
                <label for="name">Your Full Name</label>
            </div>

            <div class="field {{ $errors->has('password') ? 'has-error' : '' }}">
                <input name="password" id="password" type="password" autocomplete="new-password" placeholder=" " required>
                <label for="password">Password</label>
                @error('password')
                    <div class="field-error">
                        <i class="bi bi-exclamation-circle"></i>
                        <span>{{ $message }}</span>
                    </div>
                @enderror
            </div>

            <div class="field">
                <input name="password_confirmation" id="password_confirmation" type="password" autocomplete="new-password" placeholder=" " required>
                <label for="password_confirmation">Confirm Password</label>
            </div>

            <p class="muted" style="margin-bottom: 20px; font-size: 12px;">Use at least 14 characters with upper and lower case letters, a number, and a symbol.</p>

            <button type="submit" class="btn btn-primary w-full" style="width: 100%; padding: 12px;">
                <i class="bi bi-check-lg"></i>
                <span>Create Platform Account</span>
            </button>
        </form>
    </div>
</div>
@endsection
