<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\PlatformAnnouncement;
use App\Services\PlatformAuditRecorder;
use App\Services\SupportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlatformAnnouncementController extends Controller
{
    public function __construct(
        protected SupportService $support,
        protected PlatformAuditRecorder $audit
    ) {}

    public function index(Request $request): View
    {
        abort_unless($request->user('platform')?->hasPermission('platform.support.view'), 403);

        $announcements = PlatformAnnouncement::query()
            ->with(['creator'])
            ->withCount('reads')
            ->latest()
            ->paginate(20);

        return view('platform.support.announcements.index', [
            'announcements' => $announcements,
        ]);
    }

    public function create(Request $request): View
    {
        abort_unless($request->user('platform')?->hasPermission('platform.support.view'), 403);

        return view('platform.support.announcements.create');
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user('platform')?->hasPermission('platform.support.view'), 403);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'priority' => ['required', 'string', 'in:info,warning,critical'],
            'target_type' => ['required', 'string', 'in:all,plan,industry,tenant'],
            'target_value' => ['nullable', 'string', 'max:64'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ]);

        $announcement = $this->support->createAnnouncement(
            data: $validated,
            creator: $request->user('platform'),
        );

        $this->audit->record(
            actorId: $request->user('platform')?->id,
            action: 'platform.announcement.create',
            targetType: PlatformAnnouncement::class,
            targetId: (string) $announcement->id,
            details: [
                'title' => $announcement->title,
                'priority' => $announcement->priority,
                'target_type' => $announcement->target_type,
            ],
        );

        return redirect()->route('platform.announcements.index')
            ->with('notice', "Announcement '{$announcement->title}' published successfully.");
    }

    public function archive(Request $request, PlatformAnnouncement $announcement): RedirectResponse
    {
        abort_unless($request->user('platform')?->hasPermission('platform.support.view'), 403);

        $announcement->update(['status' => 'archived']);

        $this->audit->record(
            actorId: $request->user('platform')?->id,
            action: 'platform.announcement.archive',
            targetType: PlatformAnnouncement::class,
            targetId: (string) $announcement->id,
            details: [
                'title' => $announcement->title,
            ],
        );

        return redirect()->route('platform.announcements.index')
            ->with('notice', "Announcement '{$announcement->title}' archived.");
    }
}
