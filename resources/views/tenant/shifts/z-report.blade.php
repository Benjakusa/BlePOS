<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Z-Report Shift Settlement - {{ $shift->shift_number }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Courier New', Courier, monospace, -apple-system, BlinkMacSystemFont, sans-serif;
        }
        body {
            background: #f1f5f9;
            display: flex;
            justify-content: center;
            padding: 24px;
            color: #0f172a;
        }
        .slip-container {
            width: 380px;
            background: #fff;
            padding: 24px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            border: 1px solid #e2e8f0;
            border-radius: 8px;
        }
        .actions-bar {
            width: 380px;
            margin-bottom: 16px;
            display: flex;
            justify-content: space-between;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            border-radius: 6px;
            font-size: 0.85rem;
            font-family: inherit;
            cursor: pointer;
            text-decoration: none;
            border: none;
        }
        .btn-primary {
            background: #2563eb;
            color: #fff;
        }
        .btn-secondary {
            background: #e2e8f0;
            color: #334155;
        }
        .header {
            text-align: center;
            border-bottom: 2px dashed #94a3b8;
            padding-bottom: 12px;
            margin-bottom: 12px;
        }
        .header h1 {
            font-size: 1.25rem;
            font-weight: 700;
            text-transform: uppercase;
        }
        .header .meta {
            font-size: 0.8rem;
            color: #475569;
            margin-top: 4px;
        }
        .row-item {
            display: flex;
            justify-content: space-between;
            font-size: 0.825rem;
            margin-bottom: 5px;
        }
        .divider {
            border-top: 1px dashed #cbd5e1;
            margin: 10px 0;
        }
        .double-divider {
            border-top: 2px dashed #475569;
            margin: 12px 0;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            font-size: 0.95rem;
            font-weight: bold;
            margin-bottom: 4px;
        }
        .signatures {
            margin-top: 24px;
            border-top: 1px dashed #94a3b8;
            padding-top: 16px;
            font-size: 0.75rem;
        }
        .sig-line {
            margin-top: 24px;
            border-bottom: 1px solid #475569;
            padding-bottom: 2px;
            display: flex;
            justify-content: space-between;
        }

        @media print {
            body {
                background: #fff;
                padding: 0;
            }
            .actions-bar {
                display: none !important;
            }
            .slip-container {
                box-shadow: none;
                border: none;
                width: 100%;
                max-width: 80mm;
                padding: 4px;
            }
        }
    </style>
