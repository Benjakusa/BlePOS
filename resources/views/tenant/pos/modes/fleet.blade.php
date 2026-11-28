<div style="display: grid; grid-template-columns: minmax(0, 1fr) 360px; gap: 16px; height: calc(100dvh - 180px); min-height: 600px;">
    {{-- Left: Fleet Dispatch Terminal --}}
    <div class="card" style="padding: 20px; overflow-y: auto; display: flex; flex-direction: column;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid var(--border-color, #e2e8f0);">
            <div>
                <h2 style="font-size: 1.15rem; font-weight: 700; margin: 0; display: flex; align-items: center; gap: 8px;">
                    <i class="bi bi-car-front" style="color: #6366f1;"></i> Fleet Car Hire Dispatch Terminal
                </h2>
                <span class="muted" style="font-size: 0.8rem;">Vehicle handover, KYC customer recording, security deposit collection, and gate pass issuance.</span>
            </div>
            <span class="badge badge-info">Station: {{ $openShift->shop->name }}</span>
        </div>

        <form method="POST" action="{{ route('tenant.pos.fleet.dispatch') }}" style="display: flex; flex-direction: column; gap: 16px; flex-grow: 1;">
            @csrf

            {{-- Vehicle Selection --}}
            <div>
                <label class="form-label" style="font-size: 0.85rem; font-weight: 600;">Select Available Fleet Vehicle *</label>
                <select name="vehicle_id" class="form-control" required style="font-size: 0.95rem;">
                    <option value="">-- Choose Vehicle (Available Only) --</option>
                    @foreach($availableVehicles as $v)
                        <option value="{{ $v->id }}">
                            {{ $v->plate }} &bull; {{ $v->make }} {{ $v->model }} ({{ $v->year ?? 'Fleet' }}) &bull; {{ ucfirst($v->status) }}
                        </option>
                    @endforeach
                </select>
                @if($availableVehicles->isEmpty())
                    <span style="font-size: 0.75rem; color: #dc2626; margin-top: 4px; display: block;">No vehicles currently marked as available.</span>
                @endif
            </div>

            {{-- Customer KYC --}}
            <div style="padding: 14px; background: var(--bg-surface-secondary, #f8fafc); border-radius: 8px; border: 1px solid var(--border-color, #e2e8f0);">
                <strong style="font-size: 0.85rem; color: #334155; display: block; margin-bottom: 10px;">
                    <i class="bi bi-person-badge"></i> HIRER / DRIVER KYC INFORMATION
                </strong>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 10px;">
                    <div>
                        <label class="form-label" style="font-size: 0.8rem;">Customer Full Name *</label>
                        <input type="text" name="customer_name" class="form-control" placeholder="e.g. John Mwangi" required>
                    </div>
                    <div>
                        <label class="form-label" style="font-size: 0.8rem;">Customer Phone Number *</label>
                        <input type="tel" name="customer_phone" class="form-control" placeholder="0711000000" required>
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div>
                        <label class="form-label" style="font-size: 0.8rem;">National ID / Passport Number</label>
                        <input type="text" name="id_number" class="form-control" placeholder="12345678">
                    </div>
                    <div>
                        <label class="form-label" style="font-size: 0.8rem;">Driving License Number</label>
                        <input type="text" name="license_number" class="form-control" placeholder="DL-87654321">
                    </div>
                </div>
            </div>

            {{-- Rental Schedule --}}
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                <div>
                    <label class="form-label" style="font-size: 0.85rem; font-weight: 600;">Pickup Date &amp; Time *</label>
                    <input type="datetime-local" name="pickup_at" class="form-control" value="{{ now()->format('Y-m-d\TH:i') }}" required>
                </div>
                <div>
                    <label class="form-label" style="font-size: 0.85rem; font-weight: 600;">Scheduled Return Date &amp; Time *</label>
                    <input type="datetime-local" name="return_at" class="form-control" value="{{ now()->addDays(2)->format('Y-m-d\TH:i') }}" required>
                </div>
            </div>

            {{-- Rates, Deposit & Tender --}}
            <div style="padding: 16px; background: var(--bg-surface-secondary, #f1f5f9); border-radius: 8px; border: 1px solid #cbd5e1; margin-top: auto;">
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px;">
                    <div>
                        <label class="form-label" style="font-size: 0.825rem; font-weight: 700;">Agreed Daily Rate (KES) *</label>
                        <input type="number" step="100" min="0" name="agreed_rate" class="form-control" value="3500" required>
                    </div>
                    <div>
                        <label class="form-label" style="font-size: 0.825rem; font-weight: 700;">Security Deposit (KES) *</label>
                        <input type="number" step="500" min="0" name="deposit_amount" class="form-control" value="10000" required>
                    </div>
                    <div>
                        <label class="form-label" style="font-size: 0.825rem; font-weight: 700;">Deposit Tender *</label>
                        <select name="payment_method" class="form-control" required>
                            <option value="cash">Cash Tender</option>
                            <option value="mpesa">M-Pesa Mobile</option>
                        </select>
                    </div>
                </div>

                <div style="margin-top: 14px; display: flex; justify-content: flex-end;">
                    <button type="submit" class="btn btn-primary" style="padding: 10px 24px; font-weight: 700;">
                        <i class="bi bi-printer"></i> Dispatch &amp; Print Gate Pass
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- Right: Currently Dispatched Fleet --}}
    <div class="card" style="padding: 16px; display: flex; flex-direction: column; overflow: hidden;">
        <h3 style="font-size: 1rem; font-weight: 700; margin-bottom: 12px; display: flex; align-items: center; justify-content: space-between;">
            <span><i class="bi bi-clock-history"></i> Active Rentals</span>
            <span class="badge badge-warning">{{ $recentBookings->count() }} Hired</span>
        </h3>

        <div style="overflow-y: auto; flex-grow: 1; display: flex; flex-direction: column; gap: 8px;">
            @forelse($recentBookings as $b)
                <div style="padding: 10px; background: var(--bg-surface-secondary, #f8fafc); border: 1px solid var(--border-color, #e2e8f0); border-radius: 6px; font-size: 0.8rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2px;">
                        <strong style="color: #4338ca;">{{ $b->vehicle?->plate }}</strong>
                        <span class="badge badge-info" style="font-size: 0.7rem;">{{ ucfirst($b->status) }}</span>
                    </div>
                    <div class="muted">Hirer: <strong>{{ $b->customer?->name }}</strong></div>
                    <div class="muted" style="font-size: 0.75rem;">Return: {{ \Carbon\Carbon::parse($b->return_at)->format('d/m H:i') }}</div>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 6px;">
                        <span>Dep: <strong>KES {{ number_format((float) $b->deposit_amount, 0) }}</strong></span>
                        <a href="{{ route('tenant.pos.fleet-slip', $b) }}" target="_blank" class="btn btn-secondary btn-sm" style="padding: 2px 8px; font-size: 0.75rem;">
                            <i class="bi bi-printer"></i> Pass
                        </a>
                    </div>
                </div>
            @empty
                <div class="muted" style="text-align: center; padding: 24px; font-size: 0.85rem;">
                    No active vehicle rentals at this moment.
                </div>
            @endforelse
        </div>
    </div>
</div>
