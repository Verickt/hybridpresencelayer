<?php

namespace App\Http\Controllers;

use App\Models\Booth;
use App\Models\Event;
use App\Models\EventSession;
use App\Services\PresenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route as RouteFacade;
use Illuminate\Support\Facades\URL;

class QrResolveController extends Controller
{
    public function __invoke(Request $request, Event $event, PresenceService $presenceService): JsonResponse
    {
        $request->validate([
            'payload' => ['required', 'string'],
        ]);

        $payload = $request->input('payload');

        // Reject external or malformed payloads
        if (! str_starts_with($payload, '/')) {
            return response()->json(['message' => 'Invalid QR payload.'], 422);
        }

        // Verify the user is a participant of this event
        $isParticipant = $event->participants()->where('user_id', $request->user()->id)->exists();
        if (! $isParticipant) {
            return response()->json(['message' => 'Not a participant of this event.'], 403);
        }

        // Validate the signed URL
        if (! URL::hasCorrectSignature($this->buildFakeRequest($payload), false, ['signature'])) {
            // Check if it's expired vs just invalid
            if (URL::hasCorrectSignature($this->buildFakeRequest($payload), false)) {
                return response()->json(['message' => 'QR code has expired.'], 410);
            }

            return response()->json(['message' => 'Invalid QR payload.'], 422);
        }

        if (URL::signatureHasNotExpired($this->buildFakeRequest($payload)) === false) {
            return response()->json(['message' => 'QR code has expired.'], 410);
        }

        // Resolve the route from the payload
        $matchedRoute = $this->resolveRoute($payload);

        if (! $matchedRoute) {
            return response()->json(['message' => 'Invalid QR payload.'], 422);
        }

        $routeName = $matchedRoute->getName();
        $parameters = $matchedRoute->parameters();

        // Verify event scope
        $payloadEvent = $parameters['event'] ?? null;
        if (! $payloadEvent instanceof Event || $payloadEvent->id !== $event->id) {
            return response()->json(['message' => 'QR code belongs to a different event.'], 403);
        }

        return match ($routeName) {
            'event.sessions.qr-checkin' => $this->handleSessionCheckIn($request, $event, $parameters['session'], $presenceService),
            'event.booths.qr-checkin' => $this->handleBoothCheckIn($request, $event, $parameters['booth'], $presenceService),
            default => response()->json(['message' => 'Unsupported QR action.'], 422),
        };
    }

    private function handleSessionCheckIn(Request $request, Event $event, EventSession $session, PresenceService $presenceService): JsonResponse
    {
        $presenceService->checkInToSession($request->user(), $event, $session);

        return response()->json([
            'message' => 'Checked in',
            'action' => 'session_check_in',
            'target' => [
                'type' => 'session',
                'id' => $session->id,
                'title' => $session->title,
            ],
        ]);
    }

    private function handleBoothCheckIn(Request $request, Event $event, Booth $booth, PresenceService $presenceService): JsonResponse
    {
        $presenceService->checkInToBooth($request->user(), $event, $booth);

        return response()->json([
            'message' => 'Checked in',
            'action' => 'booth_check_in',
            'target' => [
                'type' => 'booth',
                'id' => $booth->id,
                'name' => $booth->name,
            ],
        ]);
    }

    private function buildFakeRequest(string $payload): Request
    {
        return Request::create($payload);
    }

    private function resolveRoute(string $payload): ?Route
    {
        try {
            $path = parse_url($payload, PHP_URL_PATH);
            $query = parse_url($payload, PHP_URL_QUERY);

            $fakeRequest = Request::create($path, 'GET', $query ? collect(explode('&', $query))->mapWithKeys(function ($item) {
                $parts = explode('=', $item, 2);

                return [urldecode($parts[0]) => urldecode($parts[1] ?? '')];
            })->all() : []);

            $route = RouteFacade::getRoutes()->match($fakeRequest);
            $route->bind($fakeRequest);

            // Resolve route model bindings
            app(Router::class)->substituteBindings($route);
            app(Router::class)->substituteImplicitBindings($route);

            return $route;
        } catch (\Throwable) {
            return null;
        }
    }
}
