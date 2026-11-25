@extends('platform.layout')

@section('title', 'Template: ' . $template->name)

@section('content')
<header>
    <div>
        <div style="display: flex; align-items: center; gap: 10px;">
            <h1 style="margin: 0;">{{ $template->name }}</h1>
            <span class="badge {{ $template->is_active ? 'badge-success' : 'badge-secondary' }}">
                {{ $template->is_active ? 'Active' : 'Inactive' }}
            </span>
        </div>
        <div class="muted" style="margin-top: 4px;">
            Slug: <code>{{ $template->slug }}</code> &bull; {{ count($template->default_modules ?? []) }} Default Modules &bull; {{ $tenants->count() }} Active Tenants
        </div>
    </div>
    <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
        @if(auth('platform')->user()->hasPermission('platform.modules.manage'))
            <a href="{{ route('platform.templates.edit', $template) }}" class="btn btn-secondary">
                <i class="bi bi-pencil" aria-hidden="true"></i>
                <span>Edit Template</span>
            </a>

            <form method="POST" action="{{ route('platform.templates.toggle-active', $template) }}" style="display: inline;">
                @csrf
                <button type="submit" class="btn btn-secondary">
                    {{ $template->is_active ? 'Deactivate' : 'Activate' }}
                </button>
            </form>

            @if($tenants->isEmpty())
                <form method="POST" action="{{ route('platform.templates.destroy', $template) }}" data-motion-confirm="Are you sure you want to permanently delete this industry template?" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            @endif
        @endif
        <a href="{{ route('platform.templates.index') }}" class="btn btn-secondary">Back to Templates</a>
    </div>
</header>

