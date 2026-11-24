<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharmacy_batches', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('shop_id')->constrained('shops')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('batch_number', 64);
            $table->string('supplier_name', 150)->nullable();
            $table->date('manufacturing_date')->nullable();
            $table->date('expiry_date')->index();
            $table->decimal('initial_quantity', 15, 4)->default(0.0000);
            $table->decimal('current_quantity', 15, 4)->default(0.0000);
            $table->decimal('unit_cost', 15, 2)->default(0.00);
            $table->boolean('is_quarantined')->default(false);
            $table->string('quarantine_reason')->nullable();
            $table->timestamps();

            $table->unique(['shop_id', 'product_id', 'batch_number']);
        });

        Schema::create('pharmacy_prescriptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('shop_id')->constrained('shops')->cascadeOnDelete();
            $table->string('prescription_number', 40);
            $table->string('patient_name');
            $table->string('patient_identifier', 64)->nullable();
            $table->string('doctor_name');
            $table->string('doctor_license_number', 64)->nullable();
            $table->string('hospital_clinic')->nullable();
            $table->text('diagnosis_notes')->nullable();
            $table->string('status', 30)->default('active')->index();
            $table->date('prescription_date');
            $table->date('expiry_date');
            $table->timestamps();

            $table->unique(['tenant_id', 'prescription_number']);
        });

        Schema::create('pharmacy_dispensing_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('shop_id')->constrained('shops')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('prescription_id')->nullable()->constrained('pharmacy_prescriptions')->nullOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained('pharmacy_batches')->nullOnDelete();
            $table->foreignId('sale_id')->nullable()->constrained('sales')->nullOnDelete();
            $table->decimal('quantity', 15, 4);
            $table->string('patient_name');
            $table->boolean('is_controlled_substance')->default(false);
            $table->boolean('pharmacist_verified')->default(false);
            $table->foreignId('verified_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('dosage_instructions')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharmacy_dispensing_logs');
        Schema::dropIfExists('pharmacy_prescriptions');
        Schema::dropIfExists('pharmacy_batches');
    }
};
