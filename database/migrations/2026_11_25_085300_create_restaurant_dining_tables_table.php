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
        Schema::create('dining_tables', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->string('name', 64);
            $table->string('code', 32);
            $table->string('zone', 64)->default('Main Dining'); // Main Dining, Terrace, VIP, Bar Lounge
            $table->unsignedSmallInteger('seating_capacity')->default(4);
            $table->string('status', 32)->default('available'); // available, occupied, reserved, cleaning
            $table->foreignId('current_sale_id')->nullable()->constrained('sales')->nullOnDelete();
            $table->unsignedSmallInteger('guest_count')->nullable();
            $table->timestamp('occupied_at')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['shop_id', 'code']);
            $table->index(['tenant_id', 'status']);
        });

        Schema::table('sales', function (Blueprint $table): void {
            $table->foreignId('dining_table_id')->nullable()->after('cashier_shift_id')->constrained('dining_tables')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('dining_table_id');
        });

        Schema::dropIfExists('dining_tables');
    }
};
