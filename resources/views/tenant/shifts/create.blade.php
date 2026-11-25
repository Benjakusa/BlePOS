@extends('tenant.layout')

@section('title', 'Open Shift')

@section('content')
<header>
    <div>
        <h1>Open Work Shift</h1>
        <div class="muted">Record opening cash float in register till to start processing sales</div>
    </div>
    <div>
        <a href="{{ route('tenant.shifts.index') }}" class="btn btn-secondary">Shift History</a>
    </div>
</header>

<div class="panel">
    <form action="{{ route('tenant.shifts.store') }}" method="POST" class="form" style="max-width: 600px; display: flex; flex-direction: column; gap: 14px;">
        @csrf

        {{-- Cashier & Branch Header --}}
        <div style="background: var(--clr-grey-tint, #eaedf2); padding: 12px 14px; border-radius: var(--radius-sm, 8px); margin-bottom: 4px;">
            <p style="margin: 0; font-size: 13px;"><strong>Cashier:</strong> {{ auth()->user()->name }} ({{ auth()->user()->email }})</p>
        </div>

        {{-- Shop / Branch Selection --}}
        <x-input
            type="select"
            name="shop_id"
            id="shift_shop_id"
            label="Branch / Shop Location"
            :required="true"
        >
            @foreach($shops as $shopItem)
                <option value="{{ $shopItem->id }}" @selected(old('shop_id', $activeShop?->id) == $shopItem->id)>
                    {{ $shopItem->name }} ({{ $shopItem->code }})
                </option>
            @endforeach
        </x-input>

        {{-- Register / Till Selection --}}
        <x-input
            type="select"
            name="register_id"
            id="shift_register_id"
            label="Register / Till Terminal"
            :required="true"
        >
            @foreach($shops as $shopItem)
                @foreach($shopItem->registers as $reg)
                    <option value="{{ $reg->id }}" data-shop="{{ $shopItem->id }}" @selected(old('register_id') == $reg->id)>
                        {{ $shopItem->name }} &mdash; {{ $reg->name }} ({{ $reg->code }})
                    </option>
                @endforeach
            @endforeach
        </x-input>

        <x-input
            type="number"
            step="0.01"
            min="0"
            name="opening_float"
            label="Opening Cash Float (KES)"
            value="{{ old('opening_float', '0.00') }}"
            :required="true"
            :autofocus="true"
            helper="Count the physical notes and coins in the drawer before starting."
        />

        <x-input
            type="textarea"
            name="notes"
            label="Shift Opening Notes (Optional)"
            rows="2"
            placeholder="e.g. Received float envelope #42 from safe"
            value="{{ old('notes') }}"
        />

        <div style="margin-top: 8px;">
            <button type="submit" class="btn">Open Shift & Begin Sales</button>
        </div>
    </form>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const shopSelect = document.getElementById('shift_shop_id');
    const registerSelect = document.getElementById('shift_register_id');
    if (!shopSelect || !registerSelect) return;

    function filterRegisters() {
        const selectedShop = shopSelect.value;
        const options = registerSelect.querySelectorAll('option');
        let firstMatch = null;

        options.forEach(opt => {
            const shop = opt.getAttribute('data-shop');
            if (!shop || shop === selectedShop) {
                opt.style.display = '';
                if (!firstMatch) firstMatch = opt;
            } else {
                opt.style.display = 'none';
            }
        });

        // If currently selected option is hidden, select first visible
        const currentSelected = registerSelect.selectedOptions[0];
        if (currentSelected && currentSelected.style.display === 'none' && firstMatch) {
            firstMatch.selected = true;
        }
    }

    shopSelect.addEventListener('change', filterRegisters);
    filterRegisters();
});
</script>
@endpush
@endsection
