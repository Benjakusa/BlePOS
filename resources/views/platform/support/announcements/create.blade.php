@extends('platform.layout')

@section('title', 'Broadcast Platform Announcement')

@section('content')
<header>
    <div>
        <h1>New Platform Announcement</h1>
        <div class="muted">Publish targeted bulletin or system broadcast to tenants</div>
    </div>
    <div>
        <a href="{{ route('platform.announcements.index') }}" class="btn secondary">Back to Announcements</a>
    </div>
</header>

@if ($errors->any())
    <div class="notice" style="border-color: #fca5a5; background: #fef2f2; color: #991b1b;" role="alert">
        <strong>Please check the errors:</strong>
        <ul style="margin: 8px 0 0; padding-left: 20px;">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="panel" style="max-width: 680px;">
    <form method="POST" action="{{ route('platform.announcements.store') }}" class="form">
        @csrf

        <label>
            Title:
            <input type="text" name="title" value="{{ old('title') }}" required placeholder="e.g. Scheduled KRA eTIMS Sandbox Maintenance Notice">
        </label>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
            <label>
                Priority:
                <select name="priority" style="border: 1px solid #b9c9bf; border-radius: 5px; padding: 11px 12px; font: inherit; width: 100%;">
                    <option value="info" selected>Informational</option>
                    <option value="warning">Warning / Advisory</option>
                    <option value="critical">Critical / Urgent</option>
                </select>
            </label>

            <label>
                Target Audience:
                <select name="target_type" style="border: 1px solid #b9c9bf; border-radius: 5px; padding: 11px 12px; font: inherit; width: 100%;">
                    <option value="all" selected>All Tenants (Platform-wide)</option>
                    <option value="plan">Specific Subscription Plan</option>
                    <option value="industry">Specific Industry Type</option>
                    <option value="tenant">Single Tenant</option>
                </select>
            </label>
        </div>

        <label>
            Target Value (Plan code, industry slug, or tenant ID — optional):
            <input type="text" name="target_value" value="{{ old('target_value') }}" placeholder="Leave blank for all tenants">
        </label>

        <label>
            Announcement Content:
            <textarea name="content" rows="6" required style="border: 1px solid #b9c9bf; border-radius: 5px; padding: 11px 12px; font: inherit; width: 100%;">{{ old('content') }}</textarea>
        </label>

        <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 16px;">
            <a href="{{ route('platform.announcements.index') }}" class="btn secondary">Cancel</a>
            <button type="submit">Publish Announcement</button>
        </div>
    </form>
</div>
@endsection
