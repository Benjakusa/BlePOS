@extends('platform.layout')

@section('title', 'Platform staff sign in')

@section('content')
    <section class="panel">
        <p class="muted">Sign in with your platform staff account. Tenant accounts cannot access this console.</p>
        <form class="form" method="post" action="{{ route('platform.login.store') }}">
            @csrf
            <label>Email address
                <input name="email" type="email" autocomplete="username" value="{{ old('email') }}" required autofocus>
                @error('email')<span class="error">{{ $message }}</span>@enderror
            </label>
            <label>Password
                <input name="password" type="password" autocomplete="current-password" required>
                @error('password')<span class="error">{{ $message }}</span>@enderror
            </label>
            <button type="submit">Continue to MFA</button>
        </form>
        <p><a href="{{ route('platform.password.request') }}">Forgot your password?</a></p>
    </section>
@endsection
