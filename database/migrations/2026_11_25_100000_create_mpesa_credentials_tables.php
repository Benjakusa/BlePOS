<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mpesa_credentials', function (Blueprint $table) {
            $table->id();
            $table->char('uuid', 36)->unique();
            $table->enum('owner_type', ['platform', 'tenant', 'branch'])->index();
            $table->unsignedInteger('owner_id')->nullable();
            $table->unsignedInteger('tenant_id')->nullable();
            $table->unsignedInteger('branch_id')->nullable();
            $table->enum('environment', ['sandbox', 'production'])->default('sandbox');
            $table->string('shortcode', 20);
            $table->text('passkey');
            $table->string('consumer_key', 500);
            $table->text('consumer_secret');
            $table->string('initiator_name', 500)->nullable();
            $table->text('security_credential')->nullable();
            $table->string('callback_url', 500);
            $table->string('callback_token', 100)->unique()->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->timestamps();

            // FK: tenant_id → tenants.id (cascade delete)
            $table->foreign('tenant_id')
                ->references('id')->on('tenants')
                ->cascadeOnDelete();

            // FK: branch_id → shops.id (null on delete)
            $table->foreign('branch_id')
                ->references('id')->on('shops')
                ->nullOnDelete();
        });

        // Partial unique index: when owner_id IS NOT NULL use composite unique;
        // platform rows have owner_id = NULL (MySQL allows multiple NULLs in unique index).
        // We rely on MySQL NULL uniqueness behaviour here — multiple NULL owner_ids are allowed
        // naturally so this single index covers both cases correctly.
        DB::statement(
            'CREATE UNIQUE INDEX mpesa_cred_owner_env_shortcode '.
            'ON mpesa_credentials (owner_type, owner_id, environment, shortcode)'
        );

        Schema::create('mpesa_credential_audits', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('mpesa_credential_id')->nullable();
            $table->enum('actor_type', ['platform_user', 'tenant_user']);
            $table->unsignedInteger('actor_id');
            $table->string('action', 50);
            $table->string('owner_type', 20);
            $table->unsignedInteger('owner_id')->nullable();
            $table->unsignedInteger('tenant_id')->nullable();
            $table->string('shortcode', 20);
            $table->string('environment', 20);
            $table->json('changes')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mpesa_credential_audits');
        Schema::dropIfExists('mpesa_credentials');
    }
};
