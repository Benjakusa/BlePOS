@extends('tenant.layout')

@section('title', 'Parcel '.$parcel->tracking_number)

@section('content')
<div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:18px;">
    <div>
        <a href="{{ route('tenant.parcels.index') }}" class="btn btn-ghost btn-sm"><i class="bi bi-arrow-left"></i> Parcel desk</a>
        <h1 class="page-title" style="margin-top:12px;">{{ $parcel->tracking_number }}</h1>
        <p class="muted" style="margin:4px 0 0;">Parcel tracking and office handoff history</p>
    </div>
    @php
        $badge = match ($parcel->status) {
            'received' => 'warning',
            'in_transit' => 'info',
            'arrived', 'delivered' => 'success',
            default => 'secondary',
        };
    @endphp
    <span class="badge badge-{{ $badge }}" style="font-size:14px;padding:8px 12px;">{{ ucfirst(str_replace('_', ' ', $parcel->status)) }}</span>
</div>

<div class="grid grid-2" style="align-items:start;gap:18px;">
    <section class="card">
        <h2 class="section-title">Shipment details</h2>
        <dl style="display:grid;grid-template-columns:minmax(110px, 1fr) 2fr;gap:10px;margin:18px 0 0;">
            <dt class="muted">From</dt><dd>{{ $parcel->originOffice?->name }} — {{ $parcel->originOffice?->city }}</dd>
            <dt class="muted">To</dt><dd>{{ $parcel->destinationOffice?->name }} — {{ $parcel->destinationOffice?->city }}</dd>
            <dt class="muted">Contents</dt><dd>{{ $parcel->contents }}</dd>
            <dt class="muted">Service</dt><dd>{{ ucfirst($parcel->service_level) }}@if($parcel->weight_kg) · {{ number_format((float) $parcel->weight_kg, 2) }} kg @endif</dd>
            <dt class="muted">Fee</dt><dd>KES {{ number_format((float) $parcel->fee, 2) }}</dd>
            <dt class="muted">Sender</dt><dd>{{ $parcel->sender_name }} · {{ $parcel->sender_phone }}</dd>
            <dt class="muted">Recipient</dt><dd>{{ $parcel->recipient_name }} · {{ $parcel->recipient_phone }}</dd>
            <dt class="muted">Received by</dt><dd>{{ $parcel->receivedBy?->name }} · {{ $parcel->received_at?->format('d M Y, H:i') }}</dd>
            @if($parcel->dispatchedBy)
                <dt class="muted">Dispatched by</dt><dd>{{ $parcel->dispatchedBy->name }} · {{ $parcel->dispatched_at?->format('d M Y, H:i') }}</dd>
            @endif
            @if($parcel->branchReceivedBy)
                <dt class="muted">Office received by</dt><dd>{{ $parcel->branchReceivedBy->name }} · {{ $parcel->branch_received_at?->format('d M Y, H:i') }}</dd>
            @endif
            @if($parcel->deliveredBy)
                <dt class="muted">Delivered by</dt><dd>{{ $parcel->deliveredBy->name }} · {{ $parcel->delivered_at?->format('d M Y, H:i') }}</dd>
            @endif
        </dl>

        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:22px;">
            @if($canDispatch && $parcel->status === 'received' && (int) $parcel->origin_shop_id === (int) $currentShopId)
                <form method="POST" action="{{ route('tenant.parcels.dispatch', $parcel) }}">
                    @csrf
                    <button class="btn btn-primary"><i class="bi bi-truck"></i> Dispatch to destination</button>
                </form>
            @endif
            @if($canReceive && $parcel->status === 'in_transit' && (int) $parcel->destination_shop_id === (int) $currentShopId)
                <form method="POST" action="{{ route('tenant.parcels.receive-destination', $parcel) }}">
                    @csrf
                    <button class="btn btn-primary"><i class="bi bi-box-arrow-in-down"></i> Receive at this office</button>
                </form>
            @endif
            @if($canDeliver && $parcel->status === 'arrived' && (int) $parcel->current_shop_id === (int) $currentShopId)
                <form method="POST" action="{{ route('tenant.parcels.deliver', $parcel) }}">
                    @csrf
                    <button class="btn btn-primary"><i class="bi bi-check2-circle"></i> Mark delivered</button>
                </form>
            @endif
        </div>
        @if($errors->any())
            <div class="alert alert-danger" style="margin-top:14px;">{{ $errors->first() }}</div>
        @endif
    </section>

    <section class="card">
        <h2 class="section-title">Tracking history</h2>
        <ol style="list-style:none;padding:0;margin:18px 0 0;">
            @forelse($parcel->events as $event)
                <li style="position:relative;padding:0 0 18px 24px;border-left:2px solid var(--clr-border, #d7dee7);margin-left:6px;">
                    <span style="position:absolute;left:-7px;top:3px;width:12px;height:12px;border-radius:50%;background:var(--clr-blue, #2563eb);"></span>
                    <strong>{{ ucfirst(str_replace('_', ' ', $event->event_type)) }}</strong>
                    <div class="muted">{{ $event->office?->name ?? 'Office' }} · {{ $event->actor?->name ?? 'Staff' }}</div>
                    <small class="muted">{{ $event->occurred_at->format('d M Y, H:i') }}</small>
                    @if($event->note)<p style="margin:5px 0 0;">{{ $event->note }}</p>@endif
                </li>
            @empty
                <li class="muted">No tracking events recorded.</li>
            @endforelse
        </ol>
    </section>
</div>
@endsection
