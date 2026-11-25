@extends('tenant.layout')

@section('title', 'New Print Job')

@section('content')
<div class="card" style="max-width: 640px;">
    <div style="margin-bottom: 24px;">
        <h2 class="section-title" style="font-size: 18px;">New Print Job</h2>
        <p class="muted" style="margin-top: 2px;">Create a new customer print job request</p>
    </div>

    @if($errors->any())
        <div class="alert alert-danger" style="margin-bottom: 16px;">
            <ul style="margin: 0; padding-left: 16px;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('tenant.printing.jobs.store') }}">
        @csrf

        <div style="display: flex; flex-direction: column; gap: 16px;">
            <x-input
                name="customer_name"
                label="Customer Name"
                :value="old('customer_name')"
                required
                placeholder="e.g. John Doe"
            />

            <x-input
                name="customer_phone"
                label="Customer Phone"
                :value="old('customer_phone')"
                required
                placeholder="e.g. 0712345678"
            />

            <x-input
                name="title"
                label="Job Title"
                :value="old('title')"
                required
                placeholder="e.g. 500 Flyers A5"
            />

            <x-input
                type="textarea"
                name="notes"
                label="Notes (optional)"
                :value="old('notes')"
                placeholder="Any special instructions or requirements..."
                rows="4"
            />

            <div style="display: flex; gap: 12px; flex-wrap: wrap; margin-top: 8px;">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i>
                    <span>Create Job</span>
                </button>
                <a href="{{ route('tenant.printing.jobs') }}" class="btn btn-ghost">Cancel</a>
            </div>
        </div>
    </form>
</div>
@endsection
