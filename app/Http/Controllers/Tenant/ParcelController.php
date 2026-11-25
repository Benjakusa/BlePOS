<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Parcel;
use App\Models\ParcelEvent;
use App\Models\Shop;
use App\Services\ShopContext;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ParcelController extends Controller
{
    public function index(Request $request): View
    {
        $tenant = TenantContext::getTenant();
        $shopId = ShopContext::getShopId();
        abort_if($tenant === null || $shopId === null, 403, 'Select a destination office first.');

        $officeParcels = Parcel::query()
            ->where(function ($query) use ($shopId): void {
                $query->where('current_shop_id', $shopId)
                    ->orWhere(function ($incoming) use ($shopId): void {
                        $incoming->where('status', 'in_transit')
                            ->where('destination_shop_id', $shopId);
                    });
            });

        $counts = [
            'received' => (clone $officeParcels)->where('current_shop_id', $shopId)->where('status', 'received')->count(),
            'incoming' => (clone $officeParcels)->where('status', 'in_transit')->where('destination_shop_id', $shopId)->count(),
            'arrived' => (clone $officeParcels)->where('current_shop_id', $shopId)->where('status', 'arrived')->count(),
            'delivered' => (clone $officeParcels)->where('current_shop_id', $shopId)->where('status', 'delivered')->count(),
        ];

        $parcels = (clone $officeParcels)
            ->with(['originOffice', 'destinationOffice'])
            ->when($request->filled('q'), function ($query) use ($request): void {
                $term = $request->string('q')->trim()->value();
                $query->where(function ($search) use ($term): void {
                    $search->where('tracking_number', 'like', "%{$term}%")
                        ->orWhere('recipient_name', 'like', "%{$term}%")
                        ->orWhere('recipient_phone', 'like', "%{$term}%");
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->value()))
            ->orderByDesc('updated_at')
            ->paginate(20)
            ->withQueryString();

        return view('tenant.parcels.index', [
            'parcels' => $parcels,
            'counts' => $counts,
            'office' => Shop::query()->findOrFail($shopId),
            'canReceive' => $request->user()->hasPermission('parcel.receive'),
        ]);
    }

    public function create(): View
    {
        abort_unless(request()->user()?->hasPermission('parcel.receive'), 403);
        $tenant = TenantContext::getTenant();
        $shopId = ShopContext::getShopId();
        abort_if($tenant === null || $shopId === null, 403);

        $offices = Shop::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->whereKeyNot($shopId)
            ->orderBy('city')
            ->orderBy('name')
            ->get();

        return view('tenant.parcels.create', [
            'office' => Shop::withoutGlobalScopes()->where('tenant_id', $tenant->id)->findOrFail($shopId),
            'offices' => $offices,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->hasPermission('parcel.receive'), 403);
        $tenant = TenantContext::getTenant();
        $shopId = ShopContext::getShopId();
        abort_if($tenant === null || $shopId === null, 403);

        $data = $request->validate([
            'destination_shop_id' => ['required', 'integer'],
            'sender_name' => ['required', 'string', 'max:120'],
            'sender_phone' => ['required', 'string', 'max:32'],
            'recipient_name' => ['required', 'string', 'max:120'],
            'recipient_phone' => ['required', 'string', 'max:32'],
            'contents' => ['required', 'string', 'max:255'],
            'weight_kg' => ['nullable', 'numeric', 'gt:0', 'max:1000'],
            'service_level' => ['required', 'in:standard,express'],
            'fee' => ['required', 'numeric', 'min:0', 'max:1000000'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $destination = Shop::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->findOrFail($data['destination_shop_id']);
        abort_if((int) $destination->id === (int) $shopId, 422, 'Choose a destination office different from the receiving office.');

        $parcel = DB::transaction(function () use ($data, $destination, $request, $tenant, $shopId): Parcel {
            $trackingNumber = $this->newTrackingNumber($tenant->id);
            $parcel = Parcel::query()->create([
                'tenant_id' => $tenant->id,
                'tracking_number' => $trackingNumber,
                'origin_shop_id' => $shopId,
                'current_shop_id' => $shopId,
                'destination_shop_id' => $destination->id,
                'sender_name' => $data['sender_name'],
                'sender_phone' => $data['sender_phone'],
                'recipient_name' => $data['recipient_name'],
                'recipient_phone' => $data['recipient_phone'],
                'contents' => $data['contents'],
                'weight_kg' => $data['weight_kg'] ?? null,
                'service_level' => $data['service_level'],
                'fee' => $data['fee'],
                'status' => 'received',
                'received_by' => $request->user()->id,
                'received_at' => now(),
            ]);

            $this->recordEvent($parcel, $request, 'received', null, 'received', $data['note'] ?? 'Parcel accepted at counter.', $shopId);

            return $parcel;
        });

        return redirect()->route('tenant.parcels.show', $parcel)->with('success', "Parcel {$parcel->tracking_number} received.");
    }

    public function show(Parcel $parcel): View
    {
        $this->authorizeParcel($parcel, request());

        return view('tenant.parcels.show', [
            'parcel' => $parcel->load([
                'originOffice',
                'currentOffice',
                'destinationOffice',
                'receivedBy',
                'dispatchedBy',
                'branchReceivedBy',
                'deliveredBy',
                'events.office',
                'events.actor',
            ]),
            'currentShopId' => ShopContext::getShopId(),
            'canDispatch' => request()->user()->hasPermission('parcel.dispatch'),
            'canReceive' => request()->user()->hasPermission('parcel.receive'),
            'canDeliver' => request()->user()->hasPermission('parcel.deliver'),
        ]);
    }

    public function dispatch(Request $request, Parcel $parcel): RedirectResponse
    {
        abort_unless($request->user()?->hasPermission('parcel.dispatch'), 403);
        $this->authorizeParcel($parcel, $request);
        $request->validate(['note' => ['nullable', 'string', 'max:500']]);
        $shopId = ShopContext::getShopId();

        DB::transaction(function () use ($parcel, $request, $shopId): void {
            $locked = Parcel::query()->lockForUpdate()->findOrFail($parcel->id);
            abort_unless($locked->status === 'received' && (int) $locked->current_shop_id === (int) $shopId, 422, 'Only a parcel received at this office can be dispatched.');
            $locked->update([
                'status' => 'in_transit',
                'dispatched_by' => $request->user()->id,
                'dispatched_at' => now(),
            ]);
            $this->recordEvent($locked, $request, 'dispatched', 'received', 'in_transit', $request->input('note'), $shopId);
        });

        return back()->with('success', 'Parcel dispatched to '.$parcel->destinationOffice()->value('name').'.');
    }

    public function receiveAtDestination(Request $request, Parcel $parcel): RedirectResponse
    {
        abort_unless($request->user()?->hasPermission('parcel.receive'), 403);
        $this->authorizeParcel($parcel, $request);
        $request->validate(['note' => ['nullable', 'string', 'max:500']]);
        $shopId = ShopContext::getShopId();

        DB::transaction(function () use ($parcel, $request, $shopId): void {
            $locked = Parcel::query()->lockForUpdate()->findOrFail($parcel->id);
            abort_unless($locked->status === 'in_transit' && (int) $locked->destination_shop_id === (int) $shopId, 422, 'This parcel is not currently expected at this office.');
            $locked->update([
                'status' => 'arrived',
                'current_shop_id' => $shopId,
                'branch_received_by' => $request->user()->id,
                'branch_received_at' => now(),
            ]);
            $this->recordEvent($locked, $request, 'office_received', 'in_transit', 'arrived', $request->input('note') ?: 'Parcel received at destination office.', $shopId);
        });

        return back()->with('success', 'Parcel received at this destination office.');
    }

    public function deliver(Request $request, Parcel $parcel): RedirectResponse
    {
        abort_unless($request->user()?->hasPermission('parcel.deliver'), 403);
        $this->authorizeParcel($parcel, $request);
        $data = $request->validate(['note' => ['nullable', 'string', 'max:500']]);
        $shopId = ShopContext::getShopId();

        DB::transaction(function () use ($parcel, $request, $shopId, $data): void {
            $locked = Parcel::query()->lockForUpdate()->findOrFail($parcel->id);
            abort_unless($locked->status === 'arrived' && (int) $locked->current_shop_id === (int) $shopId, 422, 'Only a parcel received at this office can be delivered.');
            $locked->update([
                'status' => 'delivered',
                'delivered_by' => $request->user()->id,
                'delivered_at' => now(),
            ]);
            $this->recordEvent($locked, $request, 'delivered', 'arrived', 'delivered', $data['note'] ?? 'Parcel delivered to recipient.', $shopId);
        });

        return back()->with('success', 'Parcel marked as delivered.');
    }

    private function authorizeParcel(Parcel $parcel, Request $request): void
    {
        abort_unless($request->user()?->hasPermission('parcel.view'), 403);
        $tenant = TenantContext::getTenant();
        abort_if($tenant === null || (int) $parcel->tenant_id !== (int) $tenant->id, 404);

        $user = $request->user();
        if ($user->hasPermission('shops.all.manage') || $user->isTenantOwner()) {
            return;
        }

        $shopIds = $user->shops()
            ->withoutGlobalScopes()
            ->where('shops.tenant_id', $tenant->id)
            ->pluck('shops.id');
        abort_unless($shopIds->contains($parcel->origin_shop_id)
            || $shopIds->contains($parcel->current_shop_id)
            || $shopIds->contains($parcel->destination_shop_id), 403);
    }

    private function recordEvent(
        Parcel $parcel,
        Request $request,
        string $type,
        ?string $from,
        string $to,
        ?string $note,
        ?int $shopId,
    ): void {
        ParcelEvent::query()->create([
            'tenant_id' => $parcel->tenant_id,
            'parcel_id' => $parcel->id,
            'shop_id' => $shopId,
            'user_id' => $request->user()?->id,
            'event_type' => $type,
            'from_status' => $from,
            'to_status' => $to,
            'note' => $note,
            'occurred_at' => now(),
        ]);
    }

    private function newTrackingNumber(int $tenantId): string
    {
        do {
            $trackingNumber = 'PW-'.now()->format('ymd').'-'.Str::upper(Str::random(6));
        } while (Parcel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('tracking_number', $trackingNumber)
            ->exists());

        return $trackingNumber;
    }
}
