<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Restore the singleton mutex row if it was removed after initial setup.
     */
    public function up(): void
    {
        if (Schema::hasTable('platform_audit_chain_locks')) {
            DB::table('platform_audit_chain_locks')->insertOrIgnore(['id' => 1]);
        }
    }

    /**
     * Keep the audit-chain mutex in place when rolling back this repair.
     */
    public function down(): void
    {
        // This row is required by the audit recorder and was created by the original table migration.
    }
};
