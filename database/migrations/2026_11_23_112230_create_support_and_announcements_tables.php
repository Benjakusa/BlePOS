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
        Schema::create('support_cases', function (Blueprint $table): void {
            $table->id();
            $table->string('case_number', 64)->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('shop_id')->nullable()->constrained('shops')->nullOnDelete();
            $table->foreignId('assigned_agent_id')->nullable()->constrained('platform_users')->nullOnDelete();
            $table->string('requester_name', 128);
            $table->string('requester_email', 128);
            $table->string('subject', 255);
            $table->text('description');
            $table->string('category', 64)->default('general')->index(); // billing, hardware_pos, inventory, user_access, etims_tax, system_bug, general
            $table->string('priority', 32)->default('medium')->index(); // low, medium, high, urgent
            $table->string('status', 32)->default('open')->index(); // open, in_progress, waiting_on_tenant, waiting_on_platform, resolved, closed
            $table->text('resolution_notes')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('sla_due_at')->nullable();
            $table->timestamps();
        });

        Schema::create('support_case_notes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('support_case_id')->constrained('support_cases')->cascadeOnDelete();
            $table->foreignId('platform_user_id')->nullable()->constrained('platform_users')->nullOnDelete();
            $table->string('author_name', 128);
            $table->text('content');
            $table->boolean('is_internal')->default(true); // true = platform internal only, false = tenant visible
            $table->timestamps();
        });

        Schema::create('platform_announcements', function (Blueprint $table): void {
            $table->id();
            $table->string('title', 255);
            $table->text('content');
            $table->string('priority', 32)->default('info')->index(); // info, warning, critical
            $table->string('target_type', 32)->default('all')->index(); // all, plan, industry, tenant
            $table->string('target_value', 64)->nullable();
            $table->string('status', 32)->default('draft')->index(); // draft, published, archived
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('platform_users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('platform_announcement_reads', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('platform_announcement_id')->constrained('platform_announcements')->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('read_at');

            $table->unique(['platform_announcement_id', 'tenant_id', 'user_id'], 'announcement_tenant_user_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('platform_announcement_reads');
        Schema::dropIfExists('platform_announcements');
        Schema::dropIfExists('support_case_notes');
        Schema::dropIfExists('support_cases');
    }
};
