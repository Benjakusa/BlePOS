@extends('tenant.layout')

@section('title', 'Table Management')

@section('content')
<div class="page-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
    <div>
        <h1 style="font-size: 22px; font-weight: 600; margin: 0; color: #0f172a;">Floor Plan &amp; Table Management</h1>
        <p style="font-size: 13px; color: #64748b; margin: 4px 0 0 0;">Monitor dining room zones, live guest seating, orders, and table availability.</p>
    </div>
    <div style="display: flex; gap: 10px;">
        <a href="{{ route('tenant.pos.checkout') }}" class="btn btn-secondary" style="text-decoration:none;">
            <i class="bi bi-display"></i> Open POS Terminal
        </a>
        <button type="button" class="btn" onclick="document.getElementById('add-table-modal').style.display='flex'">
            <i class="bi bi-plus-lg"></i> Add Dining Table
        </button>
    </div>
</div>

{{-- Live Status KPI Cards --}}
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px; margin-bottom: 20px;">
    <div class="card" style="padding: 16px; border-left: 4px solid #16a34a;">
        <span style="font-size: 12px; color: #64748b; text-transform: uppercase; font-weight: 600;">Available Tables</span>
        <div style="font-size: 24px; font-weight: 700; color: #16a34a; margin-top: 4px;">{{ $stats['available'] }}</div>
        <span style="font-size: 11px; color: #94a3b8;">Ready for seating</span>
    </div>
    <div class="card" style="padding: 16px; border-left: 4px solid #dc2626;">
        <span style="font-size: 12px; color: #64748b; text-transform: uppercase; font-weight: 600;">Occupied (Dine-in)</span>
        <div style="font-size: 24px; font-weight: 700; color: #dc2626; margin-top: 4px;">{{ $stats['occupied'] }}</div>
        <span style="font-size: 11px; color: #94a3b8;">Guests dining now</span>
    </div>
    <div class="card" style="padding: 16px; border-left: 4px solid #2563eb;">
        <span style="font-size: 12px; color: #64748b; text-transform: uppercase; font-weight: 600;">Reserved</span>
        <div style="font-size: 24px; font-weight: 700; color: #2563eb; margin-top: 4px;">{{ $stats['reserved'] }}</div>
        <span style="font-size: 11px; color: #94a3b8;">Upcoming reservations</span>
    </div>
    <div class="card" style="padding: 16px; border-left: 4px solid #64748b;">
        <span style="font-size: 12px; color: #64748b; text-transform: uppercase; font-weight: 600;">Seating Capacity</span>
        <div style="font-size: 24px; font-weight: 700; color: #0f172a; margin-top: 4px;">{{ $stats['total_seats'] }}</div>
        <span style="font-size: 11px; color: #94a3b8;">Across {{ $stats['total'] }} tables</span>
    </div>
</div>

{{-- Zone Tabs / Filter Bar --}}
<div class="card" style="margin-bottom: 20px; padding: 12px 18px;">
    <form method="get" action="{{ route('tenant.tables.index') }}" style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
        <div style="width: 200px;">
            <x-input type="select" name="zone" label="Dining Zone" onchange="this.form.submit()">
                <option value="">All Dining Zones</option>
                @foreach($zones as $z)
                    <option value="{{ $z }}" @selected(request('zone') === $z)>{{ $z }}</option>
                @endforeach
            </x-input>
        </div>
        <div style="width: 180px;">
            <x-input type="select" name="status" label="Status" onchange="this.form.submit()">
                <option value="">All Statuses</option>
                <option value="available" @selected(request('status') === 'available')>Available</option>
                <option value="occupied" @selected(request('status') === 'occupied')>Occupied</option>
                <option value="reserved" @selected(request('status') === 'reserved')>Reserved</option>
                <option value="cleaning" @selected(request('status') === 'cleaning')>Cleaning</option>
            </x-input>
        </div>
        @if(request()->hasAny(['zone', 'status']))
            <a href="{{ route('tenant.tables.index') }}" class="btn btn-outline btn-sm" style="text-decoration:none;">Clear Filters</a>
        @endif
    </form>
</div>

