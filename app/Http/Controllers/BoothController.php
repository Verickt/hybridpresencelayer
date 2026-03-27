<?php

namespace App\Http\Controllers;

use App\Models\Booth;
use App\Models\BoothDemo;
use App\Models\BoothThread;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BoothController extends Controller
{
    public function index(Request $request, Event $event): Response
    {
        $userTagIds = $request->user()->interestTags()
            ->wherePivot('event_id', $event->id)
            ->pluck('interest_tags.id');

        $booths = $event->booths()
            ->with(['interestTags', 'staff:id,name'])
            ->withCount(['visits' => fn ($q) => $q->where('is_anonymous', false)])
            ->get()
            ->map(function (Booth $booth) use ($userTagIds) {
                $boothTagIds = $booth->interestTags->pluck('id');
                $relevance = $boothTagIds->intersect($userTagIds)->count();

                return [
                    'id' => $booth->id,
                    'name' => $booth->name,
                    'company' => $booth->company,
                    'description' => $booth->description,
                    'interest_tags' => $booth->interestTags->pluck('name'),
                    'visitor_count' => $booth->visits_count,
                    'staff' => $booth->staff->map(fn ($s) => ['id' => $s->id, 'name' => $s->name]),
                    'relevance' => $relevance,
                ];
            })
            ->sortByDesc('relevance')
            ->values();

        return Inertia::render('Event/Booths', [
            'event' => ['id' => $event->id, 'name' => $event->name, 'slug' => $event->slug],
            'booths' => $booths,
        ]);
    }

    public function show(Request $request, Event $event, Booth $booth): Response
    {
        $viewerId = $request->user()->id;
        $viewerParticipant = $request->user()->events()->where('event_id', $event->id)->first()?->pivot;
        $isStaff = $booth->staff()->where('user_id', $viewerId)->exists();

        $visitors = $booth->visits()
            ->where('is_anonymous', false)
            ->whereNull('left_at')
            ->with('user:id,name,company')
            ->get()
            ->map(fn ($v) => [
                'id' => $v->user->id,
                'name' => $v->user->name,
                'company' => $v->user->company,
                'participant_type' => $v->participant_type,
                'entered_at' => $v->entered_at->toISOString(),
            ]);

        $staff = $booth->staff()->get()->map(fn ($s) => [
            'id' => $s->id,
            'name' => $s->name,
            'status' => $s->events()->where('event_id', $event->id)->first()?->pivot?->status,
        ]);

        $activeDemo = $booth->demos()
            ->where('status', 'live')
            ->with(['promptThread' => fn ($query) => $query
                ->with([
                    'user:id,name',
                    'replies' => fn ($replyQuery) => $replyQuery->with('user:id,name'),
                    'votes' => fn ($voteQuery) => $voteQuery->where('user_id', $viewerId),
                ])
                ->withCount(['votes', 'replies']),
            ])
            ->latest('starts_at')
            ->first();

        $pinnedThread = $this->threadsQuery($booth, $viewerId)
            ->where('kind', 'question')
            ->where('is_pinned', true)
            ->first();

        $threads = $this->threadsQuery($booth, $viewerId)
            ->where('kind', 'question')
            ->when($pinnedThread, fn ($query) => $query->where('id', '!=', $pinnedThread->id))
            ->orderBy('is_answered')
            ->orderByDesc('votes_count')
            ->orderByDesc('last_activity_at')
            ->get()
            ->map(fn (BoothThread $thread) => $this->serializeThread($thread));

        return Inertia::render('Event/BoothDetail', [
            'event' => ['id' => $event->id, 'name' => $event->name, 'slug' => $event->slug],
            'booth' => [
                'id' => $booth->id,
                'name' => $booth->name,
                'company' => $booth->company,
                'description' => $booth->description,
                'content_links' => $booth->content_links,
                'interest_tags' => $booth->interestTags->pluck('name'),
            ],
            'viewer' => [
                'user_id' => $viewerId,
                'is_staff' => $isStaff,
                'can_post' => $viewerParticipant !== null,
                'can_moderate' => $isStaff,
                'participant_type' => $viewerParticipant?->participant_type,
            ],
            'active_demo' => $activeDemo ? $this->serializeDemo($activeDemo) : null,
            'pinned_thread' => $pinnedThread ? $this->serializeThread($pinnedThread) : null,
            'threads' => $threads,
            'visitors' => $visitors,
            'staff' => $staff,
        ]);
    }

    private function threadsQuery(Booth $booth, int $viewerId)
    {
        return $booth->threads()
            ->with([
                'user:id,name',
                'replies' => fn ($query) => $query->with('user:id,name'),
                'votes' => fn ($query) => $query->where('user_id', $viewerId),
            ])
            ->withCount(['votes', 'replies']);
    }

    public function store(Request $request, Event $event): RedirectResponse
    {
        abort_unless($request->user()->id === $event->organizer_id, 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'company' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $event->booths()->create($validated);

        return redirect()->back();
    }

    public function destroy(Request $request, Event $event, Booth $booth): RedirectResponse
    {
        abort_unless($request->user()->id === $event->organizer_id, 403);

        $booth->delete();

        return redirect()->back();
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
            'viewer_has_voted' => $thread->relationLoaded('votes') && $thread->votes->isNotEmpty(),
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
