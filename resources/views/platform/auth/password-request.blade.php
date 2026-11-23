@extends('platform.layout')

@section('title', 'Reset platform password')

@section('content')
    <section class="panel">
        <p>Enter your platform staff email address. If the account exists, we will send a reset link.</p>
        <form class="form" method="post" action="{{ route('platform.password.email') }}">
            @csrf
            <label>Email address<input name="email" type="email" autocomplete="email" value="{{ old('email') }}" required autofocus>@error('email')<span class="error">{{ $message }}</span>@enderror</label>
            <button type="submit">Send reset link</button>
        </form>
    </section>
@endsection
