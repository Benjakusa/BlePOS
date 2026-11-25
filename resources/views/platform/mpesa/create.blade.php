@extends('platform.layout')

@section('title', 'Add Platform M-Pesa Credential')

@section('content')
<div class="page-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <h1 style="font-size: 22px; font-weight: 600; margin: 0;">Add Platform M-Pesa Credential</h1>
    <a href="{{ route('platform.mpesa.index') }}" class="btn btn-secondary" style="text-decoration: none;">Back</a>
</div>

<div class="panel" style="max-width: 680px;">
    <form action="{{ route('platform.mpesa.store') }}" method="POST" style="display: flex; flex-direction: column; gap: 14px;">
        @csrf
        <x-input type="select" name="environment" label="Environment" required>
            <option value="sandbox" @selected(old('environment', 'sandbox') === 'sandbox')>Sandbox</option>
            <option value="production" @selected(old('environment') === 'production')>Production</option>
        </x-input>
        <x-input name="shortcode" label="Shortcode" value="{{ old('shortcode') }}" required />
        <x-input type="password" name="passkey" label="Passkey" required />
        <x-input type="password" name="consumer_key" label="Consumer Key" required />
        <x-input type="password" name="consumer_secret" label="Consumer Secret" required />
        <x-input name="initiator_name" label="Initiator Name (optional)" value="{{ old('initiator_name') }}" />
        <x-input type="textarea" name="security_credential" label="Security Credential (optional)" rows="2" />
        <x-input name="callback_url" label="Callback URL" value="{{ old('callback_url', url('/api/mpesa/callback')) }}" required />
        <x-input type="toggle" name="is_active" label="Active" value="1" :checked="true" />
        <x-input type="toggle" name="is_default" label="Default for Environment" value="1" :checked="old('is_default', false)" />
        <div style="display: flex; gap: 10px;">
            <button type="submit" class="btn">Save Platform Credential</button>
            <a href="{{ route('platform.mpesa.index') }}" class="btn btn-secondary" style="text-decoration: none;">Cancel</a>
        </div>
    </form>
</div>
@endsection
