<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Receipt #{{ $sale->sale_number }} · {{ $tenant->name }}</title>
    <style>
        body { font-family: Helvetica, Arial, sans-serif; font-size: 13px; color: #000; margin: 0; padding: 12px; background: #fff; }
        .receipt { max-width: 320px; margin: 0 auto; }
        .center { text-align: center; }
        .bold { font-weight: bold; }
        .divider { border-top: 1px dashed #000; margin: 8px 0; }
        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        th, td { padding: 3px 0; }
        .right { text-align: right; }
        @media print {
            .no-print { display: none !important; }
            body { padding: 0; }
        }
    </style>
</head>
<body>
<div class="receipt">
    <div class="no-print" style="margin-bottom: 16px; text-align: center;">
        <button onclick="window.print()" style="padding: 8px 16px; background: var(--clr-green); color: #fff; border: none; border-radius: 4px; font-weight: bold; cursor: pointer;">Print Receipt</button>
        <a href="{{ route('tenant.pos.checkout') }}" style="display: inline-block; margin-left: 8px; padding: 8px 14px; background: #e2e8f0; color: #334155; text-decoration: none; border-radius: 4px;">Back to POS</a>
    </div>

    <div class="center">
        <div class="bold" style="font-size: 16px;">{{ $tenant->name }}</div>
        <div>{{ $sale->shop->name }}</div>
        <div>{{ $sale->shop->address ?? 'Nairobi, Kenya' }}</div>
        @if($tenant->tax_pin)
            <div>PIN: {{ $tenant->tax_pin }}</div>
        @endif
        <div class="divider"></div>
        <div>TAX INVOICE / CASH SALE</div>
        <div class="bold">#{{ $sale->sale_number }}</div>
        <div>{{ $sale->created_at->format('d/m/Y H:i:s') }}</div>
        <div>Cashier: {{ $sale->user->name ?? 'Staff' }}</div>
    </div>

    <div class="divider"></div>

    <table>
        <thead>
            <tr>
                <th style="text-align: left;">Item</th>
                <th class="right">Qty</th>
                <th class="right">Price</th>
                <th class="right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sale->items as $item)
                <tr>
                    <td colspan="4" class="bold">{{ $item->product->name ?? 'Item' }}</td>
                </tr>
                <tr>
                    <td></td>
                    <td class="right">{{ (float) $item->quantity }}</td>
                    <td class="right">{{ number_format((float) $item->unit_price, 2) }}</td>
                    <td class="right bold">{{ number_format((float) $item->total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="divider"></div>

    <table>
        <tr>
            <td>Subtotal:</td>
            <td class="right">KES {{ number_format((float) $sale->subtotal, 2) }}</td>
        </tr>
        <tr>
            <td>VAT (Included):</td>
            <td class="right">KES {{ number_format((float) $sale->tax_total, 2) }}</td>
        </tr>
        @if((float) $sale->discount_total > 0)
        <tr>
            <td>Discount:</td>
            <td class="right">-KES {{ number_format((float) $sale->discount_total, 2) }}</td>
        </tr>
        @endif
        <tr class="bold" style="font-size: 14px;">
            <td>TOTAL:</td>
            <td class="right">KES {{ number_format((float) $sale->grand_total, 2) }}</td>
        </tr>
    </table>

    <div class="divider"></div>

    <table>
        @foreach($sale->payments as $payment)
            <tr>
                <td>Paid ({{ strtoupper($payment->method) }}{{ $payment->reference ? ' - '.$payment->reference : '' }}):</td>
                <td class="right">KES {{ number_format((float) $payment->amount, 2) }}</td>
            </tr>
        @endforeach
        <tr>
            <td>Change Due:</td>
            <td class="right">KES {{ number_format((float) $sale->change_due, 2) }}</td>
        </tr>
    </table>

    <div class="divider"></div>

    <div class="center" style="font-size: 11px;">
        <div>Thank you for your business!</div>
        <div>Powered by BlePOS Platform</div>
    </div>
</div>
</body>
</html>
