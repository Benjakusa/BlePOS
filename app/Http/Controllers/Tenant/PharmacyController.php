<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\PharmacyBatch;
use App\Models\PharmacyPrescription;
use App\Models\PharmacyPrescriptionItem;
use App\Models\Product;
use App\Models\Shop;
use App\Services\PharmacyService;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PharmacyController extends Controller
{
    public function index(): View
    {
        $tenant = TenantContext::getTenant();
        abort_if(! $tenant || $tenant->industry !== 'pharmacy', 404);

        return view('tenant.pharmacy.index', [
            'tenant' => $tenant,
            'shops' => Shop::query()->where('tenant_id', $tenant->id)->orderBy('name')->get(),
            'products' => Product::query()->where('tenant_id', $tenant->id)->where('is_active', true)->orderBy('name')->get(),
            'batches' => PharmacyBatch::query()->with('product')->latest()->paginate(25),
            'prescriptions' => PharmacyPrescription::query()->with('items.product')->latest()->paginate(25, ['*'], 'prescriptions_page'),
        ]);
    }

    public function storeBatch(Request $request, PharmacyService $pharmacy): RedirectResponse
    {
        $tenant = TenantContext::getTenant();
        abort_if(! $tenant || $tenant->industry !== 'pharmacy', 404);
        abort_unless($request->user()->hasPermission('inventory.receive'), 403);
        $data = $request->validate([
            'shop_id' => ['required', 'integer', 'exists:shops,id'],
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'batch_number' => ['required', 'string', 'max:64'],
            'expiry_date' => ['required', 'date', 'after:today'],
            'manufacturing_date' => ['nullable', 'date', 'before_or_equal:expiry_date'],
            'supplier_name' => ['nullable', 'string', 'max:150'],
            'initial_quantity' => ['required', 'numeric', 'gt:0'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
        ]);
        $shop = Shop::query()->where('tenant_id', $tenant->id)->findOrFail($data['shop_id']);
        abort_unless($request->user()->canAccessShop($shop), 403);
        $product = Product::query()->where('tenant_id', $tenant->id)->findOrFail($data['product_id']);
        $pharmacy->recordBatch($shop, $product, $data, $request->user());

        return back()->with('status', 'Medicine batch recorded and shop stock updated.');
    }

    public function storePrescription(Request $request): RedirectResponse
    {
        $tenant = TenantContext::getTenant();
        abort_if(! $tenant || $tenant->industry !== 'pharmacy', 404);
        abort_unless($request->user()->hasPermission('users.manage'), 403);
        $data = $request->validate([
            'shop_id' => ['required', 'integer', 'exists:shops,id'],
            'patient_name' => ['required', 'string', 'max:255'],
            'patient_identifier' => ['nullable', 'string', 'max:64'],
            'doctor_name' => ['required', 'string', 'max:255'],
            'doctor_license_number' => ['required', 'string', 'max:64'],
            'hospital_clinic' => ['nullable', 'string', 'max:255'],
            'prescription_date' => ['required', 'date', 'before_or_equal:today'],
            'expiry_date' => ['required', 'date', 'after_or_equal:today'],
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'dosage_instructions' => ['required', 'string', 'max:500'],
        ]);
        $shop = Shop::query()->where('tenant_id', $tenant->id)->findOrFail($data['shop_id']);
        abort_unless($request->user()->canAccessShop($shop), 403);
        $product = Product::query()->where('tenant_id', $tenant->id)->findOrFail($data['product_id']);

        DB::transaction(function () use ($data, $tenant, $shop, $product): void {
            $prescription = PharmacyPrescription::query()->create([
                'tenant_id' => $tenant->id,
                'shop_id' => $shop->id,
                'prescription_number' => 'RX-'.now()->format('Ymd').'-'.strtoupper(Str::random(8)),
                'patient_name' => $data['patient_name'],
                'patient_identifier' => $data['patient_identifier'] ?? null,
                'doctor_name' => $data['doctor_name'],
                'doctor_license_number' => $data['doctor_license_number'],
                'hospital_clinic' => $data['hospital_clinic'] ?? null,
                'status' => 'active',
                'prescription_date' => $data['prescription_date'],
                'expiry_date' => $data['expiry_date'],
            ]);
            PharmacyPrescriptionItem::query()->create([
                'prescription_id' => $prescription->id,
                'product_id' => $product->id,
                'prescribed_quantity' => $data['quantity'],
                'dispensed_quantity' => 0,
                'dosage_instructions' => $data['dosage_instructions'],
            ]);
        });

        return back()->with('status', 'Prescription recorded and quantity linked to the product.');
    }
}
