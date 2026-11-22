@extends('platform.layout')

@section('title', 'Password change required')

@section('content')
    <section class="panel">
        <p>An administrator requires a password change before you can use the console.</p>
        <form class="form" method="post" action="{{ route('platform.password.change.store') }}">
            @csrf
            <label>Current password<input name="current_password" type="password" autocomplete="current-password" required>@error('current_password')<span class="error">{{ $message }}</span>@enderror</label>
            <label>New password<input name="password" type="password" autocomplete="new-password" required>@error('password')<span class="error">{{ $message }}</span>@enderror</label>
            <label>Confirm password<input name="password_confirmation" type="password" autocomplete="new-password" required></label>
            <p class="muted">Use at least 14 characters with upper and lower case letters, a number, and a symbol.</p>
            <button type="submit">Change password and continue to MFA</button>
        </form>
    </section>
@endsection
