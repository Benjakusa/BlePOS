@extends('platform.layout')

@section('title', 'Confirm Sensitive Action')

@section('content')
<div style="max-width: 440px; margin: 40px auto;">
    <div class="card" style="padding: 32px;">
        <div style="text-align: center; margin-bottom: 24px;">
            <div class="stat-icon red" style="margin: 0 auto 12px; width: 48px; height: 48px; font-size: 22px;">
                <i class="bi bi-shield-exclamation"></i>
            </div>
            <h2 class="section-title" style="font-size: 20px;">Confirm Sensitive Action</h2>
            <p class="muted" style="margin-top: 6px; font-size: 13px;">Enter your password and current authenticator code. This confirmation is valid for five minutes.</p>
        </div>

        <form method="post" action="{{ route('platform.step-up.store') }}">
            @csrf

            <div class="field {{ $errors->has('password') ? 'has-error' : '' }}">
                <input name="password" id="password" type="password" autocomplete="current-password" placeholder=" " required autofocus>
                <label for="password">Current Password</label>
                @error('password')
                    <div class="field-error">
                        <i class="bi bi-exclamation-circle"></i>
                        <span>{{ $message }}</span>
                    </div>
                @enderror
            </div>

            <div class="field {{ $errors->has('code') ? 'has-error' : '' }}">
                <input name="code" id="code" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" placeholder=" " required>
                <label for="code">6-digit Authenticator Code</label>
                @error('code')
                    <div class="field-error">
                        <i class="bi bi-exclamation-circle"></i>
                        <span>{{ $message }}</span>
                    </div>
                @enderror
            </div>

            <button type="submit" class="btn btn-primary w-full" style="width: 100%; padding: 12px; margin-top: 8px;">
                <i class="bi bi-shield-check"></i>
                <span>Confirm Identity</span>
            </button>
        </form>
    </div>
</div>
@endsection
