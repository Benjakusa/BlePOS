@extends('platform.layout')

@section('title', 'Register New Tenant')

@section('content')
<section class="panel" style="max-width: 720px;">
    <h2>Tenant Onboarding Wizard</h2>
    <p class="muted">Provision a new multi-shop business entity with automated database setup and industry template installation.</p>

    <form method="post" action="{{ route('platform.tenants.store') }}" class="form" style="max-width: 100%;">
        @csrf

        <h3>1. Business Identity</h3>
        <label>
            Legal Business Name
            <input type="text" name="legal_name" value="{{ old('legal_name') }}" required placeholder="e.g. Acme Supermarkets Kenya Ltd">
            @error('legal_name') <span class="error">{{ $message }}</span> @enderror
        </label>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
            <label>
                Tenant Code (Unique)
                <input type="text" name="code" value="{{ old('code') }}" required placeholder="e.g. ACME01">
                @error('code') <span class="error">{{ $message }}</span> @enderror
            </label>
            <label>
                URL Slug
                <input type="text" name="slug" value="{{ old('slug') }}" required placeholder="e.g. acme-supermarkets">
                @error('slug') <span class="error">{{ $message }}</span> @enderror
            </label>
        </div>

        <label>
            KRA PIN (Optional)
            <input type="text" name="kra_pin" value="{{ old('kra_pin') }}" placeholder="e.g. P051234567X">
            @error('kra_pin') <span class="error">{{ $message }}</span> @enderror
        </label>

        <h3>2. Industry Blueprint & Subscription</h3>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
            <label>
                Industry Blueprint
                <select name="industry" style="border: 1px solid #b9c9bf; border-radius: 5px; padding: 11px 12px; font: inherit;">
                    <option value="general_retail">General Retail</option>
                    <option value="supermarket">Supermarket</option>
                    <option value="pharmacy">Pharmacy & Healthcare</option>
                    <option value="restaurant">Restaurant & F&B</option>
                    <option value="hospitality">Hotel & Hospitality</option>
                    <option value="wholesale">Wholesale & Distribution</option>
                    <option value="fuel_station">Fuel Station</option>
                    <option value="salon">Salon & Spa</option>
                </select>
            </label>
            <label>
                Subscription Plan
                <select name="plan_code" style="border: 1px solid #b9c9bf; border-radius: 5px; padding: 11px 12px; font: inherit;">
                    @forelse($plans as $plan)
                        <option value="{{ $plan->code }}">{{ $plan->name }} (KES {{ number_format($plan->monthly_price) }}/mo)</option>
                    @empty
                        <option value="starter">Starter Plan</option>
                    @endforelse
                </select>
            </label>
        </div>

        <label>
            Dedicated Custom Subdomain or Domain
            <input type="text" name="domain" value="{{ old('domain') }}" required placeholder="e.g. acme.blepos.co.ke">
            @error('domain') <span class="error">{{ $message }}</span> @enderror
        </label>

        <h3>3. Initial Branch & Owner</h3>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
            <label>
                Initial Shop Name
                <input type="text" name="initial_shop_name" value="{{ old('initial_shop_name', 'Main Branch') }}" required>
            </label>
            <label>
                Shop Code
                <input type="text" name="initial_shop_code" value="{{ old('initial_shop_code', 'MAIN') }}" required>
            </label>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
            <label>
                Owner Full Name
                <input type="text" name="owner_name" value="{{ old('owner_name') }}" required>
            </label>
            <label>
                Owner Email (Invitation Recipient)
                <input type="email" name="owner_email" value="{{ old('owner_email') }}" required>
            </label>
        </div>

        <div style="margin-top: 10px;">
            <button type="submit">Start 30-Step Provisioning</button>
            <a href="{{ route('platform.tenants.index') }}" style="margin-left: 12px; color: #63736b;">Cancel</a>
        </div>
    </form>
</section>
@endsection
