<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $callbackSecret = (string) config('services.mpesa.callback_secret', '');
        $legacyCredentials = DB::table('mpesa_credentials')->whereNotNull('callback_token')->orderBy('id')->get();
        if ($legacyCredentials->isNotEmpty() && strlen($callbackSecret) < 32) {
            throw new RuntimeException('Set MPESA_CALLBACK_SECRET to at least 32 characters before migrating M-Pesa callback tokens.');
        }

        Schema::table('mpesa_credentials', function (Blueprint $table): void {
            $table->char('callback_token_hash', 64)->nullable()->unique()->after('callback_token');
            $table->char('legacy_callback_token_hash', 64)->nullable()->unique()->after('callback_token_hash');
        });

        foreach ($legacyCredentials as $credential) {
            $currentToken = hash_hmac('sha256', 'mpesa-callback:'.$credential->uuid, $callbackSecret);
            DB::table('mpesa_credentials')->where('id', $credential->id)->update([
                'callback_token_hash' => hash('sha256', $currentToken),
                'legacy_callback_token_hash' => hash('sha256', (string) $credential->callback_token),
                'callback_token' => null,
            ]);
        }

        Schema::create('sale_stock_reservations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('shop_id')->constrained('shops')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->decimal('quantity', 15, 4);
            $table->string('status', 16)->default('reserved')->index();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
            $table->unique(['sale_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_stock_reservations');
        Schema::table('mpesa_credentials', function (Blueprint $table): void {
            $table->dropUnique(['legacy_callback_token_hash']);
            $table->dropUnique(['callback_token_hash']);
            $table->dropColumn(['callback_token_hash', 'legacy_callback_token_hash']);
        });
    }
};
