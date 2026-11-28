<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\MpesaCredential;
use App\Models\MpesaCredentialAudit;
use App\Models\MpesaTransaction;
use App\Models\Tenant;
use App\Services\MpesaEncryptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PlatformMpesaController extends Controller
{
    public function __construct(private MpesaEncryptionService $encryption) {}

    public function index(): View
    {
        // Platform-level credentials (owner_type = 'platform')
        $platformCredentials = MpesaCredential::withoutGlobalScopes()
            ->where('owner_type', 'platform')
            ->orderBy('environment')
            ->orderByDesc('is_default')
            ->get();

        // Per-tenant summary counts
        $tenantSummary = MpesaCredential::withoutGlobalScopes()
            ->whereIn('owner_type', ['tenant', 'branch'])
            ->whereNotNull('tenant_id')
            ->selectRaw('tenant_id, COUNT(*) as total, SUM(is_active) as active_count')
            ->groupBy('tenant_id')
            ->with('tenant:id,name')
            ->get();
        $pendingRefundCount = MpesaTransaction::query()->where('status', 'refund_required')->count();

        return view('platform.mpesa.index', compact('platformCredentials', 'tenantSummary', 'pendingRefundCount'));
    }

    public function refundReviewIndex(): View
    {
        $transactions = MpesaTransaction::query()
            ->where('status', 'refund_required')
            ->with(['credential', 'sale', 'platformPayment.invoice'])
            ->orderByDesc('id')
            ->paginate(25);

        return view('platform.mpesa.refund-review', compact('transactions'));
    }

    public function recordRefundReview(Request $request, MpesaTransaction $transaction): RedirectResponse
    {
        $validated = $request->validate([
            'refund_reference' => ['required', 'string', 'max:120'],
            'refund_notes' => ['required', 'string', 'max:500'],
        ]);
        $actor = $request->user('platform');

        DB::transaction(function () use ($transaction, $validated, $actor, $request): void {
            $locked = MpesaTransaction::query()->lockForUpdate()->findOrFail($transaction->id);
            abort_unless($locked->status === 'refund_required' && ($locked->sale_id !== null || $locked->platform_payment_id !== null), 409, 'This M-Pesa transaction is not awaiting refund review.');
            $locked->forceFill([
                'status' => 'refunded',
                'refund_reference' => trim($validated['refund_reference']),
                'refund_recorded_by' => $actor->id,
                'refund_recorded_at' => now(),
                'refund_notes' => trim($validated['refund_notes']),
            ])->save();

            $credential = $locked->credential;
            MpesaCredentialAudit::query()->create([
                'mpesa_credential_id' => $credential?->id,
                'actor_type' => 'platform_user',
                'actor_id' => $actor->id,
                'action' => 'pos_refund_recorded',
                'owner_type' => $credential?->owner_type ?? 'platform',
                'owner_id' => $credential?->owner_id,
                'tenant_id' => $locked->tenant_id,
                'shortcode' => $credential?->shortcode ?? 'unknown',
                'environment' => $credential?->environment ?? 'production',
                'changes' => [
                    'transaction_id' => $locked->id,
                    'sale_id' => $locked->sale_id,
                    'platform_payment_id' => $locked->platform_payment_id,
                    'amount' => $locked->amount,
                    'provider_receipt' => $locked->provider_receipt,
                    'refund_reference' => $locked->refund_reference,
                    'refund_notes' => $locked->refund_notes,
                    'ip_address' => $request->ip(),
                ],
            ]);
        });

        return redirect()->route('platform.mpesa.refunds.index')->with('success', 'Refund evidence recorded and the M-Pesa case was closed.');
    }

    public function create(): View
    {
        return view('platform.mpesa.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
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

        $credential = MpesaCredential::create([
            'owner_type' => 'platform',
            'owner_id' => null,
            'tenant_id' => null,
            'branch_id' => null,
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
            'created_by' => auth('platform')->id(),
            'updated_by' => auth('platform')->id(),
        ]);

        if ($credential->is_default) {
            MpesaCredential::withoutGlobalScopes()
                ->where('owner_type', 'platform')
                ->where('environment', $validated['environment'])
                ->where('id', '!=', $credential->id)
                ->update(['is_default' => false]);
        }

        MpesaCredentialAudit::create([
            'mpesa_credential_id' => $credential->id,
            'actor_type' => 'platform_user',
            'actor_id' => auth('platform')->id(),
            'action' => 'created',
            'owner_type' => 'platform',
            'owner_id' => null,
            'tenant_id' => null,
            'shortcode' => $credential->shortcode,
            'environment' => $credential->environment,
            'changes' => ['fields' => ['shortcode', 'consumer_key', 'environment', 'owner_type', 'is_active', 'is_default']],
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('platform.mpesa.index')->with('success', 'Platform M-Pesa credential created.');
    }

    public function edit(MpesaCredential $credential): View
    {
        return view('platform.mpesa.edit', compact('credential'));
    }

    public function update(Request $request, MpesaCredential $credential): RedirectResponse
    {
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
            'updated_by' => auth('platform')->id(),
        ];

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

        $credential->update($updateData);

        if ($credential->is_default) {
            MpesaCredential::withoutGlobalScopes()
                ->where('owner_type', $credential->owner_type)
                ->where('environment', $validated['environment'])
                ->where('id', '!=', $credential->id)
                ->update(['is_default' => false]);
        }

        MpesaCredentialAudit::create([
            'mpesa_credential_id' => $credential->id,
            'actor_type' => 'platform_user',
            'actor_id' => auth('platform')->id(),
            'action' => 'updated',
            'owner_type' => $credential->owner_type,
            'owner_id' => $credential->owner_id,
            'tenant_id' => $credential->tenant_id,
            'shortcode' => $credential->shortcode,
            'environment' => $credential->environment,
            'changes' => ['fields' => $changedFields],
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('platform.mpesa.index')->with('success', 'Platform M-Pesa credential updated.');
    }

    public function destroy(Request $request, MpesaCredential $credential): RedirectResponse
    {
        MpesaCredentialAudit::create([
            'mpesa_credential_id' => $credential->id,
            'actor_type' => 'platform_user',
            'actor_id' => auth('platform')->id(),
            'action' => 'deleted',
            'owner_type' => $credential->owner_type,
            'owner_id' => $credential->owner_id,
            'tenant_id' => $credential->tenant_id,
            'shortcode' => $credential->shortcode,
            'environment' => $credential->environment,
            'changes' => null,
            'ip_address' => $request->ip(),
        ]);

        $credential->delete();

        return redirect()->route('platform.mpesa.index')->with('success', 'Credential removed.');
    }
}
