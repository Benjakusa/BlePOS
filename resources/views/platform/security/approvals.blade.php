@extends('platform.layout')

@section('title', 'Pending approvals')

@section('content')
    @forelse($approvals as $approval)
        <section class="panel" style="margin-bottom:14px">
            <h2>{{ $approval->action_type }}</h2>
            <p>Requested by {{ $approval->requester->name }} · {{ $approval->created_at->toDayDateTimeString() }}</p>
            <p>Target {{ $approval->target_type }} #{{ $approval->target_id }}</p>
            <p>Reason: {{ $approval->reason }}</p>
            <p>Expires {{ $approval->expires_at?->toDayDateTimeString() ?? 'Never' }}</p>
            @if($approval->expires_at?->isPast())
                <p class="muted">This request has expired and will be marked automatically.</p>
            @else
                @if($approval->requested_by !== auth('platform')->id())
                    <form method="post" action="{{ route('platform.approvals.approve', $approval) }}" style="margin-bottom:12px">
                        @csrf <button type="submit">Approve as {{ auth('platform')->user()->name }}</button>
                    </form>
                @else
                    <p class="muted">You cannot approve your own request.</p>
                @endif
                @if($approval->requested_by === auth('platform')->id())
                    <form method="post" action="{{ route('platform.approvals.cancel', $approval) }}" class="form">
                        @csrf
                        <x-input name="reason" label="Cancellation reason" minlength="8" maxlength="1000" required />
                        <button type="submit">Cancel request</button>
                    </form>
                @endif
            @endif
        </section>
    @empty
        <section class="panel"><p class="muted">No pending approval requests.</p></section>
    @endforelse
@endsection
