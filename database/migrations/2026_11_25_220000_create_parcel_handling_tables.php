<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parcels', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('tracking_number', 40);
            $table->foreignId('origin_shop_id')->constrained('shops')->restrictOnDelete();
            $table->foreignId('current_shop_id')->constrained('shops')->restrictOnDelete();
            $table->foreignId('destination_shop_id')->constrained('shops')->restrictOnDelete();
            $table->string('sender_name', 120);
            $table->string('sender_phone', 32);
            $table->string('recipient_name', 120);
            $table->string('recipient_phone', 32);
            $table->string('contents', 255);
            $table->decimal('weight_kg', 8, 2)->nullable();
            $table->string('service_level', 24)->default('standard');
            $table->decimal('fee', 12, 2)->default(0);
            $table->string('status', 24)->default('received')->index();
            $table->foreignId('received_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('received_at');
            $table->foreignId('dispatched_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('dispatched_at')->nullable();
            $table->foreignId('branch_received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('branch_received_at')->nullable();
            $table->foreignId('delivered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'tracking_number']);
            $table->index(['tenant_id', 'current_shop_id', 'status']);
            $table->index(['tenant_id', 'destination_shop_id', 'status']);
        });

        Schema::create('parcel_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('parcel_id')->constrained('parcels')->cascadeOnDelete();
            $table->foreignId('shop_id')->nullable()->constrained('shops')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event_type', 32);
            $table->string('from_status', 24)->nullable();
            $table->string('to_status', 24);
            $table->string('note', 500)->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['tenant_id', 'parcel_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parcel_events');
        Schema::dropIfExists('parcels');
    }
};
