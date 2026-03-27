<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\JsonResponse;

class ManifestController extends Controller
{
    public function __invoke(Event $event): JsonResponse
    {
        return response()->json([
            'name' => $event->name,
            'short_name' => substr($event->name, 0, 12),
            'description' => $event->description ?? 'Hybrid Presence Platform',
            'start_url' => route('event.feed', $event),
            'display' => 'standalone',
            'background_color' => '#ffffff',
            'theme_color' => $event->theme_color,
            'orientation' => 'portrait',
            'icons' => [
                ['src' => '/icons/icon-192.png', 'sizes' => '192x192', 'type' => 'image/png'],
                ['src' => '/icons/icon-512.png', 'sizes' => '512x512', 'type' => 'image/png'],
            ],
        ])->header('Cache-Control', 'private, no-store');
    }
}
