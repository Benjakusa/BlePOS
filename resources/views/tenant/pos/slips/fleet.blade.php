<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fleet Gate Pass & Agreement - Booking #{{ $booking->id }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Courier New', Courier, monospace, sans-serif; }
        body { background: #f1f5f9; display: flex; justify-content: center; padding: 24px; color: #0f172a; }
        .slip-container { width: 420px; background: #fff; padding: 24px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); border: 1px solid #e2e8f0; border-radius: 8px; }
        .actions-bar { width: 420px; margin-bottom: 16px; display: flex; justify-content: space-between; }
        .btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 14px; border-radius: 6px; font-size: 0.85rem; font-family: inherit; cursor: pointer; text-decoration: none; border: none; }
        .btn-primary { background: #2563eb; color: #fff; }
        .btn-secondary { background: #e2e8f0; color: #334155; }
        .header { text-align: center; border-bottom: 2px dashed #94a3b8; padding-bottom: 12px; margin-bottom: 12px; }
        .header h1 { font-size: 1.25rem; font-weight: 700; text-transform: uppercase; }
        .row-item { display: flex; justify-content: space-between; font-size: 0.825rem; margin-bottom: 5px; }
        .divider { border-top: 1px dashed #cbd5e1; margin: 10px 0; }
        .double-divider { border-top: 2px dashed #475569; margin: 12px 0; }
        .vehicle-box { padding: 12px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; margin-bottom: 10px; }
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
            <a href="{{ route('tenant.pos.checkout', ['mode' => 'fleet']) }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Fleet POS
            </a>
            <button onclick="window.print()" class="btn btn-primary">
                <i class="bi bi-printer"></i> Print Gate Pass
            </button>
        </div>

        <div class="slip-container">
            <div class="header">
                <h1>{{ $tenant->name }}</h1>
                <div style="font-size: 0.8rem; color: #475569; margin-top: 4px;">CAR HIRE &amp; FLEET MANAGEMENT</div>
                <div style="font-weight: bold; margin-top: 6px; font-size: 0.9rem;">DISPATCH GATE PASS &amp; HANDOVER</div>
            </div>

            <div class="row-item">
                <span>Booking ID:</span>
                <strong>#BK-{{ str_pad($booking->id, 5, '0', STR_PAD_LEFT) }}</strong>
            </div>
            <div class="row-item">
                <span>Date Dispatched:</span>
                <span>{{ $booking->created_at?->format('d/m/Y H:i') }}</span>
            </div>

            <div class="divider"></div>

            <div class="vehicle-box">
                <div style="font-size: 1.1rem; font-weight: bold; color: #1e40af; margin-bottom: 4px;">
                    {{ $booking->vehicle?->plate }}
                </div>
                <div style="font-size: 0.85rem; color: #334155;">
                    {{ $booking->vehicle?->make }} {{ $booking->vehicle?->model }} ({{ $booking->vehicle?->year ?? 'Fleet' }})
                </div>
            </div>

            <div class="row-item">
                <span>Hirer / Customer:</span>
                <strong>{{ $booking->customer?->name }}</strong>
            </div>
            <div class="row-item">
                <span>Phone Number:</span>
                <span>{{ $booking->customer?->phone }}</span>
            </div>
            @if($booking->customer?->id_number)
            <div class="row-item">
                <span>ID / Passport:</span>
                <span>{{ $booking->customer?->id_number }}</span>
            </div>
            @endif
            @if($booking->customer?->license_number)
            <div class="row-item">
                <span>Driving License:</span>
                <span>{{ $booking->customer?->license_number }}</span>
            </div>
            @endif

            <div class="divider"></div>

            <div class="row-item">
                <span>Pickup Time:</span>
                <span>{{ \Carbon\Carbon::parse($booking->pickup_at)->format('d/m/Y H:i') }}</span>
            </div>
            <div class="row-item">
                <span>Scheduled Return:</span>
                <span>{{ \Carbon\Carbon::parse($booking->return_at)->format('d/m/Y H:i') }}</span>
            </div>
            <div class="row-item">
                <span>Daily Rental Rate:</span>
                <strong>KES {{ number_format((float) $booking->agreed_rate, 2) }}</strong>
            </div>

            <div class="double-divider"></div>

            <div class="row-item" style="font-size: 0.95rem; font-weight: bold;">
                <span>Security Deposit Held:</span>
                <span style="color: #16a34a;">KES {{ number_format((float) $booking->deposit_amount, 2) }}</span>
            </div>
            <div class="row-item" style="font-size: 0.95rem; font-weight: bold;">
                <span>Est. Total Billed:</span>
                <span>KES {{ number_format((float) $booking->total_billed, 2) }}</span>
            </div>

            <div class="divider"></div>

            <div style="margin-top: 24px; font-size: 0.75rem;">
                <div style="margin-bottom: 24px; border-bottom: 1px solid #94a3b8; padding-bottom: 4px; display: flex; justify-content: space-between;">
                    <span>Hirer Signature &amp; Acceptance</span>
                    <span>Date</span>
                </div>
                <div style="border-bottom: 1px solid #94a3b8; padding-bottom: 4px; display: flex; justify-content: space-between;">
                    <span>Fleet Officer Signature</span>
                    <span>Gate Pass OK</span>
                </div>
            </div>

            <div style="text-align: center; margin-top: 16px; font-size: 0.7rem; color: #64748b;">
                Drive safely &bull; Please return vehicle with matching fuel level.
            </div>
        </div>
    </div>
</body>
</html>
