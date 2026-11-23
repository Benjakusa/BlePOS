@extends('platform.layout')

@section('title', 'Accept platform invitation')

@section('content')
    <section class="panel">
        <p>Invitation for <strong>{{ $invitation->email }}</strong> as {{ $invitation->role->name }}. MFA enrollment is required at first sign in.</p>
        <form class="form" method="post" action="{{ route('platform.invitation.accept.store', ['token' => $token]) }}">
            @csrf
            <label>Your name<input name="name" maxlength="255" autocomplete="name" required></label>
            <label>Password<input name="password" type="password" autocomplete="new-password" required>@error('password')<span class="error">{{ $message }}</span>@enderror</label>
            <label>Confirm password<input name="password_confirmation" type="password" autocomplete="new-password" required></label>
            <p class="muted">Use at least 14 characters with upper and lower case letters, a number, and a symbol.</p>
            <button type="submit">Create platform account</button>
        </form>
    </section>
@endsection
