@extends('platform.layout')

@section('title', 'Industry Templates')

@section('content')
<header>
    <div>
        <h1>Industry Templates</h1>
        <div class="muted">Pre-configured module blueprints for instant tenant provisioning and business vertical setup.</div>
    </div>
    @if(auth('platform')->user()->hasPermission('platform.modules.manage'))
        <div>
            <a href="{{ route('platform.templates.create') }}" class="btn">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                <span>Create Template</span>
            </a>
        </div>
    @endif
</header>

@if(session('notice'))
    <div class="notice" role="alert">{{ session('notice') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger" style="background: #fee2e2; border: 1px solid #fecaca; color: #b91c1c; padding: 12px 16px; border-radius: var(--radius-btn); margin-bottom: 20px;" role="alert">
        {{ session('error') }}
    </div>
@endif

<div class="panel" style="margin-bottom: 20px; max-width: 100%;">
    <form method="GET" action="{{ route('platform.templates.index') }}" style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
        <div style="flex: 1; min-width: 260px;">
            <input type="text" name="q" value="{{ $searchQuery }}" placeholder="Search templates by name, slug, or description...">
        </div>
        <button type="submit" class="btn">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <span>Search</span>
        </button>
        @if($searchQuery)
            <a href="{{ route('platform.templates.index') }}" class="btn btn-secondary">Clear</a>
        @endif
    </form>
</div>

<div class="table-container" style="background: var(--color-neutral-0); border-radius: var(--radius-card); box-shadow: var(--shadow-card); overflow-x: auto;">
    <table style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr>
                <th style="padding: 10px 14px;">Name</th>
                <th style="padding: 10px 14px;">Slug</th>
                <th style="padding: 10px 14px; text-align: center;">Modules</th>
                <th style="padding: 10px 14px;">Default Modules</th>
                <th style="padding: 10px 14px;">Description</th>
                <th style="padding: 10px 14px; text-align: center;">Status</th>
                <th style="padding: 10px 14px; text-align: right;">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($templates as $template)
                <tr>
                    <td style="padding: 12px 14px;">
                        <a href="{{ route('platform.templates.show', $template) }}" style="font-weight: 600; color: var(--color-ink-950);">
                            {{ $template->name }}
                        </a>
                        @if(($template->tenants_count ?? 0) > 0)
                            <div style="font-size: 11px; color: var(--color-neutral-500); margin-top: 2px;">
                                {{ $template->tenants_count }} {{ Str::plural('tenant', $template->tenants_count) }} active
                            </div>
                        @endif
                    </td>
                    <td style="padding: 12px 14px;">
                        <code style="background: var(--color-neutral-100); padding: 2px 6px; border-radius: 4px; font-size: 12px;">{{ $template->slug }}</code>
                    </td>
                    <td style="padding: 12px 14px; text-align: center;">
                        <span class="badge badge-info" style="font-size: 11px;">
                            {{ count($template->default_modules ?? []) }}
                        </span>
                    </td>
                    <td style="padding: 12px 14px; max-width: 320px;">
                        <div style="display: flex; flex-wrap: wrap; gap: 4px;">
                            @foreach(array_slice($template->default_modules ?? [], 0, 5) as $modSlug)
                                <span style="font-size: 11px; background: var(--color-neutral-100); color: var(--color-ink-800); padding: 2px 6px; border-radius: 4px; white-space: nowrap;">
                                    {{ str_replace('mod-', '', $modSlug) }}
                                </span>
                            @endforeach
                            @if(count($template->default_modules ?? []) > 5)
                                <span style="font-size: 11px; color: var(--color-neutral-500); padding: 2px 4px;">
                                    +{{ count($template->default_modules) - 5 }} more
                                </span>
                            @endif
                        </div>
                    </td>
                    <td style="padding: 12px 14px; font-size: 13px; color: var(--color-neutral-600); max-width: 280px;">
                        {{ Str::limit($template->description ?? '—', 90) }}
                    </td>
                    <td style="padding: 12px 14px; text-align: center;">
                        <span class="badge {{ $template->is_active ? 'badge-success' : 'badge-secondary' }}" style="font-size: 11px;">
                            {{ $template->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td style="padding: 12px 14px; text-align: right;">
                        <div style="display: inline-flex; gap: 6px; align-items: center;">
                            <a href="{{ route('platform.templates.show', $template) }}" class="btn btn-sm btn-secondary">View</a>
                            @if(auth('platform')->user()->hasPermission('platform.modules.manage'))
                                <a href="{{ route('platform.templates.edit', $template) }}" class="btn btn-sm btn-secondary">Edit</a>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="padding: 40px 20px; text-align: center;">
                        <div style="max-width: 420px; margin: 0 auto;">
                            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="color: var(--color-neutral-400); margin-bottom: 12px;"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg>
                            <h3 style="margin: 0 0 6px 0; font-size: 16px;">No industry templates defined</h3>
                            <p class="muted" style="margin: 0 0 16px 0; font-size: 13px;">
                                @if($searchQuery)
                                    No templates matched your query "{{ $searchQuery }}". Try a different term or reset filters.
                                @else
                                    Industry blueprints allow rapid provisioning by grouping modules and defaults for specific sectors.
                                @endif
                            </p>
                            @if(auth('platform')->user()->hasPermission('platform.modules.manage'))
                                <a href="{{ route('platform.templates.create') }}" class="btn">Create template</a>
                            @endif
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
