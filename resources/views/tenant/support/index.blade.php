@extends('tenant.layout')

@section('title', 'Support Tickets')

@section('content')
<div class="page-header" style="display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:20px;">
    <div><h1>Support Tickets</h1><p class="muted">Contact the BlePOS platform team and follow replies here.</p></div>
    <a class="btn btn-primary" href="{{ route('tenant.support.cases.create') }}"><i class="bi bi-plus-lg"></i> New ticket</a>
</div>
<div class="panel">
    @if($cases->isEmpty())
        <p class="muted">No support tickets yet.</p>
    @else
        <div style="overflow-x:auto"><table class="table"><thead><tr><th>Ticket</th><th>Subject</th><th>Status</th><th>Priority</th><th>Opened</th></tr></thead><tbody>
        @foreach($cases as $case)
            <tr><td><a href="{{ route('tenant.support.cases.show', $case) }}">{{ $case->case_number }}</a></td><td>{{ $case->subject }}</td><td>{{ str_replace('_', ' ', ucfirst($case->status)) }}</td><td>{{ ucfirst($case->priority) }}</td><td>{{ $case->created_at->format('d M Y') }}</td></tr>
        @endforeach
        </tbody></table></div>
        {{ $cases->links() }}
    @endif
</div>
@endsection
