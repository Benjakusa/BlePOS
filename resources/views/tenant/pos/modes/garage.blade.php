<div style="display: grid; grid-template-columns: minmax(0, 1fr) 360px; gap: 16px; height: calc(100dvh - 180px); min-height: 600px;">
    {{-- Left: Auto Garage Counter Intake --}}
    <div class="card" style="padding: 20px; overflow-y: auto; display: flex; flex-direction: column;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid var(--border-color, #e2e8f0);">
            <div>
                <h2 style="font-size: 1.15rem; font-weight: 700; margin: 0; display: flex; align-items: center; gap: 8px;">
                    <i class="bi bi-tools" style="color: #ea580c;"></i> Auto Garage Service Counter
                </h2>
                <span class="muted" style="font-size: 0.8rem;">Vehicle check-in, reported defect diagnostics, deposit collection, and job card work orders.</span>
            </div>
            <span class="badge badge-info">Workshop: {{ $openShift->shop->name }}</span>
        </div>

        <form method="POST" action="{{ route('tenant.pos.garage.intake') }}" style="display: flex; flex-direction: column; gap: 16px; flex-grow: 1;">
            @csrf

            {{-- Vehicle Info --}}
            <div style="padding: 14px; background: var(--bg-surface-secondary, #f8fafc); border-radius: 8px; border: 1px solid var(--border-color, #e2e8f0);">
                <strong style="font-size: 0.85rem; color: #334155; display: block; margin-bottom: 10px;">
                    <i class="bi bi-truck"></i> VEHICLE IDENTIFICATION
                </strong>
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px;">
                    <div>
                        <label class="form-label" style="font-size: 0.8rem;">Registration Plate *</label>
                        <input type="text" name="plate" class="form-control" placeholder="e.g. KDA 123X" required style="text-transform: uppercase; font-weight: 700;">
                    </div>
                    <div>
                        <label class="form-label" style="font-size: 0.8rem;">Make *</label>
                        <input type="text" name="make" class="form-control" placeholder="e.g. Toyota" required>
                    </div>
                    <div>
                        <label class="form-label" style="font-size: 0.8rem;">Model *</label>
                        <input type="text" name="model" class="form-control" placeholder="e.g. Hilux" required>
                    </div>
                </div>
            </div>

            {{-- Customer & Mileage --}}
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px;">
                <div>
                    <label class="form-label" style="font-size: 0.85rem; font-weight: 600;">Customer Name *</label>
                    <input type="text" name="customer_name" class="form-control" placeholder="e.g. Peter Njoroge" required>
                </div>
                <div>
                    <label class="form-label" style="font-size: 0.85rem; font-weight: 600;">Customer Phone *</label>
                    <input type="tel" name="customer_phone" class="form-control" placeholder="0722123456" required>
                </div>
                <div>
                    <label class="form-label" style="font-size: 0.85rem; font-weight: 600;">Current Mileage (KM)</label>
                    <input type="number" step="1" min="0" name="mileage_in" class="form-control" placeholder="e.g. 145000">
                </div>
            </div>

            {{-- Reported Issues & Scope of Work --}}
            <div>
                <label class="form-label" style="font-size: 0.85rem; font-weight: 600;">Reported Issues &amp; Requested Services *</label>
                <textarea name="service_notes" class="form-control" rows="3" required placeholder="e.g. 5,000 KM major service (oil change, oil filter, air filter, brake pad inspection and wheel alignment). Customer reports squeaking front right brake."></textarea>
            </div>

            {{-- Labour Estimate, Deposit & Tender --}}
            <div style="padding: 16px; background: var(--bg-surface-secondary, #f1f5f9); border-radius: 8px; border: 1px solid #cbd5e1; margin-top: auto;">
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px;">
                    <div>
                        <label class="form-label" style="font-size: 0.825rem; font-weight: 700;">Labour Estimate (KES) *</label>
                        <input type="number" step="100" min="0" name="labour_estimate" class="form-control" value="2500" required>
                    </div>
                    <div>
                        <label class="form-label" style="font-size: 0.825rem; font-weight: 700;">Intake Deposit (KES)</label>
                        <input type="number" step="100" min="0" name="deposit_amount" class="form-control" value="0">
                    </div>
                    <div>
                        <label class="form-label" style="font-size: 0.825rem; font-weight: 700;">Deposit Tender Method</label>
                        <select name="payment_method" class="form-control">
                            <option value="cash">Cash Tender</option>
                            <option value="mpesa">M-Pesa Mobile</option>
                        </select>
                    </div>
                </div>

                <div style="margin-top: 14px; display: flex; justify-content: flex-end;">
                    <button type="submit" class="btn btn-primary" style="padding: 10px 24px; font-weight: 700;">
                        <i class="bi bi-printer"></i> Intake Vehicle &amp; Print Job Card
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- Right: In-Progress Workshop Jobs --}}
    <div class="card" style="padding: 16px; display: flex; flex-direction: column; overflow: hidden;">
        <h3 style="font-size: 1rem; font-weight: 700; margin-bottom: 12px; display: flex; align-items: center; justify-content: space-between;">
            <span><i class="bi bi-clock-history"></i> Workshop Jobs</span>
            <span class="badge badge-warning">{{ $recentJobCards->count() }} Active</span>
        </h3>

        <div style="overflow-y: auto; flex-grow: 1; display: flex; flex-direction: column; gap: 8px;">
            @forelse($recentJobCards as $jc)
                <div style="padding: 10px; background: var(--bg-surface-secondary, #f8fafc); border: 1px solid var(--border-color, #e2e8f0); border-radius: 6px; font-size: 0.8rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2px;">
                        <strong style="color: #c2410c;">{{ $jc->vehicle?->plate }}</strong>
                        <span class="badge badge-warning" style="font-size: 0.7rem;">{{ ucfirst($jc->status) }}</span>
                    </div>
                    <div class="muted">Job: <strong>{{ $jc->job_number }}</strong> &bull; {{ $jc->customer?->name }}</div>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 6px;">
                        <span>Est: <strong>KES {{ number_format((float) ($jc->estimate?->grand_total ?? 0), 2) }}</strong></span>
                        <a href="{{ route('tenant.pos.garage-slip', $jc) }}" target="_blank" class="btn btn-secondary btn-sm" style="padding: 2px 8px; font-size: 0.75rem;">
                            <i class="bi bi-printer"></i> Job Card
                        </a>
                    </div>
                </div>
            @empty
                <div class="muted" style="text-align: center; padding: 24px; font-size: 0.85rem;">
                    No job cards logged yet today.
                </div>
            @endforelse
        </div>
    </div>
</div>
