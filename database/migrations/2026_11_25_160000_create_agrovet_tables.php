<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agrovet_products', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('shop_id');
            $table->string('name');
            $table->string('category')->nullable();
            $table->string('unit_base');
            $table->string('unit_alt')->nullable();
            $table->decimal('conversion_factor', 10, 4)->default(1);
            $table->string('grade')->nullable();
            $table->decimal('reorder_level', 12, 3)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('agrovet_suppliers', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('shop_id');
            $table->string('name');
            $table->string('phone');
            $table->string('address')->nullable();
            $table->unsignedInteger('credit_terms')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('agrovet_customers', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('shop_id');
            $table->string('name');
            $table->string('phone');
            $table->string('village')->nullable();
            $table->decimal('credit_limit', 12, 2)->default(0);
            $table->decimal('credit_balance', 12, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('agrovet_stock_movements', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('shop_id');
            $table->unsignedBigInteger('product_id');
            $table->foreign('product_id')->references('id')->on('agrovet_products');
            $table->string('batch_number')->nullable();
            $table->date('expiry_date')->nullable();
            $table->enum('movement_type', ['purchase', 'sale', 'adjustment']);
            $table->decimal('quantity', 12, 3);
            $table->string('unit');
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('reference_type')->nullable();
            $table->timestamp('moved_at');
            $table->timestamps();
        });

        Schema::create('agrovet_sales', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('shop_id');
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->foreign('customer_id')->references('id')->on('agrovet_customers');
            $table->string('sale_number');
            $table->decimal('total_amount', 12, 2);
            $table->enum('payment_type', ['cash', 'credit', 'mpesa']);
            $table->string('reference')->nullable();
            $table->json('payment_meta')->nullable();
            $table->timestamp('sold_at');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('agrovet_credit_ledger', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('shop_id');
            $table->unsignedBigInteger('customer_id');
            $table->foreign('customer_id')->references('id')->on('agrovet_customers');
            $table->enum('type', ['sale', 'repayment']);
            $table->decimal('amount', 12, 2);
            $table->string('reference')->nullable();
            $table->enum('method', ['cash', 'mpesa'])->nullable();
            $table->json('payment_meta')->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();
        });

        Schema::create('agrovet_purchases', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('shop_id');
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->foreign('supplier_id')->references('id')->on('agrovet_suppliers');
            $table->unsignedBigInteger('product_id');
            $table->foreign('product_id')->references('id')->on('agrovet_products');
            $table->string('batch_number')->nullable();
            $table->date('expiry_date')->nullable();
            $table->decimal('quantity', 12, 3);
            $table->decimal('unit_cost', 12, 2);
            $table->decimal('total_cost', 12, 2);
            $table->enum('payment_status', ['paid', 'partial', 'unpaid'])->default('unpaid');
            $table->timestamp('purchased_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agrovet_purchases');
        Schema::dropIfExists('agrovet_credit_ledger');
        Schema::dropIfExists('agrovet_sales');
        Schema::dropIfExists('agrovet_stock_movements');
        Schema::dropIfExists('agrovet_customers');
        Schema::dropIfExists('agrovet_suppliers');
        Schema::dropIfExists('agrovet_products');
    }
};
