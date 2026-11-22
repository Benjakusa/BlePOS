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
        Schema::table('platform_approvals', function (Blueprint $table) {
            $table->foreignId('cancelled_by')->nullable()->after('approved_by')->constrained('platform_users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable()->after('approved_at');
            $table->text('cancellation_reason')->nullable()->after('reason');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('platform_approvals', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cancelled_by');
            $table->dropColumn(['cancelled_at', 'cancellation_reason']);
        });
    }
};
