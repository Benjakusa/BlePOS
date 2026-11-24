@extends('platform.layout')

@section('title', 'DSAR ' . $dpa->request_number)

@section('content')
<header>
    <div>
        <div style="display: flex; align-items: center; gap: 10px;">
            <h1>{{ $dpa->request_number }}</h1>
            <span class="badge" style="background: {{ $dpa->status === 'completed' ? '#dcfce7' : ($dpa->status === 'rejected' ? '#fee2e2' : '#e0e7ff') }}; color: {{ $dpa->status === 'completed' ? '#166534' : ($dpa->status === 'rejected' ? '#991b1b' : '#3730a3') }}; padding: 4px 8px; border-radius: 4px;">
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
                <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 6px; padding: 12px; white-space: pre-wrap;">{{ $dpa->resolution_summary }}</div>
            @endif

            @if($dpa->rejection_reason)
                <h3 style="font-size: 15px; margin-top: 20px; color: #991b1b;">Rejection Justification</h3>
                <div style="background: #fee2e2; border: 1px solid #fca5a5; border-radius: 6px; padding: 12px; white-space: pre-wrap;">{{ $dpa->rejection_reason }}</div>
            @endif
        </div>
    </div>

    <div>
        <div class="panel" style="max-width: none;">
            <h3 style="font-size: 16px; margin-top: 0;">Update Request Status</h3>
            <form method="POST" action="{{ route('platform.compliance.dpa.status', $dpa) }}" style="display: grid; gap: 12px;">
                @csrf
                <label style="font-size: 13px;">Status
                    <select name="status" style="border: 1px solid #b9c9bf; border-radius: 5px; padding: 8px 10px; font: inherit; width: 100%;">
                        @foreach(['pending' => 'Pending', 'under_review' => 'Under Review', 'processing' => 'Processing', 'completed' => 'Completed', 'rejected' => 'Rejected'] as $st => $lbl)
                            <option value="{{ $st }}" @selected($dpa->status === $st)>{{ $lbl }}</option>
                        @endforeach
                    </select>
                </label>

                <label style="font-size: 13px;">Resolution Summary (for Completed)
                    <textarea name="resolution_summary" rows="3" style="border: 1px solid #b9c9bf; border-radius: 5px; padding: 8px 10px; font: inherit; width: 100%;" placeholder="Summary of data exported, rectified or deleted...">{{ $dpa->resolution_summary }}</textarea>
                </label>

                <label style="font-size: 13px;">Rejection Reason (for Rejected)
                    <textarea name="rejection_reason" rows="2" style="border: 1px solid #b9c9bf; border-radius: 5px; padding: 8px 10px; font: inherit; width: 100%;" placeholder="Statutory exemption or reason for rejection...">{{ $dpa->rejection_reason }}</textarea>
                </label>

                <div>
                    <button type="submit" style="width: 100%;">Update Status</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
