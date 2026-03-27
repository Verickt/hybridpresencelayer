<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Services\DashboardService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, Event $event, DashboardService $dashboardService): Response
    {
        abort_unless($request->user()->id === $event->organizer_id, 403);

        return Inertia::render('Event/Dashboard', [
            'event' => ['id' => $event->id, 'name' => $event->name, 'slug' => $event->slug],
            'overview' => $dashboardService->overview($event),
            'sessionAnalytics' => $dashboardService->sessionAnalytics($event),
            'boothPerformance' => $dashboardService->boothPerformance($event),
        ]);
    }
}
