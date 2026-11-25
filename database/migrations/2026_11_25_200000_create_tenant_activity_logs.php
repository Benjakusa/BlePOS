<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_activity_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('shop_id')->nullable()->constrained('shops')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 160);
            $table->string('route_name', 160)->nullable();
            $table->string('subject_type', 160)->nullable();
            $table->string('subject_id', 80)->nullable();
            $table->string('subject_label', 240)->nullable();
            $table->timestamp('occurred_at')->index();
            $table->timestamps();
            $table->index(['tenant_id', 'user_id', 'occurred_at'], 'tenant_activity_employee_time_idx');
            $table->index(['tenant_id', 'shop_id', 'occurred_at'], 'tenant_activity_shop_time_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_activity_logs');
    }
};
