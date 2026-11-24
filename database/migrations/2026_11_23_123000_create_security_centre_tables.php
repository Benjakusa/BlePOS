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
        Schema::create('platform_security_incidents', function (Blueprint $table) {
            $table->id();
            $table->string('incident_number')->unique();
            $table->string('title');
            $table->string('category')->index();
            $table->string('severity')->index();
            $table->string('status')->default('new')->index();
            $table->foreignId('platform_user_id')->nullable()->constrained('platform_users')->nullOnDelete();
            $table->foreignId('reporter_id')->nullable()->constrained('platform_users')->nullOnDelete();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
            $table->text('description');
            $table->json('evidence_metadata')->nullable();
            $table->text('containment_steps')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('platform_security_incident_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incident_id')->constrained('platform_security_incidents')->cascadeOnDelete();
            $table->foreignId('platform_user_id')->constrained('platform_users')->cascadeOnDelete();
            $table->text('note');
            $table->boolean('is_containment_action')->default(false);
            $table->timestamps();
        });

        Schema::create('platform_ip_rules', function (Blueprint $table) {
            $table->id();
            $table->string('ip_or_cidr')->index();
            $table->string('rule_type')->index(); // allowlist, denylist
            $table->string('scope')->default('global'); // platform_console, tenant_api, global
            $table->string('reason');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('expires_at')->nullable()->index();
            $table->foreignId('created_by_user_id')->nullable()->constrained('platform_users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('platform_security_keys', function (Blueprint $table) {
            $table->id();
            $table->string('key_id')->unique();
            $table->string('name');
            $table->string('key_type')->index(); // platform_master_key, tenant_data_key, envelope_encryption_key, webhook_signing_key
            $table->string('algorithm')->default('AES-256-GCM');
            $table->unsignedInteger('version')->default(1);
            $table->string('status')->default('active')->index(); // active, rotating, deprecated, retired
            $table->string('fingerprint');
            $table->string('envelope_wrapped_by_key_id')->nullable();
            $table->unsignedInteger('rotation_schedule_days')->default(90);
            $table->timestamp('last_rotated_at')->nullable();
            $table->timestamp('next_rotation_due_at')->nullable();
            $table->unsignedTinyInteger('reencryption_progress_percent')->default(100);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('platform_security_keys');
        Schema::dropIfExists('platform_ip_rules');
        Schema::dropIfExists('platform_security_incident_notes');
        Schema::dropIfExists('platform_security_incidents');
    }
};
