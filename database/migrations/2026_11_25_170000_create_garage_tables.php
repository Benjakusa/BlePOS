<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('garage_customers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('shop_id');
            $table->string('name');
            $table->string('phone');
            $table->string('email')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('garage_vehicles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('shop_id');
            $table->unsignedBigInteger('customer_id');
            $table->string('plate');
            $table->string('vin')->nullable();
            $table->string('make');
            $table->string('model');
            $table->unsignedSmallInteger('year')->nullable();
            $table->string('color')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'plate']);
        });

        Schema::create('garage_job_cards', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('shop_id');
            $table->unsignedBigInteger('vehicle_id');
            $table->unsignedBigInteger('customer_id');
            $table->string('job_number');
            $table->enum('status', [
                'received', 'diagnosing', 'awaiting_approval', 'approved',
                'in_progress', 'awaiting_parts', 'completed', 'delivered', 'cancelled',
            ])->default('received');
            $table->unsignedBigInteger('estimate_id')->nullable();
            $table->unsignedInteger('mileage_in')->nullable();
            $table->unsignedInteger('mileage_out')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('garage_estimates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('shop_id');
            $table->unsignedBigInteger('job_id');
            $table->decimal('total_parts', 12, 2)->default(0);
            $table->decimal('total_labour', 12, 2)->default(0);
            $table->decimal('grand_total', 12, 2)->default(0);
            $table->boolean('is_approved')->default(false);
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('garage_job_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('shop_id');
            $table->unsignedBigInteger('job_id');
            $table->enum('type', ['part', 'labour']);
            $table->string('description');
            $table->unsignedBigInteger('part_id')->nullable();
            $table->decimal('quantity', 10, 3)->default(1);
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->decimal('hours', 6, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('garage_parts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('shop_id');
            $table->string('name');
            $table->string('sku')->nullable();
            $table->decimal('quantity_on_hand', 12, 3)->default(0);
            $table->decimal('reorder_level', 12, 3)->default(0);
            $table->decimal('cost_price', 12, 2)->default(0);
            $table->decimal('selling_price', 12, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('garage_part_consumption', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('shop_id');
            $table->unsignedBigInteger('job_id');
            $table->unsignedBigInteger('part_id');
            $table->decimal('quantity_used', 10, 3);
            $table->timestamp('consumed_at');
            $table->timestamps();
        });

        Schema::create('garage_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('shop_id');
            $table->unsignedBigInteger('job_id');
            $table->decimal('amount', 12, 2);
            $table->enum('method', ['cash', 'mpesa']);
            $table->string('reference')->nullable();
            $table->json('payment_meta')->nullable();
            $table->timestamp('paid_at');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('garage_mechanics', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('shop_id');
            $table->string('name');
            $table->string('phone');
            $table->decimal('commission_rate', 5, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('garage_mechanics');
        Schema::dropIfExists('garage_payments');
        Schema::dropIfExists('garage_part_consumption');
        Schema::dropIfExists('garage_parts');
        Schema::dropIfExists('garage_job_items');
        Schema::dropIfExists('garage_estimates');
        Schema::dropIfExists('garage_job_cards');
        Schema::dropIfExists('garage_vehicles');
        Schema::dropIfExists('garage_customers');
    }
};
