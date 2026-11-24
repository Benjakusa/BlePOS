@extends('platform.layout')

@section('title', 'Register New Tenant')

@section('content')
<section class="panel" style="max-width: 720px;">
    <h2>Tenant Onboarding Wizard</h2>
    <p class="muted" style="margin-bottom: 20px;">Provision a new multi-shop business entity with automated database setup and industry template installation.</p>

    <form method="post" action="{{ route('platform.tenants.store') }}" class="form" style="max-width: 100%;">
        @csrf

        <h3 style="margin-bottom: 12px;">1. Business Identity</h3>
        <x-input
            type="text"
            name="legal_name"
            label="Legal Business Name"
            :required="true"
        />

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
            <x-input
                type="text"
                name="code"
                label="Tenant Code (Unique)"
                :required="true"
            />
            <x-input
                type="text"
                name="slug"
                label="URL Slug"
                :required="true"
            />
        </div>

        <x-input
            type="text"
            name="kra_pin"
            label="KRA PIN (Optional)"
        />

        <h3 style="margin: 18px 0 12px;">2. Industry Blueprint &amp; Subscription</h3>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
            <x-input type="select" name="industry" label="Industry Blueprint">
                @forelse($industries as $ind)
                    <option value="{{ $ind->slug }}" @selected(old('industry') === $ind->slug)>{{ $ind->name }}</option>
                @empty
                    <option value="retail">Retail Supermarket &amp; General Store</option>
                @endforelse
            </x-input>

            <x-input type="select" name="plan_code" label="Subscription Plan">
                @forelse($plans as $plan)
                    <option value="{{ $plan->code }}" @selected(old('plan_code') === $plan->code)>{{ $plan->name }} (KES {{ number_format($plan->monthly_price) }}/mo)</option>
                @empty
                    <option value="starter">Starter Plan</option>
                @endforelse
            </x-input>
        </div>

        <x-input
            type="text"
            name="domain"
            label="Dedicated Custom Subdomain or Domain"
            :required="true"
        />

        <h3 style="margin: 18px 0 12px;">3. Initial Branch &amp; Owner</h3>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
            <x-input
                type="text"
                name="initial_shop_name"
                label="Initial Shop Name"
                value="Main Branch"
                :required="true"
            />
            <x-input
                type="text"
                name="initial_shop_code"
                label="Shop Code"
                value="MAIN"
                :required="true"
            />
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
            <x-input
                type="text"
                name="owner_name"
                label="Owner Full Name"
                :required="true"
            />
            <x-input
                type="email"
                name="owner_email"
                label="Owner Email (Invitation Recipient)"
                :required="true"
            />
        </div>

        <div style="margin-top: 16px; display: flex; align-items: center; gap: 12px;">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-rocket-takeoff"></i>
                <span>Start 30-Step Provisioning</span>
            </button>
            <a href="{{ route('platform.tenants.index') }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</section>
@endsection
