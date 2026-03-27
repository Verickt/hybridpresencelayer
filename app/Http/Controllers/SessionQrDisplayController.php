<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventSession;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Inertia\Response;

class SessionQrDisplayController extends Controller
{
    public function __invoke(Request $request, Event $event, EventSession $session): Response
    {
        abort_unless($request->user()->id === $event->organizer_id, 403);

        $payload = URL::temporarySignedRoute(
            'event.sessions.qr-checkin',
            $this->payloadExpiresAt($session),
            ['event' => $event, 'session' => $session],
            absolute: false,
        );

        return Inertia::render('Event/SessionQrDisplay', [
            'event' => [
                'id' => $event->id,
                'name' => $event->name,
                'slug' => $event->slug,
            ],
            'session' => [
                'id' => $session->id,
                'title' => $session->title,
                'room' => $session->room,
                'starts_at' => $session->starts_at->toISOString(),
                'ends_at' => $session->ends_at->toISOString(),
            ],
            'qr' => [
                'payload' => $payload,
                'svg' => $this->renderQrSvg($payload),
                'expires_at' => $this->payloadExpiresAt($session)->toISOString(),
                'remote_join_url' => route('event.sessions.show', [$event, $session]),
            ],
        ]);
    }

    private function payloadExpiresAt(EventSession $session): CarbonInterface
    {
        $minimumExpiry = now()->addMinutes(30);
        $sessionExpiry = $session->ends_at->copy()->addMinutes(30);

        if ($sessionExpiry->lessThan($minimumExpiry)) {
            return $minimumExpiry;
        }

        return $sessionExpiry;
    }

    private function renderQrSvg(string $payload): string
    {
        return (new Writer(new ImageRenderer(
            new RendererStyle(384),
            new SvgImageBackEnd,
        )))->writeString($payload);
    }
}
