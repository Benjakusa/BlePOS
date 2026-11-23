@extends('platform.layout')

@section('title', 'Confirm sensitive action')

@section('content')
    <section class="panel">
        <p>Enter your password and current authenticator code. This confirmation is valid for five minutes.</p>
        <form class="form" method="post" action="{{ route('platform.step-up.store') }}">
            @csrf
            <label>Password
                <input name="password" type="password" autocomplete="current-password" required autofocus>
                @error('password')<span class="error">{{ $message }}</span>@enderror
            </label>
            <label>Authenticator code
                <input name="code" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" required>
                @error('code')<span class="error">{{ $message }}</span>@enderror
            </label>
            <button type="submit">Confirm identity</button>
        </form>
    </section>
@endsection
