@extends('tenant.layout')

@section('title', $case->case_number)

@section('content')
<div class="page-header" style="display:flex;justify-content:space-between;gap:12px;margin-bottom:20px"><div><h1>{{ $case->subject }}</h1><p class="muted">{{ $case->case_number }} · {{ ucfirst($case->priority) }} priority · {{ str_replace('_', ' ', ucfirst($case->status)) }}</p></div><a class="btn btn-secondary" href="{{ route('tenant.support.cases.index') }}">All tickets</a></div>
<div class="panel" style="margin-bottom:16px"><h2>Ticket details</h2><p style="white-space:pre-wrap">{{ $case->description }}</p><small class="muted">Opened {{ $case->created_at->format('d M Y, H:i') }}</small></div>
<div class="panel" style="margin-bottom:16px"><h2>Conversation</h2>
    @forelse($case->notes as $note)
        <article style="padding:14px 0;border-bottom:1px solid #e2e8f0"><strong>{{ $note->author_name }}</strong><small class="muted" style="margin-left:8px">{{ $note->created_at->format('d M Y, H:i') }}</small><p style="white-space:pre-wrap;margin-bottom:0">{{ $note->content }}</p></article>
    @empty<p class="muted">The support team has not replied yet.</p>@endforelse
</div>
@if(! in_array($case->status, ['resolved', 'closed'], true))
<div class="panel"><h2>Reply</h2><form method="post" action="{{ route('tenant.support.cases.reply', $case) }}">@csrf<textarea name="content" rows="4" required maxlength="2000" class="form-control">{{ old('content') }}</textarea>@error('content')<div class="text-danger">{{ $message }}</div>@enderror<button class="btn btn-primary" style="margin-top:12px" type="submit">Send reply</button></form></div>
@endif
@endsection
