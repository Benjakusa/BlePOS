@extends('tenant.layout')

@section('title', 'Edit Staff Member')

@section('content')
<div class="page-header" style="margin-bottom: 24px;">
    <div style="display: flex; align-items: center; gap: 8px;">
        <a href="{{ route('tenant.users.index') }}" class="btn btn-secondary btn-sm" style="text-decoration:none;">
            <i class="bi bi-arrow-left"></i> Back to Staff
        </a>
    </div>
    <h1 style="font-size: 22px; font-weight: 600; margin: 12px 0 4px 0; color: #0f172a;">Edit Staff: {{ $user->name }}</h1>
    <p style="font-size: 13px; color: #64748b; margin: 0;">Update account credentials, security roles, and branch assignment.</p>
</div>

<div class="card" style="max-width: 720px; padding: 24px;">
    <form method="post" action="{{ route('tenant.users.update', $user) }}">
        @csrf
        @method('PUT')

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
            <x-input
                type="text"
                name="name"
                id="name"
                label="Full Name"
                value="{{ old('name', $user->name) }}"
                :required="true"
                leadingIcon="bi-person"
            />

            <x-input
                type="email"
                name="email"
                id="email"
                label="Email Address"
                value="{{ old('email', $user->email) }}"
                :required="true"
                leadingIcon="bi-envelope"
            />
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
            <x-input
                type="text"
                name="phone"
                id="phone"
                label="Phone Number"
                value="{{ old('phone', $user->phone) }}"
                leadingIcon="bi-telephone"
            />

            <x-input
                type="password"
                name="password"
                id="password"
                label="Password (leave empty to keep current)"
                leadingIcon="bi-lock"
            />
        </div>

        {{-- Role Assignment --}}
        <div style="margin-bottom: 20px;">
            <label style="display: block; font-size: 13px; font-weight: 500; color: #334155; margin-bottom: 8px;">
                Assigned Role(s) <span style="color: #dc2626;">*</span>
            </label>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px;">
                @php $assignedRoleIds = old('role_ids', $user->roles->pluck('id')->toArray()); @endphp
                @foreach($roles as $role)
                    <label style="display: flex; align-items: flex-start; gap: 8px; padding: 10px 14px; border: 1px solid #e2e8f0; border-radius: 8px; cursor: pointer; background: #fff;">
                        <input
                            type="checkbox"
                            name="role_ids[]"
                            value="{{ $role->id }}"
                            @checked(in_array($role->id, $assignedRoleIds))
                            style="margin-top: 3px;"
                        />
                        <div>
                            <strong style="display: block; font-size: 13px; color: #0f172a;">{{ $role->name }}</strong>
                            <span style="font-size: 11px; color: #64748b; line-height: 1.3; display: block;">{{ $role->description }}</span>
                            @if($role->permissions->isNotEmpty())
                                <small style="display:block;color:#475569;margin-top:5px;">Access: {{ $role->permissions->pluck('name')->join(', ') }}</small>
                            @else
                                <small style="display:block;color:#64748b;margin-top:5px;">No operational permissions configured</small>
                            @endif
                        </div>
                    </label>
                @endforeach
            </div>
            @error('role_ids')
                <p style="color: #dc2626; font-size: 12px; margin-top: 4px;">{{ $message }}</p>
            @enderror
        </div>

        {{-- Shop Assignment --}}
        <div style="margin-bottom: 20px;">
            <label style="display: block; font-size: 13px; font-weight: 500; color: #334155; margin-bottom: 8px;">
                Branch / Shop Assignment
            </label>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px;">
                @php $assignedShopIds = old('shop_ids', $user->shops->pluck('id')->toArray()); @endphp
                @foreach($shops as $shop)
                    <label style="display: flex; align-items: center; gap: 8px; padding: 10px 14px; border: 1px solid #e2e8f0; border-radius: 8px; cursor: pointer; background: #fff;">
                        <input
                            type="checkbox"
                            name="shop_ids[]"
                            value="{{ $shop->id }}"
                            @checked(in_array($shop->id, $assignedShopIds))
                        />
                        <span style="font-size: 13px; color: #0f172a;">{{ $shop->name }} ({{ $shop->code }})</span>
                    </label>
                @endforeach
            </div>
        </div>

        {{-- Status --}}
        <div style="max-width: 250px; margin-bottom: 24px;">
            <x-input
                type="select"
                name="status"
                id="status"
                label="Account Status"
            >
                <option value="active" @selected(old('status', $user->status) === 'active')>Active</option>
                <option value="inactive" @selected(old('status', $user->status) === 'inactive')>Inactive / Suspended</option>
            </x-input>
        </div>

        <div style="display: flex; gap: 12px; align-items: center;">
            <button type="submit" class="btn">
                <i class="bi bi-check-lg"></i> Update Staff Member
            </button>
            <a href="{{ route('tenant.users.index') }}" class="btn btn-secondary" style="text-decoration:none;">Cancel</a>
        </div>
    </form>
</div>
@endsection
