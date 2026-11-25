<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->boolean('is_reusable')->default(false)->after('track_stock');
            $table->unsignedSmallInteger('availability_period_hours')->nullable()->after('is_reusable');
        });

        Schema::table('sale_items', function (Blueprint $table): void {
            $table->timestamp('availability_release_at')->nullable()->index()->after('total_price');
            $table->timestamp('availability_released_at')->nullable()->after('availability_release_at');
        });
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table): void {
            $table->dropColumn(['availability_release_at', 'availability_released_at']);
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn(['is_reusable', 'availability_period_hours']);
        });
    }
};
