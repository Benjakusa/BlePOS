@extends('platform.layout')

@section('title', 'Roles and permissions')

@section('content')
    <section class="panel">
        <h2>Create custom role</h2>
        <form class="form" method="post" action="{{ route('platform.roles.store') }}">
            @csrf
            <x-input name="name" label="Name" maxlength="120" required />
            <x-input name="description" label="Description" maxlength="1000" required />
            <x-input name="reason" label="Reason" minlength="8" maxlength="1000" required />
            <x-input type="search" name="permission_search_new" label="Search permissions" data-permission-search />
            <fieldset><legend>Permission grants</legend>
                @foreach($permissionsByArea as $area => $permissions)
                    <fieldset><legend>{{ ucfirst($area) }}</legend>
                    @foreach($permissions as $permission)
                        <div data-permission-label data-permission-name="{{ $permission->name }}" style="display:inline-block;margin:5px 12px 5px 0;">
                            <div class="ui-choice-wrap" data-ui-input="checkbox">
                                <label class="ui-choice-label" for="perm_new_{{ $permission->id }}">
                                    <input type="checkbox" id="perm_new_{{ $permission->id }}" name="permission_ids[]" value="{{ $permission->id }}" data-initial="0" class="ui-choice-native">
                                    <span class="ui-check-box" aria-hidden="true"><i class="bi bi-check"></i></span>
                                    <span class="ui-choice-text">{{ $permission->name }}</span>
                                </label>
                            </div>
                        </div>
                    @endforeach
                    </fieldset>
                @endforeach
            </fieldset>
            <p class="muted" data-permission-diff aria-live="polite">No permission grants selected.</p>
            <button type="submit">Create role</button>
        </form>
    </section>

    @foreach($roles as $role)
        <details class="panel" style="margin-top:16px">
            <summary><strong>{{ $role->name }}</strong> · {{ $role->is_builtin ? 'Built-in' : 'Custom' }} · {{ $role->permissions->count() }} permissions</summary>
            <p>{{ $role->description }}</p>
            @if($role->is_builtin)
                <ul>@foreach($role->permissions as $permission)<li><code>{{ $permission->name }}</code></li>@endforeach</ul>
            @else
                <form class="form" method="post" action="{{ route('platform.roles.update', $role) }}">
                    @csrf @method('PUT')
                    <x-input name="name" label="Name" :value="$role->name" required />
                    <x-input name="description" label="Description" :value="$role->description" required />
                    <x-input name="reason" label="Reason" minlength="8" required />
                    <x-input type="search" name="permission_search_{{ $role->id }}" label="Search permissions" data-permission-search />
                    <fieldset><legend>Permission changes</legend>
                        @foreach($permissionsByArea as $area => $permissions)
                            <fieldset><legend>{{ ucfirst($area) }}</legend>
                            @foreach($permissions as $permission)
                                @php($isAssigned = $role->permissions->contains('id', $permission->id))
                                <div data-permission-label data-permission-name="{{ $permission->name }}" style="display:inline-block;margin:5px 12px 5px 0;">
                                    <div class="ui-choice-wrap" data-ui-input="checkbox">
                                        <label class="ui-choice-label" for="perm_{{ $role->id }}_{{ $permission->id }}">
                                            <input type="checkbox" id="perm_{{ $role->id }}_{{ $permission->id }}" name="permission_ids[]" value="{{ $permission->id }}" data-initial="{{ $isAssigned ? '1' : '0' }}" @checked($isAssigned) class="ui-choice-native">
                                            <span class="ui-check-box" aria-hidden="true"><i class="bi bi-check"></i></span>
                                            <span class="ui-choice-text">{{ $permission->name }}</span>
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                            </fieldset>
                        @endforeach
                    </fieldset>
                    <p class="muted" data-permission-diff aria-live="polite">No permission changes.</p>
                    <button type="submit">Save permission changes</button>
                </form>
                @if($role->users()->doesntExist())
                    <form method="post" action="{{ route('platform.roles.destroy', $role) }}" style="margin-top:12px;display:flex;gap:8px;align-items:center;">
                        @csrf @method('DELETE')
                        <x-input name="reason" label="Reason for deletion" minlength="8" required :compact="true" />
                        <button type="submit">Delete custom role</button>
                    </form>
                @endif
            @endif
        </details>
    @endforeach
@endsection

@pushOnce('scripts')
    <script nonce="{{ request()->attributes->get('csp_nonce') }}">
        document.querySelectorAll('form').forEach((form) => {
            const checkboxes = [...form.querySelectorAll('input[name="permission_ids[]"]')];
            const diff = form.querySelector('[data-permission-diff]');
            const search = form.querySelector('[data-permission-search]');

            if (diff && checkboxes.length) {
                const refreshDiff = () => {
                    const added = checkboxes.filter((input) => input.dataset.initial === '0' && input.checked).map((input) => input.closest('[data-permission-name]').dataset.permissionName);
                    const removed = checkboxes.filter((input) => input.dataset.initial === '1' && !input.checked).map((input) => input.closest('[data-permission-name]').dataset.permissionName);
                    const parts = [];
                    if (added.length) parts.push(`Grant: ${added.join(', ')}`);
                    if (removed.length) parts.push(`Remove: ${removed.join(', ')}`);
                    diff.textContent = parts.length ? parts.join(' · ') : 'No permission changes.';
                };

                checkboxes.forEach((input) => input.addEventListener('change', refreshDiff));
                form.addEventListener('submit', (event) => {
                    if (form.dataset.permissionsConfirmed === '1') { delete form.dataset.permissionsConfirmed; return; }
                    const initial = checkboxes.some((input) => input.dataset.initial === '1');
                    if (initial && diff.textContent !== 'No permission changes.') {
                        event.preventDefault();
                        window.BleMotion.confirm(diff.textContent + '\n\nSave this permission change?').then((confirmed) => {
                            if (confirmed) { form.dataset.permissionsConfirmed = '1'; form.requestSubmit(); }
                        });
                    }
                });
                refreshDiff();
            }

            if (search) {
                search.addEventListener('input', () => {
                    const term = search.value.trim().toLowerCase();
                    form.querySelectorAll('[data-permission-label]').forEach((label) => {
                        label.hidden = term !== '' && !label.dataset.permissionName.toLowerCase().includes(term);
                    });
                });
            }
        });
    </script>
@endPushOnce
