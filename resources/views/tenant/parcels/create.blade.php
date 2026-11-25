@extends('tenant.layout')

@section('title', 'Receive Parcel')

@section('content')
<div class="card" style="max-width:900px;">
    <div style="margin-bottom:20px;">
        <a href="{{ route('tenant.parcels.index') }}" class="btn btn-ghost btn-sm"><i class="bi bi-arrow-left"></i> Parcel desk</a>
        <h1 class="section-title" style="margin-top:14px;">Receive a parcel</h1>
        <p class="muted">This records the sender and recipient, creates a tracking number, and places the parcel at {{ $office->name }}. Dispatch is handled by an office dispatcher.</p>
    </div>

    @if($errors->any())
        <div class="alert alert-danger" role="alert">
            <ul style="margin:0;padding-left:18px;">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('tenant.parcels.store') }}">
        @csrf
        <div class="grid grid-2" style="gap:16px;">
            <x-input name="sender_name" label="Sender name" :value="old('sender_name')" required />
            <x-input name="sender_phone" label="Sender phone" :value="old('sender_phone')" required />
            <x-input name="recipient_name" label="Recipient name" :value="old('recipient_name')" required />
            <x-input name="recipient_phone" label="Recipient phone" :value="old('recipient_phone')" required />
            <x-input name="contents" label="Parcel contents" :value="old('contents')" placeholder="e.g. Documents, clothing, spare parts" required />
            <x-input type="select" name="destination_shop_id" label="Destination office" required>
                <option value="">Choose a destination</option>
                @foreach($offices as $destination)
                    <option value="{{ $destination->id }}" @selected((string) old('destination_shop_id') === (string) $destination->id)>
                        {{ $destination->name }} — {{ $destination->city }}
                    </option>
                @endforeach
            </x-input>
            <x-input type="select" name="service_level" label="Service level" required>
                <option value="standard" @selected(old('service_level', 'standard') === 'standard')>Standard</option>
                <option value="express" @selected(old('service_level') === 'express')>Express</option>
            </x-input>
            <x-input name="weight_kg" label="Weight (kg), optional" type="number" step="0.01" min="0.01" :value="old('weight_kg')" />
            <x-input name="fee" label="Delivery fee (KES)" type="number" step="0.01" min="0" :value="old('fee', '0.00')" required />
            <x-input name="note" label="Receiving note, optional" :value="old('note')" placeholder="Condition or special handling" />
        </div>
        <div style="display:flex;gap:10px;margin-top:20px;">
            <button type="submit" class="btn btn-primary"><i class="bi bi-box-arrow-in-down"></i> Receive parcel</button>
            <a href="{{ route('tenant.parcels.index') }}" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
</div>
@endsection
