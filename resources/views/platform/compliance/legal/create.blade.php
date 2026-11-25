@extends('platform.layout')

@section('title', 'Publish Legal Document Version')

@section('content')
<header>
    <div>
        <h1>Publish Legal Document Version</h1>
        <div class="muted">Publish an updated or new version of a platform policy or legal agreement</div>
    </div>
    <a href="{{ route('platform.compliance.legal.index') }}" class="btn secondary">Back to Documents</a>
</header>

<div class="panel">
    <form method="POST" action="{{ route('platform.compliance.legal.store') }}" class="form" style="max-width: 680px; display: flex; flex-direction: column; gap: 14px;">
        @csrf

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 14px;">
            <x-input type="select" name="slug" label="Policy Identifier (Slug)" required>
                <option value="terms-of-service" @selected(old('slug') === 'terms-of-service')>Terms of Service (terms-of-service)</option>
                <option value="privacy-policy" @selected(old('slug') === 'privacy-policy')>Privacy Policy (privacy-policy)</option>
                <option value="data-processing-agreement" @selected(old('slug') === 'data-processing-agreement')>Data Processing Agreement (data-processing-agreement)</option>
                <option value="sla-policy" @selected(old('slug') === 'sla-policy')>Service Level Agreement (sla-policy)</option>
                <option value="acceptable-use" @selected(old('slug') === 'acceptable-use')>Acceptable Use Policy (acceptable-use)</option>
            </x-input>

            <x-input name="version" label="Version" value="1.1" required placeholder="e.g. 1.1" />
        </div>

        <x-input name="title" label="Document Title" required placeholder="e.g. BlePOS Master Terms of Service" />

        <x-input type="date" name="effective_from" label="Effective Date" :value="date('Y-m-d')" />

        <x-input name="changelog" label="Changelog / Revision Summary" placeholder="e.g. Updated Section 4 regarding Kenyan eTIMS fiscalization requirements" />

        <x-input type="textarea" name="content" label="Document Content (Markdown / Plain Text)" rows="12" required style="font-family: Helvetica, Arial, sans-serif;" />

        <div style="margin-top: 10px;">
            <button type="submit">Publish Version</button>
        </div>
    </form>
</div>
@endsection
