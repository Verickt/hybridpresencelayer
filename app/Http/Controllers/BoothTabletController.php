<?php

namespace App\Http\Controllers;

use App\Models\Booth;
use App\Models\BoothDemo;
use App\Models\BoothThread;
use App\Models\Event;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Inertia\Response;

class BoothTabletController extends Controller
{
    public function __invoke(Request $request, Event $event, Booth $booth): Response
    {
        abort_unless($booth->event_id === $event->id, 404);
        abort_unless($booth->staff()->where('user_id', $request->user()->id)->exists(), 403);

        $expiresAt = $this->payloadExpiresAt($event);
        $payload = URL::temporarySignedRoute(
            'event.booths.qr-checkin',
            $expiresAt,
            ['event' => $event, 'booth' => $booth],
            absolute: false,
        );

        $activeDemo = $booth->demos()
            ->where('status', 'live')
            ->with(['promptThread' => fn ($query) => $query
                ->with([
                    'user:id,name',
                    'replies' => fn ($replyQuery) => $replyQuery->with('user:id,name'),
                ])
                ->withCount(['votes', 'replies']),
            ])
            ->latest('starts_at')
            ->first();

        $threads = $booth->threads()
            ->where('kind', 'question')
            ->with([
                'user:id,name',
                'replies' => fn ($query) => $query->with('user:id,name'),
            ])
            ->withCount(['votes', 'replies'])
            ->orderBy('is_answered')
            ->orderByDesc('is_pinned')
            ->orderByDesc('last_activity_at')
            ->get()
            ->map(fn (BoothThread $thread) => $this->serializeThread($thread))
            ->values();

        return Inertia::render('Event/BoothTablet', [
            'event' => [
                'id' => $event->id,
                'name' => $event->name,
                'slug' => $event->slug,
            ],
            'booth' => [
                'id' => $booth->id,
                'name' => $booth->name,
                'company' => $booth->company,
                'description' => $booth->description,
            ],
            'active_demo' => $activeDemo ? $this->serializeDemo($activeDemo) : null,
            'threads' => $threads,
            'qr' => [
                'payload' => $payload,
                'svg' => $this->renderQrSvg($payload),
                'expires_at' => $expiresAt->toISOString(),
                'booth_url' => route('event.booths.show', [$event, $booth]),
            ],
        ]);
    }

    private function payloadExpiresAt(Event $event): CarbonInterface
    {
        $minimumExpiry = now()->addHours(8);
        $eventExpiry = $event->ends_at?->copy()->addDay();

        if (! $eventExpiry || $eventExpiry->lessThan($minimumExpiry)) {
            return $minimumExpiry;
        }

        return $eventExpiry;
    }

    private function renderQrSvg(string $payload): string
    {
        return (new Writer(new ImageRenderer(
            new RendererStyle(384),
            new SvgImageBackEnd,
        )))->writeString($payload);
    }

    private function serializeDemo(BoothDemo $demo): array
    {
        return [
            'id' => $demo->id,
            'title' => $demo->title,
            'status' => $demo->status,
            'starts_at' => $demo->starts_at?->toISOString(),
            'prompt_thread' => $demo->promptThread ? $this->serializeThread($demo->promptThread) : null,
        ];
    }

    private function serializeThread(BoothThread $thread): array
    {
        return [
            'id' => $thread->id,
            'kind' => $thread->kind,
            'body' => $thread->body,
            'is_answered' => $thread->is_answered,
            'is_pinned' => $thread->is_pinned,
            'follow_up_requested_at' => $thread->follow_up_requested_at?->toISOString(),
            'last_activity_at' => $thread->last_activity_at?->toISOString(),
            'votes_count' => $thread->votes_count ?? 0,
            'user' => [
                'id' => $thread->user->id,
                'name' => $thread->user->name,
            ],
            'replies' => $thread->replies->map(fn ($reply) => [
                'id' => $reply->id,
                'body' => $reply->body,
                'is_staff_answer' => $reply->is_staff_answer,
                'created_at' => $reply->created_at?->toISOString(),
                'user' => [
                    'id' => $reply->user->id,
                    'name' => $reply->user->name,
                ],
            ])->values(),
        ];
    }
}
