@extends('tenant.layout')

@section('title', 'POS Checkout')

@push('styles')
<style>
    .pos-grid {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 340px;
        gap: 12px;
        height: calc(100dvh - 128px);
        min-height: 600px;
        overflow: hidden;
    }
    .pos-main {
        display: grid;
        grid-template-rows: auto minmax(0, 1fr) 80px;
        gap: 12px;
        height: 100%;
        min-height: 0;
        min-width: 0;
        overflow: hidden;
    }
    .catalog-panel {
        background: var(--clr-surface, #fff);
        border-radius: var(--radius-card, 12px);
        box-shadow: var(--shadow-sm);
        padding: 8px;
        display: flex;
        flex-direction: column;
        gap: 6px;
        min-height: 0;
        overflow: hidden;
    }
    .pos-search-row {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 8px;
        align-items: center;
        flex: 0 0 auto;
    }
    .catalog-panel .ui-field { margin-bottom: 0; }
    .catalog-panel .ui-meta-row { min-height: 0; margin-top: 0; }
    .category-filter { position: relative; }
    .category-filter-toggle { display: inline-flex; align-items: center; justify-content: center; gap: 6px; min-height: 36px; white-space: nowrap; }
    .category-filter-menu { display: none; position: absolute; z-index: 30; top: calc(100% + 6px); right: 0; width: min(260px, 80vw); max-height: 280px; overflow-y: auto; padding: 6px; border-radius: 12px; background: var(--clr-surface); box-shadow: var(--shadow-md); }
    .category-filter.is-open .category-filter-menu { display: grid; gap: 2px; }
    .cat-pill {
        display: flex;
        align-items: center;
        justify-content: space-between;
        width: 100%;
        min-height: 34px;
        padding: 6px 10px;
        border: 0;
        border-radius: 8px;
        background: transparent;
        color: var(--clr-text);
        text-align: left;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
    }
    .cat-pill:hover { background: var(--clr-grey-tint); }
    .cat-pill.active { color: var(--clr-green); }
    .product-cards-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        grid-auto-rows: minmax(120px, auto);
        gap: 6px;
        overflow-y: auto;
        overflow-x: hidden;
        padding: 0 4px 2px 0;
        flex: 1 1 auto;
        min-height: 0;
        align-content: start;
        scrollbar-width: thin;
        scrollbar-color: var(--clr-border) var(--clr-grey-tint);
    }
    .product-card-btn {
        border: 1px solid var(--clr-border);
        border-radius: 10px;
        padding: 5px;
        background: var(--clr-surface);
        cursor: pointer;
        text-align: left;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        gap: 2px;
        min-width: 0;
        width: 100%;
        min-height: 120px;
        box-sizing: border-box;
        overflow: hidden;
        transition: border-color var(--dur-fast), box-shadow var(--dur-fast), transform var(--dur-fast);
    }
    .product-card-btn:hover {
        border-color: var(--clr-green);
        box-shadow: var(--shadow-sm);
        transform: translateY(-1px);
    }
    .product-card-thumb {
        width: 100%;
        height: 64px;
        border-radius: 8px;
        background: var(--clr-header-tint);
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        padding: 4px;
    }
    .product-card-thumb img {
        width: 100%;
        height: 100%;
        object-fit: contain;
        display: block;
    }
    .product-card-body {
        min-width: 0;
        width: 100%;
        overflow: hidden;
    }
    .product-card-name {
        font-size: 12px;
        font-weight: 600;
        color: #0f172a;
        line-height: 1.2;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .product-card-cat {
        font-size: 11px;
        color: #64748b;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        display: block;
        margin-top: 1px;
        line-height: 1.2;
    }
    .product-card-price {
        font-weight: 700;
        color: #16a34a;
        font-size: 12px;
        line-height: 1.2;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .pos-cart {
        min-height: 0;
        overflow-y: auto;
        overflow-x: hidden;
        background: var(--clr-surface, #fff);
        border-radius: var(--radius-card, 12px);
        box-shadow: var(--shadow-sm);
    }
    .pos-summary {
        background: var(--clr-surface, #fff);
        border-radius: var(--radius-card, 12px);
        box-shadow: var(--shadow-sm);
        padding: 18px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        min-height: 0;
        overflow-y: auto;
    }
    .totals-line { display: flex; justify-content: space-between; padding: 5px 0; font-size: 13.5px; }
    .totals-grand { font-size: 18px; font-weight: 700; color: var(--clr-green, #16a34a); border-top: 2px dashed var(--clr-border, #cbd5e1); padding-top: 10px; margin-top: 8px; }
    .qty-btn { width: 28px; height: 28px; border: 1px solid var(--clr-border, #cbd5e1); background: var(--clr-grey-tint, #eaedf2); border-radius: var(--radius-sm, 6px); cursor: pointer; font-weight: 600; display: inline-flex; align-items: center; justify-content: center; }
    .qty-btn:hover { background: #cbd5e1; }
    .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(15,23,42,.4); backdrop-filter: blur(2px); z-index: 999; align-items: center; justify-content: center; }
    .modal-box { background: var(--clr-surface, #fff); border-radius: var(--radius-card, 12px); padding: 24px; width: 100%; max-width: 480px; box-shadow: var(--shadow-lg); }

    @media (max-width: 900px) {
        .pos-grid { grid-template-columns: 1fr; height: auto; min-height: 0; overflow: visible; }
        .pos-main { height: auto; overflow: visible; grid-template-rows: auto minmax(400px, 58vh) 140px; }
        .catalog-panel { min-height: 0; }
        .product-cards-grid { min-height: 300px; }
        .pos-summary { min-height: 320px; }
    }
    @media (max-width: 480px) {
        .pos-grid { gap: 8px; }
        .category-filter-toggle .filter-button-label { display: none; }
        .product-cards-grid { gap: 6px; }
        .product-card-btn { padding: 5px; }
    }
</style>
@endpush

@section('content')
<div class="pos-grid">
    <div class="pos-main">
        {{-- Compact Header Bar --}}
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px; flex-shrink: 0;">
            <div>
                <h1 class="page-title" style="font-size: 18px; margin: 0;">POS Terminal</h1>
                <div class="muted" style="font-size: 12px;">
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

        {{-- Unified barcode and product search with category dropdown --}}
        <div class="catalog-panel">
            <div class="pos-search-row">
                <x-input
                    type="search"
                    id="barcode-scan-input"
                    name="barcode_scan"
                    label="Scan barcode or search products"
                    leadingIcon="bi-upc-scan"
                    autocomplete="off"
                    autofocus
                />

                <div class="category-filter" id="category-filter">
                    <button type="button" id="category-filter-toggle" class="btn btn-secondary category-filter-toggle" aria-expanded="false" aria-controls="category-filter-menu">
                        <i class="bi bi-list" aria-hidden="true"></i>
                        <span class="filter-button-label">Filters</span>
                        <span id="selected-category-label">All Categories</span>
                    </button>
                    <div class="category-filter-menu" id="category-filter-menu" role="radiogroup" aria-label="Filter products by category">
                        <button type="button" class="cat-pill active" data-category="" data-category-label="All Categories" role="radio" aria-checked="true">
                            <span>All Categories</span><i class="bi bi-check2" aria-hidden="true"></i>
                        </button>
                        @foreach($categories as $category)
                            <button type="button" class="cat-pill" data-category="{{ $category->id }}" data-category-label="{{ $category->name }}" role="radio" aria-checked="false">
                                <span>{{ $category->name }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Vertical Product Cards Grid (no slider) --}}
            <div class="product-cards-grid" id="product-cards-container">
                <div style="grid-column: 1 / -1; text-align: center; color: #64748b; padding: 20px;">
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
                            <th style="width: 95px; text-align: right;">Price</th>
                            <th style="width: 75px; text-align: right;">Tax %</th>
                            <th style="width: 100px; text-align: right;">Total</th>
                            <th style="width: 44px; text-align: center;"></th>
                        </tr>
                    </thead>
                    <tbody id="cart-rows">
                        <tr id="empty-cart-row">
                            <td colspan="6" style="text-align: center; color: var(--clr-muted); padding: 28px 16px;">
                                <i class="bi bi-cart3" style="font-size: 22px; display: block; margin-bottom: 6px;"></i>
                                Cart is empty. Click any product card or scan a barcode to add.
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
            <h2 class="section-title" style="margin-bottom: 12px; border-bottom: 1px solid var(--clr-header-tint); padding-bottom: 8px; font-size: 16px;">
                Order Summary
            </h2>

            <div style="margin-bottom: 10px;">
                <x-input type="select" id="register-select" name="register_id" label="Till / Register">
                    @foreach($registers as $reg)
                        <option value="{{ $reg->id }}" {{ $openShift->register_id == $reg->id ? 'selected' : '' }}>{{ $reg->name }} ({{ $reg->code }})</option>
                    @endforeach
                </x-input>
            </div>

            @if($tables->isNotEmpty())
            <div style="margin-bottom: 12px;">
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

        <div style="display: flex; flex-direction: column; gap: 8px; margin-top: 12px;">
            <button type="button" id="pay-btn" class="btn btn-primary" style="height: 44px; font-size: 15px;" disabled>
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
        <h2 style="font-size: 18px; margin-bottom: 14px; color: var(--clr-text);">Complete Sale Payment</h2>

        <div style="background: var(--clr-grey-tint); padding: 12px; border-radius: var(--radius-md); margin-bottom: 16px; text-align: center;">
            <span class="muted" style="font-size: 12.5px;">Total Amount Due</span>
            <div id="modal-due" style="font-size: 24px; font-weight: 700; color: var(--clr-green); margin-top: 4px;">KES 0.00</div>
        </div>

        <div style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 18px;">
            <x-input type="select" id="payment-method" name="payment_method" label="Payment Method">
                <option value="cash">Cash Tendered</option>
                <option value="mpesa">M-Pesa (STK Push / Till)</option>
                <option value="card">Credit / Debit Card</option>
            </x-input>

            <x-input type="number" step="0.01" id="tender-amount" name="amount" label="Tendered Amount (KES)" value="" />

            {{-- M-Pesa STK Push Box --}}
            <div id="mpesa-stk-box" style="display: none; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 10px; padding: 12px; flex-direction: column; gap: 10px;">
                <div style="display: flex; gap: 8px; align-items: center;">
                    <div style="flex: 1;">
                        <x-input type="tel" id="mpesa-phone" name="mpesa_phone" label="Customer M-Pesa Phone (e.g. 0712345678)" />
                    </div>
                    <button type="button" id="send-stk-btn" class="btn btn-primary" style="height: 40px; flex-shrink: 0;">
                        <i class="bi bi-phone"></i> Send STK Push
                    </button>
                </div>
                <div id="stk-status" style="display: none; font-size: 12px; padding: 8px 10px; border-radius: 8px;"></div>
            </div>

            <div id="ref-field" style="display: none;">
                <x-input type="text" id="payment-ref" name="reference" label="M-Pesa Receipt / Checkout Ref" placeholder="e.g. QDF8912KLM" />
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

    const barcodeInput = document.getElementById('barcode-scan-input');
    const searchInput = barcodeInput;
    const categoryFilter = document.getElementById('category-filter');
    const categoryFilterToggle = document.getElementById('category-filter-toggle');
    const selectedCategoryLabel = document.getElementById('selected-category-label');
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
    const mpesaStkBox = document.getElementById('mpesa-stk-box');
    const mpesaPhone = document.getElementById('mpesa-phone');
    const sendStkBtn = document.getElementById('send-stk-btn');
    const stkStatus = document.getElementById('stk-status');
    const modalChange = document.getElementById('modal-change');
    const cancelPayBtn = document.getElementById('cancel-pay-btn');
    const submitSaleBtn = document.getElementById('submit-sale-btn');

    function escapeHtml(str) {
        return String(str ?? '').replace(/[&<>"']/g, s => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
        }[s]));
    }

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
                        No products found${searchQuery ? ' matching "' + escapeHtml(searchQuery) + '"' : ''}.
                    </div>
                `;
            }
        } catch (e) {
            cardsContainer.innerHTML = '<div style="grid-column: 1 / -1; color: #dc2626; text-align: center;">Failed to load catalog.</div>';
        }
    }

    function renderProductCards(products) {
        cardsContainer.innerHTML = products.map(p => {
            const thumb = p.image_url
                ? `<img src="${escapeHtml(p.image_url)}" alt="${escapeHtml(p.name)}" loading="lazy">`
                : `<i class="bi bi-box-seam" style="font-size: 24px; color: #94a3b8;"></i>`;
            return `
                <button type="button" class="product-card-btn" title="${escapeHtml(p.name)}" onclick='addToCart(${JSON.stringify(p)})'>
                    <div class="product-card-thumb">${thumb}</div>
                    <div class="product-card-body">
                        <div class="product-card-name">${escapeHtml(p.name)}</div>
                        <span class="product-card-cat">${escapeHtml(p.category_name)}</span>
                    </div>
                    <div class="product-card-price">KES ${parseFloat(p.selling_price).toFixed(2)}</div>
                </button>
            `;
        }).join('');
    }

    async function lookupBarcode(code) {
        if (!code.trim()) return;
        try {
            const res = await fetch(`{{ route('tenant.products.lookup') }}?barcode=${encodeURIComponent(code.trim())}`);
            const data = await res.json();
            const matchedBarcode = String(data.product?.barcode ?? '').trim() === code.trim();
            const matchedSku = String(data.product?.sku ?? '').trim().toLowerCase() === code.trim().toLowerCase();
            if (data.found && (matchedBarcode || matchedSku)) {
                addToCart(data.product);
                barcodeInput.value = '';
                searchQuery = '';
                barcodeInput.dispatchEvent(new Event('input', { bubbles: true }));
                fetchCatalog();
                barcodeInput.focus();
            }
        } catch (e) {
            alert('Barcode lookup failed.');
        }
    }

    function addToCart(product) {
        const prescriptionId = product.requires_prescription ? prompt('Enter the active prescription ID for ' + product.name + ':') : null;
        if (product.requires_prescription && !prescriptionId) return;
        const pharmacistUserId = product.is_controlled_substance ? prompt('Enter the verifying pharmacist user ID:') : null;
        if (product.is_controlled_substance && !pharmacistUserId) return;
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
                prescription_id: prescriptionId ? parseInt(prescriptionId, 10) : null,
                pharmacist_user_id: pharmacistUserId ? parseInt(pharmacistUserId, 10) : null,
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
        if (item.qty <= 0) cart = cart.filter(i => i.id !== id);
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
        return { subtotal, tax, grand: subtotal + tax };
    }

    function renderCart() {
        if (cart.length === 0) {
            cartRows.innerHTML = `
                <tr id="empty-cart-row">
                    <td colspan="6" style="text-align: center; color: var(--clr-muted); padding: 28px 16px;">
                        <i class="bi bi-cart3" style="font-size: 22px; display: block; margin-bottom: 6px;"></i>
                        Cart is empty. Click any product card or scan a barcode to add.
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
        cartRows.innerHTML = cart.map(item => `
            <tr>
                <td title="${escapeHtml(item.name)}"><strong>${escapeHtml(item.name)}</strong><br><small class="muted">${escapeHtml(item.sku)}</small></td>
                <td style="text-align: center;">
                    <button type="button" class="qty-btn" onclick="updateQty(${item.id}, -1)">-</button>
                    <span style="display: inline-block; width: 28px; text-align: center; font-weight: 600;">${item.qty}</span>
                    <button type="button" class="qty-btn" onclick="updateQty(${item.id}, 1)">+</button>
                </td>
                <td style="text-align: right;">${item.price.toFixed(2)}</td>
                <td style="text-align: right;">${item.tax_rate.toFixed(1)}%</td>
                <td style="text-align: right; font-weight: 600;" class="text-green">${(item.price * item.qty).toFixed(2)}</td>
                <td style="text-align: center;">
                    <button type="button" class="btn-ghost danger" onclick="removeItem(${item.id})" aria-label="Remove item">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            </tr>
        `).join('');

        const totals = calculateTotals();
        document.getElementById('summary-subtotal').textContent = `KES ${totals.subtotal.toFixed(2)}`;
        document.getElementById('summary-tax').textContent = `KES ${totals.tax.toFixed(2)}`;
        document.getElementById('summary-grand').textContent = `KES ${totals.grand.toFixed(2)}`;
    }

    searchInput.addEventListener('ui:search', function (event) {
        searchQuery = String(event.detail?.value ?? '').trim();
        fetchCatalog();
    });

    barcodeInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            if (barcodeInput.value.trim()) lookupBarcode(barcodeInput.value);
        }
    });

    document.querySelectorAll('.cat-pill').forEach(btn => {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.cat-pill').forEach(b => {
                b.classList.remove('active');
                b.setAttribute('aria-checked', 'false');
            });
            this.classList.add('active');
            this.setAttribute('aria-checked', 'true');
            activeCategoryId = this.getAttribute('data-category');
            selectedCategoryLabel.textContent = this.getAttribute('data-category-label');
            categoryFilter.classList.remove('is-open');
            categoryFilterToggle.setAttribute('aria-expanded', 'false');
            fetchCatalog();
        });
    });

    categoryFilterToggle.addEventListener('click', function () {
        const isOpen = categoryFilter.classList.toggle('is-open');
        categoryFilterToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });
    document.addEventListener('click', function (event) {
        if (!categoryFilter.contains(event.target)) {
            categoryFilter.classList.remove('is-open');
            categoryFilterToggle.setAttribute('aria-expanded', 'false');
        }
    });
    categoryFilter.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            categoryFilter.classList.remove('is-open');
            categoryFilterToggle.setAttribute('aria-expanded', 'false');
            categoryFilterToggle.focus();
        }
    });

    clearBtn.addEventListener('click', function () {
        if (confirm('Clear all items in the cart?')) {
            cart = [];
            renderCart();
        }
    });

    payBtn.addEventListener('click', function () {
        const totals = calculateTotals();
        modalDue.textContent = `KES ${totals.grand.toFixed(2)}`;
        tenderAmount.value = totals.grand.toFixed(2);
        modalChange.textContent = 'KES 0.00';
        stkStatus.style.display = 'none';
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
        modalChange.textContent = `KES ${Math.max(0, tendered - totals.grand).toFixed(2)}`;
    });

    paymentMethod.addEventListener('change', function () {
        const isMpesa = this.value === 'mpesa';
        const isCard = this.value === 'card';
        refField.style.display = (isMpesa || isCard) ? 'block' : 'none';
        mpesaStkBox.style.display = isMpesa ? 'flex' : 'none';
    });

    sendStkBtn.addEventListener('click', async function () {
        const phone = mpesaPhone.value.trim();
        const totals = calculateTotals();
        if (!phone) {
            alert('Enter customer M-Pesa phone number.');
            return;
        }
        sendStkBtn.disabled = true;
        stkStatus.style.display = 'block';
        stkStatus.style.background = '#f1f5f9';
        stkStatus.style.color = '#334155';
        stkStatus.textContent = 'Sending STK Push prompt to ' + phone + '...';

        try {
            const res = await fetch("{{ route('tenant.pos.mpesa.stk-push') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    shop_id: shopId,
                    phone_number: phone,
                    amount: totals.grand,
                    account_reference: 'POS-' + shiftId
                })
            });
            const data = await res.json();
            stkStatus.style.background = data.success ? '#dcfce7' : '#fee2e2';
            stkStatus.style.color = data.success ? '#166534' : '#991b1b';
            stkStatus.textContent = data.customer_message || (data.success ? 'STK Push sent!' : 'STK Push failed.');
            if (data.success && data.checkout_request_id) {
                document.getElementById('payment-ref').value = data.checkout_request_id;
            }
        } catch (e) {
            stkStatus.style.background = '#fee2e2';
            stkStatus.style.color = '#991b1b';
            stkStatus.textContent = 'Network error sending STK Push.';
        } finally {
            sendStkBtn.disabled = false;
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
                prescription_id: i.prescription_id,
                pharmacist_user_id: i.pharmacist_user_id
            })),
            payments: [{
                method: paymentMethod.value,
                amount: paymentMethod.value === 'cash' ? tendered : totals.grand,
                reference: document.getElementById('payment-ref')?.value || null
            }]
        };

        try {
            const res = await fetch("{{ route('tenant.pos.process') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                    'Idempotency-Key': crypto.randomUUID()
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

    fetchCatalog();
</script>
@endpush
