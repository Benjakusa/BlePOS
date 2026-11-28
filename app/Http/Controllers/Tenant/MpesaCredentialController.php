<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\MpesaCredential;
use App\Models\MpesaCredentialAudit;
use App\Models\Shop;
use App\Services\MpesaEncryptionService;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MpesaCredentialController extends Controller
{
    public function __construct(private MpesaEncryptionService $encryption) {}

    public function index(): View
    {
        $tenant = TenantContext::getTenant();
        abort_if($tenant === null, 403);

        $user = auth()->user();
        $query = MpesaCredential::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->with(['branch', 'audits' => fn ($q) => $q->latest('id')->limit(5)]);

        if (! $user->isTenantOwner() && ! $user->hasPermission('shops.all.manage')) {
            $allowedBranchIds = $user->shops()->pluck('shops.id')->all();
            $query->where('owner_type', 'branch')
                ->whereIn('branch_id', $allowedBranchIds);
        }

        $credentials = $query
            ->orderBy('environment')
            ->orderByDesc('is_default')
            ->get();

        return view('tenant.mpesa.index', compact('credentials', 'tenant'));
    }

    public function create(): View
    {
        $tenant = TenantContext::getTenant();
        abort_if($tenant === null, 403);

        $user = auth()->user();
        $shopsQuery = Shop::query()->where('tenant_id', $tenant->id)->orderBy('name');
        if (! $user->isTenantOwner() && ! $user->hasPermission('shops.all.manage')) {
            $shopsQuery->whereIn('id', $user->shops()->pluck('shops.id'));
        }
        $shops = $shopsQuery->get();

        return view('tenant.mpesa.create', compact('tenant', 'shops'));
    }

    public function store(Request $request): RedirectResponse
    {
        $tenant = TenantContext::getTenant();
        abort_if($tenant === null, 403);

        $user = auth()->user();
        $validated = $request->validate([
            'owner_type' => ['required', Rule::in(['tenant', 'branch'])],
            'branch_id' => ['nullable', 'required_if:owner_type,branch', 'integer', Rule::exists('shops', 'id')->where('tenant_id', $tenant->id)],
            'environment' => ['required', Rule::in(['sandbox', 'production'])],
            'shortcode' => ['required', 'string', 'max:20'],
            'passkey' => ['required', 'string', 'max:1000'],
            'consumer_key' => ['required', 'string', 'max:500'],
            'consumer_secret' => ['required', 'string', 'max:500'],
            'initiator_name' => ['nullable', 'string', 'max:200'],
            'security_credential' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'is_default' => ['boolean'],
        ]);

        if ($validated['owner_type'] === 'tenant') {
            abort_unless($user->isTenantOwner() || $user->hasPermission('shops.all.manage'), 403);
            $ownerId = $tenant->id;
            $branchId = null;
        } else {
            $branchId = (int) $validated['branch_id'];
            abort_unless($user->canAccessShop($branchId), 403);
            $ownerId = $branchId;
        }

        $credential = MpesaCredential::create([
            'owner_type' => $validated['owner_type'],
            'owner_id' => $ownerId,
            'tenant_id' => $tenant->id,
            'branch_id' => $branchId,
            'environment' => $validated['environment'],
            'shortcode' => $validated['shortcode'],
            'passkey' => $this->encryption->encrypt($validated['passkey']),
            'consumer_key' => $this->encryption->encrypt($validated['consumer_key']),
            'consumer_secret' => $this->encryption->encrypt($validated['consumer_secret']),
            'initiator_name' => ! empty($validated['initiator_name']) ? $this->encryption->encrypt($validated['initiator_name']) : null,
            'security_credential' => ! empty($validated['security_credential']) ? $this->encryption->encrypt($validated['security_credential']) : null,
            'callback_url' => url('/api/mpesa/callback'), // Legacy base URL; STK uses the generated tokenized route.
            'is_active' => $request->boolean('is_active', true),
            'is_default' => $request->boolean('is_default', false),
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        // If set as default, unset others in same owner/env context
        if ($credential->is_default) {
            MpesaCredential::withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->where('owner_type', $validated['owner_type'])
                ->where('owner_id', $ownerId)
                ->where('environment', $validated['environment'])
                ->where('id', '!=', $credential->id)
                ->update(['is_default' => false]);
        }

        MpesaCredentialAudit::create([
            'mpesa_credential_id' => $credential->id,
            'actor_type' => 'tenant_user',
            'actor_id' => auth()->id(),
            'action' => 'created',
            'owner_type' => $credential->owner_type,
            'owner_id' => $credential->owner_id,
            'tenant_id' => $tenant->id,
            'shortcode' => $credential->shortcode,
            'environment' => $credential->environment,
            'changes' => ['fields' => ['shortcode', 'consumer_key', 'environment', 'owner_type', 'is_active', 'is_default']],
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('tenant.mpesa.index')->with('success', 'M-Pesa credential saved successfully.');
    }

    public function edit(MpesaCredential $mpesaCredential): View
    {
        $tenant = TenantContext::getTenant();
        abort_if($tenant === null, 403);
        $this->authorizeCredentialAccess($mpesaCredential, $tenant->id);

        $user = auth()->user();
        $shopsQuery = Shop::query()->where('tenant_id', $tenant->id)->orderBy('name');
        if (! $user->isTenantOwner() && ! $user->hasPermission('shops.all.manage')) {
            $shopsQuery->whereIn('id', $user->shops()->pluck('shops.id'));
        }
        $shops = $shopsQuery->get();

        // Write-only fields shown as masked in view
        return view('tenant.mpesa.edit', compact('mpesaCredential', 'tenant', 'shops'));
    }

    public function update(Request $request, MpesaCredential $mpesaCredential): RedirectResponse
    {
        $tenant = TenantContext::getTenant();
        abort_if($tenant === null, 403);
        $this->authorizeCredentialAccess($mpesaCredential, $tenant->id);

        $validated = $request->validate([
            'environment' => ['required', Rule::in(['sandbox', 'production'])],
            'shortcode' => ['required', 'string', 'max:20'],
            'passkey' => ['nullable', 'string', 'max:1000'],
            'consumer_key' => ['nullable', 'string', 'max:500'],
            'consumer_secret' => ['nullable', 'string', 'max:500'],
            'initiator_name' => ['nullable', 'string', 'max:200'],
            'security_credential' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'is_default' => ['boolean'],
        ]);

        $updateData = [
            'environment' => $validated['environment'],
            'shortcode' => $validated['shortcode'],
            'is_active' => $request->boolean('is_active', true),
            'is_default' => $request->boolean('is_default', false),
            'updated_by' => auth()->id(),
        ];
        $changedFields = ['shortcode', 'environment', 'is_active', 'is_default'];

        // Only re-encrypt if a new value is provided (secrets are write-only)
        if (! empty($validated['passkey'])) {
            $updateData['passkey'] = $this->encryption->encrypt($validated['passkey']);
            $changedFields[] = 'passkey';
        }
        if (! empty($validated['consumer_key'])) {
            $updateData['consumer_key'] = $this->encryption->encrypt($validated['consumer_key']);
            $changedFields[] = 'consumer_key';
        }
        if (! empty($validated['consumer_secret'])) {
            $updateData['consumer_secret'] = $this->encryption->encrypt($validated['consumer_secret']);
            $changedFields[] = 'consumer_secret';
        }
        if (! empty($validated['initiator_name'])) {
            $updateData['initiator_name'] = $this->encryption->encrypt($validated['initiator_name']);
            $changedFields[] = 'initiator_name';
        }
        if (! empty($validated['security_credential'])) {
            $updateData['security_credential'] = $this->encryption->encrypt($validated['security_credential']);
            $changedFields[] = 'security_credential';
        }

        $mpesaCredential->update($updateData);

        if ($mpesaCredential->is_default) {
            MpesaCredential::withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->where('owner_type', $mpesaCredential->owner_type)
                ->where('owner_id', $mpesaCredential->owner_id)
                ->where('environment', $validated['environment'])
                ->where('id', '!=', $mpesaCredential->id)
                ->update(['is_default' => false]);
        }

        MpesaCredentialAudit::create([
            'mpesa_credential_id' => $mpesaCredential->id,
            'actor_type' => 'tenant_user',
            'actor_id' => auth()->id(),
            'action' => 'updated',
            'owner_type' => $mpesaCredential->owner_type,
            'owner_id' => $mpesaCredential->owner_id,
            'tenant_id' => $tenant->id,
            'shortcode' => $mpesaCredential->shortcode,
            'environment' => $mpesaCredential->environment,
            'changes' => ['fields' => $changedFields],
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('tenant.mpesa.index')->with('success', 'M-Pesa credential updated.');
    }

    public function destroy(Request $request, MpesaCredential $mpesaCredential): RedirectResponse
    {
        $tenant = TenantContext::getTenant();
        abort_if($tenant === null, 403);
        $this->authorizeCredentialAccess($mpesaCredential, $tenant->id);

        MpesaCredentialAudit::create([
            'mpesa_credential_id' => $mpesaCredential->id,
            'actor_type' => 'tenant_user',
            'actor_id' => auth()->id(),
            'action' => 'deleted',
            'owner_type' => $mpesaCredential->owner_type,
            'owner_id' => $mpesaCredential->owner_id,
            'tenant_id' => $tenant->id,
            'shortcode' => $mpesaCredential->shortcode,
            'environment' => $mpesaCredential->environment,
            'changes' => null,
            'ip_address' => $request->ip(),
        ]);

        $mpesaCredential->delete();

        return redirect()->route('tenant.mpesa.index')->with('success', 'Credential removed.');
    }

    public function testConnection(Request $request, MpesaCredential $mpesaCredential): JsonResponse
    {
        $tenant = TenantContext::getTenant();
        abort_if($tenant === null, 403);
        $this->authorizeCredentialAccess($mpesaCredential, $tenant->id);

        try {
            $ck = $this->encryption->decrypt($mpesaCredential->consumer_key);
            $cs = $this->encryption->decrypt($mpesaCredential->consumer_secret);
            $baseUrl = $mpesaCredential->environment === 'sandbox'
                ? config('services.mpesa.sandbox_base_url')
                : config('services.mpesa.production_base_url');

            $response = Http::withBasicAuth($ck, $cs)
                ->timeout(10)
                ->get("{$baseUrl}/oauth/v1/generate", ['grant_type' => 'client_credentials']);

            $httpCode = $response->status();
            $data = $response->json();
            $success = $response->successful() && isset($data['access_token']);

            MpesaCredentialAudit::create([
                'mpesa_credential_id' => $mpesaCredential->id,
                'actor_type' => 'tenant_user',
                'actor_id' => auth()->id(),
                'action' => 'tested',
                'owner_type' => $mpesaCredential->owner_type,
                'owner_id' => $mpesaCredential->owner_id,
                'tenant_id' => $tenant->id,
                'shortcode' => $mpesaCredential->shortcode,
                'environment' => $mpesaCredential->environment,
                'changes' => ['http_code' => $httpCode, 'success' => $success],
                'ip_address' => $request->ip(),
            ]);

            return response()->json([
                'success' => $success,
                'http_code' => $httpCode,
                'message' => $success ? 'OAuth token obtained successfully.' : 'OAuth failed — check credentials.',
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Connection test failed.']);
        }
    }

    private function authorizeCredentialAccess(MpesaCredential $credential, int $tenantId): void
    {
        abort_unless((int) $credential->tenant_id === $tenantId, 404);

        $user = auth()->user();
        if ($user->isTenantOwner() || $user->hasPermission('shops.all.manage')) {
            return;
        }

        if ($credential->owner_type === 'tenant') {
            abort(403, 'Only tenant administrators can manage tenant-level M-Pesa credentials.');
        }

        if ($credential->owner_type === 'branch') {
            abort_unless($credential->branch_id && $user->canAccessShop((int) $credential->branch_id), 403);
        }
    }
}
