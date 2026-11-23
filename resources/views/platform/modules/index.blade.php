@extends('platform.layout')

@section('title', 'Module Catalogue')

@section('content')
<header>
    <div>
        <h1>Module Catalogue</h1>
        <div class="muted">Versioned capability catalogue ({{ $totalCount }} modules across Core, Commercial, Logistics, Finance & Industry)</div>
    </div>
    <div>
        <a href="{{ route('platform.templates.index') }}" class="btn btn-secondary">Industry Templates</a>
    </div>
</header>

@foreach($modulesByCategory as $category => $categoryModules)
    <section class="panel" style="max-width: 100%; margin-bottom: 24px;">
        <h2 style="font-size: 18px; margin-top: 0; margin-bottom: 14px; border-bottom: 2px solid #e2e8f0; padding-bottom: 8px;">
            {{ $category }} Modules
            <small class="muted" style="font-size: 13px; font-weight: normal;">({{ $categoryModules->count() }})</small>
        </h2>

        <table>
            <thead>
                <tr>
                    <th style="width: 140px;">Module Code</th>
                    <th>Name</th>
                    <th>Description</th>
                    <th style="width: 90px; text-align: center;">Version</th>
                    <th>Dependencies</th>
                    <th style="width: 100px; text-align: center;">Active Tenants</th>
                    <th style="width: 80px; text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($categoryModules as $mod)
                    <tr>
                        <td>
                            <code>{{ $mod->slug }}</code>
                            @if($mod->is_core)
                                <br><span class="badge badge-secondary" style="font-size: 10px;">Core</span>
                            @endif
                        </td>
                        <td><strong>{{ $mod->name }}</strong></td>
                        <td class="muted" style="font-size: 13px;">{{ $mod->description }}</td>
                        <td style="text-align: center;"><code>v{{ $mod->version }}</code></td>
                        <td>
                            @forelse($mod->dependencies ?? [] as $dep)
                                <span class="badge badge-info" style="margin: 2px 0;">{{ $dep }}</span>
                            @empty
                                <span class="muted" style="font-size: 12px;">None (Independent)</span>
                            @endforelse
                        </td>
                        <td style="text-align: center;">
                            <span class="badge badge-success">{{ $mod->active_tenants_count }}</span>
                        </td>
                        <td style="text-align: right;">
                            <a href="{{ route('platform.modules.show', $mod) }}" class="btn btn-secondary btn-sm">Inspect</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>
@endforeach
@endsection
