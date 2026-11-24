<?php

namespace App\Services;

use App\Models\PlatformDataProtectionRequest;
use App\Models\PlatformEtimsCertification;
use App\Models\PlatformInvoice;
use App\Models\PlatformLegalDocument;
use App\Models\PlatformUser;
use App\Models\Shop;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ComplianceService
{
    public function __construct(
        private readonly PlatformAuditRecorder $auditRecorder
    ) {}

    /**
     * Get aggregate compliance and eTIMS overview data.
     *
     * @return array<string, mixed>
     */
    public function getComplianceOverview(): array
    {
        $this->ensureDefaultEtimsChecklist();
        $this->ensureDefaultLegalDocuments();

        $certifications = PlatformEtimsCertification::query()->orderBy('id')->get();
        $passedCount = $certifications->where('status', 'passed')->count();
        $totalCount = $certifications->count();

        // Overall status is BLOCKED-ON-CERTIFICATION if any critical item is blocked or pending
        $hasBlocked = $certifications->whereIn('status', ['blocked', 'failed', 'pending'])->isNotEmpty();
        $overallEtimsStatus = $hasBlocked ? 'BLOCKED-ON-CERTIFICATION' : 'CERTIFIED';

        $totalTenants = Tenant::count();
        $totalShops = Shop::count();
        $totalInvoices = PlatformInvoice::count();

        $dpaPending = PlatformDataProtectionRequest::query()
            ->whereIn('status', ['pending', 'under_review', 'processing'])
            ->count();

        $dpaOverdue = PlatformDataProtectionRequest::query()
            ->whereIn('status', ['pending', 'under_review', 'processing'])
            ->where('due_date', '<', Carbon::now())
            ->count();

        $recentDpa = PlatformDataProtectionRequest::query()
            ->with(['tenant:id,name,subdomain', 'assignedOfficer:id,name,email'])
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        $legalDocuments = PlatformLegalDocument::query()
            ->where('is_published', true)
            ->orderBy('title')
            ->get();

        return [
            'metrics' => [
                'etims_status' => $overallEtimsStatus,
                'cert_passed' => $passedCount,
                'cert_total' => $totalCount,
                'total_tenants' => $totalTenants,
                'total_shops' => $totalShops,
                'total_invoices' => $totalInvoices,
                'dpa_pending' => $dpaPending,
                'dpa_overdue' => $dpaOverdue,
            ],
            'certifications' => $certifications,
            'recent_dpa' => $recentDpa,
            'legal_documents' => $legalDocuments,
        ];
    }

    /**
     * Update an eTIMS certification checklist component.
     */
    public function updateCertificationItem(
        PlatformEtimsCertification $item,
        string $status,
        ?string $kraReference,
        ?string $details,
        PlatformUser $user
    ): PlatformEtimsCertification {
        return DB::transaction(function () use ($item, $status, $kraReference, $details, $user): PlatformEtimsCertification {
            $before = [
                'status' => $item->status,
                'kra_reference' => $item->kra_reference,
            ];

            $item->update([
                'status' => $status,
                'kra_reference' => $kraReference ?? $item->kra_reference,
                'details' => $details ?? $item->details,
                'verified_at' => Carbon::now(),
                'verified_by_user_id' => $user->id,
            ]);

            $this->auditRecorder->record(
                actorId: $user->id,
                action: 'platform.compliance.etims_cert.updated',
                targetType: 'PlatformEtimsCertification',
                targetId: (string) $item->id,
                details: [
                    'component' => $item->component_name,
                    'before' => $before,
                    'status' => $status,
                    'kra_reference' => $kraReference,
                ]
            );

            return $item->fresh();
        });
    }

    /**
     * Create a Data Protection Request (Kenya DPA 2019 / GDPR).
     */
    public function createDataProtectionRequest(array $data, PlatformUser $user): PlatformDataProtectionRequest
    {
        return DB::transaction(function () use ($data, $user): PlatformDataProtectionRequest {
            $year = Carbon::now()->year;
            $count = PlatformDataProtectionRequest::whereYear('created_at', $year)->count() + 1;
            $requestNumber = sprintf('DPA-%d-%04d', $year, $count);

            $dueDate = ! empty($data['due_date'])
                ? Carbon::parse($data['due_date'])
                : Carbon::now()->addDays(30); // 30-day statutory timeline per Kenya DPA Sec 26

            $request = PlatformDataProtectionRequest::create([
                'request_number' => $requestNumber,
                'tenant_id' => $data['tenant_id'] ?? null,
                'subject_name' => $data['subject_name'],
                'subject_email' => $data['subject_email'],
                'subject_phone' => $data['subject_phone'] ?? null,
                'request_type' => $data['request_type'],
                'status' => 'pending',
                'legal_basis' => $data['legal_basis'] ?? 'Kenya DPA 2019 Sec 26',
                'due_date' => $dueDate,
                'assigned_officer_id' => $data['assigned_officer_id'] ?? $user->id,
            ]);

            $this->auditRecorder->record(
                actorId: $user->id,
                action: 'platform.compliance.dpa_request.created',
                targetType: 'PlatformDataProtectionRequest',
                targetId: (string) $request->id,
                details: [
                    'request_number' => $request->request_number,
                    'subject_email' => $request->subject_email,
                    'request_type' => $request->request_type,
                    'due_date' => $dueDate->toIso8601String(),
                ]
            );

            return $request;
        });
    }

    /**
     * Update data protection request status and resolution.
     */
    public function updateDataProtectionRequestStatus(
        PlatformDataProtectionRequest $request,
        string $status,
        ?string $resolutionSummary,
        ?string $rejectionReason,
        PlatformUser $user
    ): PlatformDataProtectionRequest {
        return DB::transaction(function () use ($request, $status, $resolutionSummary, $rejectionReason, $user): PlatformDataProtectionRequest {
            $before = ['status' => $request->status];

            $updates = ['status' => $status];
            if ($status === 'completed') {
                $updates['completed_at'] = Carbon::now();
                $updates['resolution_summary'] = $resolutionSummary;
            } elseif ($status === 'rejected') {
                $updates['rejection_reason'] = $rejectionReason;
            }

            $request->update($updates);

            $this->auditRecorder->record(
                actorId: $user->id,
                action: 'platform.compliance.dpa_request.status_updated',
                targetType: 'PlatformDataProtectionRequest',
                targetId: (string) $request->id,
                details: [
                    'before' => $before,
                    'status' => $status,
                    'resolution_summary' => $resolutionSummary,
                    'rejection_reason' => $rejectionReason,
                ]
            );

            return $request->fresh();
        });
    }

    /**
     * Publish or update a platform legal document version.
     */
    public function publishLegalDocument(array $data, PlatformUser $user): PlatformLegalDocument
    {
        return DB::transaction(function () use ($data, $user): PlatformLegalDocument {
            $doc = PlatformLegalDocument::create([
                'slug' => $data['slug'],
                'title' => $data['title'],
                'version' => $data['version'],
                'content' => $data['content'],
                'changelog' => $data['changelog'] ?? null,
                'is_published' => true,
                'effective_from' => ! empty($data['effective_from']) ? Carbon::parse($data['effective_from']) : Carbon::now(),
                'created_by_user_id' => $user->id,
            ]);

            $this->auditRecorder->record(
                actorId: $user->id,
                action: 'platform.compliance.legal_doc.published',
                targetType: 'PlatformLegalDocument',
                targetId: (string) $doc->id,
                details: [
                    'slug' => $doc->slug,
                    'title' => $doc->title,
                    'version' => $doc->version,
                ]
            );

            return $doc;
        });
    }

    /**
     * Ensure default eTIMS certification checklist components are populated.
     */
    public function ensureDefaultEtimsChecklist(): void
    {
        if (PlatformEtimsCertification::count() > 0) {
            return;
        }

        $items = [
            [
                'component_name' => 'Platform Invoicing Core & VAT Calculation',
                'certification_stage' => 'technical_evaluation',
                'status' => 'passed',
                'details' => 'Standard 16% Kenyan VAT computation, line item breakdown, and invoice sequential numbering per Annex A 8.3.',
                'kra_reference' => 'SPEC-KRA-ETIMS-001',
            ],
            [
                'component_name' => 'Cryptographic Signature & Fiscal Hash Engine',
                'certification_stage' => 'technical_evaluation',
                'status' => 'passed',
                'details' => 'Invoice payload hashing, public key signing, and verifiable QR code URL generation.',
                'kra_reference' => 'SPEC-KRA-ETIMS-002',
            ],
            [
                'component_name' => 'Offline Buffering & Resubmission Engine',
                'certification_stage' => 'technical_evaluation',
                'status' => 'passed',
                'details' => 'Sequential offline storage for intermittent connectivity with automatic sync.',
                'kra_reference' => 'SPEC-KRA-ETIMS-003',
            ],
            [
                'component_name' => 'Virtual Sales Control Unit (VSCU) Connector',
                'certification_stage' => 'sandbox_testing',
                'status' => 'blocked',
                'details' => 'Sandbox integration verified; awaiting official KRA live production API endpoint clearance (Annex A 8.8).',
                'kra_reference' => 'KRA-CERT-PENDING-VSCU',
            ],
            [
                'component_name' => 'KRA Production Transmission Gateway & Platform PIN',
                'certification_stage' => 'kra_signoff',
                'status' => 'blocked',
                'details' => 'Live transmission gateway currently blocked pending official regulatory certification.',
                'kra_reference' => 'KRA-REG-BLOCKED-GATE',
            ],
        ];

        foreach ($items as $item) {
            PlatformEtimsCertification::create($item);
        }
    }

    /**
     * Ensure default legal documents exist.
     */
    public function ensureDefaultLegalDocuments(): void
    {
        if (PlatformLegalDocument::count() > 0) {
            return;
        }

        $now = Carbon::now();

        $docs = [
            [
                'slug' => 'terms-of-service',
                'title' => 'BlePOS Master SaaS Agreement & Terms of Service',
                'version' => '1.0',
                'content' => "# BlePOS Terms of Service\n\nVersion 1.0. Effective date: 2026-01-01.\n\nThese terms govern the use of the BlePOS multi-tenant point-of-sale platform. All tenant operations and platform administrative access are subject to strict data isolation, role-based access control, and regulatory compliance standards.",
                'changelog' => 'Initial baseline release.',
                'effective_from' => $now,
            ],
            [
                'slug' => 'privacy-policy',
                'title' => 'BlePOS Privacy Policy (Kenya Data Protection Act 2019)',
                'version' => '1.0',
                'content' => "# BlePOS Privacy Policy\n\nConforming to the Kenya Data Protection Act 2019 and international data privacy regulations. Data subjects have statutory rights to access, rectification, erasure, and objection.",
                'changelog' => 'Initial statutory compliance baseline.',
                'effective_from' => $now,
            ],
            [
                'slug' => 'data-processing-agreement',
                'title' => 'BlePOS Data Processing Agreement (DPA)',
                'version' => '1.0',
                'content' => "# Data Processing Agreement\n\nDefines obligations between BlePOS as Data Processor and Tenants as Data Controllers. Enforces zero tenant data leakage and strict auditability.",
                'changelog' => 'Standard SaaS multi-tenant DPA.',
                'effective_from' => $now,
            ],
            [
                'slug' => 'sla-policy',
                'title' => 'BlePOS Service Level Agreement (SLA)',
                'version' => '1.0',
                'content' => "# BlePOS Service Level Agreement\n\nGuarantees 99.9% platform availability, automated daily backups with SHA-256 integrity verification, and prioritized support resolution tiers.",
                'changelog' => 'Initial operational SLA standard.',
                'effective_from' => $now,
            ],
        ];

        foreach ($docs as $doc) {
            PlatformLegalDocument::create($doc);
        }
    }
}
