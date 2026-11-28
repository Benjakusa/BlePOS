<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('platform_invoices', function (Blueprint $table): void {
            $table->string('subscription_plan_name', 120)->nullable()->after('subscription_plan_id');
        });
        DB::table('platform_invoices')->whereNotNull('subscription_plan_id')->orderBy('id')->each(function (object $invoice): void {
            $planName = DB::table('plans')->where('id', $invoice->subscription_plan_id)->value('name');
            if ($planName !== null) {
                DB::table('platform_invoices')->where('id', $invoice->id)->update(['subscription_plan_name' => $planName]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('platform_invoices', function (Blueprint $table): void {
            $table->dropColumn('subscription_plan_name');
        });
    }
};
