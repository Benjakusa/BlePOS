@extends('platform.layout')

@section('title', 'Edit Platform M-Pesa Credential')

@section('content')
<div class="page-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <h1 style="font-size: 22px; font-weight: 600; margin: 0;">Edit Platform M-Pesa Credential ({{ $credential->shortcode }})</h1>
    <a href="{{ route('platform.mpesa.index') }}" class="btn btn-secondary" style="text-decoration: none;">Back</a>
</div>

<div class="panel" style="max-width: 680px;">
    <form action="{{ route('platform.mpesa.update', $credential) }}" method="POST" style="display: flex; flex-direction: column; gap: 14px;">
        @csrf
        @method('PUT')
        <x-input type="select" name="environment" label="Environment" required>
            <option value="sandbox" @selected(old('environment', $credential->environment) === 'sandbox')>Sandbox</option>
            <option value="production" @selected(old('environment', $credential->environment) === 'production')>Production</option>
        </x-input>
        <x-input name="shortcode" label="Shortcode" value="{{ old('shortcode', $credential->shortcode) }}" required />
        <x-input type="password" name="passkey" label="Passkey ({{ $credential->maskedPasskey() }})" helper="Leave blank to keep existing." />
        <x-input type="password" name="consumer_key" label="Consumer Key ({{ $credential->maskedConsumerKey() }})" helper="Leave blank to keep existing." />
        <x-input type="password" name="consumer_secret" label="Consumer Secret ({{ $credential->maskedConsumerSecret() }})" helper="Leave blank to keep existing." />
        <x-input name="initiator_name" label="Initiator Name (optional)" />
        <x-input type="textarea" name="security_credential" label="Security Credential (optional)" rows="2" />
        <p class="muted">BlePOS generates a private callback endpoint for this credential.</p>
        <x-input type="toggle" name="is_active" label="Active" value="1" :checked="old('is_active', $credential->is_active)" />
        <x-input type="toggle" name="is_default" label="Default for Environment" value="1" :checked="old('is_default', $credential->is_default)" />
        <div style="display: flex; gap: 10px;">
            <button type="submit" class="btn">Update Platform Credential</button>
            <a href="{{ route('platform.mpesa.index') }}" class="btn btn-secondary" style="text-decoration: none;">Cancel</a>
        </div>
    </form>
</div>
@endsection
