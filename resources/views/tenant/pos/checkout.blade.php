@extends('tenant.layout')

@section('title', 'POS Checkout')

@push('styles')
<style>
    .pos-grid { display: grid; grid-template-columns: 1fr 380px; gap: 20px; min-height: calc(100vh - 120px); }
    .pos-main { display: flex; flex-direction: column; gap: 16px; min-height: 0; }
    .pos-cart { min-height: 220px; max-height: 320px; overflow-y: auto; background: var(--clr-surface, #fff); border-radius: var(--radius-card, 12px); box-shadow: var(--shadow-sm); }
    .pos-summary { background: var(--clr-surface, #fff); border-radius: var(--radius-card, 12px); box-shadow: var(--shadow-sm); padding: 24px; display: flex; flex-direction: column; justify-content: space-between; }
    .totals-line { display: flex; justify-content: space-between; padding: 6px 0; font-size: 14px; }
    .totals-grand { font-size: 20px; font-weight: 700; color: var(--clr-green, #16a34a); border-top: 2px dashed var(--clr-border, #cbd5e1); padding-top: 14px; margin-top: 12px; }
    .qty-btn { width: 30px; height: 30px; border: 1px solid var(--clr-border, #cbd5e1); background: var(--clr-grey-tint, #eaedf2); border-radius: var(--radius-sm, 6px); cursor: pointer; font-weight: 600; display: inline-flex; align-items: center; justify-content: center; }
    .qty-btn:hover { background: #cbd5e1; }
    .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(15,23,42,.4); backdrop-filter: blur(2px); z-index: 999; align-items: center; justify-content: center; }
    .modal-box { background: var(--clr-surface, #fff); border-radius: var(--radius-card, 12px); padding: 28px; width: 100%; max-width: 480px; box-shadow: var(--shadow-lg); }

    /* Product Catalog Picker Grid */
    .catalog-panel { background: var(--clr-surface, #fff); border-radius: var(--radius-card, 12px); box-shadow: var(--shadow-sm); padding: 18px; display: flex; flex-direction: column; gap: 14px; flex: 1; }
    .category-pills { display: flex; gap: 8px; overflow-x: auto; padding-bottom: 4px; }
    .cat-pill { padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 600; cursor: pointer; border: 1px solid var(--clr-border, #cbd5e1); background: #f8fafc; color: #475569; white-space: nowrap; }
    .cat-pill.active { background: #0f172a; color: #fff; border-color: #0f172a; }
    .product-cards-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 10px; max-height: 280px; overflow-y: auto; padding-right: 4px; }
    .product-card-btn { border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px; background: #fff; cursor: pointer; text-align: left; display: flex; flex-direction: column; justify-content: space-between; min-height: 80px; transition: border-color .15s, box-shadow .15s; }
    .product-card-btn:hover { border-color: #16a34a; box-shadow: 0 2px 6px rgba(0,0,0,.06); transform: translateY(-1px); }
</style>
@endpush

@section('content')
<div class="pos-grid">
    <div class="pos-main">
        {{-- Header Bar --}}
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
            <div>
                <h1 class="page-title" style="font-size: 20px; margin: 0;">POS Terminal</h1>
                <div class="muted" style="margin-top: 2px; font-size: 13px;">
                    Shift: <strong>{{ $openShift->shift_number }}</strong> &bull; 
                    Shop: <strong>{{ $openShift->shop->name }}</strong> &bull; 
                    Cashier: <strong>{{ auth()->user()->name }}</strong>
                </div>
            </div>
            <div style="display: flex; gap: 8px;">
                <a href="{{ route('tenant.tables.index') }}" class="btn btn-outline btn-sm">
                    <i class="bi bi-grid-3x3"></i>
                    <span>Floor Tables</span>
                </a>
                <a href="{{ route('tenant.shifts.show', $openShift) }}" class="btn btn-secondary btn-sm">
                    <i class="bi bi-clock-history"></i>
                    <span>Shift Details</span>
                </a>
            </div>
        </div>

        {{-- Manual Product Catalog Browser & Live Search Picker --}}
        <div class="catalog-panel">
            <div style="display: flex; gap: 12px; align-items: center;">
                <div style="flex: 1;">
                    <x-input
                        id="product-search-input"
                        name="search_query"
                        placeholder="Type product name, SKU, or scan barcode to filter catalog..."
                        leadingIcon="bi-search"
                        autocomplete="off"
                        autofocus
                    />
                </div>
                <button type="button" id="scan-barcode-btn" class="btn btn-secondary" style="height: 40px;" title="Lookup by exact barcode scan">
                    <i class="bi bi-upc-scan"></i> Barcode
                </button>
            </div>

            {{-- Category Filter Pills --}}
            <div class="category-pills" id="category-pills">
                <button type="button" class="cat-pill active" data-category="">All Categories</button>
                @foreach($categories as $category)
                    <button type="button" class="cat-pill" data-category="{{ $category->id }}">{{ $category->name }}</button>
                @endforeach
            </div>

            {{-- Clickable Product Cards Grid --}}
            <div class="product-cards-grid" id="product-cards-container">
                <div style="grid-column: 1 / -1; text-align: center; color: #64748b; padding: 24px;">
                    Loading product catalog...
                </div>
            </div>
        </div>

        {{-- Active Cart --}}
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
                            <td colspan="6" style="text-align: center; color: var(--clr-muted); padding: 40px 16px;">
                                <i class="bi bi-cart3" style="font-size: 24px; display: block; margin-bottom: 8px;"></i>
                                Cart is empty. Click any item above or scan a barcode to add.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Order Summary & Checkout Sidebar --}}
    <div class="pos-summary">
        <div>
            <h2 class="section-title" style="margin-bottom: 14px; border-bottom: 1px solid var(--clr-header-tint); padding-bottom: 8px; font-size: 16px;">
                Order Summary
            </h2>
            
            {{-- Till Selection --}}
            <div style="margin-bottom: 12px;">
                <x-input type="select" id="register-select" name="register_id" label="Till / Register">
                    @foreach($registers as $reg)
                        <option value="{{ $reg->id }}" {{ $openShift->register_id == $reg->id ? 'selected' : '' }}>{{ $reg->name }} ({{ $reg->code }})</option>
                    @endforeach
                </x-input>
            </div>

            {{-- Restaurant Dining Table Assignment --}}
            @if($tables->isNotEmpty())
            <div style="margin-bottom: 14px;">
                <x-input type="select" id="table-select" name="dining_table_id" label="Dining Table (Dine-in)">
                    <option value="">Takeaway / Walk-in / Bar Counter</option>
                    @foreach($tables as $tbl)
                        <option value="{{ $tbl->id }}" @selected(request('table_id') == $tbl->id)>
                            {{ $tbl->name }} ({{ $tbl->zone }} &bull; {{ $tbl->status }})
                        </option>
                    @endforeach
                </x-input>
            </div>
            @endif

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
                <span>Grand Total:</span>
                <span id="summary-grand">KES 0.00</span>
            </div>
        </div>

        <div style="display: flex; flex-direction: column; gap: 10px; margin-top: 16px;">
            <button type="button" id="pay-btn" class="btn btn-primary" style="height: 48px; font-size: 16px;" disabled>
                <i class="bi bi-wallet2"></i>
                <span>Complete Payment</span>
            </button>
            <button type="button" id="clear-btn" class="btn btn-outline btn-sm">
                <i class="bi bi-x-circle"></i> Clear Cart
            </button>
        </div>
    </div>
</div>

{{-- Payment Modal --}}
<div id="payment-modal" class="modal-overlay">
    <div class="modal-box">
        <h2 style="font-size: 18px; margin-bottom: 16px; color: var(--clr-text);">Complete Sale Payment</h2>
        
        <div style="background: var(--clr-grey-tint); padding: 14px; border-radius: var(--radius-md); margin-bottom: 18px; text-align: center;">
            <span class="muted" style="font-size: 13px;">Total Amount Due</span>
            <div id="modal-due" style="font-size: 26px; font-weight: 700; color: var(--clr-green); margin-top: 4px;">KES 0.00</div>
        </div>

        <div style="display: flex; flex-direction: column; gap: 14px; margin-bottom: 20px;">
            <x-input type="select" id="payment-method" name="payment_method" label="Payment Method">
                <option value="cash">Cash Tendered</option>
                <option value="mpesa">M-Pesa Mobile Money</option>
                <option value="card">Credit / Debit Card</option>
            </x-input>

            <x-input type="number" step="0.01" id="tender-amount" name="amount" label="Tendered Amount (KES)" value="" />

            <div id="ref-field" style="display: none;">
                <x-input type="text" id="payment-ref" name="reference" label="M-Pesa Code / Card Approval Ref" placeholder="e.g. QDF8912KLM" />
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
<script nonce="{{ request()->attributes->get('csp_nonce') }}">
    const shiftId = {{ $openShift->id }};
    const shopId = {{ $openShift->shop_id }};
    let cart = [];
    let activeCategoryId = '';
    let searchQuery = '';

    const searchInput = document.getElementById('product-search-input');
    const scanBarcodeBtn = document.getElementById('scan-barcode-btn');
    const cardsContainer = document.getElementById('product-cards-container');
    const cartRows = document.getElementById('cart-rows');
    const payBtn = document.getElementById('pay-btn');
    const clearBtn = document.getElementById('clear-btn');
    const tableSelect = document.getElementById('table-select');
    const modal = document.getElementById('payment-modal');
    const modalDue = document.getElementById('modal-due');
    const tenderAmount = document.getElementById('tender-amount');
    const paymentMethod = document.getElementById('payment-method');
    const refField = document.getElementById('ref-field');
    const modalChange = document.getElementById('modal-change');
    const cancelPayBtn = document.getElementById('cancel-pay-btn');
    const submitSaleBtn = document.getElementById('submit-sale-btn');

    // Fetch and render catalog products
    async function fetchCatalog() {
        try {
            const params = new URLSearchParams();
            if (activeCategoryId) params.append('category_id', activeCategoryId);
            if (searchQuery) params.append('q', searchQuery);

            const res = await fetch(`{{ route('tenant.products.lookup') }}?${params.toString()}`);
            const data = await res.json();

            if (data.found && data.products && data.products.length > 0) {
                renderProductCards(data.products);
            } else {
                cardsContainer.innerHTML = `
                    <div style="grid-column: 1 / -1; text-align: center; color: #94a3b8; padding: 24px;">
                        No products found matching "${searchQuery}".
                    </div>
                `;
            }
        } catch (e) {
            cardsContainer.innerHTML = '<div style="grid-column: 1 / -1; color: #dc2626; text-align: center;">Failed to load catalog.</div>';
        }
    }

    function renderProductCards(products) {
        cardsContainer.innerHTML = products.map(p => `
            <button type="button" class="product-card-btn" onclick='addToCart(${JSON.stringify(p)})'>
                <div>
                    <strong style="font-size: 13px; color: #0f172a; display: block; line-height: 1.3;">${p.name}</strong>
                    <span style="font-size: 11px; color: #64748b;">${p.category_name}</span>
                </div>
                <div style="font-weight: 700; color: #16a34a; font-size: 13px; margin-top: 6px;">
                    KES ${parseFloat(p.selling_price).toFixed(2)}
                </div>
            </button>
        `).join('');
    }

    // Exact barcode scanner lookup
    async function lookupBarcode(code) {
        if (!code.trim()) return;
        try {
            const res = await fetch(`{{ route('tenant.products.lookup') }}?barcode=${encodeURIComponent(code)}`);
            const data = await res.json();
            if (data.found) {
                addToCart(data.product);
                searchInput.value = '';
                searchQuery = '';
                fetchCatalog();
            } else {
                alert(`Product not found for: ${code}`);
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

    window.addToCart = addToCart;

    window.updateQty = function(id, delta) {
        const item = cart.find(i => i.id === id);
        if (!item) return;
        item.qty += delta;
        if (item.qty <= 0) {
            cart = cart.filter(i => i.id !== id);
        }
        renderCart();
    };

    window.removeItem = function(id) {
        cart = cart.filter(i => i.id !== id);
        renderCart();
    };

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
                    <td colspan="6" style="text-align: center; color: var(--clr-muted); padding: 40px 16px;">
                        <i class="bi bi-cart3" style="font-size: 24px; display: block; margin-bottom: 8px;"></i>
                        Cart is empty. Click any item above or scan a barcode to add.
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

    // Live search debouncing
    let searchTimeout;
    searchInput.addEventListener('input', function () {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            searchQuery = searchInput.value.trim();
            fetchCatalog();
        }, 250);
    });

    searchInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            const val = searchInput.value.trim();
            if (val) lookupBarcode(val);
        }
    });

    scanBarcodeBtn.addEventListener('click', function () {
        const val = searchInput.value.trim();
        if (val) lookupBarcode(val);
    });

    // Category pill filtering
    document.querySelectorAll('.cat-pill').forEach(btn => {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.cat-pill').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            activeCategoryId = this.getAttribute('data-category');
            fetchCatalog();
        });
    });

    clearBtn.addEventListener('click', function () {
        if (confirm('Are you sure you want to clear all items in the cart?')) {
            cart = [];
            renderCart();
        }
    });

    // Payment Modal Logic
    payBtn.addEventListener('click', function () {
        const totals = calculateTotals();
        modalDue.textContent = `KES ${totals.grand.toFixed(2)}`;
        tenderAmount.value = totals.grand.toFixed(2);
        modalChange.textContent = 'KES 0.00';
        modal.style.display = 'flex';
        tenderAmount.focus();
        tenderAmount.select();
    });

    cancelPayBtn.addEventListener('click', function () {
        modal.style.display = 'none';
    });

    tenderAmount.addEventListener('input', function () {
        const totals = calculateTotals();
        const tendered = parseFloat(tenderAmount.value) || 0;
        const change = Math.max(0, tendered - totals.grand);
        modalChange.textContent = `KES ${change.toFixed(2)}`;
    });

    paymentMethod.addEventListener('change', function () {
        if (this.value === 'mpesa' || this.value === 'card') {
            refField.style.display = 'block';
        } else {
            refField.style.display = 'none';
        }
    });

    submitSaleBtn.addEventListener('click', async function () {
        const totals = calculateTotals();
        const tendered = parseFloat(tenderAmount.value) || 0;

        if (tendered < totals.grand && paymentMethod.value === 'cash') {
            alert('Tendered cash is less than the grand total amount.');
            return;
        }

        submitSaleBtn.disabled = true;
        submitSaleBtn.textContent = 'Processing...';

        const payload = {
            shift_id: shiftId,
            shop_id: shopId,
            register_id: parseInt(document.getElementById('register-select').value),
            dining_table_id: tableSelect ? (tableSelect.value ? parseInt(tableSelect.value) : null) : null,
            items: cart.map(i => ({
                product_id: i.id,
                quantity: i.qty,
                unit_price: i.price,
                tax_rate: i.tax_rate
            })),
            payments: [{
                method: paymentMethod.value,
                amount: totals.grand,
                reference: document.getElementById('payment-ref')?.value || null
            }]
        };

        try {
            const res = await fetch("{{ route('tenant.pos.process') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            });

            const data = await res.json();
            if (data.success) {
                window.location.href = `/tenant/pos/receipt/${data.sale_id}`;
            } else {
                alert('Sale failed: ' + (data.message || 'Unknown error'));
                submitSaleBtn.disabled = false;
                submitSaleBtn.innerHTML = '<i class="bi bi-printer"></i> <span>Finish &amp; Print Receipt</span>';
            }
        } catch (e) {
            alert('Sale processing failed.');
            submitSaleBtn.disabled = false;
            submitSaleBtn.innerHTML = '<i class="bi bi-printer"></i> <span>Finish &amp; Print Receipt</span>';
        }
    });

    // Initial load
    fetchCatalog();
</script>
@endpush
