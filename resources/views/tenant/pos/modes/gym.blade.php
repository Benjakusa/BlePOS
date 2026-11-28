<div style="display: grid; grid-template-columns: minmax(0, 1fr) 360px; gap: 16px; height: calc(100dvh - 180px); min-height: 600px;">
    {{-- Left: Front Desk Operations --}}
    <div class="card" style="padding: 20px; overflow-y: auto; display: flex; flex-direction: column;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid var(--border-color, #e2e8f0);">
            <div>
                <h2 style="font-size: 1.15rem; font-weight: 700; margin: 0; display: flex; align-items: center; gap: 8px;">
                    <i class="bi bi-activity" style="color: #0284c7;"></i> Gym Front Desk Terminal
                </h2>
                <span class="muted" style="font-size: 0.8rem;">Member check-ins, walk-in day passes, and membership plan renewals.</span>
            </div>
            <span class="badge badge-info">Desk: {{ $openShift->shop->name }}</span>
        </div>

        {{-- Front Desk Operation Selection Pills --}}
        <div style="display: flex; gap: 8px; margin-bottom: 20px;">
            <button type="button" class="btn btn-sm" id="tab_btn_checkin" onclick="switchGymTab('checkin')" style="background: #2563eb; color: #fff;">
                <i class="bi bi-qr-code-scan"></i> Member Check-in
            </button>
            <button type="button" class="btn btn-outline btn-sm" id="tab_btn_daypass" onclick="switchGymTab('daypass')">
                <i class="bi bi-ticket-perforated"></i> Walk-in Day Pass
            </button>
            <button type="button" class="btn btn-outline btn-sm" id="tab_btn_plan" onclick="switchGymTab('plan')">
                <i class="bi bi-card-checklist"></i> Sell Membership Plan
            </button>
        </div>

        {{-- Form 1: Member Check-In --}}
        <div id="gym_section_checkin" style="display: flex; flex-direction: column; gap: 16px;">
            <form method="POST" action="{{ route('tenant.pos.gym.action') }}">
                @csrf
                <input type="hidden" name="action_type" value="checkin">

                <div class="card" style="padding: 16px; background: var(--bg-surface-secondary, #f8fafc); border: 1px solid var(--border-color, #e2e8f0);">
                    <label class="form-label" style="font-size: 0.85rem; font-weight: 600;">Select Member (or Search by Phone/Name) *</label>
                    <select name="member_id" class="form-control" required style="font-size: 0.95rem; margin-bottom: 12px;">
                        <option value="">-- Choose Member --</option>
                        @foreach($gymMembers as $m)
                            <option value="{{ $m->id }}">{{ $m->name }} &bull; {{ $m->phone }}</option>
                        @endforeach
                    </select>

                    <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px; font-weight: 700; font-size: 1rem;">
                        <i class="bi bi-box-arrow-in-right"></i> Confirm Member Check-In
                    </button>
                </div>
            </form>
        </div>

        {{-- Form 2: Walk-In Day Pass --}}
        <div id="gym_section_daypass" style="display: none; flex-direction: column; gap: 16px;">
            <form method="POST" action="{{ route('tenant.pos.gym.action') }}">
                @csrf
                <input type="hidden" name="action_type" value="day_pass">

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px;">
                    <div>
                        <label class="form-label" style="font-size: 0.85rem; font-weight: 600;">Guest Name *</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. David Otieno" required>
                    </div>
                    <div>
                        <label class="form-label" style="font-size: 0.85rem; font-weight: 600;">Guest Phone Number *</label>
                        <input type="tel" name="phone" class="form-control" placeholder="0722000000" required>
                    </div>
                </div>

                <div style="padding: 16px; background: var(--bg-surface-secondary, #f1f5f9); border-radius: 8px; border: 1px solid #cbd5e1;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; align-items: center;">
                        <div>
                            <label class="form-label" style="font-size: 0.85rem; font-weight: 700;">Day Pass Fee (KES) *</label>
                            <input type="number" step="50" min="0" name="amount" class="form-control" value="500" required style="font-size: 1.2rem; font-weight: 700; color: #1e40af;">
                        </div>
                        <div>
                            <label class="form-label" style="font-size: 0.85rem; font-weight: 700;">Payment Tender *</label>
                            <select name="payment_method" class="form-control" required style="font-size: 0.95rem; font-weight: 600;">
                                <option value="cash">Cash Tender (Record to Shift)</option>
                                <option value="mpesa">M-Pesa Mobile Money</option>
                            </select>
                        </div>
                    </div>

                    <div style="margin-top: 16px; display: flex; justify-content: flex-end;">
                        <button type="submit" class="btn btn-primary" style="padding: 10px 24px; font-weight: 700;">
                            <i class="bi bi-printer"></i> Charge Pass &amp; Check In
                        </button>
                    </div>
                </div>
            </form>
        </div>

        {{-- Form 3: Sell Membership Plan --}}
        <div id="gym_section_plan" style="display: none; flex-direction: column; gap: 16px;">
            <form method="POST" action="{{ route('tenant.pos.gym.action') }}">
                @csrf
                <input type="hidden" name="action_type" value="plan_sale">

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px;">
                    <div>
                        <label class="form-label" style="font-size: 0.85rem; font-weight: 600;">Select Member *</label>
                        <select name="member_id" class="form-control" required>
                            <option value="">-- Choose Member --</option>
                            @foreach($gymMembers as $m)
                                <option value="{{ $m->id }}">{{ $m->name }} ({{ $m->phone }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label" style="font-size: 0.85rem; font-weight: 600;">Membership Plan *</label>
                        <select name="plan_id" class="form-control" required>
                            <option value="">-- Choose Plan --</option>
                            @foreach($gymPlans as $gp)
                                <option value="{{ $gp->id }}">
                                    {{ $gp->name }} &bull; KES {{ number_format((float) $gp->price, 2) }} ({{ ucfirst($gp->billing_cycle) }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div style="padding: 16px; background: var(--bg-surface-secondary, #f1f5f9); border-radius: 8px; border: 1px solid #cbd5e1;">
                    <div>
                        <label class="form-label" style="font-size: 0.85rem; font-weight: 700;">Payment Tender Method *</label>
                        <select name="payment_method" class="form-control" required style="font-size: 0.95rem; font-weight: 600;">
                            <option value="cash">Cash Tender (Record to Shift)</option>
                            <option value="mpesa">M-Pesa Mobile Money</option>
                        </select>
                    </div>

                    <div style="margin-top: 16px; display: flex; justify-content: flex-end;">
                        <button type="submit" class="btn btn-primary" style="padding: 10px 24px; font-weight: 700;">
                            <i class="bi bi-printer"></i> Activate Plan &amp; Issue Slip
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Right: Live Front Desk Stream --}}
    <div class="card" style="padding: 16px; display: flex; flex-direction: column; overflow: hidden;">
        <h3 style="font-size: 1rem; font-weight: 700; margin-bottom: 12px; display: flex; align-items: center; justify-content: space-between;">
            <span><i class="bi bi-clock-history"></i> Today's Check-ins</span>
            <span class="badge badge-success">{{ $recentCheckins->count() }} Entries</span>
        </h3>

        <div style="overflow-y: auto; flex-grow: 1; display: flex; flex-direction: column; gap: 8px;">
            @forelse($recentCheckins as $chk)
                <div style="padding: 10px; background: var(--bg-surface-secondary, #f8fafc); border: 1px solid var(--border-color, #e2e8f0); border-radius: 6px; font-size: 0.8rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2px;">
                        <strong>{{ $chk->member?->name ?? 'Guest' }}</strong>
                        <span class="muted" style="font-size: 0.75rem;">{{ $chk->checked_in_at?->format('H:i') }}</span>
                    </div>
                    <div class="muted" style="font-size: 0.75rem;">Phone: {{ $chk->member?->phone ?? '—' }}</div>
                </div>
            @empty
                <div class="muted" style="text-align: center; padding: 24px; font-size: 0.85rem;">
                    No check-ins logged yet today.
                </div>
            @endforelse
        </div>
    </div>
</div>

<script>
function switchGymTab(tab) {
    const tabs = ['checkin', 'daypass', 'plan'];
    tabs.forEach(t => {
        const sec = document.getElementById('gym_section_' + t);
        const btn = document.getElementById('tab_btn_' + t);
        if (t === tab) {
            sec.style.display = 'flex';
            btn.style.background = '#2563eb';
            btn.style.color = '#fff';
            btn.classList.remove('btn-outline');
        } else {
            sec.style.display = 'none';
            btn.style.background = 'transparent';
            btn.style.color = 'inherit';
            btn.classList.add('btn-outline');
        }
    });
}
</script>
