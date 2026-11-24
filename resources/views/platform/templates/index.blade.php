@extends('platform.layout')

@section('title', 'Industry Templates')

@section('content')
<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
        <div>
            <h2 class="section-title" style="font-size: 18px;">Industry Templates</h2>
            <p class="muted" style="margin-top: 2px;">Pre-configured module blueprints for instant tenant provisioning and business vertical setup.</p>
        </div>
        @if(auth('platform')->user()->hasPermission('platform.modules.manage'))
            <a href="{{ route('platform.templates.create') }}" class="btn btn-primary" aria-label="Create Template">
                <i class="bi bi-plus-lg"></i>
                <span>Create Template</span>
            </a>
        @endif
    </div>

    <form method="GET" action="{{ route('platform.templates.index') }}" style="display: flex; gap: 12px; align-items: flex-start; flex-wrap: wrap; margin-bottom: 24px;">
        <div style="flex: 1; min-width: 260px; max-width: 440px;">
            <x-input
                type="search"
                name="q"
                label="Search templates by name, slug, or description"
                :value="$searchQuery"
                wrapperClass="mb-0"
            />
        </div>
        <button type="submit" class="btn btn-secondary" style="height: 40px;">
            <i class="bi bi-search"></i>
            <span>Search</span>
        </button>
        @if($searchQuery)
            <a href="{{ route('platform.templates.index') }}" class="btn btn-ghost" style="height: 40px;">
                <i class="bi bi-x-circle"></i>
                <span>Clear</span>
            </a>
        @endif
    </form>

    <div class="table-container">
        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Slug</th>
                        <th style="text-align: center;">Modules</th>
                        <th>Default Modules</th>
                        <th>Description</th>
                        <th style="text-align: center;">Status</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($templates as $template)
                        <tr>
                            <td title="{{ $template->name }}">
                                <a href="{{ route('platform.templates.show', $template) }}" style="font-weight: 600;" class="text-blue">
                                    {{ $template->name }}
                                </a>
                                @if(($template->tenants_count ?? 0) > 0)
                                    <div class="muted" style="font-size: 11px; margin-top: 2px;">
                                        {{ $template->tenants_count }} {{ Str::plural('tenant', $template->tenants_count) }} active
                                    </div>
                                @endif
                            </td>
                            <td title="{{ $template->slug }}">
                                <code style="background: var(--clr-grey-tint); padding: 3px 6px; border-radius: var(--radius-sm); font-size: 12px;">{{ $template->slug }}</code>
                            </td>
                            <td style="text-align: center;">
                                <span class="badge badge-info">
                                    {{ count($template->default_modules ?? []) }}
                                </span>
                            </td>
                            <td>
                                <div style="display: flex; flex-wrap: nowrap; gap: 4px; overflow: hidden;">
                                    @foreach(array_slice($template->default_modules ?? [], 0, 4) as $modSlug)
                                        <span class="chip">
                                            {{ str_replace('mod-', '', $modSlug) }}
                                        </span>
                                    @endforeach
                                    @if(count($template->default_modules ?? []) > 4)
                                        <span class="muted" style="font-size: 11px;">
                                            +{{ count($template->default_modules) - 4 }}
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td title="{{ $template->description ?? '—' }}" class="muted">
                                {{ $template->description ?? '—' }}
                            </td>
                            <td style="text-align: center;">
                                <span class="badge {{ $template->is_active ? 'badge-success' : 'badge-secondary' }}">
                                    <i class="bi bi-{{ $template->is_active ? 'check-circle' : 'dash-circle' }}"></i>
                                    {{ $template->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <div class="table-actions" style="justify-content: flex-end;">
                                    <a href="{{ route('platform.templates.show', $template) }}" class="btn-ghost" data-tooltip="View Details" aria-label="View Details">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    @if(auth('platform')->user()->hasPermission('platform.modules.manage'))
                                        <a href="{{ route('platform.templates.edit', $template) }}" class="btn-ghost" data-tooltip="Edit Template" aria-label="Edit Template">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="padding: 40px 20px; text-align: center;">
                                <div style="max-width: 420px; margin: 0 auto;">
                                    <i class="bi bi-layout-text-window-reverse" style="font-size: 32px; color: var(--clr-muted); display: block; margin-bottom: 12px;"></i>
                                    <h3 class="section-title" style="margin-bottom: 6px;">No industry templates defined</h3>
                                    <p class="muted" style="margin-bottom: 16px; font-size: 13px;">
                                        @if($searchQuery)
                                            No templates matched your query "{{ $searchQuery }}". Try a different term or reset filters.
                                        @else
                                            Industry blueprints allow rapid provisioning by grouping modules and defaults for specific sectors.
                                        @endif
                                    </p>
                                    @if(auth('platform')->user()->hasPermission('platform.modules.manage'))
                                        <a href="{{ route('platform.templates.create') }}" class="btn btn-primary">
                                            <i class="bi bi-plus-lg"></i>
                                            <span>Create template</span>
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
