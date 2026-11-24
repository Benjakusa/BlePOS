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
        Schema::create('platform_etims_certifications', function (Blueprint $table) {
            $table->id();
            $table->string('component_name');
            $table->string('certification_stage')->index(); // sandbox_testing, technical_evaluation, security_audit, kra_signoff
            $table->string('status')->default('pending')->index(); // pending, passed, failed, blocked
            $table->text('details')->nullable();
            $table->string('kra_reference')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by_user_id')->nullable()->constrained('platform_users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('platform_data_protection_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_number')->unique();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
            $table->string('subject_name');
            $table->string('subject_email')->index();
            $table->string('subject_phone')->nullable();
            $table->string('request_type')->index(); // access_export, rectification, erasure_deletion, objection, consent_withdrawal
            $table->string('status')->default('pending')->index(); // pending, under_review, processing, completed, rejected
            $table->string('legal_basis')->default('Kenya DPA 2019 Sec 26');
            $table->timestamp('due_date');
            $table->text('rejection_reason')->nullable();
            $table->text('resolution_summary')->nullable();
            $table->foreignId('assigned_officer_id')->nullable()->constrained('platform_users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('platform_legal_documents', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->index(); // terms-of-service, privacy-policy, data-processing-agreement, sla-policy
            $table->string('title');
            $table->string('version');
            $table->longText('content');
            $table->text('changelog')->nullable();
            $table->boolean('is_published')->default(true)->index();
            $table->timestamp('effective_from');
            $table->foreignId('created_by_user_id')->nullable()->constrained('platform_users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('platform_legal_documents');
        Schema::dropIfExists('platform_data_protection_requests');
        Schema::dropIfExists('platform_etims_certifications');
    }
};
