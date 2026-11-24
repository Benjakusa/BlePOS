<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\PlatformLegalDocument;
use App\Services\ComplianceService;
use App\Services\PlatformPermissionAuthorizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlatformLegalDocumentController extends Controller
{
    public function index(
        Request $request,
        PlatformPermissionAuthorizer $authorizer,
        ComplianceService $complianceService
    ): View {
        $authorizer->ensure($request->user('platform'), 'platform.compliance.view');

        $complianceService->ensureDefaultLegalDocuments();

        $documents = PlatformLegalDocument::query()
            ->with('creator:id,name,email')
            ->orderBy('slug')
            ->orderByDesc('version')
            ->paginate(20);

        return view('platform.compliance.legal.index', [
            'documents' => $documents,
        ]);
    }

    public function create(
        Request $request,
        PlatformPermissionAuthorizer $authorizer
    ): View {
        $authorizer->ensure($request->user('platform'), 'platform.compliance.view');

        return view('platform.compliance.legal.create');
    }

    public function store(
        Request $request,
        PlatformPermissionAuthorizer $authorizer,
        ComplianceService $complianceService
    ): RedirectResponse {
        $authorizer->ensure($request->user('platform'), 'platform.compliance.view');

        $validated = $request->validate([
            'slug' => ['required', 'string', 'max:100'],
            'title' => ['required', 'string', 'max:255'],
            'version' => ['required', 'string', 'max:20'],
            'content' => ['required', 'string'],
            'changelog' => ['nullable', 'string'],
            'effective_from' => ['nullable', 'date'],
        ]);

        $doc = $complianceService->publishLegalDocument($validated, $request->user('platform'));

        return redirect()->route('platform.compliance.legal.show', $doc)
            ->with('notice', "Legal document [{$doc->title}] v{$doc->version} published.");
    }

    public function show(
        Request $request,
        PlatformLegalDocument $legal,
        PlatformPermissionAuthorizer $authorizer
    ): View {
        $authorizer->ensure($request->user('platform'), 'platform.compliance.view');

        $legal->load('creator');

        return view('platform.compliance.legal.show', [
            'document' => $legal,
        ]);
    }
}