@if(session('notice'))
    <div class="notice" role="alert">{{ session('notice') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger" style="background: #EAEEF3; border: 1px solid #DC2626; color: #b91c1c; padding: 12px 16px; border-radius: var(--radius-btn); margin-bottom: 20px;" role="alert">
        {{ session('error') }}
    </div>
@endif

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 24px;">
    <!-- Left Column: Modules in Dependency Order -->
    <div>
        <div class="panel" style="max-width: 100%; margin-bottom: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
                <div>
                    <h3 style="margin: 0; font-size: 16px;">Topological Activation Order</h3>
                    <span class="muted" style="font-size: 13px;">Modules will be enabled in this exact dependency sequence:</span>
                </div>
                <span class="badge badge-info">{{ $orderedModules->count() }} Modules</span>
            </div>

            <div style="display: grid; gap: 8px;">
                @foreach($orderedModules as $index => $mod)
                    <div style="display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; background: var(--color-neutral-50); border: 1px solid var(--color-neutral-200); border-radius: 8px;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <span style="display: inline-flex; align-items: center; justify-content: center; width: 24px; height: 24px; background: var(--color-neutral-200); color: var(--color-ink-900); border-radius: 50%; font-size: 11px; font-weight: 600;">
                                {{ $index + 1 }}
                            </span>
                            <div>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <strong style="color: var(--color-ink-950); font-size: 14px;">{{ $mod->name }}</strong>
                                    <code>{{ $mod->slug }}</code>
                                    @if($mod->is_core)
                                        <span class="badge badge-info" style="font-size: 10px; padding: 1px 6px;">Core</span>
                                    @endif
                                </div>
                                <div style="font-size: 12px; color: var(--color-neutral-600); margin-top: 2px;">
                                    {{ $mod->description }}
                                </div>
                            </div>
                        </div>
                        <div style="text-align: right; font-size: 12px;">
                            <span class="badge badge-secondary">{{ $mod->category }}</span>
                            @if(!empty($mod->dependencies))
                                <div style="font-size: 11px; color: var(--color-neutral-500); margin-top: 4px;">
                                    Depends on: <strong>{{ implode(', ', $mod->dependencies) }}</strong>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Provisioned Tenants -->
        <div class="panel" style="max-width: 100%;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
                <div>
                    <h3 style="margin: 0; font-size: 16px;">Tenants on this Template</h3>
                    <span class="muted" style="font-size: 13px;">Businesses provisioned with the <code>{{ $template->slug }}</code> blueprint.</span>
                </div>
                <span class="badge badge-secondary">{{ $tenants->count() }} Tenants</span>
            </div>

            @if($tenants->isNotEmpty())
                <div class="table-container" style="background: #fff; border-radius: 8px;">
                    <table style="width: 100%;">
                        <thead>
                            <tr>
                                <th>Tenant</th>
                                <th>Code</th>
                                <th>Status</th>
                                <th>Provisioned</th>
                                <th style="text-align: right;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($tenants as $t)
                                <tr>
                                    <td>
                                        <a href="{{ route('platform.tenants.show', $t) }}" style="font-weight: 600;">
                                            {{ $t->name }}
                                        </a>
                                    </td>
                                    <td><code>{{ $t->code }}</code></td>
                                    <td><span class="badge badge-success">{{ strtoupper($t->status) }}</span></td>
                                    <td class="muted" style="font-size: 12px;">{{ $t->created_at?->format('Y-m-d') }}</td>
                                    <td style="text-align: right;">
                                        <a href="{{ route('platform.tenants.show', $t) }}" class="btn btn-sm btn-secondary">Manage</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="muted" style="padding: 16px 0; font-size: 13px;">
                    No tenants have been provisioned directly with this template yet.
                </div>
            @endif
        </div>
    </div>

    <!-- Right Column: Details & Apply Blueprint to Tenant -->
    <div>
        <!-- Template Summary Card -->
        <div class="panel" style="max-width: 100%; margin-bottom: 20px;">
            <h3 style="margin-top: 0; margin-bottom: 12px; font-size: 16px;">Description</h3>
            <p style="font-size: 13.5px; line-height: 1.6; color: var(--color-ink-900); margin: 0 0 16px 0;">
                {{ $template->description ?: 'No detailed description provided.' }}
            </p>

            <div style="border-top: 1px solid var(--color-neutral-200); padding-top: 12px; font-size: 12.5px; color: var(--color-neutral-600); display: grid; gap: 6px;">
                <div>Created: <strong>{{ $template->created_at?->toFormattedDateString() }}</strong></div>
                <div>Last Updated: <strong>{{ $template->updated_at?->toFormattedDateString() }}</strong></div>
                <div>Status: <strong>{{ $template->is_active ? 'Active for Provisioning' : 'Deactivated' }}</strong></div>
            </div>
        </div>

        <!-- Apply to Tenant Tool -->
        @if(auth('platform')->user()->hasPermission('platform.modules.manage'))
            <div class="panel" style="max-width: 100%;">
                <h3 style="margin-top: 0; margin-bottom: 6px; font-size: 16px;">Apply Blueprint to Tenant</h3>
                <p class="muted" style="font-size: 13px; margin: 0 0 14px 0;">
                    Safely installs missing modules for an existing tenant without disabling already-enabled features.
                </p>

                <form method="POST" action="{{ route('platform.templates.apply', $template) }}" id="apply-form">
                    @csrf
                    <div style="display: grid; gap: 12px;">
                        <x-input type="select" name="tenant_id" id="target-tenant-select" label="Select Target Tenant" :required="true">
                            <option value="">-- Choose Tenant --</option>
                            @foreach($allTenants as $tenantOption)
                                <option value="{{ $tenantOption->id }}">{{ $tenantOption->name }} ({{ $tenantOption->code }})</option>
                            @endforeach
                        </x-input>

                        <!-- Preview Area -->
                        <div id="preview-box" style="display: none; background: var(--color-neutral-50); border: 1px solid var(--color-neutral-200); border-radius: 8px; padding: 12px; font-size: 12.5px;">
                            <div style="font-weight: 600; margin-bottom: 6px; color: var(--color-ink-950);">Installation Preview:</div>
                            <div id="preview-will-enable" style="color: var(--clr-green-hover); margin-bottom: 4px;"></div>
                            <div id="preview-already-enabled" style="color: var(--color-neutral-600);"></div>
                        </div>

                        <button type="submit" class="btn" id="btn-apply-confirm" disabled style="width: 100%; justify-content: center;">
                            Apply Template Blueprint
                        </button>
                    </div>
                </form>
            </div>
        @endif
    </div>
</div>

<script nonce="{{ request()->attributes->get('csp_nonce') }}">
document.addEventListener('DOMContentLoaded', function() {
    const tenantSelect = document.getElementById('target-tenant-select');
    const previewBox = document.getElementById('preview-box');
    const willEnableDiv = document.getElementById('preview-will-enable');
    const alreadyEnabledDiv = document.getElementById('preview-already-enabled');
    const confirmBtn = document.getElementById('btn-apply-confirm');
    const templateId = {{ $template->id }};

    if (tenantSelect) {
        tenantSelect.addEventListener('change', function() {
            const tenantId = tenantSelect.value;
            if (!tenantId) {
                previewBox.style.display = 'none';
                confirmBtn.disabled = true;
                return;
            }

            fetch(`/platform-console/templates/${templateId}/tenants/${tenantId}/preview`)
                .then(res => res.json())
                .then(data => {
                    previewBox.style.display = 'block';
                    confirmBtn.disabled = false;

                    if (data.will_enable && data.will_enable.length > 0) {
                        willEnableDiv.innerHTML = `<strong>+ ${data.will_enable.length} modules to activate:</strong> ` + data.will_enable.join(', ');
                    } else {
                        willEnableDiv.innerHTML = `<em>All modules from this template are already enabled for this tenant.</em>`;
                    }

                    if (data.already_enabled && data.already_enabled.length > 0) {
                        alreadyEnabledDiv.innerHTML = `<strong>&bull; ${data.already_enabled.length} already active:</strong> ` + data.already_enabled.join(', ');
                    } else {
                        alreadyEnabledDiv.innerHTML = '';
                    }
                })
                .catch(() => {
                    previewBox.style.display = 'none';
                    confirmBtn.disabled = false;
                });
        });
    }
});
</script>
@endsection
