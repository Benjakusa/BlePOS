<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('currency', 3)->default('KES');
            $table->decimal('monthly_price', 15, 2)->default(0);
            $table->decimal('annual_price', 15, 2)->default(0);
            $table->unsignedInteger('trial_days')->default(14);
            $table->unsignedInteger('max_shops')->default(1);
            $table->unsignedInteger('max_users')->default(5);
            $table->unsignedInteger('max_registers')->default(2);
            $table->unsignedInteger('max_warehouses')->default(1);
            $table->boolean('is_active')->default(true);
            $table->json('features')->nullable();
            $table->timestamps();
        });

        Schema::create('subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained('plans');
            $table->string('status', 32)->default('trial')->index();
            $table->string('billing_cycle', 20)->default('monthly');
            $table->timestamp('starts_at');
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('renews_at')->nullable();
            $table->timestamp('grace_ends_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
        });

        Schema::create('industry_templates', function (Blueprint $table): void {
            $table->id();
            $table->string('slug', 64)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('default_modules');
            $table->json('tax_rates');
            $table->json('default_roles');
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        Schema::create('provisioning_runs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('run_identifier', 64)->unique();
            $table->string('status', 32)->default('pending')->index();
            $table->unsignedSmallInteger('current_step')->default(1);
            $table->unsignedSmallInteger('total_steps')->default(30);
            $table->unsignedSmallInteger('attempt_count')->default(1);
            $table->string('error_category', 64)->nullable();
            $table->text('error_message')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('provisioning_steps', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('provisioning_run_id')->constrained('provisioning_runs')->cascadeOnDelete();
            $table->unsignedSmallInteger('step_number');
            $table->string('name', 120);
            $table->string('status', 32)->default('pending')->index();
            $table->unsignedSmallInteger('attempt_count')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->unique(['provisioning_run_id', 'step_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provisioning_steps');
        Schema::dropIfExists('provisioning_runs');
        Schema::dropIfExists('industry_templates');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('plans');
    }
};
