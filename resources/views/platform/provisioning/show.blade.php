@extends('platform.layout')

@section('title', 'Provisioning Run ' . $run->run_identifier)

@section('content')
<section class="panel" style="max-width: 100%; margin-bottom: 24px;">
    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
        <div>
            <h2>Run: <code>{{ $run->run_identifier }}</code></h2>
            <p class="muted">Tenant: <strong><a href="{{ route('platform.tenants.show', $run->tenant) }}">{{ $run->tenant->name }}</a></strong> (<code>{{ $run->tenant->code }}</code>) · Started: {{ $run->created_at->format('M d, Y H:i:s') }}</p>
        </div>
        <div>
            <span style="padding: 6px 12px; border-radius: 4px; font-weight: 600; {{ $run->status === 'completed' ? 'background: var(--clr-grey-tint); color: var(--clr-green);' : 'background: var(--clr-grey-tint); color: var(--clr-red);' }}">
                {{ ucfirst($run->status) }}
            </span>
        </div>
    </div>

    @if($run->isFailed())
        <div style="background: var(--clr-grey-tint); border: 1px solid var(--clr-grey-tint); color: var(--clr-red); padding: 14px; border-radius: 6px; margin-top: 14px;">
            <strong>Failure Details:</strong> {{ $run->error_message }}
            @if(auth('platform')->user()->hasPermission('platform.provisioning.retry'))
                <form method="post" action="{{ route('platform.provisioning.retry', $run) }}" style="margin-top: 10px;">
                    @csrf
                    <input type="hidden" name="reason" value="Retry requested by platform operator.">
                    <button type="submit" style="background: var(--clr-red);">Retry Failed Step</button>
                </form>
            @endif
        </div>
    @endif
</section>

<section class="panel" style="max-width: 100%;">
    <h3>30-Step Execution Manifest</h3>
    <table style="width: 100%; border-collapse: collapse; text-align: left; margin-top: 12px;">
        <thead>
            <tr style="border-bottom: 2px solid #dce5df; color: #63736b;">
                <th style="padding: 8px 12px; width: 60px;">Step</th>
                <th style="padding: 8px 12px;">Step Name</th>
                <th style="padding: 8px 12px;">Status</th>
                <th style="padding: 8px 12px;">Duration</th>
                <th style="padding: 8px 12px;">Error Log</th>
            </tr>
        </thead>
        <tbody>
            @foreach($run->steps as $step)
                <tr style="border-bottom: 1px solid #eef2ef;">
                    <td style="padding: 10px 12px; font-weight: 600;">#{{ $step->step_number }}</td>
                    <td style="padding: 10px 12px;">{{ $step->name }}</td>
                    <td style="padding: 10px 12px;">
                        <span style="font-weight: 600; {{ $step->status === 'completed' ? 'color: var(--clr-green);' : ($step->status === 'failed' ? 'color: var(--clr-red);' : 'color: #63736b;') }}">
                            {{ ucfirst($step->status) }}
                        </span>
                    </td>
                    <td style="padding: 10px 12px;">{{ $step->duration_ms ? $step->duration_ms . ' ms' : '-' }}</td>
                    <td style="padding: 10px 12px; color: var(--clr-red); font-size: 13px;">{{ $step->error_message ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</section>
@endsection
