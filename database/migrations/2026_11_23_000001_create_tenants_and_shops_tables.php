<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('trading_name')->nullable();
            $table->string('code', 32)->unique();
            $table->string('slug', 64)->unique();
            $table->string('kra_pin', 32)->nullable()->unique();
            $table->string('registration_number', 64)->nullable();
            $table->string('business_type', 64)->default('retail');
            $table->string('industry', 64)->default('general_retail');
            $table->string('status', 32)->default('pending')->index();
            $table->string('owner_name');
            $table->string('owner_email');
            $table->string('owner_phone', 32)->nullable();
            $table->string('country', 3)->default('KEN');
            $table->string('county', 64)->nullable();
            $table->string('city', 64)->nullable();
            $table->text('address')->nullable();
            $table->string('currency', 3)->default('KES');
            $table->string('timezone', 40)->default('Africa/Nairobi');
            $table->json('settings')->nullable();
            $table->string('database_connection')->nullable();
            $table->timestamps();
        });

        Schema::create('tenant_domains', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('domain', 120)->unique();
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_verified')->default(false);
            $table->timestamps();
        });

        Schema::create('shops', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 32);
            $table->string('branch_number', 32)->nullable();
            $table->string('kra_branch_id', 32)->nullable();
            $table->string('phone', 32)->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('county', 64)->nullable();
            $table->string('city', 64)->nullable();
            $table->string('currency', 3)->default('KES');
            $table->string('timezone', 40)->default('Africa/Nairobi');
            $table->text('receipt_header')->nullable();
            $table->text('receipt_footer')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'code']);
        });

        Schema::create('registers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('shop_id')->constrained('shops')->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 32);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['shop_id', 'code']);
        });

        Schema::create('warehouses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('shop_id')->nullable()->constrained('shops')->nullOnDelete();
            $table->string('name');
            $table->string('code', 32);
            $table->text('location')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'code']);
        });

        Schema::create('tenant_roles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug', 64);
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(false);
            $table->timestamps();

            $table->unique(['tenant_id', 'slug']);
        });

        Schema::create('tenant_permissions', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 80)->unique();
            $table->string('group', 80);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->foreignId('tenant_id')->nullable()->after('id')->constrained('tenants')->cascadeOnDelete();
            $table->string('status', 24)->default('active')->after('password');
            $table->string('phone', 32)->nullable()->after('status');
            $table->boolean('must_reset_password')->default(false)->after('phone');
        });

        Schema::create('tenant_role_user', function (Blueprint $table): void {
            $table->foreignId('tenant_role_id')->constrained('tenant_roles')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['tenant_role_id', 'user_id']);
        });

        Schema::create('tenant_permission_role', function (Blueprint $table): void {
            $table->foreignId('tenant_permission_id')->constrained('tenant_permissions')->cascadeOnDelete();
            $table->foreignId('tenant_role_id')->constrained('tenant_roles')->cascadeOnDelete();

            $table->primary(['tenant_permission_id', 'tenant_role_id']);
        });

        Schema::create('shop_user', function (Blueprint $table): void {
            $table->foreignId('shop_id')->constrained('shops')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->primary(['shop_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_user');
        Schema::dropIfExists('tenant_permission_role');
        Schema::dropIfExists('tenant_role_user');

        Schema::table('users', function (Blueprint $table): void {
            $table->dropForeign(['tenant_id']);
            $table->dropColumn(['tenant_id', 'status', 'phone', 'must_reset_password']);
        });

        Schema::dropIfExists('tenant_permissions');
        Schema::dropIfExists('tenant_roles');
        Schema::dropIfExists('warehouses');
        Schema::dropIfExists('registers');
        Schema::dropIfExists('shops');
        Schema::dropIfExists('tenant_domains');
        Schema::dropIfExists('tenants');
    }
};
