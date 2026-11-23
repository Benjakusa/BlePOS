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
        Schema::create('platform_health_probes', function (Blueprint $table): void {
            $table->id();
            $table->string('subsystem', 64)->index(); // database, cache, storage, queues, scheduler
            $table->string('status', 32)->index(); // healthy, degraded, unhealthy
            $table->decimal('latency_ms', 8, 2)->default(0.00);
            $table->json('details')->nullable();
            $table->timestamp('probed_at')->index();
            $table->timestamps();
        });

        Schema::create('platform_backup_runs', function (Blueprint $table): void {
            $table->id();
            $table->string('backup_type', 64)->index(); // database, tenant_database, files, full_system
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->cascadeOnDelete();
            $table->string('status', 32)->default('completed')->index(); // completed, in_progress, failed, verified
            $table->string('filename', 255);
            $table->string('destination', 64)->default('local_encrypted');
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->string('checksum', 64)->nullable(); // SHA-256
            $table->boolean('is_encrypted')->default(true);
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });

        Schema::create('platform_restore_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('platform_backup_run_id')->constrained('platform_backup_runs')->cascadeOnDelete();
            $table->foreignId('requested_by')->constrained('platform_users')->cascadeOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('platform_users')->nullOnDelete();
            $table->string('status', 32)->default('requested')->index(); // requested, test_restored, approved, production_completed, rejected
            $table->string('target_environment', 64)->default('isolated_sandbox');
            $table->text('reason');
            $table->text('verification_notes')->nullable();
            $table->timestamp('executed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('platform_restore_requests');
        Schema::dropIfExists('platform_backup_runs');
        Schema::dropIfExists('platform_health_probes');
    }
};
