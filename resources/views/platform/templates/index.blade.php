@extends('platform.layout')

@section('title', 'Industry Templates')

@section('content')
<header>
    <div>
        <h1>Industry Templates</h1>
        <div class="muted">Pre-configured module blueprints — apply to a tenant to enable all required modules in dependency order.</div>
    </div>
</header>

@if(session('notice'))
    <div class="notice" role="alert">{{ session('notice') }}</div>
@endif

<table>
    <thead>
        <tr>
            <th>Template</th>
            <th>Slug</th>
            <th>Default modules</th>
            <th>Description</th>
        </tr>
    </thead>
    <tbody>
        @forelse($templates as $template)
            <tr>
                <td><strong>{{ $template->name }}</strong></td>
                <td><code>{{ $template->slug }}</code></td>
                <td>
                    <span class="badge">{{ count($template->default_modules ?? []) }} modules</span>
                </td>
                <td>{{ $template->description ?? '—' }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="4" class="muted" style="text-align:center;">No industry templates defined.</td>
            </tr>
        @endforelse
    </tbody>
</table>
@endsection