</head>
<body>
    <div>
        <div class="actions-bar">
            <a href="{{ route('tenant.shifts.show', $shift) }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to Shift
            </a>
            <button onclick="window.print()" class="btn btn-primary">
                <i class="bi bi-printer"></i> Print Z-Report
            </button>
        </div>

        <div class="slip-container">
            <div class="header">
                <h1>{{ $tenant->name }}</h1>
                <div class="meta">{{ $shift->shop?->name ?? 'Main Branch' }}</div>
                <div class="meta">Register: {{ $shift->register?->name ?? 'Terminal 1' }}</div>
                <div class="meta" style="font-weight: bold; margin-top: 6px;">*** CASHIER SHIFT Z-REPORT ***</div>
            </div>

            <div class="row-item">
                <span>Shift Number:</span>
                <strong>{{ $shift->shift_number }}</strong>
            </div>
            <div class="row-item">
                <span>Cashier Operator:</span>
                <strong>{{ $shift->user?->name ?? 'Staff' }}</strong>
            </div>
            <div class="row-item">
                <span>Status:</span>
                <strong style="text-transform: uppercase;">{{ $shift->status }}</strong>
            </div>
            <div class="row-item">
                <span>Opened At:</span>
                <span>{{ $shift->opened_at?->format('d/m/Y H:i:s') }}</span>
            </div>
            <div class="row-item">
                <span>Closed At:</span>
                <span>{{ $shift->closed_at?->format('d/m/Y H:i:s') ?? 'IN PROGRESS (X-REPORT)' }}</span>
            </div>

            <div class="double-divider"></div>

            <div style="font-size: 0.85rem; font-weight: bold; margin-bottom: 6px; text-transform: uppercase;">
                Sales Tender Summary
            </div>
            <div class="row-item">
                <span>Completed Sales:</span>
                <strong>{{ $totalSalesCount }}</strong>
            </div>
            <div class="row-item">
                <span>Total Gross Sales:</span>
                <strong>KES {{ number_format($totalGross, 2) }}</strong>
            </div>
            <div class="row-item">
                <span>16% VAT Included:</span>
                <span>KES {{ number_format($totalVat, 2) }}</span>
            </div>
            <div class="row-item">
                <span>Discounts Given:</span>
                <span>KES {{ number_format($totalDiscount, 2) }}</span>
            </div>

            <div class="divider"></div>

            <div class="row-item">
                <span>Cash Sales:</span>
                <strong>KES {{ number_format((float) $shift->cash_sales, 2) }}</strong>
            </div>
            <div class="row-item">
                <span>M-Pesa Mobile:</span>
                <strong>KES {{ number_format((float) $shift->mpesa_sales, 2) }}</strong>
            </div>
            <div class="row-item">
                <span>Card / POS:</span>
                <strong>KES {{ number_format((float) $shift->card_sales, 2) }}</strong>
            </div>

            <div class="double-divider"></div>

            <div style="font-size: 0.85rem; font-weight: bold; margin-bottom: 6px; text-transform: uppercase;">
                Cash Drawer Balancing
            </div>
            <div class="row-item">
                <span>(+) Opening Float:</span>
                <span>KES {{ number_format((float) $shift->opening_float, 2) }}</span>
            </div>
            <div class="row-item">
                <span>(+) Cash Sales:</span>
                <span>KES {{ number_format((float) $shift->cash_sales, 2) }}</span>
            </div>
            <div class="row-item">
                <span>(+) Cash In (Additions):</span>
                <span>KES {{ number_format((float) $shift->cash_in, 2) }}</span>
            </div>
            <div class="row-item">
                <span>(-) Cash Out (Paid Out):</span>
                <span>KES {{ number_format((float) $shift->cash_out, 2) }}</span>
            </div>

            <div class="divider"></div>

            <div class="total-row">
                <span>Expected Drawer Cash:</span>
                <span>KES {{ number_format((float) $shift->expected_cash, 2) }}</span>
            </div>
            <div class="total-row">
                <span>Actual Counted Cash:</span>
                <span>KES {{ number_format((float) ($shift->actual_cash ?? 0), 2) }}</span>
            </div>

            <div class="total-row" style="{{ (float) $shift->variance < 0 ? 'color: #dc2626;' : ((float) $shift->variance > 0 ? 'color: #16a34a;' : '') }}">
                <span>Cash Variance:</span>
                <span>{{ (float) $shift->variance > 0 ? '+' : '' }}KES {{ number_format((float) ($shift->variance ?? 0), 2) }}</span>
            </div>

            @if($shift->closing_notes)
                <div class="divider"></div>
                <div style="font-size: 0.75rem; color: #475569; margin-top: 6px;">
                    <strong>Notes:</strong> {{ $shift->closing_notes }}
                </div>
            @endif

            <div class="signatures">
                <div class="sig-line">
                    <span>Cashier Signature</span>
                    <span>Date</span>
                </div>
                <div class="sig-line" style="margin-top: 32px;">
                    <span>Manager Signature</span>
                    <span>Date</span>
                </div>
            </div>

            <div style="text-align: center; margin-top: 20px; font-size: 0.7rem; color: #64748b;">
                Generated by BlePOS Platform &bull; {{ now()->format('d/m/Y H:i:s') }}
            </div>
        </div>
    </div>
</body>
</html>
