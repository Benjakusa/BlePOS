@extends('tenant.layout')

@section('title', 'Staff Roles')

@section('content')
<div class="page-header" style="display:flex;justify-content:space-between;align-items:center;gap:16px;margin-bottom:24px;">
    <div>
        <h1 class="page-title">Roles &amp; Responsibilities</h1>
        <p class="muted" style="margin:4px 0 0;">These roles belong to {{ $tenant->name }}. Assign one or more roles to each employee.</p>
    </div>
    <a href="{{ route('tenant.roles.create') }}" class="btn"><i class="bi bi-shield-plus"></i> Create role</a>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:16px;">
    @forelse($roles as $role)
        <section class="card" style="padding:20px;">
            <div style="display:flex;justify-content:space-between;gap:12px;align-items:flex-start;">
                <div>
                    <h2 style="font-size:17px;margin:0 0 4px;">{{ $role->name }}</h2>
                    <p class="muted" style="font-size:13px;margin:0;">{{ $role->description ?: 'No responsibility summary provided.' }}</p>
                </div>
                @if($role->is_system)<span class="badge badge-info">Built in</span>@endif
            </div>
            <p style="font-size:12px;margin:14px 0 6px;"><strong>{{ $role->users_count }}</strong> assigned staff · {{ $role->permissions->count() }} permissions</p>
            <details>
                <summary style="cursor:pointer;font-size:13px;">View permission summary</summary>
                @if($role->permissions->isEmpty())
                    <p class="muted" style="font-size:12px;">No operational permissions assigned.</p>
                @else
                    <ul style="padding-left:20px;font-size:12px;">
                        @foreach($role->permissions as $permission)
                            <li><strong>{{ $permission->name }}</strong> — {{ $permission->description }}</li>
                        @endforeach
                    </ul>
                @endif
            </details>
            @unless($role->is_system)
                <div style="display:flex;gap:8px;margin-top:16px;">
                    <a href="{{ route('tenant.roles.edit', $role) }}" class="btn btn-secondary btn-sm">Edit role</a>
                    @if($role->users_count === 0)
                        <form method="post" action="{{ route('tenant.roles.destroy', $role) }}" onsubmit="return confirm('Delete this unused role?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-danger btn-sm" type="submit">Delete</button>
                        </form>
                    @endif
                </div>
            @endunless
        </section>
    @empty
        <div class="card" style="padding:24px;">No roles configured for this business.</div>
    @endforelse
</div>
@endsection
