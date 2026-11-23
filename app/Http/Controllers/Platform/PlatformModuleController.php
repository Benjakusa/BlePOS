<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Module;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlatformModuleController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user('platform')?->hasPermission('platform.modules.view'), 403);

        $modules = Module::query()
            ->withCount(['entitlements as active_tenants_count' => function ($q): void {
                $q->where('status', 'enabled');
            }])
            ->orderBy('category')
            ->orderBy('name')
            ->get();

        $modulesByCategory = $modules->groupBy('category');

        return view('platform.modules.index', [
            'modulesByCategory' => $modulesByCategory,
            'totalCount' => $modules->count(),
        ]);
    }

    public function show(Request $request, Module $module): View
    {
        abort_unless($request->user('platform')?->hasPermission('platform.modules.view'), 403);

        $module->load(['entitlements' => function ($q): void {
            $q->where('status', 'enabled')->with('tenant');
        }]);

        // Find dependent modules (modules that list this module's slug in their dependencies)
        $dependents = Module::query()
            ->whereJsonContains('dependencies', $module->slug)
            ->get();

        return view('platform.modules.show', [
            'module' => $module,
            'dependents' => $dependents,
        ]);
    }
}
