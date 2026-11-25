@extends('tenant.layout')

@section('title', $announcement->title)

@section('content')
<div class="panel" style="max-width:900px;margin:0 auto"><a href="{{ route('tenant.announcements.index') }}">← All announcements</a><p style="margin-top:20px"><span class="badge">{{ ucfirst($announcement->priority) }}</span></p><h1>{{ $announcement->title }}</h1><small class="muted">{{ $announcement->created_at->format('d M Y, H:i') }}</small><div style="white-space:pre-wrap;margin-top:24px">{{ $announcement->content }}</div></div>
@endsection
