<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $notifications = $request->user()
            ->unreadNotifications()
            ->latest()
            ->take(20)
            ->get()
            ->map(fn ($n) => [
                'id' => $n->id,
                'type' => $n->data['type'] ?? 'unknown',
                'priority' => $n->data['priority'] ?? 'low',
                'message' => $n->data['message'] ?? '',
                'created_at' => $n->created_at->toISOString(),
                'data' => $n->data,
            ]);

        return response()->json(['data' => $notifications]);
    }

    public function markRead(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return response()->json(['message' => 'Als gelesen markiert']);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json([
            'count' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    public function updatePreferences(Request $request, Event $event): JsonResponse
    {
        $validated = $request->validate([
            'notification_mode' => ['required', 'in:normal,quiet,dnd'],
        ]);

        $pivot = $request->user()->events()->where('event_id', $event->id)->first();

        if (! $pivot) {
            abort(404);
        }

        $request->user()->events()->updateExistingPivot($event->id, [
            'notification_mode' => $validated['notification_mode'],
        ]);

        return response()->json(['message' => 'Einstellungen aktualisiert']);
    }
}
