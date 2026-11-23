<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('platform_invoices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained('subscriptions')->nullOnDelete();
            $table->string('invoice_number', 64)->unique();
            $table->string('status', 32)->default('issued')->index(); // draft, issued, paid, partially_paid, overdue, voided, cancelled
            $table->string('currency', 8)->default('KES');
            $table->decimal('subtotal', 14, 2)->default(0.00);
            $table->decimal('tax_rate', 5, 2)->default(16.00); // 16% Kenyan VAT
            $table->decimal('tax_amount', 14, 2)->default(0.00);
            $table->decimal('total_amount', 14, 2)->default(0.00);
            $table->decimal('amount_paid', 14, 2)->default(0.00);
            $table->decimal('amount_due', 14, 2)->default(0.00);
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->date('due_date')->index();
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->string('etims_status', 32)->default('blocked_on_certification'); // pending, fiscalised, blocked_on_certification
            $table->string('etims_reference', 64)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('platform_invoice_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('platform_invoice_id')->constrained('platform_invoices')->cascadeOnDelete();
            $table->string('description', 255);
            $table->decimal('quantity', 10, 2)->default(1.00);
            $table->decimal('unit_price', 14, 2)->default(0.00);
            $table->decimal('amount', 14, 2)->default(0.00);
            $table->timestamps();
        });

        Schema::create('platform_payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('platform_invoice_id')->constrained('platform_invoices')->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('payment_reference', 64)->unique();
            $table->string('payment_method', 32)->default('bank_transfer'); // mpesa, bank_transfer, card, manual
            $table->string('status', 32)->default('completed')->index(); // completed, failed, refunded, partially_refunded
            $table->string('currency', 8)->default('KES');
            $table->decimal('amount', 14, 2);
            $table->string('gateway_reference', 128)->nullable();
            $table->timestamp('paid_at');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('platform_refunds', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('platform_payment_id')->constrained('platform_payments')->cascadeOnDelete();
            $table->foreignId('platform_invoice_id')->constrained('platform_invoices')->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('platform_user_id')->nullable()->constrained('platform_users')->nullOnDelete();
            $table->string('refund_reference', 64)->unique();
            $table->decimal('amount', 14, 2);
            $table->text('reason');
            $table->string('status', 32)->default('completed')->index(); // completed, pending, failed
            $table->timestamp('processed_at');
            $table->timestamps();
        });

        Schema::create('platform_dunning_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('platform_invoice_id')->constrained('platform_invoices')->cascadeOnDelete();
            $table->string('stage', 64)->index(); // upcoming_due_7d, due_date, overdue_1d, overdue_3d, overdue_7d, grace_warning, suspension_warning, suspended
            $table->string('channel', 32)->default('email'); // email, sms, system
            $table->string('status', 32)->default('sent')->index(); // sent, failed, skipped
            $table->timestamp('triggered_at');
            $table->json('details')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('platform_dunning_events');
        Schema::dropIfExists('platform_refunds');
        Schema::dropIfExists('platform_payments');
        Schema::dropIfExists('platform_invoice_items');
        Schema::dropIfExists('platform_invoices');
    }
};
