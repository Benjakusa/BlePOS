@extends('platform.layout')

@section('title', 'Edit Template · ' . $template->name)

@section('content')
<header>
    <div>
        <h1>Edit Industry Template: {{ $template->name }}</h1>
        <div class="muted">Update blueprint metadata, configuration, and default module selections.</div>
    </div>
    <div style="display: flex; gap: 8px;">
        <a href="{{ route('platform.templates.show', $template) }}" class="btn btn-secondary">View Template</a>
        <a href="{{ route('platform.templates.index') }}" class="btn btn-secondary">Back to Templates</a>
    </div>
</header>

@if($errors->any())
    <div class="alert alert-danger" style="background: #EAEEF3; border: 1px solid #DC2626; color: #b91c1c; padding: 12px 16px; border-radius: var(--radius-btn); margin-bottom: 20px;">
        <strong>Validation Error:</strong>
        <ul style="margin: 6px 0 0 18px; padding: 0;">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ route('platform.templates.update', $template) }}" id="template-form">
    @csrf
    @method('PUT')

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px;">
        <!-- Left: Metadata -->
        <div class="panel" style="max-width: 100%;">
            <h3 style="margin-top: 0; margin-bottom: 16px; font-size: 16px;">1. Template Details</h3>

            <div style="display: grid; gap: 6px;">
                <x-input
                    type="text"
                    name="name"
                    id="name"
                    label="Template Name"
                    :value="$template->name"
                    :required="true"
                />

                <x-input
                    type="text"
                    name="slug"
                    id="slug"
                    label="Slug (Lowercase, unique URL identifier)"
                    :value="$template->slug"
                    pattern="^[a-z0-9]+(?:-[a-z0-9]+)*$"
                    helper="Must be lowercase letters, numbers, and hyphens only."
                    :required="true"
                />

                <x-input
                    type="textarea"
                    name="description"
                    id="description"
                    label="Description"
                    :value="$template->description"
                    rows="3"
                />

                <x-input
                    type="toggle"
                    name="is_active"
                    id="is_active"
                    label="Active for new tenant provisioning"
                    value="1"
                    :checked="(bool) old('is_active', $template->is_active)"
                />
            </div>
        </div>

        <!-- Right: Dependency Status & Rules -->
        <div class="panel" style="max-width: 100%;">
            <h3 style="margin-top: 0; margin-bottom: 16px;">2. Dependency Validation</h3>

            <div id="dependency-warning" class="alert alert-warning" style="display: none; margin-bottom: 16px;">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <div>
                    <strong>Missing Required Dependencies:</strong>
                    <div id="missing-deps-list" style="font-size: 13px; margin: 6px 0;"></div>
                    <button type="button" id="btn-auto-add" class="btn btn-sm btn-secondary" style="margin-top: 6px;">
                        Add required modules automatically
                    </button>
                </div>
            </div>

            <div id="dependency-ok" class="alert alert-success" style="margin-bottom: 16px;">
                <i class="bi bi-check-circle-fill"></i>
                <span>All module dependencies satisfied.</span>
            </div>

            <div style="font-size: 13px; color: var(--color-neutral-600); line-height: 1.5;">
                <p><strong>Rules:</strong></p>
                <ul style="padding-left: 18px; margin: 6px 0;">
                    <li>Core modules (POS, Products, Inventory, Cashier Drawer, Staff) cannot be removed.</li>
                    <li>Updating default modules will not automatically disable modules on already provisioned tenants.</li>
                </ul>
            </div>

            <input type="hidden" name="auto_add_dependencies" id="auto_add_dependencies" value="0">
        </div>
    </div>

    <!-- Module Picker Section -->
    <div class="panel" style="max-width: 100%; margin-bottom: 24px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <div>
                <h3 style="margin: 0; font-size: 16px;">3. Default Modules</h3>
                <span class="muted" style="font-size: 13px;">Modules enabled automatically when this template is selected.</span>
            </div>
            <div id="selected-counter" style="font-weight: 600; font-size: 13px; background: var(--color-neutral-100); padding: 4px 10px; border-radius: 6px;">
                0 modules selected
            </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 18px;">
            @php $currentSelected = old('default_modules', $template->default_modules ?? []); @endphp
            @foreach($moduleCategories as $category => $categoryModules)
                <div style="background: var(--color-neutral-50); border: 1px solid var(--color-neutral-200); border-radius: var(--radius-card); padding: 14px;">
                    <h4 style="margin: 0 0 10px 0; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; color: var(--color-neutral-700);">
                        {{ $category }} ({{ $categoryModules->count() }})
                    </h4>
                    <div style="display: grid; gap: 8px;">
                        @foreach($categoryModules as $mod)
                            @php
                                $isCore = in_array($mod->slug, $coreModules, true);
                                $isChecked = $isCore || in_array($mod->slug, $currentSelected, true);
                            @endphp
                            <label class="ui-choice-label" style="display: flex; align-items: flex-start; gap: 10px; font-weight: normal; cursor: {{ $isCore ? 'default' : 'pointer' }}; background: var(--clr-surface); padding: 10px 12px; border-radius: 10px; box-shadow: var(--shadow-sm);">
                                @if($isCore)
                                    <input type="checkbox" checked disabled class="ui-choice-native">
                                    <span class="ui-check-box" aria-hidden="true" style="margin-top: 2px; opacity: 0.7;"><i class="bi bi-check"></i></span>
                                    <input type="hidden" name="default_modules[]" value="{{ $mod->slug }}">
                                @else
                                    <input type="checkbox" name="default_modules[]" value="{{ $mod->slug }}" class="ui-choice-native module-checkbox" data-slug="{{ $mod->slug }}" data-name="{{ $mod->name }}" {{ $isChecked ? 'checked' : '' }}>
                                    <span class="ui-check-box" aria-hidden="true" style="margin-top: 2px;"><i class="bi bi-check"></i></span>
                                @endif
                                <div style="flex: 1; min-width: 0;">
                                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 6px;">
                                        <span style="font-weight: 600; font-size: 13px; color: var(--color-ink-950);">{{ $mod->name }}</span>
                                        @if($isCore)
                                            <span class="badge badge-info" style="font-size: 10px; padding: 1px 6px;">Core (Locked)</span>
                                        @endif
                                    </div>
                                    <div style="font-size: 12px; color: var(--color-neutral-600); margin-top: 2px;">
                                        {{ $mod->description }}
                                    </div>
                                    @if(!empty($mod->dependencies))
                                        <div style="font-size: 11px; color: var(--color-neutral-500); margin-top: 4px;">
                                            Needs: <strong>{{ implode(', ', $mod->dependencies) }}</strong>
                                        </div>
                                    @endif
                                </div>
                            </label>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div style="display: flex; gap: 12px; align-items: center;">
        <button type="submit" class="btn" style="padding: 10px 20px; font-size: 15px;">
            <i class="bi bi-check-lg" aria-hidden="true"></i>
            <span>Update Industry Template</span>
        </button>
        <a href="{{ route('platform.templates.show', $template) }}" class="btn btn-secondary">Cancel</a>
    </div>
