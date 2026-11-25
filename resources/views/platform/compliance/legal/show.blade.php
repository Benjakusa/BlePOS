@extends('platform.layout')

@section('title', $document->title)

@section('content')
<header>
    <div>
        <div style="display: flex; align-items: center; gap: 10px;">
            <h1>{{ $document->title }}</h1>
            <span class="badge" style="background: var(--clr-grey-tint); color: #3730a3; padding: 4px 8px; border-radius: 4px; font-weight: 600;">
                v{{ $document->version }}
            </span>
        </div>
        <div class="muted" style="margin-top: 4px;">Effective from {{ $document->effective_from->format('F d, Y') }} · Published by {{ $document->creator?->name ?? 'System' }}</div>
    </div>
    <div style="display: flex; gap: 8px;">
        <a href="{{ route('platform.compliance.legal.index') }}" class="btn secondary">All Documents</a>
    </div>
</header>

@if(session('notice'))
    <div class="notice" role="alert">{{ session('notice') }}</div>
@endif

<div class="panel" style="max-width: none; margin-bottom: 20px;">
    @if($document->changelog)
        <div style="background: var(--clr-grey-tint); border: 1px solid #e2e8f0; border-radius: 6px; padding: 12px; margin-bottom: 20px; font-size: 13px;">
            <strong>Changelog:</strong> {{ $document->changelog }}
        </div>
    @endif

    <div style="white-space: pre-wrap; font-family: inherit; line-height: 1.6; font-size: 14px;">{{ $document->content }}</div>
</div>
@endsection
