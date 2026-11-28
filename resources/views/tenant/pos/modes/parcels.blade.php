<div style="display: grid; grid-template-columns: minmax(0, 1fr) 360px; gap: 16px; height: calc(100dvh - 180px); min-height: 600px;">
    {{-- Left: Parcel Intake Form --}}
    <div class="card" style="padding: 20px; overflow-y: auto; display: flex; flex-direction: column;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid var(--border-color, #e2e8f0);">
            <div>
                <h2 style="font-size: 1.15rem; font-weight: 700; margin: 0; display: flex; align-items: center; gap: 8px;">
                    <i class="bi bi-box-seam" style="color: #2563eb;"></i> Parcel Counter Intake
                </h2>
                <span class="muted" style="font-size: 0.8rem;">Register outgoing freight, collect shipping fee, and print thermal waybill.</span>
            </div>
            <span class="badge badge-info">Origin: {{ $openShift->shop->name }}</span>
        </div>

        <form method="POST" action="{{ route('tenant.pos.parcels.intake') }}" style="display: flex; flex-direction: column; gap: 16px; flex-grow: 1;">
            @csrf

            {{-- Routing & Service Level --}}
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                <div>
                    <label class="form-label" style="font-size: 0.85rem; font-weight: 600;">Destination Office / Branch *</label>
                    <select name="destination_shop_id" class="form-control" required style="font-size: 0.9rem;">
                        <option value="">Select Destination Branch</option>
                        @foreach($destinationShops as $dShop)
                            <option value="{{ $dShop->id }}" {{ old('destination_shop_id') == $dShop->id ? 'selected' : '' }}>
                                {{ $dShop->name }} ({{ $dShop->code }}) &bull; {{ $dShop->city ?? 'Kenya' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label" style="font-size: 0.85rem; font-weight: 600;">Service Level *</label>
                    <select name="service_level" id="parcel_service_level" class="form-control" required onchange="calculateParcelFee()">
                        <option value="standard">Standard Freight (1-2 Days)</option>
                        <option value="express">Express Overnight (Next Morning)</option>
                    </select>
                </div>
            </div>

            {{-- Sender & Recipient --}}
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; padding: 14px; background: var(--bg-surface-secondary, #f8fafc); border-radius: 8px; border: 1px solid var(--border-color, #e2e8f0);">
                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <strong style="font-size: 0.85rem; color: #1e40af;"><i class="bi bi-person-up"></i> SENDER DETAILS</strong>
                    <div>
                        <label class="form-label" style="font-size: 0.8rem;">Sender Full Name *</label>
                        <input type="text" name="sender_name" class="form-control" required placeholder="e.g. Samuel Kamau" value="{{ old('sender_name') }}">
                    </div>
                    <div>
                        <label class="form-label" style="font-size: 0.8rem;">Sender Phone Number *</label>
                        <input type="tel" name="sender_phone" class="form-control" required placeholder="0712345678" value="{{ old('sender_phone') }}">
                    </div>
                </div>

                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <strong style="font-size: 0.85rem; color: #15803d;"><i class="bi bi-person-down"></i> RECIPIENT DETAILS</strong>
                    <div>
                        <label class="form-label" style="font-size: 0.8rem;">Recipient Full Name *</label>
                        <input type="text" name="recipient_name" class="form-control" required placeholder="e.g. Mary Wanjiku" value="{{ old('recipient_name') }}">
                    </div>
                    <div>
                        <label class="form-label" style="font-size: 0.8rem;">Recipient Phone Number *</label>
                        <input type="tel" name="recipient_phone" class="form-control" required placeholder="0798765432" value="{{ old('recipient_phone') }}">
                    </div>
                </div>
            </div>

            {{-- Package Specifications --}}
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 14px;">
                <div>
                    <label class="form-label" style="font-size: 0.85rem; font-weight: 600;">Package Description / Contents *</label>
                    <input type="text" name="contents" class="form-control" required placeholder="e.g. Electronics, Clothing bundle, Auto spare part" value="{{ old('contents') }}">
                </div>
                <div>
                    <label class="form-label" style="font-size: 0.85rem; font-weight: 600;">Weight (KG)</label>
                    <input type="number" step="0.1" min="0.1" max="1000" id="parcel_weight" name="weight_kg" class="form-control" value="1.0" oninput="calculateParcelFee()">
                </div>
            </div>

            {{-- Fee and Tender Section --}}
            <div style="padding: 16px; background: var(--bg-surface-secondary, #f1f5f9); border-radius: 8px; border: 1px solid #cbd5e1; margin-top: auto;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; align-items: center;">
                    <div>
                        <label class="form-label" style="font-size: 0.85rem; font-weight: 700;">Shipping Fee (KES) *</label>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span style="font-weight: 700; font-size: 1.1rem;">KES</span>
                            <input type="number" step="1" min="0" id="parcel_fee" name="fee" class="form-control" style="font-size: 1.25rem; font-weight: 700; color: #1e40af;" value="300" required>
                        </div>
                    </div>

                    <div>
                        <label class="form-label" style="font-size: 0.85rem; font-weight: 700;">Payment Tender Method *</label>
                        <select name="payment_method" class="form-control" required style="font-size: 0.95rem; font-weight: 600;">
                            <option value="cash">Cash Tender (Record to Shift)</option>
                            <option value="mpesa">M-Pesa Mobile Money</option>
                            <option value="pod">Pay on Delivery (Collect at Destination)</option>
                        </select>
                    </div>
                </div>

                <div style="margin-top: 14px; display: flex; justify-content: flex-end;">
                    <button type="submit" class="btn btn-primary" style="padding: 10px 24px; font-size: 1rem; font-weight: 700;">
                        <i class="bi bi-printer"></i> Register &amp; Print Waybill
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- Right: Recent Waybills in this branch --}}
    <div class="card" style="padding: 16px; display: flex; flex-direction: column; overflow: hidden;">
        <h3 style="font-size: 1rem; font-weight: 700; margin-bottom: 12px; display: flex; align-items: center; justify-content: space-between;">
            <span><i class="bi bi-clock-history"></i> Recent Waybills</span>
            <span class="badge badge-success">{{ $recentParcels->count() }} Processed</span>
        </h3>

        <div style="overflow-y: auto; flex-grow: 1; display: flex; flex-direction: column; gap: 8px;">
            @forelse($recentParcels as $p)
                <div style="padding: 10px; background: var(--bg-surface-secondary, #f8fafc); border: 1px solid var(--border-color, #e2e8f0); border-radius: 6px; font-size: 0.8rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                        <strong style="color: #2563eb;">{{ $p->tracking_number }}</strong>
                        <span class="badge {{ $p->status === 'delivered' ? 'badge-success' : 'badge-warning' }}" style="font-size: 0.7rem;">
                            {{ ucfirst($p->status) }}
                        </span>
                    </div>
                    <div class="muted">To: <strong>{{ $p->recipient_name }}</strong> ({{ $p->destinationOffice?->name }})</div>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 6px;">
                        <span style="font-weight: 700;">KES {{ number_format((float) $p->fee, 2) }}</span>
                        <a href="{{ route('tenant.pos.waybill', $p) }}" target="_blank" class="btn btn-secondary btn-sm" style="padding: 2px 8px; font-size: 0.75rem;">
                            <i class="bi bi-printer"></i> Waybill
                        </a>
                    </div>
                </div>
            @empty
                <div class="muted" style="text-align: center; padding: 24px; font-size: 0.85rem;">
                    No parcels dispatched from this counter yet today.
                </div>
            @endforelse
        </div>
    </div>
</div>

<script>
function calculateParcelFee() {
    const weight = parseFloat(document.getElementById('parcel_weight').value) || 1.0;
    const isExpress = document.getElementById('parcel_service_level').value === 'express';
    
    // Base rate: KES 300 for first 2kg, + KES 50 per additional kg.
    let fee = 300;
    if (weight > 2) {
        fee += Math.ceil(weight - 2) * 50;
    }
    if (isExpress) {
        fee += 200;
    }
    document.getElementById('parcel_fee').value = fee;
}
</script>
