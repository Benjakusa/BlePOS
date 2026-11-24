@extends('platform.layout')

@section('title', 'Compliance & eTIMS Console')

@section('content')
<header>
    <div>
        <h1>Compliance & eTIMS Console</h1>
        <div class="muted">KRA eTIMS regulatory certification, shop fiscalization oversight, data protection requests, and legal policy versioning (Annex A, Part 9 & Part 15)</div>
    </div>
    <div style="display: flex; gap: 8px;">
        <a href="{{ route('platform.compliance.dpa.index') }}" class="btn">Data Protection (DSAR)</a>
        <a href="{{ route('platform.compliance.legal.index') }}" class="btn secondary">Legal Documents</a>
    </div>
</header>

@if(session('notice'))
    <div class="notice" role="alert">{{ session('notice') }}</div>
@endif

<div style="background: {{ $metrics['etims_status'] === 'CERTIFIED' ? '#f0fdf4' : '#fffbeb' }}; border: 1px solid {{ $metrics['etims_status'] === 'CERTIFIED' ? '#bbf7d0' : '#fde68a' }}; border-radius: 8px; padding: 18px 22px; margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center;">
    <div>
        <div style="display: flex; align-items: center; gap: 10px;">
            <span style="font-weight: 700; font-size: 16px;">KRA eTIMS Platform Certification Status:</span>
            <span class="badge" style="background: {{ $metrics['etims_status'] === 'CERTIFIED' ? '#dcfce7' : '#fee2e2' }}; color: {{ $metrics['etims_status'] === 'CERTIFIED' ? '#166534' : '#991b1b' }}; padding: 4px 10px; border-radius: 4px; font-weight: 700;">
                {{ $metrics['etims_status'] }}
            </span>
        </div>
        <div class="muted" style="font-size: 13px; margin-top: 6px;">
            {{ $metrics['etims_status'] === 'CERTIFIED'
                ? 'All technical and regulatory criteria verified. Real-time fiscal invoice transmissions active.'
                : 'Platform operates in technical simulation mode. Live KRA production transmission gateway blocked pending official regulatory sign-off (Annex A 8.8). Regulatory placeholder active — pending official KRA clearance.' }}
        </div>
    </div>
    <div style="text-align: right;">
        <span style="font-size: 24px; font-weight: bold;">{{ $metrics['cert_passed'] }} / {{ $metrics['cert_total'] }}</span>
        <div class="muted" style="font-size: 12px;">Criteria Verified</div>
    </div>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 24px;">
    <div class="panel" style="padding: 16px;">
        <span class="muted" style="font-size: 13px; text-transform: uppercase;">Active Tenants</span>
        <div style="font-size: 26px; font-weight: bold; margin-top: 4px;">{{ $metrics['total_tenants'] }}</div>
        <div class="muted" style="font-size: 12px; margin-top: 4px;">Registered organizations</div>
    </div>
    <div class="panel" style="padding: 16px;">
        <span class="muted" style="font-size: 13px; text-transform: uppercase;">Monitored Shops</span>
        <div style="font-size: 26px; font-weight: bold; margin-top: 4px;">{{ $metrics['total_shops'] }}</div>
        <div class="muted" style="font-size: 12px; margin-top: 4px;">Branches and registers</div>
    </div>
    <div class="panel" style="padding: 16px;">
        <span class="muted" style="font-size: 13px; text-transform: uppercase;">Platform Invoices</span>
        <div style="font-size: 26px; font-weight: bold; margin-top: 4px;">{{ $metrics['total_invoices'] }}</div>
        <div class="muted" style="font-size: 12px; margin-top: 4px;">Sequential fiscal records</div>
    </div>
    <div class="panel" style="padding: 16px;">
        <span class="muted" style="font-size: 13px; text-transform: uppercase;">Pending DPA Requests</span>
        <div style="font-size: 26px; font-weight: bold; margin-top: 4px; color: {{ $metrics['dpa_overdue'] > 0 ? '#b91c1c' : '#146b4d' }};">
            {{ $metrics['dpa_pending'] }}
        </div>
        <div class="muted" style="font-size: 12px; margin-top: 4px;">{{ $metrics['dpa_overdue'] }} statutory overdue</div>
    </div>
</div>

