<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\PlatformEtimsCertification;
use App\Services\ComplianceService;
use App\Services\PlatformPermissionAuthorizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlatformComplianceController extends Controller
{
    public function index(
        Request $request,
        PlatformPermissionAuthorizer $authorizer,
        ComplianceService $complianceService
    ): View {
        $authorizer->ensure($request->user('platform'), 'platform.compliance.view');

        $overview = $complianceService->getComplianceOverview();

        return view('platform.compliance.index', [
            'metrics' => $overview['metrics'],
            'certifications' => $overview['certifications'],
            'recentDpa' => $overview['recent_dpa'],
            'legalDocuments' => $overview['legal_documents'],
        ]);
    }

    public function updateCertification(
        Request $request,
        PlatformEtimsCertification $item,
        PlatformPermissionAuthorizer $authorizer,
        ComplianceService $complianceService
    ): RedirectResponse {
        $authorizer->ensure($request->user('platform'), 'platform.compliance.view');

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:pending,passed,failed,blocked'],
            'kra_reference' => ['nullable', 'string', 'max:100'],
            'details' => ['nullable', 'string'],
        ]);

        $complianceService->updateCertificationItem(
            $item,
            $validated['status'],
            $validated['kra_reference'] ?? null,
            $validated['details'] ?? null,
            $request->user('platform')
        );

        return redirect()->route('platform.compliance.index')
            ->with('notice', "eTIMS certification component [{$item->component_name}] updated to {$validated['status']}.");
    }
}
