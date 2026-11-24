@extends('tenant.layout')

@section('title', 'POS Checkout')

@push('styles')
<style>
    .pos-grid { display: grid; grid-template-columns: 1fr 380px; gap: 20px; height: calc(100vh - 120px); }
    .pos-main { display: flex; flex-direction: column; gap: 16px; min-height: 0; }
    .pos-cart { flex: 1; overflow-y: auto; background: var(--clr-surface); border-radius: var(--radius-card); box-shadow: var(--shadow-sm); }
    .pos-summary { background: var(--clr-surface); border-radius: var(--radius-card); box-shadow: var(--shadow-sm); padding: 24px; display: flex; flex-direction: column; justify-content: space-between; }
    .scanner-box { display: flex; gap: 12px; background: var(--clr-surface); padding: 14px; border-radius: var(--radius-card); box-shadow: var(--shadow-sm); }
    .totals-line { display: flex; justify-content: space-between; padding: 6px 0; font-size: 14px; }
    .totals-grand { font-size: 24px; font-weight: 700; color: var(--clr-green); border-top: 2px dashed var(--clr-border); padding-top: 14px; margin-top: 12px; }
    .qty-btn { width: 30px; height: 30px; border: 1px solid var(--clr-border); background: var(--clr-grey-tint); border-radius: var(--radius-sm); cursor: pointer; font-weight: 600; display: inline-flex; align-items: center; justify-content: center; }
    .qty-btn:hover { background: var(--clr-border); }
    .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(15,23,42,.4); backdrop-filter: blur(2px); z-index: 999; align-items: center; justify-content: center; }
    .modal-box { background: var(--clr-surface); border-radius: var(--radius-card); padding: 28px; width: 100%; max-width: 480px; box-shadow: var(--shadow-lg); }
</style>
@endpush

@section('content')
<div class="pos-grid">
    <div class="pos-main">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
            <div>
                <h1 class="page-title" style="font-size: 20px;">POS Terminal</h1>
                <div class="muted" style="margin-top: 2px; font-size: 13px;">
                    Shift: <strong>{{ $openShift->shift_number }}</strong> &bull; 
                    Shop: <strong>{{ $openShift->shop->name }}</strong> &bull; 
                    Cashier: <strong>{{ auth()->user()->name }}</strong>
                </div>
            </div>
            <div>
                <a href="{{ route('tenant.shifts.show', $openShift) }}" class="btn btn-secondary btn-sm">
                    <i class="bi bi-clock-history"></i>
                    <span>Shift Details</span>
                </a>
            </div>
        </div>

        <div class="scanner-box">
            <div style="flex: 1;">
                <input type="text" id="barcode-input" placeholder="Scan barcode or type SKU / product name and press Enter..." autofocus autocomplete="off" style="width: 100%; font-size: 15px; padding: 12px 14px;">
            </div>
            <button type="button" id="lookup-btn" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i>
                <span>Add to Cart</span>
            </button>
        </div>

        <div class="pos-cart table-container">
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th style="width: 110px; text-align: center;">Qty</th>
                            <th style="width: 110px; text-align: right;">Price</th>
                            <th style="width: 90px; text-align: right;">Tax %</th>
                            <th style="width: 110px; text-align: right;">Total</th>
                            <th style="width: 50px; text-align: center;"></th>
                        </tr>
                    </thead>
                    <tbody id="cart-rows">
                        <tr id="empty-cart-row">
                            <td colspan="6" style="text-align: center; color: var(--clr-muted); padding: 48px 16px;">
                                <i class="bi bi-cart3" style="font-size: 32px; display: block; margin-bottom: 8px;"></i>
                                Cart is empty. Scan a barcode or search to add items.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="pos-summary">
        <div>
            <h2 class="section-title" style="margin-bottom: 16px; border-bottom: 1px solid var(--clr-header-tint); padding-bottom: 10px;">Order Summary</h2>
            
            <div class="field" style="margin-bottom: 16px;">
                <select id="register-select">
                    @foreach($registers as $reg)
                        <option value="{{ $reg->id }}" {{ $openShift->register_id == $reg->id ? 'selected' : '' }}>{{ $reg->name }} ({{ $reg->code }})</option>
                    @endforeach
                </select>
                <label for="register-select">Register / Terminal</label>
            </div>

            <div class="totals-line">
                <span class="muted">Subtotal (Excl. Tax):</span>
                <strong id="summary-subtotal">KES 0.00</strong>
            </div>
            <div class="totals-line">
                <span class="muted">Tax (VAT 16%):</span>
                <strong id="summary-tax">KES 0.00</strong>
            </div>
            <div class="totals-line">
                <span class="muted">Discount:</span>
                <span id="summary-discount" class="muted">KES 0.00</span>
            </div>
            <div class="totals-line totals-grand">
                <span>Total Due:</span>
                <span id="summary-grand">KES 0.00</span>
            </div>
        </div>

        <div style="display: grid; gap: 10px; margin-top: 24px;">
            <button type="button" id="pay-btn" class="btn btn-primary" style="padding: 14px; font-size: 16px;" disabled>
                <i class="bi bi-credit-card"></i>
                <span>Pay / Tender</span>
            </button>
            <button type="button" id="clear-btn" class="btn btn-secondary btn-sm">
                <i class="bi bi-trash"></i>
                <span>Clear Cart</span>
            </button>
        </div>
    </div>
