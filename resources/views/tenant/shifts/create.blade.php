@extends('tenant.layout')

@section('title', 'Open Cashier Shift')

@section('content')
<header>
    <div>
        <h1>Open Cashier Shift</h1>
        <div class="muted">Start a register session with opening float</div>
    </div>
    <div>
        <a href="{{ route('tenant.shifts.index') }}" class="btn btn-secondary">Back to Shifts</a>
    </div>
</header>

<div class="panel">
    <form action="{{ route('tenant.shifts.store') }}" method="POST" class="form">
        @csrf
        <div>
            <label for="shop_id">Shop Location *</label>
            <select id="shop_id" name="shop_id" required>
                @foreach($shops as $shop)
                    <option value="{{ $shop->id }}">{{ $shop->name }} ({{ $shop->code }})</option>
                @endforeach
            </select>
            @error('shop_id') <div class="badge badge-danger" style="margin-top: 4px;">{{ $message }}</div> @enderror
        </div>

        <div>
            <label for="register_id">Register / Till Terminal *</label>
            <select id="register_id" name="register_id" required>
                @foreach($shops as $shop)
                    @foreach($shop->registers as $reg)
                        <option value="{{ $reg->id }}" data-shop="{{ $shop->id }}">{{ $shop->name }} &mdash; {{ $reg->name }} ({{ $reg->code }})</option>
                    @endforeach
                @endforeach
            </select>
            @error('register_id') <div class="badge badge-danger" style="margin-top: 4px;">{{ $message }}</div> @enderror
        </div>

        <div>
            <label for="opening_float">Opening Cash Float (KES) *</label>
            <input type="number" step="0.01" id="opening_float" name="opening_float" value="{{ old('opening_float', '0.00') }}" required>
            <span class="muted" style="font-size: 12px;">Physical cash in the drawer at the start of shift.</span>
            @error('opening_float') <div class="badge badge-danger" style="margin-top: 4px;">{{ $message }}</div> @enderror
        </div>

        <div>
            <label for="notes">Shift Notes (Optional)</label>
            <textarea id="notes" name="notes" rows="2" placeholder="e.g. Morning shift">{{ old('notes') }}</textarea>
        </div>

        <div style="margin-top: 8px;">
            <button type="submit" class="btn">Open Shift</button>
        </div>
    </form>
</div>
@endsection
