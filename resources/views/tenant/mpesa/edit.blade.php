@extends('tenant.layout')

@section('title', 'Edit M-Pesa Credential')

@section('content')
<div class="page-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <div>
        <h1 style="font-size: 22px; font-weight: 600; margin: 0; color: #0f172a;">Edit M-Pesa Credential ({{ $mpesaCredential->shortcode }})</h1>
        <p style="font-size: 13px; color: #64748b; margin: 4px 0 0 0;">
            Level: {{ ucfirst($mpesaCredential->owner_type) }}
            @if($mpesaCredential->branch) — {{ $mpesaCredential->branch->name }} @endif
        </p>
    </div>
    <a href="{{ route('tenant.mpesa.index') }}" class="btn btn-secondary" style="text-decoration: none;">Back to Vault</a>
</div>

<div class="panel" style="max-width: 680px;">
    <form action="{{ route('tenant.mpesa.update', $mpesaCredential) }}" method="POST" style="display: flex; flex-direction: column; gap: 14px;">
        @csrf
        @method('PUT')

        <x-input type="select" name="environment" label="Environment" required>
            <option value="sandbox" @selected(old('environment', $mpesaCredential->environment) === 'sandbox')>Sandbox</option>
            <option value="production" @selected(old('environment', $mpesaCredential->environment) === 'production')>Production</option>
        </x-input>

        <x-input name="shortcode" label="Shortcode (Paybill / Till)" value="{{ old('shortcode', $mpesaCredential->shortcode) }}" required />
        <x-input type="password" name="passkey" label="Passkey ({{ $mpesaCredential->maskedPasskey() }})" helper="Leave blank to keep existing encrypted passkey." />
        <x-input type="password" name="consumer_key" label="Consumer Key ({{ $mpesaCredential->maskedConsumerKey() }})" helper="Leave blank to keep existing encrypted key." />
        <x-input type="password" name="consumer_secret" label="Consumer Secret ({{ $mpesaCredential->maskedConsumerSecret() }})" helper="Leave blank to keep existing encrypted secret." />
        <x-input name="initiator_name" label="Initiator Name (B2C, optional)" helper="Leave blank to keep existing value." />
        <x-input type="textarea" name="security_credential" label="Security Credential (B2C, optional)" rows="2" />
        <x-input name="callback_url" label="Callback URL" value="{{ old('callback_url', $mpesaCredential->callback_url) }}" required />

        <x-input type="toggle" name="is_active" label="Active" value="1" :checked="old('is_active', $mpesaCredential->is_active)" />
        <x-input type="toggle" name="is_default" label="Set as Default for this Environment" value="1" :checked="old('is_default', $mpesaCredential->is_default)" />

        <div style="display: flex; gap: 10px; margin-top: 8px;">
            <button type="submit" class="btn">Update Credential</button>
            <a href="{{ route('tenant.mpesa.index') }}" class="btn btn-secondary" style="text-decoration: none;">Cancel</a>
        </div>
    </form>
</div>
@endsection
