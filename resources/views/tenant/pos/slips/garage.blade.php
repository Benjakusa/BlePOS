<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Garage Job Card - {{ $jobCard->job_number }}</title>
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
            <a href="{{ route('tenant.pos.checkout', ['mode' => 'garage']) }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Garage POS
            </a>
            <button onclick="window.print()" class="btn btn-primary">
                <i class="bi bi-printer"></i> Print Job Card
            </button>
        </div>

        <div class="slip-container">
            <div class="header">
                <h1>{{ $tenant->name }}</h1>
                <div style="font-size: 0.8rem; color: #475569; margin-top: 4px;">AUTO GARAGE &amp; SERVICE REPAIR</div>
                <div style="font-weight: bold; margin-top: 6px; font-size: 0.9rem;">JOB CARD INTAKE &amp; WORK ORDER</div>
            </div>

            <div class="row-item">
                <span>Job Card Number:</span>
                <strong>{{ $jobCard->job_number }}</strong>
            </div>
            <div class="row-item">
                <span>Received Date:</span>
                <span>{{ $jobCard->received_at?->format('d/m/Y H:i') }}</span>
            </div>
            <div class="row-item">
                <span>Status:</span>
                <strong style="text-transform: uppercase; color: #d97706;">{{ $jobCard->status }}</strong>
            </div>

            <div class="divider"></div>

            <div class="vehicle-box">
                <div style="font-size: 1.15rem; font-weight: bold; color: #1e40af; margin-bottom: 4px;">
                    {{ $jobCard->vehicle?->plate }}
                </div>
                <div style="font-size: 0.85rem; color: #334155;">
                    {{ $jobCard->vehicle?->make }} {{ $jobCard->vehicle?->model }}
                </div>
                <div style="font-size: 0.8rem; color: #64748b; margin-top: 4px;">
                    Mileage In: <strong>{{ number_format($jobCard->mileage_in ?? 0) }} KM</strong>
                </div>
            </div>

            <div class="row-item">
                <span>Customer Name:</span>
                <strong>{{ $jobCard->customer?->name }}</strong>
            </div>
            <div class="row-item">
                <span>Customer Phone:</span>
                <span>{{ $jobCard->customer?->phone }}</span>
            </div>

            <div class="divider"></div>

            <div style="font-size: 0.825rem; font-weight: bold; margin-bottom: 4px;">REPORTED ISSUES / SERVICES:</div>
            <div style="font-size: 0.8rem; background: #f8fafc; padding: 8px; border-radius: 4px; margin-bottom: 10px;">
                {{ $jobCard->notes }}
            </div>

            <div class="divider"></div>

            <div class="row-item">
                <span>Initial Labour Estimate:</span>
                <span>KES {{ number_format((float) ($jobCard->estimate?->total_labour ?? 0), 2) }}</span>
            </div>
            @php
                $depositTotal = $jobCard->payments->sum('amount');
            @endphp
            <div class="row-item" style="font-weight: bold;">
                <span>Deposit Paid:</span>
                <span style="color: #16a34a;">KES {{ number_format((float) $depositTotal, 2) }}</span>
            </div>

            <div class="double-divider"></div>

            <div style="margin-top: 24px; font-size: 0.75rem;">
                <div style="margin-bottom: 24px; border-bottom: 1px solid #94a3b8; padding-bottom: 4px; display: flex; justify-content: space-between;">
                    <span>Customer Authorization</span>
                    <span>Date</span>
                </div>
                <div style="border-bottom: 1px solid #94a3b8; padding-bottom: 4px; display: flex; justify-content: space-between;">
                    <span>Service Advisor / Mechanic</span>
                    <span>Intake OK</span>
                </div>
            </div>

            <div style="text-align: center; margin-top: 16px; font-size: 0.7rem; color: #64748b;">
                Parts and additional repairs subject to customer consent.
            </div>
        </div>
    </div>
</body>
</html>