{{-- Visual Floor Plan Grid --}}
<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 16px;">
    @forelse($tables as $table)
        @php
            $badgeColor = match($table->status) {
                'available' => '#16a34a',
                'occupied' => '#dc2626',
                'reserved' => '#2563eb',
                default => '#64748b',
            };
            $bgTint = match($table->status) {
                'available' => '#f0fdf4',
                'occupied' => '#fef2f2',
                'reserved' => '#eff6ff',
                default => '#f8fafc',
            };
        @endphp
        <div class="card" style="padding: 16px; border-top: 4px solid {{ $badgeColor }}; background: #fff; display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
                    <div>
                        <strong style="font-size: 16px; color: #0f172a; display: block;">{{ $table->name }}</strong>
                        <span style="font-size: 12px; color: #64748b;">Code: {{ $table->code }} &bull; {{ $table->zone }}</span>
                    </div>
                    <span style="font-size: 11px; font-weight: 600; text-transform: uppercase; background: {{ $bgTint }}; color: {{ $badgeColor }}; padding: 3px 8px; border-radius: 6px; border: 1px solid {{ $badgeColor }}20;">
                        {{ $table->status }}
                    </span>
                </div>

                <div style="font-size: 13px; color: #334155; margin: 10px 0; display: flex; flex-direction: column; gap: 4px;">
                    <div><i class="bi bi-people" style="color: #64748b;"></i> <strong>{{ $table->seating_capacity }}</strong> Seats</div>
                    @if($table->isOccupied())
                        <div><i class="bi bi-person-check" style="color: #dc2626;"></i> <strong>{{ $table->guest_count ?? $table->seating_capacity }}</strong> Guests seated</div>
                        <div style="font-size: 12px; color: #64748b;">Seated at: {{ $table->occupied_at?->format('H:i') ?? 'Just now' }}</div>
                    @endif
                    @if($table->notes)
                        <div style="font-size: 12px; color: #64748b; font-style: italic; margin-top: 4px;">"{{ $table->notes }}"</div>
                    @endif
                </div>
            </div>

            <div style="border-top: 1px solid #f1f5f9; padding-top: 12px; margin-top: 8px; display: flex; gap: 8px; flex-wrap: wrap;">
                @if($table->isAvailable())
                    <form method="post" action="{{ route('tenant.tables.status', $table) }}" style="flex: 1;">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" value="occupied">
                        <button type="submit" class="btn btn-sm" style="width: 100%; background: #16a34a;">
                            <i class="bi bi-person-plus"></i> Seat Guests
                        </button>
                    </form>
                @elseif($table->isOccupied())
                    <form method="post" action="{{ route('tenant.tables.status', $table) }}" style="flex: 1;">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" value="available">
                        <button type="submit" class="btn btn-secondary btn-sm" style="width: 100%;">
                            <i class="bi bi-check-circle"></i> Free Table
                        </button>
                    </form>
                    <a href="{{ route('tenant.pos.checkout') }}?table_id={{ $table->id }}" class="btn btn-primary btn-sm" style="text-decoration:none;">
                        <i class="bi bi-cart3"></i> Order
                    </a>
                @else
                    <form method="post" action="{{ route('tenant.tables.status', $table) }}" style="flex: 1;">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" value="available">
                        <button type="submit" class="btn btn-secondary btn-sm" style="width: 100%;">
                            Set Available
                        </button>
                    </form>
                @endif
            </div>
        </div>
    @empty
        <div class="card" style="grid-column: 1 / -1; padding: 40px; text-align: center; color: #64748b;">
            <i class="bi bi-grid-3x3" style="font-size: 32px; display: block; margin-bottom: 8px; color: #94a3b8;"></i>
            No dining tables registered yet. Click <strong>Add Dining Table</strong> to create your floor layout.
        </div>
    @endforelse
</div>

{{-- Add Table Modal --}}
<div id="add-table-modal" style="display: none; position: fixed; inset: 0; background: rgba(15,23,42,.4); backdrop-filter: blur(2px); z-index: 999; align-items: center; justify-content: center;">
    <div class="card" style="background: #fff; width: 100%; max-width: 480px; padding: 24px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);">
        <h2 style="font-size: 18px; font-weight: 600; margin: 0 0 16px 0; color: #0f172a;">Add Dining Table</h2>
        <form method="post" action="{{ route('tenant.tables.store') }}">
            @csrf

            <div style="margin-bottom: 14px;">
                <x-input type="select" name="shop_id" label="Restaurant Branch" required>
                    @foreach($shops as $shop)
                        <option value="{{ $shop->id }}">{{ $shop->name }}</option>
                    @endforeach
                </x-input>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                <x-input type="text" name="name" label="Table Name (e.g. Table 1)" required />
                <x-input type="text" name="code" label="Table Code (e.g. T-01)" required />
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                <x-input type="select" name="zone" label="Dining Zone" required>
                    <option value="Main Dining">Main Dining</option>
                    <option value="Terrace & Garden">Terrace &amp; Garden</option>
                    <option value="VIP Lounge">VIP Lounge</option>
                    <option value="Bar Counter">Bar Counter</option>
                </x-input>
                <x-input type="number" name="seating_capacity" label="Seat Capacity" value="4" min="1" max="50" required />
            </div>

            <div style="margin-bottom: 16px;">
                <x-input type="select" name="status" label="Initial Status" required>
                    <option value="available">Available</option>
                    <option value="occupied">Occupied</option>
                    <option value="reserved">Reserved</option>
                </x-input>
            </div>

            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('add-table-modal').style.display='none'">Cancel</button>
                <button type="submit" class="btn">Create Table</button>
            </div>
        </form>
    </div>
</div>
@endsection