<div class="panel" style="max-width: none; margin-bottom: 24px;">
    <h2 style="font-size: 18px; margin-top: 0;">KRA eTIMS Certification Checklist & Technical Audit</h2>
    <div class="muted" style="margin-bottom: 14px; font-size: 13px;">Regulatory compliance evaluation against Kenya Revenue Authority (KRA) eTIMS specifications.</div>

    <table cellpadding="10" cellspacing="0" border="1" style="width: 100%; border-collapse: collapse; font-size: 13px;">
        <thead>
            <tr style="background: #f8faf9;">
                <th>Component / Evaluation Item</th>
                <th>Stage</th>
                <th>Status</th>
                <th>KRA Reference</th>
                <th>Technical Details</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach($certifications as $cert)
                <tr>
                    <td><strong>{{ $cert->component_name }}</strong></td>
                    <td><code>{{ $cert->certification_stage }}</code></td>
                    <td>
                        <span class="badge" style="background: {{ $cert->status === 'passed' ? '#dcfce7' : ($cert->status === 'blocked' ? '#fee2e2' : '#fef3c7') }}; color: {{ $cert->status === 'passed' ? '#166534' : ($cert->status === 'blocked' ? '#991b1b' : '#92400e') }}; padding: 3px 8px; border-radius: 4px; font-weight: 600;">
                            {{ strtoupper($cert->status) }}
                        </span>
                    </td>
                    <td><code>{{ $cert->kra_reference ?? 'Pending' }}</code></td>
                    <td>{{ $cert->details }}</td>
                    <td>
                        <details>
                            <summary style="cursor: pointer; color: #146b4d; font-weight: 600;">Update</summary>
                            <form method="POST" action="{{ route('platform.compliance.etims-cert.update', $cert) }}" style="margin-top: 8px; display: grid; gap: 6px;">
                                @csrf
                                <select name="status" style="padding: 4px 6px; font-size: 12px;">
                                    <option value="passed" @selected($cert->status === 'passed')>Passed</option>
                                    <option value="pending" @selected($cert->status === 'pending')>Pending</option>
                                    <option value="blocked" @selected($cert->status === 'blocked')>Blocked</option>
                                    <option value="failed" @selected($cert->status === 'failed')>Failed</option>
                                </select>
                                <input name="kra_reference" value="{{ $cert->kra_reference }}" placeholder="KRA Reference..." style="padding: 4px 6px; font-size: 12px;">
                                <button type="submit" class="btn" style="padding: 4px 8px; font-size: 11px;">Save Status</button>
                            </form>
                        </details>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
    <div class="panel" style="max-width: none;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
            <h2 style="font-size: 18px; margin: 0;">Recent Data Protection Requests (Kenya DPA)</h2>
            <a href="{{ route('platform.compliance.dpa.create') }}" class="btn" style="padding: 6px 10px; font-size: 13px;">+ Log Request</a>
        </div>
        <table cellpadding="8" cellspacing="0" border="1" style="width: 100%; border-collapse: collapse; font-size: 13px;">
            <thead>
                <tr style="background: #f8faf9;">
                    <th>Request #</th>
                    <th>Subject</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Due Date</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentDpa as $dpa)
                    <tr>
                        <td>
                            <a href="{{ route('platform.compliance.dpa.show', $dpa) }}">
                                <strong>{{ $dpa->request_number }}</strong>
                            </a>
                        </td>
                        <td>{{ $dpa->subject_name }}</td>
                        <td><code>{{ $dpa->request_type }}</code></td>
                        <td>
                            <span class="badge" style="background: {{ $dpa->status === 'completed' ? '#dcfce7' : ($dpa->status === 'rejected' ? '#fee2e2' : '#e0e7ff') }}; color: {{ $dpa->status === 'completed' ? '#166534' : ($dpa->status === 'rejected' ? '#991b1b' : '#3730a3') }}; padding: 2px 6px; border-radius: 4px;">
                                {{ strtoupper($dpa->status) }}
                            </span>
                        </td>
                        <td>
                            <span style="color: {{ $dpa->isOverdue() ? '#b91c1c' : 'inherit' }}; font-weight: {{ $dpa->isOverdue() ? 'bold' : 'normal' }};">
                                {{ $dpa->due_date->format('M d, Y') }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="muted" style="text-align: center; padding: 16px;">No data protection requests logged.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="panel" style="max-width: none;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
            <h2 style="font-size: 18px; margin: 0;">Published Legal & Compliance Policies</h2>
            <a href="{{ route('platform.compliance.legal.create') }}" class="btn" style="padding: 6px 10px; font-size: 13px;">+ New Version</a>
        </div>
        <table cellpadding="8" cellspacing="0" border="1" style="width: 100%; border-collapse: collapse; font-size: 13px;">
            <thead>
                <tr style="background: #f8faf9;">
                    <th>Policy Document</th>
                    <th>Version</th>
                    <th>Effective Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($legalDocuments as $doc)
                    <tr>
                        <td><strong>{{ $doc->title }}</strong></td>
                        <td>v{{ $doc->version }}</td>
                        <td>{{ $doc->effective_from->format('M d, Y') }}</td>
                        <td>
                            <a href="{{ route('platform.compliance.legal.show', $doc) }}" style="font-size: 12px;">View</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
