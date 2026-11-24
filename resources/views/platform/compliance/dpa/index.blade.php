@extends('platform.layout')

@section('title', 'Data Protection Requests')

@section('content')
<header>
    <div>
        <h1>Data Protection Requests</h1>
        <div class="muted">Subject access, data rectification, and erasure workflows (Kenya Data Protection Act 2019 / GDPR)</div>
    </div>
    <div style="display: flex; gap: 8px;">
        <a href="{{ route('platform.compliance.index') }}" class="btn secondary">Compliance Console</a>
        <a href="{{ route('platform.compliance.dpa.create') }}" class="btn">+ Log DSAR Request</a>
    </div>
</header>

@if(session('notice'))
    <div class="notice" role="alert">{{ session('notice') }}</div>
@endif

<section class="panel" style="max-width: none; margin-bottom: 18px;">
    <form method="get" action="{{ route('platform.compliance.dpa.index') }}" style="display: flex; gap: 12px; align-items: end; flex-wrap: wrap;">
        <div style="min-width: 170px;">
            <x-input type="select" name="status" label="Status" :selected="$filters['status'] ?? ''">
                <option value="">All statuses</option>
                @foreach(['pending' => 'Pending', 'under_review' => 'Under Review', 'processing' => 'Processing', 'completed' => 'Completed', 'rejected' => 'Rejected'] as $val => $lbl)
                    <option value="{{ $val }}" @selected(($filters['status'] ?? '') === $val)>{{ $lbl }}</option>
                @endforeach
            </x-input>
        </div>
        <div style="min-width: 190px;">
            <x-input type="select" name="request_type" label="Request Type" :selected="$filters['request_type'] ?? ''">
                <option value="">All types</option>
                @foreach(['access_export' => 'Access / Export', 'rectification' => 'Rectification', 'erasure_deletion' => 'Erasure / Deletion', 'objection' => 'Objection', 'consent_withdrawal' => 'Consent Withdrawal'] as $val => $lbl)
                    <option value="{{ $val }}" @selected(($filters['request_type'] ?? '') === $val)>{{ $lbl }}</option>
                @endforeach
            </x-input>
        </div>
        <button type="submit">Filter</button>
        @if(!empty(array_filter($filters)))
            <a href="{{ route('platform.compliance.dpa.index') }}" style="align-self: center;">Clear</a>
        @endif
    </form>
</section>

<section class="panel" style="max-width: none; overflow-x: auto;">
    <table cellpadding="10" cellspacing="0" border="1" style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="background: #f8faf9;">
                <th>Request #</th>
                <th>Subject Name</th>
                <th>Subject Email</th>
                <th>Type</th>
                <th>Legal Basis</th>
                <th>Status</th>
                <th>Statutory Due Date</th>
                <th>Assigned Officer</th>
            </tr>
        </thead>
        <tbody>
            @forelse($requests as $req)
                <tr>
                    <td>
                        <a href="{{ route('platform.compliance.dpa.show', $req) }}">
                            <strong>{{ $req->request_number }}</strong>
                        </a>
                    </td>
                    <td>{{ $req->subject_name }}</td>
                    <td>{{ $req->subject_email }}</td>
                    <td><code>{{ $req->request_type }}</code></td>
                    <td><small>{{ $req->legal_basis }}</small></td>
                    <td>
                        <span class="badge" style="background: #EAEEF3; color: {{ $req->status === 'completed' ? '#16A34A' : ($req->status === 'rejected' ? '#DC2626' : '#2563EB') }}; padding: 3px 8px; border-radius: 4px;">
                            {{ strtoupper($req->status) }}
                        </span>
                    </td>
                    <td>
                        <span style="color: {{ $req->isOverdue() ? '#DC2626' : 'inherit' }}; font-weight: {{ $req->isOverdue() ? 'bold' : 'normal' }};">
                            {{ $req->due_date->format('Y-m-d') }}
                            @if($req->isOverdue())
                                <small style="display: block; color: #DC2626;">[Overdue]</small>
                            @endif
                        </span>
                    </td>
                    <td>{{ $req->assignedOfficer?->name ?? 'Unassigned' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="muted" style="text-align: center; padding: 24px;">No data protection requests recorded.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
    {{ $requests->links() }}
</section>
@endsection
