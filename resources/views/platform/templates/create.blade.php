@extends('platform.layout')

@section('title', 'Create Industry Template')

@section('content')
<header>
    <div>
        <h1>Create Industry Template</h1>
        <div class="muted">Define a new industry blueprint with bundled default modules and automated dependency resolution.</div>
    </div>
    <div>
        <a href="{{ route('platform.templates.index') }}" class="btn btn-secondary">Back to Templates</a>
    </div>
</header>

@if($errors->any())
    <div class="alert alert-danger" style="background: #fee2e2; border: 1px solid #fecaca; color: #b91c1c; padding: 12px 16px; border-radius: var(--radius-btn); margin-bottom: 20px;">
        <strong>Validation Error:</strong>
        <ul style="margin: 6px 0 0 18px; padding: 0;">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ route('platform.templates.store') }}" id="template-form">
    @csrf

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px;">
        <!-- Left: Metadata -->
        <div class="panel" style="max-width: 100%;">
            <h3 style="margin-top: 0; margin-bottom: 16px; font-size: 16px;">1. Template Details</h3>

            <div style="display: grid; gap: 14px;">
                <label>
                    Template Name
                    <input type="text" name="name" id="name" value="{{ old('name') }}" required placeholder="e.g. Pharmacy & Healthcare Store">
                    @error('name') <span class="error">{{ $message }}</span> @enderror
                </label>

                <label>
                    Slug (Lowercase, unique URL identifier)
                    <input type="text" name="slug" id="slug" value="{{ old('slug') }}" required placeholder="e.g. pharmacy-healthcare" pattern="^[a-z0-9]+(?:-[a-z0-9]+)*$">
                    <small class="muted">Must be lowercase letters, numbers, and hyphens only.</small>
                    @error('slug') <span class="error">{{ $message }}</span> @enderror
                </label>

                <label>
                    Description
                    <textarea name="description" rows="3" placeholder="Describe the target business model and key functional workflows...">{{ old('description') }}</textarea>
                    @error('description') <span class="error">{{ $message }}</span> @enderror
                </label>

                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; margin-top: 6px;">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }} style="width: auto;">
                    <span style="font-weight: 500;">Active for new tenant provisioning</span>
                </label>
            </div>
        </div>

        <!-- Right: Live Dependency Status & Quick Actions -->
        <div class="panel" style="max-width: 100%;">
            <h3 style="margin-top: 0; margin-bottom: 16px; font-size: 16px;">2. Dependency Validation</h3>

            <div id="dependency-warning" style="display: none; background: #fef3c7; border: 1px solid #fde68a; color: #92400e; padding: 14px; border-radius: var(--radius-btn); margin-bottom: 16px;">
                <div style="display: flex; gap: 10px; align-items: flex-start;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink: 0; margin-top: 2px;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    <div>
                        <strong>Missing Required Dependencies:</strong>
                        <div id="missing-deps-list" style="font-size: 13px; margin: 6px 0;"></div>
                        <button type="button" id="btn-auto-add" class="btn btn-sm" style="background: #b45309; margin-top: 6px;">
                            Add required modules automatically
                        </button>
                    </div>
                </div>
            </div>

            <div id="dependency-ok" style="background: #e8f8ee; border: 1px solid #bbf7d0; color: #166534; padding: 14px; border-radius: var(--radius-btn); margin-bottom: 16px;">
                <div style="display: flex; gap: 8px; align-items: center;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                    <span>All module dependencies satisfied.</span>
                </div>
            </div>

            <div style="font-size: 13px; color: var(--color-neutral-600); line-height: 1.5;">
                <p><strong>Rules:</strong></p>
                <ul style="padding-left: 18px; margin: 6px 0;">
                    <li>Core modules (POS, Products, Inventory, Cashier Drawer, Staff) are always included and locked.</li>
                    <li>Industry and Commercial modules automatically resolve prerequisites when saved.</li>
                    <li>The system computes the safe topological order when applying modules to tenants.</li>
                </ul>
            </div>

            <input type="hidden" name="auto_add_dependencies" id="auto_add_dependencies" value="0">
        </div>
    </div>

    <!-- Module Picker Section -->
    <div class="panel" style="max-width: 100%; margin-bottom: 24px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <div>
                <h3 style="margin: 0; font-size: 16px;">3. Select Default Modules</h3>
                <span class="muted" style="font-size: 13px;">Grouped by operational domain. Check modules to bundle with this template.</span>
            </div>
            <div id="selected-counter" style="font-weight: 600; font-size: 13px; background: var(--color-neutral-100); padding: 4px 10px; border-radius: 6px;">
                0 modules selected
            </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 18px;">
            @foreach($moduleCategories as $category => $categoryModules)
                <div style="background: var(--color-neutral-50); border: 1px solid var(--color-neutral-200); border-radius: var(--radius-card); padding: 14px;">
                    <h4 style="margin: 0 0 10px 0; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; color: var(--color-neutral-700);">
                        {{ $category }} ({{ $categoryModules->count() }})
                    </h4>
                    <div style="display: grid; gap: 8px;">
                        @foreach($categoryModules as $mod)
                            @php
                                $isCore = in_array($mod->slug, $coreModules, true);
                                $isChecked = $isCore || in_array($mod->slug, old('default_modules', []), true);
                            @endphp
                            <label style="display: flex; align-items: flex-start; gap: 8px; font-weight: normal; cursor: {{ $isCore ? 'default' : 'pointer' }}; background: #fff; padding: 8px 10px; border-radius: 6px; box-shadow: var(--shadow-input);">
                                @if($isCore)
                                    <input type="checkbox" checked disabled style="width: auto; margin-top: 3px;">
                                    <input type="hidden" name="default_modules[]" value="{{ $mod->slug }}">
                                @else
                                    <input type="checkbox" name="default_modules[]" value="{{ $mod->slug }}" class="module-checkbox" data-slug="{{ $mod->slug }}" data-name="{{ $mod->name }}" {{ $isChecked ? 'checked' : '' }} style="width: auto; margin-top: 3px;">
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
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
            <span>Save Industry Template</span>
        </button>
        <a href="{{ route('platform.templates.index') }}" class="btn btn-secondary">Cancel</a>
    </div>
</form>

<script>
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
    const nameInput = document.getElementById('name');
    const slugInput = document.getElementById('slug');

    // Auto-generate slug from name if not manually modified
    if (nameInput && slugInput) {
        nameInput.addEventListener('input', function() {
            if (!slugInput.dataset.manual) {
                slugInput.value = nameInput.value.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
            }
        });
        slugInput.addEventListener('input', function() {
            slugInput.dataset.manual = 'true';
        });
    }

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
