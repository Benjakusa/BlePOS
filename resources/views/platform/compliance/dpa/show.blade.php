@extends('platform.layout')

@section('title', 'DSAR ' . $dpa->request_number)

@section('content')
<header>
    <div>
        <div style="display: flex; align-items: center; gap: 10px;">
            <h1>{{ $dpa->request_number }}</h1>
            <span class="badge" style="background: #EAEEF3; color: {{ $dpa->status === 'completed' ? '#16A34A' : ($dpa->status === 'rejected' ? '#DC2626' : '#2563EB') }}; padding: 4px 8px; border-radius: 4px;">
                {{ strtoupper($dpa->status) }}
            </span>
        </div>
        <div class="muted" style="margin-top: 4px;">{{ $dpa->subject_name }} ({{ $dpa->subject_email }})</div>
    </div>
    <div style="display: flex; gap: 8px;">
        <a href="{{ route('platform.compliance.dpa.index') }}" class="btn secondary">All Requests</a>
    </div>
</header>

@if(session('notice'))
    <div class="notice" role="alert">{{ session('notice') }}</div>
@endif

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
    <div>
        <div class="panel" style="max-width: none; margin-bottom: 20px;">
            <h2 style="font-size: 18px; margin-top: 0;">Data Subject & Request Profile</h2>
            <table cellpadding="8" cellspacing="0" style="width: 100%; font-size: 14px;">
                <tr>
                    <td class="muted" style="width: 180px;">Subject Name:</td>
                    <td><strong>{{ $dpa->subject_name }}</strong></td>
                </tr>
                <tr>
                    <td class="muted">Email Address:</td>
                    <td>{{ $dpa->subject_email }}</td>
                </tr>
                <tr>
                    <td class="muted">Phone Number:</td>
                    <td>{{ $dpa->subject_phone ?? '—' }}</td>
                </tr>
                <tr>
                    <td class="muted">Request Type:</td>
                    <td><code>{{ $dpa->request_type }}</code></td>
                </tr>
                <tr>
                    <td class="muted">Legal Basis:</td>
                    <td>{{ $dpa->legal_basis }}</td>
                </tr>
                <tr>
                    <td class="muted">Target Tenant:</td>
                    <td>{{ $dpa->tenant?->name ?? 'Platform Global' }}</td>
                </tr>
                <tr>
                    <td class="muted">Statutory Deadline:</td>
                    <td>
                        <span style="color: {{ $dpa->isOverdue() ? '#b91c1c' : '#15803d' }}; font-weight: bold;">
                            {{ $dpa->due_date->format('M d, Y') }} ({{ $dpa->due_date->diffForHumans() }})
                        </span>
                    </td>
                </tr>
            </table>

            @if($dpa->resolution_summary)
                <h3 style="font-size: 15px; margin-top: 20px; color: #166534;">Resolution Summary</h3>
                <div style="background: #EAEEF3; border: 1px solid #CBD5E1; border-radius: 6px; padding: 12px; white-space: pre-wrap;">{{ $dpa->resolution_summary }}</div>
            @endif

            @if($dpa->rejection_reason)
                <h3 style="font-size: 15px; margin-top: 20px; color: #991b1b;">Rejection Justification</h3>
                <div style="background: #EAEEF3; border: 1px solid #DC2626; border-radius: 6px; padding: 12px; white-space: pre-wrap;">{{ $dpa->rejection_reason }}</div>
            @endif
        </div>
    </div>

    <div>
        <div class="panel" style="max-width: none;">
            <h3 style="font-size: 16px; margin-top: 0;">Update Request Status</h3>
            <form method="POST" action="{{ route('platform.compliance.dpa.status', $dpa) }}" style="display: flex; flex-direction: column; gap: 12px;">
                @csrf
                <x-input type="select" name="status" label="Status" :selected="$dpa->status">
                    @foreach(['pending' => 'Pending', 'under_review' => 'Under Review', 'processing' => 'Processing', 'completed' => 'Completed', 'rejected' => 'Rejected'] as $st => $lbl)
                        <option value="{{ $st }}" @selected($dpa->status === $st)>{{ $lbl }}</option>
                    @endforeach
                </x-input>

                <x-input type="textarea" name="resolution_summary" label="Resolution Summary (for Completed)" rows="3" :value="$dpa->resolution_summary" placeholder="Summary of data exported, rectified or deleted..." />

                <x-input type="textarea" name="rejection_reason" label="Rejection Reason (for Rejected)" rows="2" :value="$dpa->rejection_reason" placeholder="Statutory exemption or reason for rejection..." />

                <div>
                    <button type="submit" style="width: 100%;">Update Status</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
