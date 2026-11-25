<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gym_plans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('shop_id');
            $table->string('name');
            $table->enum('billing_cycle', ['monthly', 'weekly', 'sessions']);
            $table->decimal('price', 12, 2);
            $table->unsignedInteger('session_credits')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('gym_members', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('shop_id');
            $table->string('name');
            $table->string('phone');
            $table->string('email')->nullable();
            $table->date('dob')->nullable();
            $table->string('photo_path')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('gym_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('shop_id');
            $table->unsignedBigInteger('member_id');
            $table->unsignedBigInteger('plan_id');
            $table->enum('status', ['active', 'frozen', 'expired', 'cancelled'])->default('active');
            $table->date('start_date');
            $table->date('end_date');
            $table->date('freeze_start')->nullable();
            $table->date('freeze_end')->nullable();
            $table->unsignedInteger('session_credits_remaining')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('member_id')->references('id')->on('gym_members');
            $table->foreign('plan_id')->references('id')->on('gym_plans');
        });

        Schema::create('gym_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('shop_id');
            $table->unsignedBigInteger('subscription_id');
            $table->unsignedBigInteger('member_id');
            $table->decimal('amount', 12, 2);
            $table->enum('method', ['cash', 'mpesa']);
            $table->string('reference')->nullable();
            $table->json('payment_meta')->nullable();
            $table->timestamp('paid_at');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('subscription_id')->references('id')->on('gym_subscriptions');
            $table->foreign('member_id')->references('id')->on('gym_members');
        });

        Schema::create('gym_trainers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('shop_id');
            $table->string('name');
            $table->string('phone');
            $table->string('email')->nullable();
            $table->decimal('commission_rate', 5, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('gym_classes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('shop_id');
            $table->unsignedBigInteger('trainer_id')->nullable();
            $table->string('name');
            $table->timestamp('scheduled_at');
            $table->unsignedInteger('duration_minutes')->default(60);
            $table->unsignedInteger('capacity')->default(20);
            $table->boolean('is_cancelled')->default(false);
            $table->timestamps();

            $table->foreign('trainer_id')->references('id')->on('gym_trainers');
        });

        Schema::create('gym_class_bookings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('shop_id');
            $table->unsignedBigInteger('class_id');
            $table->unsignedBigInteger('member_id');
            $table->enum('status', ['confirmed', 'waitlisted', 'attended', 'cancelled'])->default('confirmed');
            $table->timestamp('attended_at')->nullable();
            $table->timestamps();

            $table->foreign('class_id')->references('id')->on('gym_classes');
            $table->foreign('member_id')->references('id')->on('gym_members');
        });

        Schema::create('gym_checkins', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('shop_id');
            $table->unsignedBigInteger('member_id');
            $table->timestamp('checked_in_at');
            $table->enum('method', ['manual', 'barcode', 'qr'])->default('manual');
            $table->timestamps();

            $table->foreign('member_id')->references('id')->on('gym_members');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gym_checkins');
        Schema::dropIfExists('gym_class_bookings');
        Schema::dropIfExists('gym_classes');
        Schema::dropIfExists('gym_trainers');
        Schema::dropIfExists('gym_payments');
        Schema::dropIfExists('gym_subscriptions');
        Schema::dropIfExists('gym_members');
        Schema::dropIfExists('gym_plans');
    }
};
