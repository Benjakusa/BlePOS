@extends('tenant.layout')

@section('title', 'Employee Activity')

@section('content')
<div class="page-header" style="display:flex;justify-content:space-between;align-items:flex-end;gap:16px;flex-wrap:wrap;margin-bottom:20px;">
    <div>
        <h1 class="page-title">Employee Activity</h1>
        <p class="muted" style="margin-top:4px;">Review staff actions and completed sales for {{ $rangeLabel }}.</p>
    </div>
    <form method="GET" action="{{ route('tenant.activity.index') }}" style="display:flex;gap:8px;align-items:flex-end;flex-wrap:wrap;">
        <div data-period-filter><x-input type="select" name="period" label="Report period" wrapperClass="activity-period-select" data-period-select>
            <option value="day" @selected($period === 'day')>Day</option>
            <option value="month" @selected($period === 'month')>Month</option>
            <option value="year" @selected($period === 'year')>Year</option>
        </x-input></div>
        <div data-period-field="day"><x-input type="date" name="date_day" label="Date" :value="$period === 'day' ? $date : now()->format('Y-m-d')" /></div>
        <div data-period-field="month"><x-input type="month" name="date_month" label="Month" :value="$period === 'month' ? $date : now()->format('Y-m')" /></div>
        <div data-period-field="year"><x-input type="number" name="date_year" label="Year" min="2000" max="2100" :value="$period === 'year' ? $date : now()->format('Y')" /></div>
        <button type="submit" class="btn">View report</button>
    </form>
</div>

<div class="grid-cards">
    <div class="stat-card"><div class="stat-body"><div class="stat-label">Recorded actions</div><div class="stat-value">{{ number_format($totals['actions']) }}</div></div></div>
    <div class="stat-card"><div class="stat-body"><div class="stat-label">Completed sales</div><div class="stat-value">{{ number_format($totals['sales_count']) }}</div></div></div>
    <div class="stat-card"><div class="stat-body"><div class="stat-label">Sales value</div><div class="stat-value">KES {{ number_format($totals['sales_total'], 2) }}</div></div></div>
</div>

<div class="card" style="margin-bottom:20px;">
    <h2 style="margin-bottom:14px;">Totals by employee</h2>
    <div class="table-container">
        <table>
            <thead><tr><th>Employee</th><th>Role</th><th class="right">Actions</th><th class="right">Sales</th><th class="right">Sales value</th></tr></thead>
            <tbody>
            @forelse($employees as $row)
                <tr>
                    <td><strong>{{ $row['employee']->name }}</strong><br><span class="muted">{{ $row['employee']->email }}</span></td>
                    <td>{{ $row['employee']->roles->pluck('name')->join(', ') ?: '—' }}</td>
                    <td class="right">{{ number_format($row['actions_count']) }}</td>
                    <td class="right">{{ number_format($row['sales_count']) }}</td>
                    <td class="right">KES {{ number_format($row['sales_total'], 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="muted">No employees found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:14px;">
        <h2>Activity details</h2>
        <span class="muted">{{ $activity->total() }} recorded actions</span>
    </div>
    <div class="table-container">
        <table>
            <thead><tr><th>When</th><th>Employee</th><th>Action</th><th>Record</th><th>Shop</th></tr></thead>
            <tbody>
            @forelse($activity as $entry)
                <tr>
                    <td>{{ $entry->occurred_at->format('d M Y, H:i:s') }}</td>
                    <td>{{ $entry->user?->name ?? 'Former employee' }}</td>
                    <td>{{ $entry->action }}</td>
                    <td>{{ $entry->subject_label ?? ($entry->subject_type ? class_basename($entry->subject_type).' #'.$entry->subject_id : '—') }}</td>
                    <td>{{ $entry->shop?->name ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="muted">No actions were recorded for this period. Activity logging starts from the time this feature is deployed.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div style="margin-top:16px;">{{ $activity->links() }}</div>
</div>

<p class="muted" style="margin-top:12px;">Activity logs begin at deployment. Completed sales are summarized from existing sale records.</p>
@endsection
