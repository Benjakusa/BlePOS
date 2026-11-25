<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('support_cases', function (Blueprint $table): void {
            $table->foreignId('requester_user_id')->nullable()->after('tenant_id')->constrained('users')->nullOnDelete();
        });

        Schema::table('support_case_notes', function (Blueprint $table): void {
            $table->foreignId('tenant_user_id')->nullable()->after('platform_user_id')->constrained('users')->nullOnDelete();
        });

        Schema::create('notifications', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::table('support_case_notes', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('tenant_user_id');
        });
        Schema::table('support_cases', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('requester_user_id');
        });
    }
};
