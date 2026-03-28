<?php

namespace App\Http\Controllers;

use App\Models\Booth;
use App\Models\BoothVisit;
use App\Models\Event;
use App\Models\EventSession;
use App\Models\SessionCheckIn;
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
            return response()->json(['message' => 'Ungültiger QR-Code.'], 422);
        }

        // Verify the user is a participant of this event
        $isParticipant = $event->participants()->where('user_id', $request->user()->id)->exists();
        if (! $isParticipant) {
            return response()->json(['message' => 'Kein Teilnehmer dieser Veranstaltung.'], 403);
        }

        // Build a fake request for signature validation
        $fakeRequest = Request::create(
            url($payload),
            'GET',
        );

        // Check signature validity (relative = true for relative signed URLs)
        $hasValidSignature = URL::hasCorrectSignature($fakeRequest, false);
        $hasNotExpired = URL::signatureHasNotExpired($fakeRequest);

        if (! $hasNotExpired && $hasValidSignature) {
            return response()->json(['message' => 'QR-Code abgelaufen.'], 410);
        }

        if (! $hasValidSignature) {
            // It might be expired — check ignoring expiry
            $fakeRequestForExpiry = Request::create(url($payload), 'GET');
            if (URL::hasCorrectSignature($fakeRequestForExpiry, false, ['expires'])) {
                return response()->json(['message' => 'QR-Code abgelaufen.'], 410);
            }

            return response()->json(['message' => 'Ungültiger QR-Code.'], 422);
        }

        // Resolve the route from the payload
        $matchedRoute = $this->resolveRoute($payload);

        if (! $matchedRoute) {
            return response()->json(['message' => 'Ungültiger QR-Code.'], 422);
        }

        $routeName = $matchedRoute->getName();
        $rawParams = $matchedRoute->parameters();

        // Verify event scope before handling the QR action.
        $payloadEvent = $rawParams['event'] ?? null;
        $payloadEventSlug = $payloadEvent instanceof Event
            ? $payloadEvent->slug
            : $payloadEvent;

        if (! $payloadEventSlug || $payloadEventSlug !== $event->slug) {
            return response()->json(['message' => 'QR-Code gehört zu einer anderen Veranstaltung.'], 422);
        }

        return match ($routeName) {
            'event.sessions.qr-checkin' => $this->handleSessionCheckIn(
                $request, $event,
                EventSession::where('event_id', $event->id)->findOrFail($this->normalizeRouteKey($rawParams['session'])),
                $presenceService,
            ),
            'event.booths.qr-checkin' => $this->handleBoothCheckIn(
                $request, $event,
                Booth::where('event_id', $event->id)->findOrFail($this->normalizeRouteKey($rawParams['booth'])),
                $presenceService,
            ),
            default => response()->json(['message' => 'Nicht unterstützte QR-Aktion.'], 422),
        };
    }

    private function handleSessionCheckIn(Request $request, Event $event, EventSession $session, PresenceService $presenceService): JsonResponse
    {
        $presenceService->checkInToSession($request->user(), $event, $session);

        return response()->json([
            'message' => 'Eingecheckt',
            'action' => 'session_check_in',
            'redirect_to' => route('event.sessions.show', [$event, $session], false),
            'target' => [
                'type' => 'session',
                'id' => $session->id,
                'title' => $session->title,
            ],
        ]);
    }

    private function handleBoothCheckIn(Request $request, Event $event, Booth $booth, PresenceService $presenceService): JsonResponse
    {
        $user = $request->user();
        $pivot = $user->events()->where('event_id', $event->id)->first()?->pivot;

        $lastSessionId = null;
        if ($pivot?->status === 'in_session') {
            $lastSessionId = SessionCheckIn::where('user_id', $user->id)
                ->whereNull('checked_out_at')
                ->value('event_session_id');
        }

        BoothVisit::firstOrCreate(
            [
                'user_id' => $user->id,
                'booth_id' => $booth->id,
                'left_at' => null,
            ],
            [
                'is_anonymous' => false,
                'participant_type' => $pivot?->participant_type,
                'from_session_id' => $lastSessionId,
                'entered_at' => now(),
            ],
        );

        $presenceService->checkInToBooth($request->user(), $event, $booth);

        return response()->json([
            'message' => 'Eingecheckt',
            'action' => 'booth_check_in',
            'redirect_to' => route('event.booths.show', [$event, $booth], false),
            'target' => [
                'type' => 'booth',
                'id' => $booth->id,
                'name' => $booth->name,
            ],
        ]);
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

    private function normalizeRouteKey(mixed $value): mixed
    {
        return is_object($value) && method_exists($value, 'getRouteKey')
            ? $value->getRouteKey()
            : $value;
    }
}
