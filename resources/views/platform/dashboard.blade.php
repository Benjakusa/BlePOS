@extends('platform.layout')

@section('title', 'Platform dashboard')

@section('content')
    @if (session('platform_recovery_codes'))
        <section class="panel">
            <h2>Save your recovery codes</h2>
            <p>These codes are shown once. Each code works once and resets your authenticator enrollment.</p>
            <ul>@foreach(session('platform_recovery_codes') as $code)<li><code>{{ $code }}</code></li>@endforeach</ul>
        </section>
    @endif
    <section class="panel">
        <h2>Control plane is ready</h2>
        <p class="muted">Your platform session is protected by multi-factor authentication. Dashboard metrics will appear as their underlying data sources are implemented.</p>
        <p>Assigned roles: {{ $user->roles()->orderBy('name')->pluck('name')->join(', ') ?: 'None' }}</p>
    </section>
@endsection
