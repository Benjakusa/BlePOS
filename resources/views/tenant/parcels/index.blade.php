@extends('tenant.layout')

@section('title', 'Parcel Desk')

@section('content')
<div class="page-header" style="display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap;">
    <div>
        <h1 class="page-title">Parcel Desk</h1>
        <p class="muted" style="margin:4px 0 0;">{{ $office->name }} · Receive parcels, follow office transfers, and check delivery status.</p>
    </div>
    @if($canReceive)
        <a href="{{ route('tenant.parcels.create') }}" class="btn btn-primary">
            <i class="bi bi-box-arrow-in-down"></i> Receive parcel
        </a>
    @endif
</div>

<div class="grid grid-4 mb-4">
    <div class="card p-3"><h3>{{ $counts['received'] }}</h3><p class="text-muted mb-0">Awaiting dispatch</p></div>
    <div class="card p-3"><h3>{{ $counts['incoming'] }}</h3><p class="text-muted mb-0">Incoming to this office</p></div>
    <div class="card p-3"><h3>{{ $counts['arrived'] }}</h3><p class="text-muted mb-0">At this office</p></div>
    <div class="card p-3"><h3>{{ $counts['delivered'] }}</h3><p class="text-muted mb-0">Delivered</p></div>
</div>

<div class="card">
    <form method="GET" action="{{ route('tenant.parcels.index') }}" style="display:flex;gap:10px;align-items:end;flex-wrap:wrap;margin-bottom:18px;">
        <label style="flex:1;min-width:220px;">
            <span class="form-label">Search parcels</span>
            <input class="form-control" type="search" name="q" value="{{ request('q') }}" placeholder="Tracking number, recipient or phone">
        </label>
        <label style="min-width:180px;">
            <span class="form-label">Status</span>
            <select class="form-control" name="status">
                <option value="">All statuses</option>
                @foreach(['received', 'in_transit', 'arrived', 'delivered'] as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                @endforeach
            </select>
        </label>
        <button class="btn btn-secondary" type="submit"><i class="bi bi-search"></i> Search</button>
        @if(request()->hasAny(['q', 'status']))
            <a href="{{ route('tenant.parcels.index') }}" class="btn btn-ghost">Clear</a>
        @endif
    </form>

    <div class="table-container">
        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Tracking</th>
                        <th>Recipient</th>
                        <th>Route</th>
                        <th>Service</th>
                        <th>Status</th>
                        <th>Last updated</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($parcels as $parcel)
                        @php
                            $badge = match ($parcel->status) {
                                'received' => 'warning',
                                'in_transit' => 'info',
                                'arrived' => 'success',
                                'delivered' => 'success',
                                default => 'secondary',
                            };
                        @endphp
                        <tr>
                            <td><strong>{{ $parcel->tracking_number }}</strong><br><small class="muted">{{ $parcel->contents }}</small></td>
                            <td>{{ $parcel->recipient_name }}<br><small class="muted">{{ $parcel->recipient_phone }}</small></td>
                            <td>{{ $parcel->originOffice?->city }} → {{ $parcel->destinationOffice?->city }}</td>
                            <td>{{ ucfirst($parcel->service_level) }}<br><small class="muted">KES {{ number_format((float) $parcel->fee, 2) }}</small></td>
                            <td><span class="badge badge-{{ $badge }}">{{ ucfirst(str_replace('_', ' ', $parcel->status)) }}</span></td>
                            <td>{{ $parcel->updated_at->format('d M Y, H:i') }}</td>
                            <td><a href="{{ route('tenant.parcels.show', $parcel) }}" class="btn btn-secondary btn-sm">Open</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" style="text-align:center;padding:32px;" class="muted">No parcels found for this office.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div style="margin-top:16px;">{{ $parcels->links() }}</div>
</div>
@endsection
