@extends('tenant.layout')

@section('title', 'Print Jobs')

@section('content')
<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
        <div>
            <h2 class="section-title" style="font-size: 18px;">Print Jobs</h2>
            <p class="muted" style="margin-top: 2px;">All customer print jobs and their current status</p>
        </div>
        <a href="{{ route('tenant.printing.jobs.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i>
            <span>New Job</span>
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success" style="margin-bottom: 16px;">{{ session('success') }}</div>
    @endif

    <div class="table-container">
        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Customer</th>
                        <th>Title</th>
                        <th>Status</th>
                        <th>Total</th>
                        <th>Balance Due</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($jobs as $job)
                        <tr>
                            <td>{{ $job->id }}</td>
                            <td>
                                <div>{{ $job->customer_name }}</div>
                                <small class="muted">{{ $job->customer_phone }}</small>
                            </td>
                            <td>{{ $job->title }}</td>
                            <td>
                                @php
                                    $badgeMap = [
                                        'quote'         => 'secondary',
                                        'approved'      => 'info',
                                        'in_design'     => 'warning',
                                        'in_production' => 'warning',
                                        'ready'         => 'success',
                                        'delivered'     => 'success',
                                        'cancelled'     => 'danger',
                                    ];
                                    $badge = $badgeMap[$job->status] ?? 'secondary';
                                @endphp
                                <span class="badge badge-{{ $badge }}">{{ ucfirst(str_replace('_', ' ', $job->status)) }}</span>
                            </td>
                            <td>{{ number_format($job->total_amount, 2) }}</td>
                            <td>{{ number_format($job->balance_due, 2) }}</td>
                            <td>
                                <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                                    <form method="POST" action="{{ route('tenant.printing.jobs.advance', $job) }}">
                                        @csrf
                                        <button class="btn btn-secondary btn-sm" title="Advance status">
                                            <i class="bi bi-arrow-right-circle"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 32px;" class="muted">No print jobs found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div style="margin-top: 16px;">
        {{ $jobs->links() }}
    </div>
</div>
@endsection
