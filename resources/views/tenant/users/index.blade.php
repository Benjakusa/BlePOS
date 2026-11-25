@extends('tenant.layout')

@section('title', 'Staff Management')

@section('content')
<div class="page-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
    <div>
        <h1 style="font-size: 22px; font-weight: 600; margin: 0; color: #0f172a;">Staff & Team Members</h1>
        <p style="font-size: 13px; color: #64748b; margin: 4px 0 0 0;">Manage your team, assign security roles, and branch permissions.</p>
    </div>
    <a href="{{ route('tenant.users.create') }}" class="btn" style="text-decoration: none;">
        <i class="bi bi-person-plus"></i> Add Staff Member
    </a>
</div>

{{-- Filter Bar --}}
<div class="card" style="margin-bottom: 20px; padding: 14px 18px;">
    <form method="get" action="{{ route('tenant.users.index') }}" style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
        <div style="flex: 1; min-width: 220px;">
            <x-input
                type="search"
                name="search"
                placeholder="Search staff by name, email, or phone..."
                value="{{ request('search') }}"
            />
        </div>
        <div style="width: 200px;">
            <x-input
                type="select"
                name="role"
                aria-label="Filter by Role"
            >
                <option value="">All Roles</option>
                @foreach($roles as $role)
                    <option value="{{ $role->slug }}" @selected(request('role') === $role->slug)>{{ $role->name }}</option>
                @endforeach
            </x-input>
        </div>
        <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
        @if(request()->hasAny(['search', 'role']))
            <a href="{{ route('tenant.users.index') }}" class="btn btn-outline btn-sm" style="text-decoration:none;">Reset</a>
        @endif
    </form>
</div>

{{-- Staff Table --}}
<div class="card" style="padding: 0; overflow: hidden;">
    <table class="data-table" style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="background: #f8fafc; border-bottom: 1px solid #e2e8f0; text-align: left; font-size: 12px; text-transform: uppercase; color: #64748b; letter-spacing: 0.05em;">
                <th style="padding: 12px 18px;">Staff Member</th>
                <th style="padding: 12px 18px;">Contact</th>
                <th style="padding: 12px 18px;">Assigned Role(s)</th>
                <th style="padding: 12px 18px;">Assigned Branches</th>
                <th style="padding: 12px 18px;">Status</th>
                <th style="padding: 12px 18px; text-align: right;">Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse($users as $user)
                <tr style="border-bottom: 1px solid #f1f5f9; font-size: 13px;">
                    <td style="padding: 14px 18px;">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <div style="width: 32px; height: 32px; border-radius: 50%; background: #eaedf2; display: flex; align-items: center; justify-content: center; font-weight: 600; color: #1e293b;">
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            </div>
                            <div>
                                <strong style="color: #0f172a; display: block;">{{ $user->name }}</strong>
                                @if($user->isTenantOwner())
                                    <span style="font-size: 11px; background: #e0f2fe; color: #0284c7; padding: 2px 6px; border-radius: 4px; font-weight: 500;">Account Owner</span>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td style="padding: 14px 18px;">
                        <div style="color: #0f172a;">{{ $user->email }}</div>
                        <div style="font-size: 12px; color: #64748b;">{{ $user->phone ?? '—' }}</div>
                    </td>
                    <td style="padding: 14px 18px;">
                        @foreach($user->roles as $role)
                            <span style="display: inline-block; background: #f1f5f9; color: #334155; font-size: 11px; font-weight: 600; padding: 2px 8px; border-radius: 6px; margin-right: 4px;">
                                {{ $role->name }}
                            </span>
                        @endforeach
                    </td>
                    <td style="padding: 14px 18px;">
                        @forelse($user->shops as $shop)
                            <span style="display: inline-block; font-size: 12px; color: #475569;">
                                {{ $shop->name }}{{ $loop->last ? '' : ',' }}
                            </span>
                        @empty
                            <span style="color: #94a3b8; font-style: italic;">All branches (Owner)</span>
                        @endforelse
                    </td>
                    <td style="padding: 14px 18px;">
                        @if($user->status === 'active')
                            <span style="display: inline-flex; align-items: center; gap: 4px; font-size: 12px; color: #15803d; font-weight: 500;">
                                <span style="width: 6px; height: 6px; border-radius: 50%; background: #16a34a;"></span> Active
                            </span>
                        @else
                            <span style="display: inline-flex; align-items: center; gap: 4px; font-size: 12px; color: #94a3b8; font-weight: 500;">
                                <span style="width: 6px; height: 6px; border-radius: 50%; background: #cbd5e1;"></span> Inactive
                            </span>
                        @endif
                    </td>
                    <td style="padding: 14px 18px; text-align: right;">
                        <a href="{{ route('tenant.users.edit', $user) }}" class="btn btn-secondary btn-sm" style="text-decoration:none;">
                            <i class="bi bi-pencil"></i> Edit
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="padding: 30px; text-align: center; color: #64748b;">
                        No staff members found matching your criteria.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($users->hasPages())
    <div style="margin-top: 18px;">
        {{ $users->links() }}
    </div>
@endif

@endsection
