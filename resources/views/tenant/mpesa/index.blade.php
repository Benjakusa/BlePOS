@extends('tenant.layout')

@section('title', 'M-Pesa Credential Vault')

@section('content')
<div class="page-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
    <div>
        <h1 style="font-size: 22px; font-weight: 600; margin: 0; color: #0f172a;">M-Pesa Credential Vault</h1>
        <p style="font-size: 13px; color: #64748b; margin: 4px 0 0 0;">Manage encrypted STK Push, C2B, and B2C credentials for {{ $tenant->name }} and its branches.</p>
    </div>
    <a href="{{ route('tenant.mpesa.create') }}" class="btn" style="text-decoration: none;">
        <i class="bi bi-plus-lg"></i> Add M-Pesa Credential
    </a>
</div>

@if(session('success'))
    <div style="background: #dcfce7; color: #166534; padding: 12px 16px; border-radius: 10px; margin-bottom: 16px; font-size: 13px;">
        <i class="bi bi-check-circle-fill"></i> {{ session('success') }}
    </div>
@endif

@if($credentials->isEmpty())
    <div class="panel" style="text-align: center; padding: 48px 24px;">
        <i class="bi bi-shield-lock" style="font-size: 36px; color: #94a3b8;"></i>
        <h3 style="margin: 12px 0 6px; font-size: 16px; color: #0f172a;">No M-Pesa Credentials Configured</h3>
        <p style="color: #64748b; font-size: 13px; margin-bottom: 18px;">Add your Safaricom Daraja Paybill or Till credentials to accept M-Pesa payments at checkout.</p>
        <a href="{{ route('tenant.mpesa.create') }}" class="btn" style="text-decoration: none;">Add First Credential</a>
    </div>
@else
    @foreach(['sandbox' => 'Sandbox Environment', 'production' => 'Production Environment'] as $envKey => $envLabel)
        @php($group = $credentials->where('environment', $envKey))
        @if($group->isNotEmpty())
            <div style="margin-bottom: 24px;">
                <h2 style="font-size: 15px; font-weight: 600; color: #334155; margin-bottom: 12px;">{{ $envLabel }}</h2>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 16px;">
                    @foreach($group as $cred)
                        <div class="panel" style="display: flex; flex-direction: column; gap: 12px;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 8px;">
                                <div>
                                    <div style="font-size: 15px; font-weight: 600; color: #0f172a;">
                                        Shortcode: {{ $cred->shortcode }}
                                    </div>
                                    <div style="font-size: 12px; color: #64748b; margin-top: 2px;">
                                        @if($cred->owner_type === 'branch')
                                            <i class="bi bi-shop"></i> Branch: {{ $cred->branch?->name ?? 'Branch #' . $cred->branch_id }}
                                        @else
                                            <i class="bi bi-building"></i> Tenant Default
                                        @endif
                                    </div>
                                </div>
                                <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                                    <span style="font-size: 11px; padding: 3px 8px; border-radius: 999px; font-weight: 600; background: {{ $cred->environment === 'production' ? '#fee2e2' : '#fef3c7' }}; color: {{ $cred->environment === 'production' ? '#991b1b' : '#92400e' }};">
                                        {{ strtoupper($cred->environment) }}
                                    </span>
                                    @if($cred->is_default)
                                        <span style="font-size: 11px; padding: 3px 8px; border-radius: 999px; font-weight: 600; background: #dbeafe; color: #1e40af;">DEFAULT</span>
                                    @endif
                                    <span style="font-size: 11px; padding: 3px 8px; border-radius: 999px; font-weight: 600; background: {{ $cred->is_active ? '#dcfce7' : '#f1f5f9' }}; color: {{ $cred->is_active ? '#166534' : '#64748b' }};">
                                        {{ $cred->is_active ? 'ACTIVE' : 'INACTIVE' }}
                                    </span>
                                </div>
                            </div>

                            <div style="font-size: 12px; color: #475569; background: #f8fafc; padding: 10px 12px; border-radius: 8px; display: flex; flex-direction: column; gap: 4px;">
                                <div><strong>Consumer Key:</strong> <code>{{ $cred->maskedConsumerKey() }}</code></div>
                                <div><strong>Consumer Secret:</strong> <code>{{ $cred->maskedConsumerSecret() }}</code></div>
                                <div><strong>Passkey:</strong> <code>{{ $cred->maskedPasskey() }}</code></div>
                                <div style="word-break: break-all;"><strong>Callback Route:</strong> <code>{{ route('mpesa.callback', $cred->callback_token) }}</code></div>
                            </div>

                            <div id="test-result-{{ $cred->id }}" style="display: none; font-size: 12px; padding: 8px 10px; border-radius: 8px;"></div>

                            <div style="display: flex; gap: 8px; flex-wrap: wrap; align-items: center; margin-top: auto;">
                                <button type="button" class="btn btn-secondary" onclick="testMpesaConnection({{ $cred->id }}, '{{ route('tenant.mpesa.test', $cred) }}')" style="font-size: 12px; padding: 6px 10px;">
                                    <i class="bi bi-lightning-charge"></i> Test OAuth
                                </button>
                                <a href="{{ route('tenant.mpesa.edit', $cred) }}" class="btn btn-secondary" style="font-size: 12px; padding: 6px 10px; text-decoration: none;">
                                    <i class="bi bi-pencil"></i> Edit
                                </a>
                                <form action="{{ route('tenant.mpesa.destroy', $cred) }}" method="POST" data-motion-confirm="Delete this M-Pesa credential?" style="margin: 0; margin-left: auto;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-secondary" style="font-size: 12px; padding: 6px 10px; color: #dc2626;">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    @endforeach
@endif

<script>
async function testMpesaConnection(id, url) {
    const box = document.getElementById('test-result-' + id);
    box.style.display = 'block';
    box.style.background = '#f1f5f9';
    box.style.color = '#334155';
    box.textContent = 'Testing OAuth connection...';

    try {
        const res = await fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        });
        const data = await res.json();
        box.style.background = data.success ? '#dcfce7' : '#fee2e2';
        box.style.color = data.success ? '#166534' : '#991b1b';
        box.textContent = data.message || (data.success ? 'OAuth connected.' : 'Connection failed.');
    } catch (e) {
        box.style.background = '#fee2e2';
        box.style.color = '#991b1b';
        box.textContent = 'Network error testing connection.';
    }
}
</script>
@endsection
