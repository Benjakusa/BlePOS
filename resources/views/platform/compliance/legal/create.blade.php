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

@if($errors->any())
    <div class="error" style="margin-bottom: 16px;">
        <ul>
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="panel">
    <form method="POST" action="{{ route('platform.compliance.legal.store') }}" class="form" style="max-width: 680px;">
        @csrf

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 14px;">
            <label>Policy Identifier (Slug)
                <select name="slug" required style="border: 1px solid #b9c9bf; border-radius: 5px; padding: 11px 12px; font: inherit;">
                    <option value="terms-of-service" @selected(old('slug') === 'terms-of-service')>Terms of Service (terms-of-service)</option>
                    <option value="privacy-policy" @selected(old('slug') === 'privacy-policy')>Privacy Policy (privacy-policy)</option>
                    <option value="data-processing-agreement" @selected(old('slug') === 'data-processing-agreement')>Data Processing Agreement (data-processing-agreement)</option>
                    <option value="sla-policy" @selected(old('slug') === 'sla-policy')>Service Level Agreement (sla-policy)</option>
                    <option value="acceptable-use" @selected(old('slug') === 'acceptable-use')>Acceptable Use Policy (acceptable-use)</option>
                </select>
            </label>

            <label>Version
                <input name="version" value="{{ old('version', '1.1') }}" required placeholder="e.g. 1.1">
            </label>
        </div>

        <label>Document Title
            <input name="title" value="{{ old('title') }}" required placeholder="e.g. BlePOS Master Terms of Service">
        </label>

        <label>Effective Date
            <input type="date" name="effective_from" value="{{ old('effective_from', date('Y-m-d')) }}">
        </label>

        <label>Changelog / Revision Summary
            <input name="changelog" value="{{ old('changelog') }}" placeholder="e.g. Updated Section 4 regarding Kenyan eTIMS fiscalization requirements">
        </label>

        <label>Document Content (Markdown / Plain Text)
            <textarea name="content" rows="12" required style="border: 1px solid #b9c9bf; border-radius: 5px; padding: 11px 12px; font: inherit; font-family: monospace;">{{ old('content') }}</textarea>
        </label>

        <div style="margin-top: 10px;">
            <button type="submit">Publish Version</button>
        </div>
    </form>
</div>
@endsection
