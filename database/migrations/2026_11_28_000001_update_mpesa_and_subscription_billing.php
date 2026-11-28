<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table): void {
            $table->foreignId('pending_plan_id')->nullable()->after('plan_id')->constrained('plans')->nullOnDelete();
            $table->string('pending_billing_cycle', 20)->nullable()->after('billing_cycle');
        });

        Schema::table('platform_invoices', function (Blueprint $table): void {
            $table->foreignId('subscription_plan_id')->nullable()->after('subscription_id')->constrained('plans')->nullOnDelete();
            $table->string('subscription_billing_cycle', 20)->nullable()->after('subscription_plan_id');
        });

    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table): void {
            $table->dropForeign(['pending_plan_id']);
            $table->dropColumn(['pending_plan_id', 'pending_billing_cycle']);
        });

        Schema::table('platform_invoices', function (Blueprint $table): void {
            $table->dropForeign(['subscription_plan_id']);
            $table->dropColumn(['subscription_plan_id', 'subscription_billing_cycle']);
        });
    }
};
