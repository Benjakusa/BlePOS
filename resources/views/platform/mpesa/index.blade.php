@extends('platform.layout')

@section('title', 'Platform M-Pesa Credentials')

@section('content')
<div class="page-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
    <div>
        <h1 style="font-size: 22px; font-weight: 600; margin: 0;">Platform M-Pesa Credential Vault</h1>
        <p style="font-size: 13px; color: #64748b; margin: 4px 0 0 0;">Manage platform-level M-Pesa credentials and view tenant configuration summary.</p>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <a href="{{ route('platform.mpesa.refunds.index') }}" class="btn btn-secondary" style="text-decoration: none;">
            <i class="bi bi-arrow-counterclockwise"></i> Refund review ({{ $pendingRefundCount }})
        </a>
        <a href="{{ route('platform.mpesa.create') }}" class="btn" style="text-decoration: none;">
            <i class="bi bi-plus-lg"></i> Add Platform Credential
        </a>
    </div>
</div>

@if(session('success'))
    <div style="background: #dcfce7; color: #166534; padding: 12px 16px; border-radius: 10px; margin-bottom: 16px; font-size: 13px;">
        {{ session('success') }}
    </div>
@endif

<div class="panel" style="margin-bottom: 24px;">
    <h2 style="font-size: 16px; margin-top: 0;">Platform Credentials</h2>
    @if($platformCredentials->isEmpty())
        <p style="color: #64748b; font-size: 13px;">No platform-level M-Pesa credentials configured.</p>
    @else
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 14px;">
            @foreach($platformCredentials as $cred)
                <div style="border: 1px solid #e2e8f0; border-radius: 10px; padding: 14px; display: flex; flex-direction: column; gap: 8px;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <strong>Shortcode: {{ $cred->shortcode }}</strong>
                        <span style="font-size: 11px; padding: 2px 8px; border-radius: 999px; background: #fef3c7; color: #92400e;">{{ strtoupper($cred->environment) }}</span>
                    </div>
                    <div style="font-size: 12px; color: #475569;">
                        <div>Key: <code>{{ $cred->maskedConsumerKey() }}</code></div>
                        <div>Secret: <code>{{ $cred->maskedConsumerSecret() }}</code></div>
                    </div>
                    <div style="display: flex; gap: 8px; margin-top: 6px;">
                        <a href="{{ route('platform.mpesa.edit', $cred) }}" class="btn btn-secondary" style="font-size: 12px; text-decoration: none;">Edit</a>
                        <form action="{{ route('platform.mpesa.destroy', $cred) }}" method="POST" data-motion-confirm="Delete platform credential?" style="margin: 0;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-secondary" style="font-size: 12px; color: #dc2626;">Delete</button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

<div class="panel">
    <h2 style="font-size: 16px; margin-top: 0;">Tenant M-Pesa Summary (Secrets Redacted)</h2>
    @if($tenantSummary->isEmpty())
        <p style="color: #64748b; font-size: 13px;">No tenant credentials configured yet.</p>
    @else
        <table class="table">
            <thead>
                <tr>
                    <th>Tenant</th>
                    <th>Total Credentials</th>
                    <th>Active</th>
                </tr>
            </thead>
            <tbody>
                @foreach($tenantSummary as $row)
                    <tr>
                        <td>{{ $row->tenant?->name ?? 'Tenant #' . $row->tenant_id }}</td>
                        <td>{{ $row->total }}</td>
                        <td>{{ $row->active_count }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection
