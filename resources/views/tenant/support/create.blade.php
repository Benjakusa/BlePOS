@extends('tenant.layout')

@section('title', 'New Support Ticket')

@section('content')
<div style="max-width:780px;margin:0 auto">
    <div class="page-header" style="margin-bottom:20px"><h1>New support ticket</h1><p class="muted">Tell the platform team what you need help with.</p></div>
    <div class="panel"><form method="post" action="{{ route('tenant.support.cases.store') }}" style="display:grid;gap:16px">
        @csrf
        <x-input name="subject" label="Subject" value="{{ old('subject') }}" required maxlength="255" />
        <x-input type="select" name="category" label="Category" required>
            @foreach(['general' => 'General', 'billing' => 'Billing', 'hardware_pos' => 'Hardware / POS', 'inventory' => 'Inventory', 'user_access' => 'User access', 'etims_tax' => 'eTIMS / Tax', 'system_bug' => 'System issue'] as $value => $label)
                <option value="{{ $value }}" @selected(old('category', 'general') === $value)>{{ $label }}</option>
            @endforeach
        </x-input>
        <x-input type="select" name="priority" label="Priority" required>
            @foreach(['low', 'medium', 'high', 'urgent'] as $priority)<option value="{{ $priority }}" @selected(old('priority', 'medium') === $priority)>{{ ucfirst($priority) }}</option>@endforeach
        </x-input>
        <label for="description">Description</label>
        <textarea id="description" name="description" rows="7" required maxlength="10000" class="form-control">{{ old('description') }}</textarea>
        @error('description')<div class="text-danger">{{ $message }}</div>@enderror
        <div style="display:flex;gap:10px"><button class="btn btn-primary" type="submit">Submit ticket</button><a class="btn btn-secondary" href="{{ route('tenant.support.cases.index') }}">Cancel</a></div>
    </form></div>
</div>
@endsection
