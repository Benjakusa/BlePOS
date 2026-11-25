<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('print_jobs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('shop_id');
            $table->string('customer_name');
            $table->string('customer_phone');
            $table->string('title');
            $table->enum('status', ['quote', 'approved', 'in_design', 'in_production', 'ready', 'delivered', 'cancelled'])->default('quote');
            $table->decimal('deposit_amount', 12, 2)->default(0);
            $table->timestamp('deposit_paid_at')->nullable();
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('balance_due', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('print_job_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('job_id');
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('shop_id');
            $table->string('description');
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('total', 12, 2);
            $table->boolean('is_labour')->default(false);
            $table->timestamps();

            $table->foreign('job_id')->references('id')->on('print_jobs')->cascadeOnDelete();
        });

        Schema::create('print_quotes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('job_id');
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('shop_id');
            $table->unsignedInteger('version')->default(1);
            $table->decimal('subtotal', 12, 2);
            $table->decimal('total', 12, 2);
            $table->boolean('is_accepted')->default(false);
            $table->timestamp('accepted_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('job_id')->references('id')->on('print_jobs')->cascadeOnDelete();
        });

        Schema::create('print_materials', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('shop_id');
            $table->string('name');
            $table->string('unit');
            $table->decimal('quantity_on_hand', 12, 3)->default(0);
            $table->decimal('reorder_level', 12, 3)->default(0);
            $table->decimal('cost_per_unit', 12, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('print_material_consumption', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('job_id');
            $table->unsignedBigInteger('material_id');
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('shop_id');
            $table->decimal('quantity_used', 12, 3);
            $table->timestamp('consumed_at');
            $table->timestamps();

            $table->foreign('job_id')->references('id')->on('print_jobs')->cascadeOnDelete();
            $table->foreign('material_id')->references('id')->on('print_materials')->cascadeOnDelete();
        });

        Schema::create('print_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('job_id');
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('shop_id');
            $table->enum('type', ['deposit', 'balance']);
            $table->decimal('amount', 12, 2);
            $table->enum('method', ['cash', 'mpesa']);
            $table->string('reference')->nullable();
            $table->json('payment_meta')->nullable();
            $table->timestamp('paid_at');
            $table->timestamps();

            $table->foreign('job_id')->references('id')->on('print_jobs')->cascadeOnDelete();
        });

        Schema::create('print_revisions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('job_id');
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('shop_id');
            $table->unsignedInteger('revision_number')->default(1);
            $table->text('notes');
            $table->timestamp('requested_at');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->foreign('job_id')->references('id')->on('print_jobs')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('print_revisions');
        Schema::dropIfExists('print_payments');
        Schema::dropIfExists('print_material_consumption');
        Schema::dropIfExists('print_materials');
        Schema::dropIfExists('print_quotes');
        Schema::dropIfExists('print_job_items');
        Schema::dropIfExists('print_jobs');
    }
};
