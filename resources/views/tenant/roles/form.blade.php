@extends('tenant.layout')

@section('title', $role->exists ? 'Edit Role' : 'Create Role')

@section('content')
<div class="page-header" style="margin-bottom:20px;">
    <a href="{{ route('tenant.roles.index') }}" class="btn btn-secondary btn-sm">Back to roles</a>
    <h1 class="page-title" style="margin-top:12px;">{{ $role->exists ? 'Edit '.$role->name : 'Create a business role' }}</h1>
    <p class="muted">Grant only the capabilities required for this responsibility. Permission checks also apply to direct requests.</p>
</div>

<form method="post" action="{{ $role->exists ? route('tenant.roles.update', $role) : route('tenant.roles.store') }}" class="card" style="max-width:900px;padding:24px;">
    @csrf
    @if($role->exists) @method('PUT') @endif
    <div style="display:grid;grid-template-columns:1fr 2fr;gap:16px;">
        <x-input type="text" name="name" label="Role name" value="{{ old('name', $role->name) }}" :required="true" />
        <x-input type="text" name="description" label="Responsibilities" value="{{ old('description', $role->description) }}" />
    </div>

    @php $selected = old('permission_ids', $role->permissions->pluck('id')->all()); @endphp
    <h2 style="font-size:16px;margin:18px 0 8px;">Permissions by operational area</h2>
    <p class="muted" style="font-size:13px;">A role can combine responsibilities. Users receive the union of the permissions on their assigned roles.</p>
    @foreach($permissions as $group => $items)
        <fieldset style="border:1px solid #e2e8f0;border-radius:8px;padding:14px;margin:14px 0;">
            <legend style="font-weight:600;padding:0 6px;">{{ $group }}</legend>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:10px;">
                @foreach($items as $permission)
                    <label style="display:flex;align-items:flex-start;gap:8px;">
                        <input type="checkbox" name="permission_ids[]" value="{{ $permission->id }}" @checked(in_array($permission->id, $selected))>
                        <span><strong style="font-size:13px;">{{ $permission->name }}</strong><small style="display:block;color:#64748b;">{{ $permission->description }}</small></span>
                    </label>
                @endforeach
            </div>
        </fieldset>
    @endforeach

    <div style="display:flex;gap:10px;margin-top:20px;">
        <button class="btn" type="submit">{{ $role->exists ? 'Save role' : 'Create role' }}</button>
        <a href="{{ route('tenant.roles.index') }}" class="btn btn-secondary">Cancel</a>
    </div>
</form>
@endsection
