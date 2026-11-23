@extends('tenant.layout')

@section('title', 'Shift #' . $shift->shift_number)

@section('content')
<header>
    <div>
        <h1>Shift #{{ $shift->shift_number }}</h1>
        <div class="muted">
            Shop: <strong>{{ $shift->shop->name }}</strong> | 
            Register: <strong>{{ $shift->register->name }}</strong> | 
            Cashier: <strong>{{ $shift->user->name ?? 'Staff' }}</strong> |
            Opened: {{ $shift->opened_at->format('Y-m-d H:i') }}
        </div>
    </div>
    <div style="display: flex; gap: 8px;">
        @if($shift->isOpen())
            <a href="{{ route('tenant.pos.checkout') }}" class="btn">Go to POS</a>
        @endif
        <a href="{{ route('tenant.shifts.index') }}" class="btn btn-secondary">All Shifts</a>
    </div>
</header>

<div class="grid-cards">
    <div class="stat-card">
        <div class="label">Opening Float</div>
        <div class="value">KES {{ number_format((float) $shift->opening_float, 2) }}</div>
    </div>
    <div class="stat-card">
        <div class="label">Cash Sales</div>
        <div class="value" style="color: #166534;">KES {{ number_format((float) $shift->cash_sales, 2) }}</div>
    </div>
    <div class="stat-card">
        <div class="label">M-Pesa Sales</div>
        <div class="value" style="color: #0284c7;">KES {{ number_format((float) $shift->mpesa_sales, 2) }}</div>
    </div>
    <div class="stat-card">
        <div class="label">Card Sales</div>
        <div class="value">KES {{ number_format((float) $shift->card_sales, 2) }}</div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px;">
    <!-- Cash Reconciliation Card -->
    <div class="panel">
        <h2 style="font-size: 18px; margin-top: 0; margin-bottom: 14px;">Cash Drawer Status</h2>
        <table style="font-size: 14px;">
            <tr>
                <td>Opening Float:</td>
                <td style="text-align: right;">KES {{ number_format((float) $shift->opening_float, 2) }}</td>
            </tr>
            <tr>
                <td>+ Cash In (Floats/Paid In):</td>
                <td style="text-align: right; color: #166534;">+KES {{ number_format((float) $shift->cash_in, 2) }}</td>
            </tr>
            <tr>
                <td>- Cash Out (Paid Out/Drops):</td>
                <td style="text-align: right; color: #dc2626;">-KES {{ number_format((float) $shift->cash_out, 2) }}</td>
            </tr>
            <tr>
                <td>+ Cash Sales:</td>
                <td style="text-align: right; color: #166534;">+KES {{ number_format((float) $shift->cash_sales, 2) }}</td>
            </tr>
            <tr style="border-top: 2px solid #cbd5e1; font-weight: bold; font-size: 15px;">
                <td>Expected Cash in Drawer:</td>
                <td style="text-align: right; color: #0f172a;">KES {{ number_format((float) $shift->calculateExpectedCash(), 2) }}</td>
            </tr>
            @if($shift->isClosed())
                <tr style="font-weight: bold;">
                    <td>Actual Counted Cash:</td>
                    <td style="text-align: right;">KES {{ number_format((float) $shift->actual_cash, 2) }}</td>
                </tr>
                <tr style="font-weight: bold; font-size: 15px;">
                    <td>Variance (Over / Short):</td>
                    <td style="text-align: right; color: {{ (float) $shift->variance >= 0 ? '#166534' : '#dc2626' }};">
                        KES {{ number_format((float) $shift->variance, 2) }}
                    </td>
                </tr>
            @endif
        </table>

        @if($shift->isOpen())
            <!-- Cash Movement Form -->
            <div style="margin-top: 20px; border-top: 1px solid #e2e8f0; padding-top: 16px;">
                <h3 style="font-size: 15px; margin: 0 0 10px;">Record Cash In / Cash Out</h3>
                <form action="{{ route('tenant.shifts.cash-movement', $shift) }}" method="POST" style="display: flex; gap: 8px; flex-wrap: wrap;">
                    @csrf
                    <select name="type" required style="width: 110px;">
                        <option value="in">Cash In</option>
                        <option value="out">Cash Out</option>
                    </select>
                    <input type="number" step="0.01" name="amount" placeholder="Amount (KES)" required style="width: 130px;">
                    <input type="text" name="reason" placeholder="Reason (e.g. Petty cash / Till drop)" required style="flex: 1; min-width: 140px;">
                    <button type="submit" class="btn btn-secondary">Record</button>
                </form>
            </div>
        @endif
    </div>

    <!-- Close Shift Form or Closed Info -->
    <div class="panel">
        @if($shift->isOpen())
            <h2 style="font-size: 18px; margin-top: 0; margin-bottom: 14px; color: #b91c1c;">Close & Reconcile Shift</h2>
            <form action="{{ route('tenant.shifts.close', $shift) }}" method="POST" class="form" onsubmit="return confirm('Are you sure you want to close this shift? This action cannot be undone.')">
                @csrf
                <div>
                    <label for="actual_cash">Physical Counted Cash (KES) *</label>
                    <input type="number" step="0.01" id="actual_cash" name="actual_cash" required placeholder="Count physical money in till drawer">
                    <span class="muted" style="font-size: 12px;">Count all notes and coins present in the register.</span>
                    @error('actual_cash') <div class="badge badge-danger" style="margin-top: 4px;">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label for="closing_notes">Closing Notes (Optional)</label>
                    <textarea id="closing_notes" name="closing_notes" rows="2" placeholder="Explain any discrepancy or variance"></textarea>
                </div>

                <div style="margin-top: 8px;">
                    <button type="submit" class="btn btn-danger" style="width: 100%;">Close Shift Now</button>
                </div>
            </form>
        @else
            <h2 style="font-size: 18px; margin-top: 0; margin-bottom: 14px;">Shift Completed</h2>
            <div style="display: grid; gap: 8px; font-size: 14px;">
                <div><span class="muted">Status:</span> <span class="badge badge-secondary">Closed</span></div>
                <div><span class="muted">Closed At:</span> {{ $shift->closed_at?->format('Y-m-d H:i') }}</div>
                <div><span class="muted">Variance:</span> 
                    <strong style="color: {{ (float) $shift->variance >= 0 ? '#166534' : '#dc2626' }};">
                        KES {{ number_format((float) $shift->variance, 2) }}
                    </strong>
                </div>
                @if($shift->closing_notes)
                    <div style="margin-top: 8px; background: #f8fafc; padding: 10px; border-radius: 4px; font-family: monospace;">
                        {{ $shift->closing_notes }}
                    </div>
                @endif
            </div>
        @endif
    </div>
</div>

<div class="panel">
    <h2 style="font-size: 18px; margin-top: 0; margin-bottom: 14px;">Sales During This Shift (Latest 50)</h2>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Sale #</th>
                    <th>Time</th>
                    <th style="text-align: right;">Total (KES)</th>
                    <th>Status</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($shift->sales as $sale)
                    <tr>
                        <td><strong>{{ $sale->sale_number }}</strong></td>
                        <td>{{ $sale->created_at->format('H:i:s') }}</td>
                        <td style="text-align: right; font-weight: 650;">KES {{ number_format((float) $sale->grand_total, 2) }}</td>
                        <td>
                            @if($sale->status === 'completed')
                                <span class="badge badge-success">Completed</span>
                            @else
                                <span class="badge badge-secondary">{{ $sale->status }}</span>
                            @endif
                        </td>
                        <td style="text-align: right;">
                            <a href="{{ route('tenant.pos.receipt', $sale) }}" class="btn btn-secondary btn-sm" target="_blank">Receipt</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="text-align: center; color: #64748b; padding: 20px;">No sales processed during this shift yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