</div>

<!-- Payment Tender Modal -->
<div id="payment-modal" class="modal-overlay">
    <div class="modal-box">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; border-bottom: 1px solid var(--clr-header-tint); padding-bottom: 10px;">
            <h2 class="section-title" style="font-size: 18px;">Complete Payment</h2>
            <button type="button" onclick="document.getElementById('payment-modal').style.display='none'" class="btn-ghost" aria-label="Close modal">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <div style="background: var(--clr-grey-tint); padding: 14px; border-radius: var(--radius-md); margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
            <span class="muted" style="font-size: 14px;">Total Due:</span>
            <strong id="modal-due" class="text-green" style="font-size: 22px;">KES 0.00</strong>
        </div>

        <div style="display: grid; gap: 16px; margin-bottom: 20px;">
            <div class="field">
                <select id="payment-method">
                    <option value="cash">Cash Tender</option>
                    <option value="mpesa">M-Pesa Express / STK</option>
                    <option value="card">Card / Electronic POS</option>
                </select>
                <label for="payment-method">Payment Method</label>
            </div>

            <div class="field">
                <input type="number" step="0.01" id="tender-amount" value="0.00" placeholder=" ">
                <label for="tender-amount">Amount Tendered (KES)</label>
            </div>

            <div id="ref-field" class="field" style="display: none;">
                <input type="text" id="tender-ref" placeholder=" ">
                <label for="tender-ref">M-Pesa / Card Reference Code</label>
            </div>

            <div class="totals-line" id="change-line" style="display: flex; padding: 10px 14px; background: var(--clr-grey-tint); border-radius: var(--radius-md);">
                <span class="muted">Change Due:</span>
                <strong id="modal-change" class="text-blue" style="font-size: 16px;">KES 0.00</strong>
            </div>
        </div>

        <div style="display: flex; gap: 10px; justify-content: flex-end;">
            <button type="button" id="cancel-pay-btn" class="btn btn-secondary">Cancel</button>
            <button type="button" id="submit-sale-btn" class="btn btn-primary">
                <i class="bi bi-printer"></i>
                <span>Finish &amp; Print Receipt</span>
            </button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const shiftId = {{ $openShift->id }};
    const shopId = {{ $openShift->shop_id }};
    let cart = [];

    const barcodeInput = document.getElementById('barcode-input');
    const lookupBtn = document.getElementById('lookup-btn');
    const cartRows = document.getElementById('cart-rows');
    const payBtn = document.getElementById('pay-btn');
    const clearBtn = document.getElementById('clear-btn');
    const modal = document.getElementById('payment-modal');
    const modalDue = document.getElementById('modal-due');
    const tenderAmount = document.getElementById('tender-amount');
    const paymentMethod = document.getElementById('payment-method');
    const refField = document.getElementById('ref-field');
    const modalChange = document.getElementById('modal-change');
    const cancelPayBtn = document.getElementById('cancel-pay-btn');
    const submitSaleBtn = document.getElementById('submit-sale-btn');

    async function lookupProduct(query) {
        if (!query.trim()) return;
        try {
            const res = await fetch(`{{ route('tenant.products.lookup') }}?barcode=${encodeURIComponent(query)}`);
            const data = await res.json();
            if (data.found) {
                addToCart(data.product);
                barcodeInput.value = '';
            } else {
                alert(`Product not found for: ${query}`);
            }
        } catch (e) {
            alert('Lookup failed.');
        }
    }

    function addToCart(product) {
        const existing = cart.find(i => i.id === product.id);
        if (existing) {
            existing.qty += 1;
        } else {
            cart.push({
                id: product.id,
                name: product.name,
                sku: product.sku,
                price: parseFloat(product.selling_price),
                tax_rate: parseFloat(product.tax_rate || 0),
                qty: 1
            });
        }
        renderCart();
    }

    function updateQty(id, delta) {
        const item = cart.find(i => i.id === id);
        if (!item) return;
        item.qty += delta;
        if (item.qty <= 0) {
            cart = cart.filter(i => i.id !== id);
        }
        renderCart();
    }

    function removeItem(id) {
        cart = cart.filter(i => i.id !== id);
        renderCart();
    }

    function calculateTotals() {
        let subtotal = 0;
        let tax = 0;
        cart.forEach(item => {
            const lineSub = item.price * item.qty;
            const lineTax = (lineSub * item.tax_rate) / (100 + item.tax_rate);
            subtotal += (lineSub - lineTax);
            tax += lineTax;
        });
        const grand = subtotal + tax;
        return { subtotal, tax, grand };
    }

    function renderCart() {
        if (cart.length === 0) {
            cartRows.innerHTML = `
                <tr id="empty-cart-row">
                    <td colspan="6" style="text-align: center; color: var(--clr-muted); padding: 48px 16px;">
                        <i class="bi bi-cart3" style="font-size: 32px; display: block; margin-bottom: 8px;"></i>
                        Cart is empty. Scan a barcode or search to add items.
                    </td>
                </tr>
            `;
            payBtn.disabled = true;
            document.getElementById('summary-subtotal').textContent = 'KES 0.00';
            document.getElementById('summary-tax').textContent = 'KES 0.00';
            document.getElementById('summary-grand').textContent = 'KES 0.00';
            return;
        }

        payBtn.disabled = false;
        let html = '';
        cart.forEach(item => {
            const lineTotal = item.price * item.qty;
            html += `
                <tr>
                    <td title="${item.name}"><strong>${item.name}</strong><br><small class="muted">${item.sku}</small></td>
                    <td style="text-align: center;">
                        <button type="button" class="qty-btn" onclick="updateQty(${item.id}, -1)">-</button>
                        <span style="display: inline-block; width: 32px; text-align: center; font-weight: 600;">${item.qty}</span>
                        <button type="button" class="qty-btn" onclick="updateQty(${item.id}, 1)">+</button>
                    </td>
                    <td style="text-align: right;">${item.price.toFixed(2)}</td>
                    <td style="text-align: right;">${item.tax_rate.toFixed(1)}%</td>
                    <td style="text-align: right; font-weight: 600;" class="text-green">${lineTotal.toFixed(2)}</td>
                    <td style="text-align: center;">
                        <button type="button" class="btn-ghost danger" onclick="removeItem(${item.id})" aria-label="Remove item">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
        });
        cartRows.innerHTML = html;

        const totals = calculateTotals();
        document.getElementById('summary-subtotal').textContent = `KES ${totals.subtotal.toFixed(2)}`;
        document.getElementById('summary-tax').textContent = `KES ${totals.tax.toFixed(2)}`;
        document.getElementById('summary-grand').textContent = `KES ${totals.grand.toFixed(2)}`;
    }

    barcodeInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            lookupProduct(barcodeInput.value);
        }
    });

    lookupBtn.addEventListener('click', () => {
        lookupProduct(barcodeInput.value);
    });

    clearBtn.addEventListener('click', () => {
        if (cart.length > 0 && confirm('Clear entire cart?')) {
            cart = [];
            renderCart();
        }
    });

    payBtn.addEventListener('click', () => {
        const totals = calculateTotals();
        modalDue.textContent = `KES ${totals.grand.toFixed(2)}`;
        tenderAmount.value = totals.grand.toFixed(2);
        modalChange.textContent = 'KES 0.00';
        modal.style.display = 'flex';
        tenderAmount.focus();
    });

    paymentMethod.addEventListener('change', () => {
        refField.style.display = (paymentMethod.value === 'mpesa' || paymentMethod.value === 'card') ? 'block' : 'none';
    });

    tenderAmount.addEventListener('input', () => {
        const totals = calculateTotals();
        const tendered = parseFloat(tenderAmount.value) || 0;
        const change = Math.max(0, tendered - totals.grand);
        modalChange.textContent = `KES ${change.toFixed(2)}`;
    });

    cancelPayBtn.addEventListener('click', () => {
        modal.style.display = 'none';
    });

    submitSaleBtn.addEventListener('click', async () => {
        const totals = calculateTotals();
        const tendered = parseFloat(tenderAmount.value) || 0;
        if (tendered < totals.grand) {
            alert('Tendered amount is less than total due.');
            return;
        }

        submitSaleBtn.disabled = true;
        submitSaleBtn.textContent = 'Processing...';

        const payload = {
            shift_id: shiftId,
            shop_id: shopId,
            register_id: parseInt(document.getElementById('register-select').value),
            items: cart.map(i => ({
                product_id: i.id,
                quantity: i.qty,
                unit_price: i.price,
                tax_rate: i.tax_rate
            })),
            payments: [{
                method: paymentMethod.value,
                amount: tendered,
                reference: document.getElementById('tender-ref').value || null
            }]
        };

        try {
            const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const res = await fetch(`{{ route('tenant.pos.process') }}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf
                },
                body: JSON.stringify(payload)
            });

            const data = await res.json();
            if (res.ok && data.success) {
                window.location.href = `/tenant/pos/receipt/${data.sale_id}`;
            } else {
                alert(data.message || 'Error processing sale.');
                submitSaleBtn.disabled = false;
                submitSaleBtn.textContent = 'Finish & Print Receipt';
            }
        } catch (e) {
            alert('Network or server error during checkout.');
            submitSaleBtn.disabled = false;
            submitSaleBtn.textContent = 'Finish & Print Receipt';
        }
    });
</script>
@endpush
