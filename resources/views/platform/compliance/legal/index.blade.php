@extends('platform.layout')

@section('title', 'Legal & Policy Documents')

@section('content')
<header>
    <div>
        <h1>Legal & Compliance Policy Documents</h1>
        <div class="muted">Versioned platform policies, Terms of Service, Privacy Policies, and Data Processing Agreements (Annex A, 15)</div>
    </div>
    <div style="display: flex; gap: 8px;">
        <a href="{{ route('platform.compliance.index') }}" class="btn secondary">Compliance Console</a>
        <a href="{{ route('platform.compliance.legal.create') }}" class="btn">+ New Version</a>
    </div>
</header>

@if(session('notice'))
    <div class="notice" role="alert">{{ session('notice') }}</div>
@endif

<section class="panel" style="max-width: none; overflow-x: auto;">
    <table cellpadding="10" cellspacing="0" border="1" style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="background: #f8faf9;">
                <th>Slug</th>
                <th>Title</th>
                <th>Version</th>
                <th>Effective Date</th>
                <th>Author</th>
                <th>Changelog</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($documents as $doc)
                <tr>
                    <td><code>{{ $doc->slug }}</code></td>
                    <td>
                        <a href="{{ route('platform.compliance.legal.show', $doc) }}">
                            <strong>{{ $doc->title }}</strong>
                        </a>
                    </td>
                    <td>
                        <span class="badge" style="background: #e0e7ff; color: #3730a3; padding: 2px 6px; border-radius: 4px;">
                            v{{ $doc->version }}
                        </span>
                    </td>
                    <td>{{ $doc->effective_from->format('M d, Y') }}</td>
                    <td>{{ $doc->creator?->name ?? 'System' }}</td>
                    <td>{{ Str::limit($doc->changelog ?? 'Initial release', 40) }}</td>
                    <td>
                        <a href="{{ route('platform.compliance.legal.show', $doc) }}" class="btn secondary" style="padding: 4px 8px; font-size: 12px;">View</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="muted" style="text-align: center; padding: 24px;">No legal documents found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
    {{ $documents->links() }}
</section>
@endsection
