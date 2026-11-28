<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gym Front Desk Pass - {{ $payment->reference }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Courier New', Courier, monospace, sans-serif; }
        body { background: #f1f5f9; display: flex; justify-content: center; padding: 24px; color: #0f172a; }
        .slip-container { width: 380px; background: #fff; padding: 24px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); border: 1px solid #e2e8f0; border-radius: 8px; }
        .actions-bar { width: 380px; margin-bottom: 16px; display: flex; justify-content: space-between; }
        .btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 14px; border-radius: 6px; font-size: 0.85rem; font-family: inherit; cursor: pointer; text-decoration: none; border: none; }
        .btn-primary { background: #2563eb; color: #fff; }
        .btn-secondary { background: #e2e8f0; color: #334155; }
        .header { text-align: center; border-bottom: 2px dashed #94a3b8; padding-bottom: 12px; margin-bottom: 12px; }
        .header h1 { font-size: 1.25rem; font-weight: 700; text-transform: uppercase; }
        .barcode-box { text-align: center; margin: 14px 0; padding: 8px; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 4px; }
        .barcode { font-size: 1.8rem; letter-spacing: 5px; font-weight: 900; font-family: monospace; }
        .row-item { display: flex; justify-content: space-between; font-size: 0.825rem; margin-bottom: 6px; }
        .divider { border-top: 1px dashed #cbd5e1; margin: 10px 0; }
        .double-divider { border-top: 2px dashed #475569; margin: 12px 0; }
        @media print {
            body { background: #fff; padding: 0; }
            .actions-bar { display: none !important; }
            .slip-container { box-shadow: none; border: none; width: 100%; max-width: 80mm; padding: 4px; }
        }
    </style>
</head>
<body>
    <div>
        <div class="actions-bar">
            <a href="{{ route('tenant.pos.checkout', ['mode' => 'gym']) }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Gym POS
            </a>
            <button onclick="window.print()" class="btn btn-primary">
                <i class="bi bi-printer"></i> Print Pass
            </button>
        </div>

        <div class="slip-container">
            <div class="header">
                <h1>{{ $tenant->name }}</h1>
                <div style="font-size: 0.8rem; color: #475569; margin-top: 4px;">FITNESS CLUB &amp; GYMNASIUM</div>
                <div style="font-weight: bold; margin-top: 6px; font-size: 0.9rem;">FRONT DESK ADMISSION PASS</div>
            </div>

            <div class="barcode-box">
                <div class="barcode">*{{ $payment->reference }}*</div>
                <div style="font-size: 0.75rem; color: #64748b; font-weight: 600;">PASS REF: {{ $payment->reference }}</div>
            </div>

            <div class="row-item">
                <span>Member Name:</span>
                <strong>{{ $payment->member?->name ?? 'Walk-in Guest' }}</strong>
            </div>
            <div class="row-item">
                <span>Phone:</span>
                <span>{{ $payment->member?->phone ?? '—' }}</span>
            </div>
            <div class="row-item">
                <span>Plan / Service:</span>
                <strong>{{ $payment->subscription?->plan?->name ?? 'Day Pass' }}</strong>
            </div>
            <div class="row-item">
                <span>Validity:</span>
                <span>{{ $payment->subscription?->start_date?->format('d/m/Y') }} &ndash; {{ $payment->subscription?->end_date?->format('d/m/Y') }}</span>
            </div>

            <div class="divider"></div>

            <div class="row-item">
                <span>Issued At:</span>
                <span>{{ $payment->paid_at?->format('d/m/Y H:i') }}</span>
            </div>
            <div class="row-item">
                <span>Tender Method:</span>
                <strong style="text-transform: uppercase;">{{ $payment->method }}</strong>
            </div>

            <div class="double-divider"></div>

            <div class="row-item" style="font-size: 1.05rem; font-weight: bold;">
                <span>Total Amount Paid:</span>
                <span>KES {{ number_format((float) $payment->amount, 2) }}</span>
            </div>

            <div class="double-divider"></div>

            <div style="text-align: center; margin-top: 16px; font-size: 0.75rem; color: #475569;">
                Please present this pass at the turnstile / front desk reader.
                <br>Have a great workout!
            </div>
        </div>
    </div>
</body>
</html>
