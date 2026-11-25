@extends('tenant.layout')

@section('title', 'Printing & Branding')

@section('content')
<div class="card">
    <div style="margin-bottom: 24px;">
        <h2 class="section-title" style="font-size: 18px;">Printing & Branding</h2>
        <p class="muted" style="margin-top: 2px;">Overview of print jobs, production status, and material stock</p>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 16px; margin-bottom: 32px;">
        <div class="card" style="text-align: center; padding: 24px;">
            <div style="font-size: 32px; font-weight: 700; color: var(--color-primary);">{{ $totalJobs }}</div>
            <div class="muted" style="margin-top: 4px;">Total Jobs</div>
        </div>
        <div class="card" style="text-align: center; padding: 24px;">
            <div style="font-size: 32px; font-weight: 700; color: var(--color-warning);">{{ $inProduction }}</div>
            <div class="muted" style="margin-top: 4px;">In Production</div>
        </div>
        <div class="card" style="text-align: center; padding: 24px;">
            <div style="font-size: 32px; font-weight: 700; color: var(--color-success);">{{ $readyForCollection }}</div>
            <div class="muted" style="margin-top: 4px;">Ready for Collection</div>
        </div>
        <div class="card" style="text-align: center; padding: 24px;">
            <div style="font-size: 32px; font-weight: 700; color: var(--color-danger);">{{ $lowStockMaterials }}</div>
            <div class="muted" style="margin-top: 4px;">Low Stock Materials</div>
        </div>
    </div>

    <div style="display: flex; gap: 12px; flex-wrap: wrap;">
        <a href="{{ route('tenant.printing.jobs') }}" class="btn btn-primary">
            <i class="bi bi-clipboard-check"></i>
            <span>View All Jobs</span>
        </a>
        <a href="{{ route('tenant.printing.jobs.create') }}" class="btn btn-secondary">
            <i class="bi bi-plus-circle"></i>
            <span>New Job</span>
        </a>
        <a href="{{ route('tenant.printing.materials') }}" class="btn btn-secondary">
            <i class="bi bi-box-seam"></i>
            <span>Materials</span>
        </a>
    </div>
</div>
@endsection
