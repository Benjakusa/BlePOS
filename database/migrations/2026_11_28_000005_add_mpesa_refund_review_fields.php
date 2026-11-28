<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mpesa_transactions', function (Blueprint $table): void {
            $table->string('refund_reference', 120)->nullable();
            $table->foreignId('refund_recorded_by')->nullable()->constrained('platform_users')->nullOnDelete();
            $table->timestamp('refund_recorded_at')->nullable();
            $table->string('refund_notes', 500)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('mpesa_transactions', function (Blueprint $table): void {
            $table->dropForeign(['refund_recorded_by']);
            $table->dropColumn(['refund_reference', 'refund_recorded_by', 'refund_recorded_at', 'refund_notes']);
        });
    }
};
