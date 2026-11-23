@extends('platform.layout')

@section('title', 'Choose a new platform password')

@section('content')
    <section class="panel">
        <form class="form" method="post" action="{{ route('platform.password.update') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <label>Email address<input name="email" type="email" value="{{ old('email', $email) }}" required></label>
            <label>New password<input name="password" type="password" autocomplete="new-password" required>@error('password')<span class="error">{{ $message }}</span>@enderror</label>
            <label>Confirm password<input name="password_confirmation" type="password" autocomplete="new-password" required></label>
            <p class="muted">Use at least 14 characters with upper and lower case letters, a number, and a symbol.</p>
            <button type="submit">Reset password</button>
        </form>
    </section>
@endsection
