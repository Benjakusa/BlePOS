@extends('tenant.layout')

@section('title', $doc->title)

@section('content')
<div class="page-header" style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
    <div>
        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 6px;">
            <a href="{{ route('tenant.legal.index') }}" class="muted" style="text-decoration: none; font-size: 13px;">
                <i class="bi bi-arrow-left"></i> Legal Documents
            </a>
            <span class="muted">&bull;</span>
            <span class="badge badge-info">v{{ $doc->version }}</span>
        </div>
        <h1 class="page-title">{{ $doc->title }}</h1>
        <p class="muted" style="margin-top: 4px; font-size: 14px;">
            Effective from {{ $doc->effective_from?->format('F d, Y') ?? 'Immediate' }}
        </p>
    </div>
    <div style="display: flex; gap: 10px;">
        <button onclick="window.print()" class="btn btn-secondary">
            <i class="bi bi-printer"></i>
            <span>Print Document</span>
        </button>
    </div>
</div>

<div class="card p-5" style="line-height: 1.7; font-size: 14px; color: var(--clr-text);">
    @if($doc->changelog)
        <div style="background: var(--clr-bg); border: 1px solid var(--clr-border); border-radius: 6px; padding: 12px; margin-bottom: 20px; font-size: 13px;">
            <strong>Changelog:</strong> {{ $doc->changelog }}
        </div>
    @endif
    <div style="white-space: pre-wrap; font-family: inherit;">{{ $doc->content }}</div>
</div>
@endsection
