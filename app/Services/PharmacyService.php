<?php

namespace App\Services;

use App\Models\PharmacyBatch;
use App\Models\PharmacyDispensingLog;
use App\Models\PharmacyPrescription;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PharmacyService
{
    public function __construct(
        protected InventoryService $inventoryService
    ) {}

    /**
     * Record a new stock batch for a pharmaceutical product.
     *
     * @param  array{batch_number: string, expiry_date: string, supplier_name?: ?string, manufacturing_date?: ?string, initial_quantity: numeric|string, unit_cost?: numeric|string, is_quarantined?: bool, quarantine_reason?: ?string}  $data
     */
    public function recordBatch(Shop $shop, Product $product, array $data, ?User $user = null): PharmacyBatch
    {
        return DB::transaction(function () use ($shop, $product, $data, $user): PharmacyBatch {
            $initialQty = (float) $data['initial_quantity'];

            $batch = PharmacyBatch::query()->create([
                'tenant_id' => $shop->tenant_id,
                'shop_id' => $shop->id,
                'product_id' => $product->id,
                'batch_number' => $data['batch_number'],
                'supplier_name' => $data['supplier_name'] ?? null,
                'manufacturing_date' => $data['manufacturing_date'] ?? null,
                'expiry_date' => $data['expiry_date'],
                'initial_quantity' => $initialQty,
                'current_quantity' => $initialQty,
                'unit_cost' => (float) ($data['unit_cost'] ?? $product->cost_price),
                'is_quarantined' => $data['is_quarantined'] ?? false,
                'quarantine_reason' => $data['quarantine_reason'] ?? null,
            ]);

            // Adjust shop inventory balance for this product if not quarantined
            if ($initialQty > 0 && ! $batch->is_quarantined) {
                $this->inventoryService->adjustStock(
                    product: $product,
                    shop: $shop,
                    quantityDelta: $initialQty,
                    type: 'batch_intake',
                    user: $user,
                    reason: "Pharmacy Batch Intake #{$batch->batch_number}",
                    referenceType: 'PharmacyBatch',
                    referenceId: (string) $batch->id
                );
            }

            return $batch;
        });
    }

    /**
     * Enforce strict clinical safety checks before POS sale or dispensing.
     *
     * @throws ValidationException
     */
    public function validateItemForSale(
        Shop $shop,
        Product $product,
        ?int $batchId = null,
        ?User $authorizingPharmacist = null
    ): ?PharmacyBatch {
        $batch = null;

        if ($batchId) {
            $batch = PharmacyBatch::query()
                ->where('shop_id', $shop->id)
                ->where('product_id', $product->id)
                ->findOrFail($batchId);
        } else {
            // FEFO: Pick earliest-expiring active non-quarantined batch with available quantity
            $batch = PharmacyBatch::query()
                ->where('shop_id', $shop->id)
                ->where('product_id', $product->id)
                ->where('is_quarantined', false)
                ->where('current_quantity', '>', 0)
                ->orderBy('expiry_date', 'asc')
                ->first();
        }

        if ($batch) {
            if ($batch->is_quarantined) {
                throw ValidationException::withMessages([
                    'batch' => "Dispensing BLOCKED: Batch #{$batch->batch_number} is quarantined ({$batch->quarantine_reason}).",
                ]);
            }

            if ($batch->isExpired()) {
                throw ValidationException::withMessages([
                    'batch' => "Dispensing BLOCKED: Batch #{$batch->batch_number} for {$product->name} expired on {$batch->expiry_date->format('Y-m-d')}. Sale prohibited under Kenya Pharmacy and Poisons Board regulations.",
                ]);
            }
        }

        // Controlled Substances Step-Up Verification
        $isControlled = (bool) ($product->metadata['is_controlled_substance'] ?? false);
        if ($isControlled) {
            if (! $authorizingPharmacist) {
                throw ValidationException::withMessages([
                    'pharmacist' => 'Controlled Substance Step-Up Required: Certified pharmacist credential and approval required to dispense.',
                ]);
            }

            $hasRoleOrPerm = $authorizingPharmacist->hasRole('pharmacist')
                || $authorizingPharmacist->hasRole('owner')
                || $authorizingPharmacist->hasPermission('pharmacy.controlled.dispense');

            if (! $hasRoleOrPerm) {
                throw ValidationException::withMessages([
                    'pharmacist' => 'Unauthorized: Pharmacist license credential is not authorized for controlled substances.',
                ]);
            }
        }

        return $batch;
    }

    /**
     * Log a clinical dispensing action with optional pharmacist verification.
     */
    public function logDispensing(
        Shop $shop,
        User $user,
        Product $product,
        float $quantity,
        string $patientName,
        ?PharmacyBatch $batch = null,
        ?PharmacyPrescription $prescription = null,
        ?Sale $sale = null,
        ?User $verifiedBy = null,
        ?string $dosageInstructions = null
    ): PharmacyDispensingLog {
        $isControlled = (bool) ($product->metadata['is_controlled_substance'] ?? false);

        // Deduct from batch current quantity if batch specified
        if ($batch && (float) $batch->current_quantity >= $quantity) {
            $batch->update([
                'current_quantity' => bcsub((string) $batch->current_quantity, (string) $quantity, 4),
            ]);
        }

        return PharmacyDispensingLog::query()->create([
            'tenant_id' => $shop->tenant_id,
            'shop_id' => $shop->id,
            'user_id' => $user->id,
            'prescription_id' => $prescription?->id,
            'product_id' => $product->id,
            'batch_id' => $batch?->id,
            'sale_id' => $sale?->id,
            'quantity' => $quantity,
            'patient_name' => $patientName,
            'is_controlled_substance' => $isControlled,
            'pharmacist_verified' => $verifiedBy !== null,
            'verified_by_user_id' => $verifiedBy?->id,
            'verified_at' => $verifiedBy ? now() : null,
            'dosage_instructions' => $dosageInstructions,
        ]);
    }

    /**
     * Get expiry risk radar metrics and list of near-expiry or expired batches.
     *
     * @return array{expired_count: int, critical_30d_count: int, warning_60d_count: int, caution_90d_count: int, expired_batches: Collection, near_expiry_batches: Collection}
     */
    public function getExpiryAlerts(Shop $shop): array
    {
        $today = now()->toDateString();
        $in30Days = now()->addDays(30)->toDateString();
        $in60Days = now()->addDays(60)->toDateString();
        $in90Days = now()->addDays(90)->toDateString();

        $expired = PharmacyBatch::query()
            ->where('shop_id', $shop->id)
            ->where('current_quantity', '>', 0)
            ->whereDate('expiry_date', '<', $today)
            ->with('product')
            ->orderBy('expiry_date', 'asc')
            ->get();

        $nearExpiry = PharmacyBatch::query()
            ->where('shop_id', $shop->id)
            ->where('current_quantity', '>', 0)
            ->whereDate('expiry_date', '>=', $today)
            ->whereDate('expiry_date', '<=', $in90Days)
            ->with('product')
            ->orderBy('expiry_date', 'asc')
            ->get();

        $critical30 = $nearExpiry->filter(fn ($b) => $b->expiry_date->toDateString() <= $in30Days)->count();
        $warning60 = $nearExpiry->filter(fn ($b) => $b->expiry_date->toDateString() > $in30Days && $b->expiry_date->toDateString() <= $in60Days)->count();
        $caution90 = $nearExpiry->filter(fn ($b) => $b->expiry_date->toDateString() > $in60Days && $b->expiry_date->toDateString() <= $in90Days)->count();

        return [
            'expired_count' => $expired->count(),
            'critical_30d_count' => $critical30,
            'warning_60d_count' => $warning60,
            'caution_90d_count' => $caution90,
            'expired_batches' => $expired,
            'near_expiry_batches' => $nearExpiry,
        ];
    }
}
