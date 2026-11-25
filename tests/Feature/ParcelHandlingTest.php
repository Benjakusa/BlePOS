<?php

namespace Tests\Feature;

use App\Models\Parcel;
use App\Models\Shop;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\ParcelDeliveryDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParcelHandlingTest extends TestCase
{
    use RefreshDatabase;

    public function test_parcel_is_received_dispatched_received_at_destination_and_delivered_by_the_correct_roles(): void
    {
        $this->seed(ParcelDeliveryDemoSeeder::class);

        $tenant = Tenant::withoutGlobalScopes()->where('slug', 'parcelway-courier')->firstOrFail();
        $offices = Shop::withoutGlobalScopes()->where('tenant_id', $tenant->id)->get()->keyBy('code');
        $clerk = User::withoutGlobalScopes()->where('email', 'counter.nairobi@parcelway.test')->firstOrFail();
        $destinationClerk = User::withoutGlobalScopes()->where('email', 'counter.mombasa@parcelway.test')->firstOrFail();
        $dispatcher = User::withoutGlobalScopes()->where('email', 'dispatcher@parcelway.test')->firstOrFail();
        $courier = User::withoutGlobalScopes()->where('email', 'courier@parcelway.test')->firstOrFail();
        $dispatcher->forceFill(['mfa_confirmed_at' => now()])->save();

        $this->actingAs($clerk)->withSession(['active_shop_id' => $offices['PW-NBO']->id])
            ->get(route('tenant.parcels.create'))
            ->assertOk()
            ->assertSee('Receive a parcel')
            ->assertSee('Mombasa Destination Office');

        $this->actingAs($clerk)->withSession(['active_shop_id' => $offices['PW-NBO']->id])
            ->post(route('tenant.parcels.store'), [
                'destination_shop_id' => $offices['PW-MBA']->id,
                'sender_name' => 'Anne Sender',
                'sender_phone' => '+254700123456',
                'recipient_name' => 'James Recipient',
                'recipient_phone' => '+254711123456',
                'contents' => 'Signed documents',
                'service_level' => 'express',
                'weight_kg' => '0.50',
                'fee' => '650.00',
                'note' => 'Keep dry',
            ])
            ->assertRedirect();

        $parcel = Parcel::withoutGlobalScopes()->where('tenant_id', $tenant->id)->where('recipient_phone', '+254711123456')->firstOrFail();
        $this->assertSame('received', $parcel->status);
        $this->assertSame($clerk->id, $parcel->received_by);
        $this->assertDatabaseHas('parcel_events', ['parcel_id' => $parcel->id, 'event_type' => 'received']);

        $this->actingAs($clerk)->withSession(['active_shop_id' => $offices['PW-NBO']->id])
            ->get(route('tenant.parcels.show', $parcel))
            ->assertOk()
            ->assertDontSee('Dispatch to destination');

        $this->actingAs($clerk)->withSession(['active_shop_id' => $offices['PW-NBO']->id])
            ->post(route('tenant.parcels.dispatch', $parcel))
            ->assertForbidden();

        $this->actingAs($dispatcher)->withSession([
            'active_shop_id' => $offices['PW-NBO']->id,
            'tenant_mfa_verified_at' => now()->timestamp,
        ])
            ->post(route('tenant.parcels.dispatch', $parcel), ['note' => 'Loaded on the Mombasa run'])
            ->assertRedirect();

        $parcel->refresh();
        $this->assertSame('in_transit', $parcel->status);
        $this->assertSame($dispatcher->id, $parcel->dispatched_by);

        $this->actingAs($destinationClerk)->withSession(['active_shop_id' => $offices['PW-MBA']->id])
            ->post(route('tenant.parcels.receive-destination', $parcel))
            ->assertRedirect();

        $parcel->refresh();
        $this->assertSame('arrived', $parcel->status);
        $this->assertSame($offices['PW-MBA']->id, $parcel->current_shop_id);
        $this->assertSame($destinationClerk->id, $parcel->branch_received_by);

        $this->actingAs($courier)->withSession(['active_shop_id' => $offices['PW-MBA']->id])
            ->post(route('tenant.parcels.deliver', $parcel), ['note' => 'Recipient signed'])
            ->assertRedirect();

        $parcel->refresh();
        $this->assertSame('delivered', $parcel->status);
        $this->assertSame($courier->id, $parcel->delivered_by);
        $this->assertDatabaseCount('parcel_events', 4);
    }
}
