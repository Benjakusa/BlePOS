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

<div class="panel" style="max-width: 680px;">
    <form method="POST" action="{{ route('platform.announcements.store') }}" class="form" style="display: flex; flex-direction: column; gap: 14px;">
        @csrf

        <x-input name="title" label="Title" required placeholder="e.g. Scheduled KRA eTIMS Sandbox Maintenance Notice" />

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
            <x-input type="select" name="priority" label="Priority">
                <option value="info" selected>Informational</option>
                <option value="warning">Warning / Advisory</option>
                <option value="critical">Critical / Urgent</option>
            </x-input>

            <x-input type="select" name="target_type" label="Target Audience">
                <option value="all" selected>All Tenants (Platform-wide)</option>
                <option value="plan">Specific Subscription Plan</option>
                <option value="industry">Specific Industry Type</option>
                <option value="tenant">Single Tenant</option>
            </x-input>
        </div>

        <x-input name="target_value" label="Target Value (Plan code, industry slug, or tenant ID — optional)" placeholder="Leave blank for all tenants" />

        <x-input type="textarea" name="content" label="Announcement Content" rows="6" required />

        <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 10px;">
            <a href="{{ route('platform.announcements.index') }}" class="btn secondary">Cancel</a>
            <button type="submit">Publish Announcement</button>
        </div>
    </form>
</div>
@endsection