</form>

<script nonce="{{ request()->attributes->get('csp_nonce') }}">
document.addEventListener('DOMContentLoaded', function () {
    const dependencies = @json($moduleDependencies);
    const coreModules = @json($coreModules);
    const checkboxes = document.querySelectorAll('.module-checkbox');
    const warningBox = document.getElementById('dependency-warning');
    const okBox = document.getElementById('dependency-ok');
    const missingList = document.getElementById('missing-deps-list');
    const autoAddBtn = document.getElementById('btn-auto-add');
    const autoAddInput = document.getElementById('auto_add_dependencies');
    const counter = document.getElementById('selected-counter');

    function getSelectedSlugs() {
        const selected = new Set(coreModules);
        checkboxes.forEach(cb => {
            if (cb.checked) {
                selected.add(cb.dataset.slug);
            }
        });
        return selected;
    }

    function checkDependencies() {
        const selected = getSelectedSlugs();
        const missing = {};
        let hasMissing = false;

        selected.forEach(slug => {
            const deps = dependencies[slug] || [];
            deps.forEach(dep => {
                if (!selected.has(dep)) {
                    if (!missing[slug]) missing[slug] = [];
                    missing[slug].push(dep);
                    hasMissing = true;
                }
            });
        });

        counter.textContent = selected.size + ' modules selected';

        if (hasMissing) {
            let html = '<ul style="margin: 4px 0 0 16px; padding: 0;">';
            for (const [mod, deps] of Object.entries(missing)) {
                html += `<li><strong>${mod}</strong> requires: ${deps.join(', ')}</li>`;
            }
            html += '</ul>';
            missingList.innerHTML = html;
            warningBox.style.display = 'block';
            okBox.style.display = 'none';
        } else {
            warningBox.style.display = 'none';
            okBox.style.display = 'block';
        }
    }

    autoAddBtn.addEventListener('click', function () {
        autoAddInput.value = '1';
        let added = true;
        let loops = 0;
        while (added && loops < 10) {
            added = false;
            loops++;
            const selected = getSelectedSlugs();
            selected.forEach(slug => {
                const deps = dependencies[slug] || [];
                deps.forEach(dep => {
                    if (!selected.has(dep)) {
                        const cb = document.querySelector(`.module-checkbox[data-slug="${dep}"]`);
                        if (cb && !cb.checked) {
                            cb.checked = true;
                            added = true;
                        }
                    }
                });
            });
        }
        checkDependencies();
    });

    checkboxes.forEach(cb => {
        cb.addEventListener('change', checkDependencies);
    });

    checkDependencies();
});
</script>
@endsection
