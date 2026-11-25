<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mpesa_transactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('shop_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('mpesa_credential_id')->constrained()->cascadeOnDelete();
            $table->foreignId('platform_payment_id')->nullable()->constrained('platform_payments')->nullOnDelete();
            $table->unsignedBigInteger('sale_id')->nullable()->index();
            $table->string('merchant_request_id', 120)->nullable();
            $table->string('checkout_request_id', 120)->nullable();
            $table->string('account_reference', 64);
            $table->string('phone_hash', 64);
            $table->decimal('amount', 12, 2);
            $table->string('status', 24)->default('pending')->index();
            $table->string('provider_receipt', 64)->nullable();
            $table->string('result_code', 24)->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
            $table->unique(['mpesa_credential_id', 'checkout_request_id']);
            $table->unique(['mpesa_credential_id', 'provider_receipt']);
        });

        Schema::create('unmatched_mpesa_callbacks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('mpesa_credential_id')->nullable()->constrained()->nullOnDelete();
            $table->string('callback_hash', 64)->unique();
            $table->string('reason', 100);
            $table->string('result_code', 24)->nullable();
            $table->timestamp('received_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unmatched_mpesa_callbacks');
        Schema::dropIfExists('mpesa_transactions');
    }
};
