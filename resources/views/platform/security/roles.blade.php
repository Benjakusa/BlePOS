@extends('platform.layout')

@section('title', 'Roles and permissions')

@section('content')
    <section class="panel">
        <h2>Create custom role</h2>
        <form class="form" method="post" action="{{ route('platform.roles.store') }}">
            @csrf
            <label>Name<input name="name" maxlength="120" required></label>
            <label>Description<input name="description" maxlength="1000" required></label>
            <label>Reason<input name="reason" minlength="8" maxlength="1000" required></label>
            <label>Search permissions<input type="search" data-permission-search placeholder="Filter permissions"></label>
            <fieldset><legend>Permission grants</legend>
                @foreach($permissionsByArea as $area => $permissions)
                    <fieldset><legend>{{ ucfirst($area) }}</legend>
                    @foreach($permissions as $permission)
                        <label data-permission-label data-permission-name="{{ $permission->name }}" style="display:inline-flex;margin:5px 12px 5px 0;font-weight:400"><input type="checkbox" name="permission_ids[]" value="{{ $permission->id }}" data-initial="0"> {{ $permission->name }}</label>
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
                    <label>Name<input name="name" value="{{ $role->name }}" required></label>
                    <label>Description<input name="description" value="{{ $role->description }}" required></label>
                    <label>Reason<input name="reason" minlength="8" required></label>
                    <label>Search permissions<input type="search" data-permission-search placeholder="Filter permissions"></label>
                    <fieldset><legend>Permission changes</legend>
                        @foreach($permissionsByArea as $area => $permissions)
                            <fieldset><legend>{{ ucfirst($area) }}</legend>
                            @foreach($permissions as $permission)
                                @php($isAssigned = $role->permissions->contains('id', $permission->id))
                                <label data-permission-label data-permission-name="{{ $permission->name }}" style="display:inline-flex;margin:5px 12px 5px 0;font-weight:400"><input type="checkbox" name="permission_ids[]" value="{{ $permission->id }}" data-initial="{{ $isAssigned ? '1' : '0' }}" @checked($isAssigned)> {{ $permission->name }}</label>
                            @endforeach
                            </fieldset>
                        @endforeach
                    </fieldset>
                    <p class="muted" data-permission-diff aria-live="polite">No permission changes.</p>
                    <button type="submit">Save permission changes</button>
                </form>
                @if($role->users()->doesntExist())
                    <form method="post" action="{{ route('platform.roles.destroy', $role) }}" style="margin-top:12px">
                        @csrf @method('DELETE') <input name="reason" placeholder="Reason for deletion" minlength="8" required>
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
                    const added = checkboxes.filter((input) => input.dataset.initial === '0' && input.checked).map((input) => input.closest('label').dataset.permissionName);
                    const removed = checkboxes.filter((input) => input.dataset.initial === '1' && !input.checked).map((input) => input.closest('label').dataset.permissionName);
                    const parts = [];
                    if (added.length) parts.push(`Grant: ${added.join(', ')}`);
                    if (removed.length) parts.push(`Remove: ${removed.join(', ')}`);
                    diff.textContent = parts.length ? parts.join(' · ') : 'No permission changes.';
                };

                checkboxes.forEach((input) => input.addEventListener('change', refreshDiff));
                form.addEventListener('submit', (event) => {
                    const initial = checkboxes.some((input) => input.dataset.initial === '1');
                    if (initial && diff.textContent !== 'No permission changes.' && !window.confirm(diff.textContent + '\n\nSave this permission change?')) {
                        event.preventDefault();
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
