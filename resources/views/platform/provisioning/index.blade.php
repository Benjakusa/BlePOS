@extends('platform.layout')

@section('title', 'Tenant Provisioning Monitor')

@section('content')
<section class="panel" style="max-width: 100%;">
    <div style="margin-bottom: 20px;">
        <h2>Provisioning Engine Runs</h2>
        <p class="muted">Automated 30-step tenant environment provisioning history, step checkpoints, and failure recovery.</p>
    </div>

    <table style="width: 100%; border-collapse: collapse; text-align: left;">
        <thead>
            <tr style="border-bottom: 2px solid #dce5df; color: #63736b;">
                <th style="padding: 10px 12px;">Run Identifier</th>
                <th style="padding: 10px 12px;">Tenant</th>
                <th style="padding: 10px 12px;">Progress</th>
                <th style="padding: 10px 12px;">Status</th>
                <th style="padding: 10px 12px;">Started</th>
                <th style="padding: 10px 12px;">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($runs as $run)
                <tr style="border-bottom: 1px solid #eef2ef;">
                    <td style="padding: 12px;"><code>{{ $run->run_identifier }}</code></td>
                    <td style="padding: 12px; font-weight: 600;">
                        <a href="{{ route('platform.tenants.show', $run->tenant) }}">{{ $run->tenant->name }}</a>
                    </td>
                    <td style="padding: 12px;">Step {{ $run->current_step }} of {{ $run->total_steps }}</td>
                    <td style="padding: 12px;">
                        <span style="font-weight: 600; {{ $run->status === 'completed' ? 'color: #146b4d;' : ($run->status === 'failed' ? 'color: #a32121;' : 'color: #b78103;') }}">
                            {{ ucfirst($run->status) }}
                        </span>
                    </td>
                    <td style="padding: 12px;">{{ $run->created_at->format('Y-m-d H:i') }}</td>
                    <td style="padding: 12px;">
                        <a href="{{ route('platform.provisioning.show', $run) }}">Inspect Steps</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" style="padding: 30px; text-align: center; color: #63736b;">No provisioning runs recorded.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div style="margin-top: 20px;">
        {{ $runs->links() }}
    </div>
</section>
@endsection
