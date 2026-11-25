<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_financial_audit_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('shop_id')->nullable()->index();
            $table->unsignedBigInteger('actor_user_id')->nullable()->index();
            $table->string('entity');
            $table->string('entity_id');
            $table->string('action');
            $table->char('before_hash', 64)->nullable();
            $table->char('after_hash', 64);
            $table->char('prev_hash', 64)->nullable();
            $table->char('hash', 64)->unique();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['tenant_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_financial_audit_entries');
    }
};
