@extends('platform.layout')

@section('title', 'Database Migrations')

@section('content')
<header>
    <div>
        <h1>Database Migrations & Schema Versions</h1>
        <div class="muted">Central platform and tenant schema migration history (Annex A 11.3)</div>
    </div>
    <div>
        <a href="{{ route('platform.infrastructure.index') }}" class="btn secondary">Back to Infrastructure</a>
    </div>
</header>

<div class="panel" style="margin-bottom: 24px;">
    <h2>Migration Status Overview</h2>
    <p><strong>Total Executed Migrations:</strong> {{ $migrationStatus['total_ran'] }}</p>
    <p><strong>Latest Migration Batch:</strong> Batch #{{ $migrationStatus['last_batch'] }}</p>
    <p><strong>Pending Migrations:</strong>
        @if(empty($migrationStatus['pending_migrations']))
            <span class="badge" style="background: #EAEEF3; color: var(--clr-green);">UP TO DATE (0 PENDING)</span>
        @else
            <span class="badge" style="background: #EAEEF3; color: var(--clr-red);">{{ count($migrationStatus['pending_migrations']) }} PENDING</span>
        @endif
    </p>
</div>

<div class="panel">
    <h2>Executed Migration History</h2>
    <table style="width: 100%; border-collapse: collapse; margin-top: 12px;">
        <thead>
            <tr style="border-bottom: 1px solid #e2e8f0;">
                <th style="text-align: left; padding: 8px;">Migration File</th>
                <th style="text-align: center; padding: 8px;">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($migrationStatus['ran_migrations'] as $mig)
                <tr style="border-bottom: 1px solid #f1f5f9;">
                    <td style="padding: 8px;"><code>{{ $mig }}</code></td>
                    <td style="text-align: center; padding: 8px;">
                        <span class="badge" style="background: #EAEEF3; color: var(--clr-green);">MIGRATED</span>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
