<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\IndustryTemplate;
use App\Models\Tenant;
use App\Services\ModuleService;
use App\Services\PlatformAuditRecorder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlatformIndustryTemplateController extends Controller
{
    public function __construct(
        protected ModuleService $moduleService,
        protected PlatformAuditRecorder $audit
    ) {}

    public function index(Request $request): View
    {
        abort_unless($request->user('platform')?->hasPermission('platform.modules.view'), 403);

        $templates = IndustryTemplate::query()->orderBy('name')->get();

        return view('platform.templates.index', [
            'templates' => $templates,
        ]);
    }

    public function apply(Request $request, Tenant $tenant): RedirectResponse
    {
        abort_unless($request->user('platform')?->hasPermission('platform.modules.manage'), 403);

        $validated = $request->validate([
            'template_id' => ['required', 'integer', 'exists:industry_templates,id'],
        ]);

        $template = IndustryTemplate::query()->findOrFail($validated['template_id']);

        $enabledSlugs = $this->moduleService->applyIndustryTemplate($tenant, $template);

        $this->audit->record(
            actorId: $request->user('platform')?->id,
            action: 'tenant.template.apply',
            targetType: Tenant::class,
            targetId: (string) $tenant->id,
            details: [
                'template_slug' => $template->slug,
                'enabled_modules' => $enabledSlugs,
            ],
        );

        return redirect()->route('platform.tenants.show', $tenant)
            ->with('notice', "Applied {$template->name} blueprint (".count($enabledSlugs).' modules enabled).');
    }
}
