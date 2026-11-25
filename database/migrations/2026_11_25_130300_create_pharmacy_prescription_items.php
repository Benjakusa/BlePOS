<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharmacy_prescription_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('prescription_id')->constrained('pharmacy_prescriptions')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->decimal('prescribed_quantity', 15, 4);
            $table->decimal('dispensed_quantity', 15, 4)->default(0);
            $table->string('dosage_instructions', 500);
            $table->timestamps();
            $table->unique(['prescription_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharmacy_prescription_items');
    }
};
