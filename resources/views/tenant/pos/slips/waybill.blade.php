<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parcel Waybill - {{ $parcel->tracking_number }}</title>
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
        .party-box { padding: 10px; background: #f8fafc; border-radius: 6px; margin-bottom: 8px; font-size: 0.8rem; }
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
            <a href="{{ route('tenant.pos.checkout', ['mode' => 'parcels']) }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> POS Terminal
            </a>
            <button onclick="window.print()" class="btn btn-primary">
                <i class="bi bi-printer"></i> Print Waybill
            </button>
        </div>

        <div class="slip-container">
            <div class="header">
                <h1>{{ $tenant->name }}</h1>
                <div style="font-size: 0.8rem; color: #475569; margin-top: 4px;">FREIGHT &amp; PARCEL COURIER</div>
                <div style="font-weight: bold; margin-top: 6px; font-size: 0.9rem;">OFFICIAL WAYBILL RECEIPT</div>
            </div>

            <div class="barcode-box">
                <div class="barcode">*{{ $parcel->tracking_number }}*</div>
                <div style="font-size: 0.75rem; color: #64748b; font-weight: 600;">TRACKING: {{ $parcel->tracking_number }}</div>
            </div>

            <div class="row-item">
                <span>Date &amp; Time:</span>
                <span>{{ $parcel->created_at?->format('d/m/Y H:i:s') }}</span>
            </div>
            <div class="row-item">
                <span>Origin Office:</span>
                <strong>{{ $parcel->originOffice?->name ?? 'Main' }}</strong>
            </div>
            <div class="row-item">
                <span>Destination Office:</span>
                <strong style="color: #2563eb;">{{ $parcel->destinationOffice?->name ?? 'Destination' }}</strong>
            </div>
            <div class="row-item">
                <span>Service Level:</span>
                <strong style="text-transform: uppercase;">{{ $parcel->service_level }}</strong>
            </div>

            <div class="divider"></div>

            <div class="party-box">
                <strong style="display: block; margin-bottom: 4px; color: #334155;">SENDER:</strong>
                <div>{{ $parcel->sender_name }}</div>
                <div>Tel: {{ $parcel->sender_phone }}</div>
            </div>

            <div class="party-box">
                <strong style="display: block; margin-bottom: 4px; color: #334155;">RECIPIENT:</strong>
                <div>{{ $parcel->recipient_name }}</div>
                <div>Tel: {{ $parcel->recipient_phone }}</div>
            </div>

            <div class="divider"></div>

            <div class="row-item">
                <span>Package Contents:</span>
                <strong>{{ $parcel->contents }}</strong>
            </div>
            <div class="row-item">
                <span>Weight (KG):</span>
                <span>{{ number_format((float) ($parcel->weight_kg ?? 1), 2) }} KG</span>
            </div>

            <div class="double-divider"></div>

            <div class="row-item" style="font-size: 1rem; font-weight: bold;">
                <span>Freight Fee:</span>
                <span>KES {{ number_format((float) $parcel->fee, 2) }}</span>
            </div>
            <div class="row-item" style="font-size: 0.8rem; color: #16a34a; font-weight: 600;">
                <span>Payment Status:</span>
                <span>PAID AT COUNTER</span>
            </div>

            <div class="double-divider"></div>

            <div style="margin-top: 20px; font-size: 0.75rem;">
                <div style="margin-bottom: 24px; border-bottom: 1px solid #94a3b8; padding-bottom: 4px; display: flex; justify-content: space-between;">
                    <span>Sender Signature</span>
                    <span>Date</span>
                </div>
                <div style="border-bottom: 1px solid #94a3b8; padding-bottom: 4px; display: flex; justify-content: space-between;">
                    <span>Recipient Signature (On Delivery)</span>
                    <span>Date</span>
                </div>
            </div>

            <div style="text-align: center; margin-top: 16px; font-size: 0.7rem; color: #64748b;">
                Thank you for shipping with {{ $tenant->name }}!
            </div>
        </div>
    </div>
</body>
</html>
