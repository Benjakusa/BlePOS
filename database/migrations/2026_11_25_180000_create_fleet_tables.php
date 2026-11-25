<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fleet_vehicles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('shop_id');
            $table->string('plate');
            $table->string('make');
            $table->string('model');
            $table->unsignedSmallInteger('year')->nullable();
            $table->string('color')->nullable();
            $table->enum('type', ['car', 'motorbike', 'van', 'truck'])->default('car');
            $table->decimal('daily_rate', 10, 2)->default(0);
            $table->decimal('weekly_rate', 10, 2)->default(0);
            $table->decimal('deposit_amount', 10, 2)->default(0);
            $table->date('insurance_expiry')->nullable();
            $table->date('inspection_expiry')->nullable();
            $table->date('license_expiry')->nullable();
            $table->enum('status', ['available', 'booked', 'maintenance', 'inactive'])->default('available');
            $table->unsignedInteger('mileage')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'plate']);
        });

        Schema::create('fleet_customers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('shop_id');
            $table->string('name');
            $table->string('phone');
            $table->string('id_number')->nullable();
            $table->string('license_number')->nullable();
            $table->date('license_expiry')->nullable();
            $table->string('email')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('fleet_drivers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('shop_id');
            $table->string('name');
            $table->string('phone');
            $table->string('license_number')->nullable();
            $table->date('license_expiry')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('fleet_bookings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('shop_id');
            $table->unsignedBigInteger('vehicle_id');
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('driver_id')->nullable();
            $table->timestamp('pickup_at');
            $table->timestamp('return_at');
            $table->timestamp('actual_return_at')->nullable();
            $table->enum('status', ['pending', 'confirmed', 'active', 'completed', 'cancelled'])->default('pending');
            $table->enum('rate_type', ['daily', 'weekly', 'custom'])->default('daily');
            $table->decimal('agreed_rate', 10, 2);
            $table->decimal('deposit_amount', 10, 2)->default(0);
            $table->boolean('deposit_paid')->default(false);
            $table->boolean('deposit_refunded')->default(false);
            $table->decimal('total_billed', 10, 2)->default(0);
            $table->decimal('late_surcharge', 10, 2)->default(0);
            $table->decimal('damage_charge', 10, 2)->default(0);
            $table->decimal('fuel_charge', 10, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('fleet_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('booking_id');
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('shop_id');
            $table->enum('type', ['deposit', 'rental', 'balance']);
            $table->decimal('amount', 10, 2);
            $table->enum('method', ['cash', 'mpesa']);
            $table->string('reference')->nullable();
            $table->json('payment_meta')->nullable();
            $table->timestamp('paid_at');
            $table->timestamps();
        });

        Schema::create('fleet_inspections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('booking_id');
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('shop_id');
            $table->enum('type', ['pre', 'post']);
            $table->string('fuel_level')->nullable();
            $table->unsignedInteger('odometer')->nullable();
            $table->text('damage_notes')->nullable();
            $table->json('photo_paths')->nullable();
            $table->timestamp('inspected_at');
            $table->unsignedBigInteger('inspected_by')->nullable();
            $table->timestamps();
        });

        Schema::create('fleet_maintenance', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vehicle_id');
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('shop_id');
            $table->string('description');
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->decimal('cost', 10, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fleet_maintenance');
        Schema::dropIfExists('fleet_inspections');
        Schema::dropIfExists('fleet_payments');
        Schema::dropIfExists('fleet_bookings');
        Schema::dropIfExists('fleet_drivers');
        Schema::dropIfExists('fleet_customers');
        Schema::dropIfExists('fleet_vehicles');
    }
};
