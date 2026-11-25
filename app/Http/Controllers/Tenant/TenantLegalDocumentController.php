<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\PlatformLegalDocument;
use App\Services\ComplianceService;
use App\Services\TenantContext;
use Illuminate\View\View;

class TenantLegalDocumentController extends Controller
{
    public function __construct(private readonly ComplianceService $compliance)
    {
        // Ensure default legal documents exist in the platform registry
        $this->compliance->ensureDefaultLegalDocuments();
    }

    /**
     * Legal & Compliance Hub for the Tenant.
     */
    public function index(): View
    {
        $tenant = TenantContext::getTenant();
        $documents = PlatformLegalDocument::query()
            ->whereIn('slug', ['terms-of-service', 'privacy-policy', 'data-processing-agreement', 'sla-policy'])
            ->orderBy('id', 'asc')
            ->get();

        return view('tenant.legal.index', compact('tenant', 'documents'));
    }

    /**
     * Comprehensive Kenyan Law Privacy Policy (Kenya Data Protection Act, 2019).
     */
    public function privacy(): View
    {
        $tenant = TenantContext::getTenant();
        $doc = PlatformLegalDocument::where('slug', 'privacy-policy')->first();

        return view('tenant.legal.privacy', compact('tenant', 'doc'));
    }

    /**
     * Master SaaS Agreement & Terms of Service (Republic of Kenya).
     */
    public function terms(): View
    {
        $tenant = TenantContext::getTenant();
        $doc = PlatformLegalDocument::where('slug', 'terms-of-service')->first();

        return view('tenant.legal.terms', compact('tenant', 'doc'));
    }

    /**
     * Data Processing Agreement (DPA) under Section 35 & 41 of Kenya DPA 2019.
     */
    public function dpa(): View
    {
        $tenant = TenantContext::getTenant();
        $doc = PlatformLegalDocument::where('slug', 'data-processing-agreement')->first();

        return view('tenant.legal.dpa', compact('tenant', 'doc'));
    }

    /**
     * Generic legal document viewer for other versions or policies.
     */
    public function show(string $slug): View
    {
        $tenant = TenantContext::getTenant();
        $doc = PlatformLegalDocument::where('slug', $slug)->firstOrFail();

        if ($slug === 'privacy-policy') {
            return $this->privacy();
        }
        if ($slug === 'terms-of-service') {
            return $this->terms();
        }
        if ($slug === 'data-processing-agreement') {
            return $this->dpa();
        }

        return view('tenant.legal.show', compact('tenant', 'doc'));
    }
}
