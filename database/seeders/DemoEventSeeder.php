<?php

namespace Database\Seeders;

use App\Models\Booth;
use App\Models\BoothDemo;
use App\Models\BoothThread;
use App\Models\BoothThreadReply;
use App\Models\BoothThreadVote;
use App\Models\BoothVisit;
use App\Models\Connection;
use App\Models\Event;
use App\Models\EventSession;
use App\Models\IcebreakerQuestion;
use App\Models\InterestTag;
use App\Models\Message;
use App\Models\Ping;
use App\Models\SessionCheckIn;
use App\Models\SessionQuestion;
use App\Models\SessionQuestionVote;
use App\Models\SessionReaction;
use App\Models\Suggestion;
use App\Models\User;
use App\Notifications\InAppNotification;
use Carbon\CarbonInterface;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DemoEventSeeder extends Seeder
{
    public const DEMO_PASSWORD = 'password';

    public const EVENT_SLUG = 'bsi-cyber-security-conference-2026';

    public const ORGANIZER_EMAIL = 'organizer@demo.test';

    public const PARTICIPANT_EMAIL = 'participant@demo.test';

    public function run(): void
    {
        $now = now();
        $eventStartsAt = $now->copy()->subHours(2);
        $eventEndsAt = $now->copy()->addHours(6);

        $organizer = $this->upsertUser([
            'name' => 'Event Organizer',
            'email' => self::ORGANIZER_EMAIL,
            'company' => 'Hybrid Presence Layer',
            'role_title' => 'Organizer',
            'intent' => 'Keeping the event flowing smoothly.',
            'is_organizer' => true,
        ]);

        $event = Event::updateOrCreate(
            ['slug' => self::EVENT_SLUG],
            [
                'organizer_id' => $organizer->id,
                'name' => 'BSI Cyber Security Conference 2026',
                'description' => 'A seeded demo event for browsing the hybrid presence experience.',
                'venue' => 'Congress Center Basel',
                'theme_color' => '#0F766E',
                'starts_at' => $eventStartsAt,
                'ends_at' => $eventEndsAt,
                'allow_open_registration' => true,
            ],
        );

        $tags = collect([
            'Zero Trust',
            'Cloud Migration',
            'DevOps',
            'AI/ML',
            'Cybersecurity',
            'Data Privacy',
            'Observability',
            'Platform Engineering',
            'Leadership',
            'Enterprise',
        ])->mapWithKeys(fn (string $name) => [$name => InterestTag::firstOrCreate(['name' => $name])]);

        $event->interestTags()->sync($tags->pluck('id')->all());

        foreach ($this->icebreakerQuestions() as $question) {
            IcebreakerQuestion::updateOrCreate(
                ['event_id' => $event->id, 'question' => $question],
                [],
            );
        }

        $sessions = $this->syncSessions($event, $eventStartsAt);
        $booths = $this->syncBooths($event, $tags);

        $this->syncParticipant(
            $event,
            $organizer,
            [
                'participant_type' => 'physical',
                'status' => 'available',
                'context_badge' => 'Event HQ',
                'icebreaker_answer' => 'Ask me what makes hybrid networking actually work.',
                'open_to_call' => false,
                'available_after_session' => false,
                'notification_mode' => 'dnd',
                'last_active_at' => $now->copy()->subMinutes(2),
            ],
        );
        $this->syncUserInterestTags($event, $organizer, $tags, [
            'Leadership',
            'Cybersecurity',
            'Platform Engineering',
        ]);

        $attendees = collect($this->attendeeDefinitions())->mapWithKeys(function (array $attendee) use ($event, $tags, $now) {
            $user = $this->upsertUser($attendee['user']);

            $this->syncParticipant(
                $event,
                $user,
                array_merge($attendee['pivot'], [
                    'last_active_at' => $attendee['pivot']['last_active_at'] ?? $now->copy()->subMinutes($attendee['minutes_ago']),
                ]),
            );

            $this->syncUserInterestTags($event, $user, $tags, $attendee['tag_names']);

            return [$attendee['key'] => $user];
        });

        $liveSession = $sessions->get('zero-trust-live');
        $aiPanel = $sessions->get('ai-panel');
        $privacySession = $sessions->get('privacy-roundtable');
        $cloudBooth = $booths->get('cloudscale');
        $privacyBooth = $booths->get('privacyfirst');
        $cyberBooth = $booths->get('cyberdefense');
        $aiBooth = $booths->get('aisecurity');

        foreach ([
            [$attendees->get('participant'), $liveSession, null],
            [$attendees->get('jonas'), $liveSession, null],
            [$attendees->get('maya'), $liveSession, null],
            [$attendees->get('elena'), $liveSession, $now->copy()->subMinutes(16)],
            [$attendees->get('nina'), $aiPanel, null],
            [$attendees->get('priya'), $privacySession, null],
        ] as [$checkedInUser, $session, $checkedOutAt]) {
            SessionCheckIn::updateOrCreate(
                [
                    'user_id' => $checkedInUser->id,
                    'event_session_id' => $session->id,
                ],
                [
                    'checked_out_at' => $checkedOutAt,
                ],
            );
        }

        $questions = collect([
            [
                'key' => 'zero-trust-rollout',
                'user' => $attendees->get('participant'),
                'session' => $liveSession,
                'body' => 'How do you phase Zero Trust into a legacy environment without freezing delivery?',
                'is_answered' => true,
            ],
            [
                'key' => 'zero-trust-buy-in',
                'user' => $attendees->get('jonas'),
                'session' => $liveSession,
                'body' => 'What finally got leadership to fund the second rollout instead of walking away?',
                'is_answered' => false,
            ],
            [
                'key' => 'opening-networking',
                'user' => $organizer,
                'session' => $sessions->get('opening-keynote'),
                'body' => 'What is the smallest networking intervention that produced measurable follow-up value?',
                'is_answered' => true,
            ],
            [
                'key' => 'ai-noise',
                'user' => $attendees->get('maya'),
                'session' => $aiPanel,
                'body' => 'Where is the line between useful AI copilots and extra alert noise?',
                'is_answered' => false,
            ],
            [
                'key' => 'platform-guardrails',
                'user' => $attendees->get('nina'),
                'session' => $sessions->get('platform-workshop'),
                'body' => 'How do you encode compliance guardrails without making developers wait on every merge?',
                'is_answered' => false,
            ],
            [
                'key' => 'privacy-review',
                'user' => $attendees->get('priya'),
                'session' => $privacySession,
                'body' => 'How are teams shortening privacy review cycles without skipping critical approvals?',
                'is_answered' => true,
            ],
        ])->mapWithKeys(function (array $question) {
            $record = SessionQuestion::updateOrCreate(
                [
                    'user_id' => $question['user']->id,
                    'event_session_id' => $question['session']->id,
                    'body' => $question['body'],
                ],
                ['is_answered' => $question['is_answered']],
            );

            return [$question['key'] => $record];
        });

        foreach ([
            [$questions->get('zero-trust-rollout'), $attendees->get('jonas')],
            [$questions->get('zero-trust-rollout'), $attendees->get('maya')],
            [$questions->get('zero-trust-rollout'), $organizer],
            [$questions->get('zero-trust-buy-in'), $attendees->get('participant')],
            [$questions->get('zero-trust-buy-in'), $organizer],
            [$questions->get('opening-networking'), $attendees->get('participant')],
            [$questions->get('ai-noise'), $attendees->get('participant')],
            [$questions->get('ai-noise'), $attendees->get('elena')],
            [$questions->get('platform-guardrails'), $attendees->get('marc')],
            [$questions->get('platform-guardrails'), $attendees->get('participant')],
            [$questions->get('privacy-review'), $attendees->get('nina')],
            [$questions->get('privacy-review'), $organizer],
        ] as [$question, $user]) {
            SessionQuestionVote::updateOrCreate(
                [
                    'session_question_id' => $question->id,
                    'user_id' => $user->id,
                ],
                [],
            );
        }

        foreach ([
            [$attendees->get('participant'), $liveSession, 'lightbulb'],
            [$attendees->get('jonas'), $liveSession, 'clap'],
            [$attendees->get('maya'), $liveSession, 'fire'],
            [$attendees->get('elena'), $aiPanel, 'question'],
            [$attendees->get('nina'), $aiPanel, 'lightbulb'],
            [$attendees->get('priya'), $privacySession, 'clap'],
        ] as [$user, $session, $type]) {
            SessionReaction::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'event_session_id' => $session->id,
                    'type' => $type,
                ],
                [],
            );
        }

        foreach ([
            [$attendees->get('participant'), $cyberBooth, $liveSession, 'physical', $now->copy()->subMinutes(14), null],
            [$attendees->get('maya'), $cyberBooth, $liveSession, 'remote', $now->copy()->subMinutes(9), null],
            [$attendees->get('elena'), $cloudBooth, $liveSession, 'remote', $now->copy()->subMinutes(18), null],
            [$attendees->get('nina'), $aiBooth, $aiPanel, 'remote', $now->copy()->subMinutes(11), null],
            [$attendees->get('priya'), $privacyBooth, $privacySession, 'physical', $now->copy()->subMinutes(12), null],
            [$attendees->get('marc'), $cloudBooth, $sessions->get('opening-keynote'), 'physical', $now->copy()->subMinutes(42), $now->copy()->subMinutes(21)],
        ] as [$user, $booth, $fromSession, $participantType, $enteredAt, $leftAt]) {
            BoothVisit::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'booth_id' => $booth->id,
                    'from_session_id' => $fromSession?->id,
                ],
                [
                    'is_anonymous' => false,
                    'participant_type' => $participantType,
                    'entered_at' => $enteredAt,
                    'left_at' => $leftAt,
                ],
            );
        }

        $cloudStaff = $cloudBooth->staff()->first();
        $cyberStaff = $cyberBooth->staff()->first();
        $privacyStaff = $privacyBooth->staff()->first();

        $cloudDemo = BoothDemo::updateOrCreate(
            [
                'booth_id' => $cloudBooth->id,
                'title' => 'Landing zone teardown',
            ],
            [
                'started_by_user_id' => $cloudStaff->id,
                'status' => 'live',
                'starts_at' => $now->copy()->subMinutes(8),
                'ended_at' => null,
            ],
        );

        BoothThread::updateOrCreate(
            [
                'booth_id' => $cloudBooth->id,
                'booth_demo_id' => $cloudDemo->id,
                'kind' => 'demo_prompt',
            ],
            [
                'user_id' => $cloudStaff->id,
                'body' => 'Landing zone teardown',
                'is_answered' => false,
                'is_pinned' => true,
                'last_activity_at' => $now->copy()->subMinutes(8),
                'follow_up_requested_at' => null,
            ],
        );

        $boothThreads = collect([
            [
                'key' => 'cyber-recap',
                'booth' => $cyberBooth,
                'user' => $attendees->get('participant'),
                'body' => 'Can I get the incident response recap after the live demo?',
                'is_pinned' => true,
                'follow_up_requested_at' => $now->copy()->subMinutes(3),
                'last_activity_at' => $now->copy()->subMinutes(3),
            ],
            [
                'key' => 'cloud-rollout',
                'booth' => $cloudBooth,
                'user' => $attendees->get('elena'),
                'body' => 'How long does a regulated landing zone rollout usually take?',
                'is_pinned' => false,
                'follow_up_requested_at' => null,
                'last_activity_at' => $now->copy()->subMinutes(6),
            ],
            [
                'key' => 'privacy-office-hours',
                'booth' => $privacyBooth,
                'user' => $attendees->get('priya'),
                'body' => 'Are you sharing the retention template after office hours?',
                'is_pinned' => false,
                'follow_up_requested_at' => null,
                'last_activity_at' => $now->copy()->subMinutes(10),
            ],
        ])->mapWithKeys(function (array $thread) {
            $record = BoothThread::updateOrCreate(
                [
                    'booth_id' => $thread['booth']->id,
                    'body' => $thread['body'],
                ],
                [
                    'user_id' => $thread['user']->id,
                    'booth_demo_id' => null,
                    'kind' => 'question',
                    'is_answered' => false,
                    'is_pinned' => $thread['is_pinned'],
                    'follow_up_requested_at' => $thread['follow_up_requested_at'],
                    'last_activity_at' => $thread['last_activity_at'],
                ],
            );

            return [$thread['key'] => $record];
        });

        foreach ([
            [$boothThreads->get('cyber-recap'), $cyberStaff, 'Yes. We will publish the recap and the handoff checklist later today.', $now->copy()->subMinutes(2)],
            [$boothThreads->get('cloud-rollout'), $cloudStaff, 'For regulated teams we usually see a 6 to 10 week first rollout, then faster waves.', $now->copy()->subMinutes(5)],
            [$boothThreads->get('privacy-office-hours'), $privacyStaff, 'Yes. Ask here after the session and we will keep answering asynchronously.', $now->copy()->subMinutes(9)],
        ] as [$thread, $staffUser, $body, $lastActivityAt]) {
            BoothThreadReply::updateOrCreate(
                [
                    'booth_thread_id' => $thread->id,
                    'user_id' => $staffUser->id,
                    'body' => $body,
                ],
                [
                    'is_staff_answer' => true,
                    'created_at' => $lastActivityAt,
                    'updated_at' => $lastActivityAt,
                ],
            );

            $thread->update([
                'is_answered' => true,
                'last_activity_at' => $lastActivityAt,
            ]);
        }

        foreach ([
            [$boothThreads->get('cyber-recap'), $attendees->get('maya')],
            [$boothThreads->get('cyber-recap'), $organizer],
            [$boothThreads->get('cloud-rollout'), $attendees->get('participant')],
            [$boothThreads->get('cloud-rollout'), $attendees->get('marc')],
            [$boothThreads->get('privacy-office-hours'), $organizer],
        ] as [$thread, $user]) {
            BoothThreadVote::updateOrCreate(
                [
                    'booth_thread_id' => $thread->id,
                    'user_id' => $user->id,
                ],
                [],
            );
        }

        $participantToMaya = $this->upsertConnection(
            $event,
            $attendees->get('participant'),
            $attendees->get('maya'),
            'Met after the Zero Trust session and decided to compare rollout playbooks.',
            true,
        );

        $participantToMarc = $this->upsertConnection(
            $event,
            $attendees->get('participant'),
            $attendees->get('marc'),
            'Continued an in-person conversation about platform engineering debt.',
            false,
        );

        $jonasToElena = $this->upsertConnection(
            $event,
            $attendees->get('jonas'),
            $attendees->get('elena'),
            'Matched around AI observability tradeoffs across remote and onsite teams.',
            true,
        );

        foreach ([
            [$participantToMaya, $attendees->get('participant'), 'Happy to compare notes after the keynote.'],
            [$participantToMaya, $attendees->get('maya'), 'Perfect. I have a remote team checklist that might help.'],
            [$participantToMarc, $attendees->get('marc'), 'Send me the session notes and I will share our backlog template.'],
        ] as [$connection, $sender, $body]) {
            Message::updateOrCreate(
                [
                    'connection_id' => $connection->id,
                    'sender_id' => $sender->id,
                    'body' => $body,
                ],
                [],
            );
        }

        foreach ([
            [$attendees->get('participant'), $attendees->get('nina'), 'pending'],
            [$attendees->get('maya'), $attendees->get('participant'), 'matched'],
            [$attendees->get('priya'), $attendees->get('participant'), 'ignored'],
        ] as [$sender, $receiver, $status]) {
            Ping::updateOrCreate(
                [
                    'event_id' => $event->id,
                    'sender_id' => $sender->id,
                    'receiver_id' => $receiver->id,
                    'status' => $status,
                ],
                [],
            );
        }

        foreach ([
            [$attendees->get('participant'), $attendees->get('nina'), 0.87, 'Shares cloud migration and observability interests', 'pending'],
            [$attendees->get('participant'), $attendees->get('priya'), 0.82, 'Both are focused on data privacy and enterprise rollout', 'accepted'],
            [$attendees->get('maya'), $attendees->get('jonas'), 0.79, 'Live session overlap plus AI and observability tags', 'pending'],
        ] as [$suggestedTo, $suggestedUser, $score, $reason, $status]) {
            Suggestion::updateOrCreate(
                [
                    'event_id' => $event->id,
                    'suggested_to_id' => $suggestedTo->id,
                    'suggested_user_id' => $suggestedUser->id,
                ],
                [
                    'score' => $score,
                    'reason' => $reason,
                    'status' => $status,
                    'trigger' => 'demo_seed',
                    'expires_at' => $now->copy()->addMinutes(30),
                ],
            );
        }

        $this->replaceDemoNotifications($event, $organizer, [
            [
                'type' => 'attendance',
                'priority' => 'high',
                'message' => 'Zero Trust Architecture in 2026 just crossed 3 active attendees.',
                'data' => ['seed' => 'demo_event', 'session_id' => $liveSession->id],
            ],
            [
                'type' => 'booth',
                'priority' => 'medium',
                'message' => 'PrivacyFirst GmbH has active visitors waiting at the booth.',
                'data' => ['seed' => 'demo_event', 'booth_id' => $privacyBooth->id],
            ],
        ]);

        $this->replaceDemoNotifications($event, $attendees->get('participant'), [
            [
                'type' => 'match',
                'priority' => 'high',
                'message' => 'Maya Patel accepted your intro request.',
                'data' => ['seed' => 'demo_event', 'connection_id' => $participantToMaya->id],
            ],
            [
                'type' => 'session',
                'priority' => 'high',
                'message' => 'Your Zero Trust question picked up new votes.',
                'data' => ['seed' => 'demo_event', 'session_id' => $liveSession->id],
            ],
            [
                'type' => 'suggestion',
                'priority' => 'medium',
                'message' => 'Nina Kaur looks like a strong match right now.',
                'data' => ['seed' => 'demo_event', 'suggested_user_id' => $attendees->get('nina')->id],
            ],
            [
                'type' => 'booth',
                'priority' => 'medium',
                'message' => 'CloudScale Solutions just started a live booth teardown.',
                'data' => ['seed' => 'demo_event', 'booth_id' => $cloudBooth->id],
            ],
        ]);

        Cache::forget("event.{$event->id}.dashboard.overview");
    }

    private function upsertUser(array $attributes): User
    {
        return User::updateOrCreate(
            ['email' => $attributes['email']],
            [
                'name' => $attributes['name'],
                'password' => Hash::make(self::DEMO_PASSWORD),
                'company' => $attributes['company'] ?? null,
                'role_title' => $attributes['role_title'] ?? null,
                'intent' => $attributes['intent'] ?? null,
                'linkedin_url' => $attributes['linkedin_url'] ?? null,
                'phone' => $attributes['phone'] ?? null,
                'is_organizer' => $attributes['is_organizer'] ?? false,
                'is_invisible' => false,
                'email_verified_at' => now(),
            ],
        );
    }

    private function syncParticipant(Event $event, User $user, array $pivot): void
    {
        $now = now();

        DB::table('event_user')->upsert(
            [[
                'event_id' => $event->id,
                'user_id' => $user->id,
                'participant_type' => $pivot['participant_type'],
                'status' => $pivot['status'],
                'context_badge' => $pivot['context_badge'] ?? null,
                'icebreaker_answer' => $pivot['icebreaker_answer'] ?? null,
                'open_to_call' => $pivot['open_to_call'] ?? false,
                'available_after_session' => $pivot['available_after_session'] ?? false,
                'notification_mode' => $pivot['notification_mode'] ?? 'normal',
                'last_active_at' => $pivot['last_active_at'] ?? $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]],
            ['event_id', 'user_id'],
            [
                'participant_type',
                'status',
                'context_badge',
                'icebreaker_answer',
                'open_to_call',
                'available_after_session',
                'notification_mode',
                'last_active_at',
                'updated_at',
            ],
        );
    }

    private function syncUserInterestTags(Event $event, User $user, Collection $tags, array $tagNames): void
    {
        DB::table('user_interest_tag')
            ->where('event_id', $event->id)
            ->where('user_id', $user->id)
            ->delete();

        DB::table('user_interest_tag')->insert(
            collect($tagNames)->map(fn (string $tagName) => [
                'user_id' => $user->id,
                'interest_tag_id' => $tags->get($tagName)->id,
                'event_id' => $event->id,
            ])->all(),
        );
    }

    private function syncSessions(Event $event, CarbonInterface $eventStartsAt): Collection
    {
        return collect([
            [
                'key' => 'opening-keynote',
                'title' => 'Opening Keynote: Why Hybrid Networking Still Breaks',
                'description' => 'Sets the stage for why the social layer matters more than the stream.',
                'speaker' => 'Dr. Sarah Chen',
                'room' => 'Main Stage',
                'starts_at' => $eventStartsAt->copy()->addMinutes(5),
                'ends_at' => $eventStartsAt->copy()->addMinutes(40),
            ],
            [
                'key' => 'zero-trust-live',
                'title' => 'Zero Trust Architecture in 2026',
                'description' => 'A practical walkthrough of rollout sequencing, governance, and stakeholder buy-in.',
                'speaker' => 'Marcus Weber',
                'room' => 'Stage A',
                'starts_at' => $eventStartsAt->copy()->addMinutes(75),
                'ends_at' => $eventStartsAt->copy()->addMinutes(160),
            ],
            [
                'key' => 'ai-panel',
                'title' => 'AI in Cybersecurity Operations',
                'description' => 'A panel on copilots, analyst fatigue, and signal quality.',
                'speaker' => 'Elena Rossi',
                'room' => 'Stage B',
                'starts_at' => $eventStartsAt->copy()->addMinutes(190),
                'ends_at' => $eventStartsAt->copy()->addMinutes(250),
            ],
            [
                'key' => 'platform-workshop',
                'title' => 'Platform Engineering for Regulated Teams',
                'description' => 'How to accelerate delivery without losing compliance traceability.',
                'speaker' => 'Nina Kaur',
                'room' => 'Workshop Room',
                'starts_at' => $eventStartsAt->copy()->addMinutes(275),
                'ends_at' => $eventStartsAt->copy()->addMinutes(335),
            ],
            [
                'key' => 'privacy-roundtable',
                'title' => 'Data Privacy Leadership Roundtable',
                'description' => 'A moderated discussion on privacy strategy across remote and onsite teams.',
                'speaker' => 'Priya Mehta',
                'room' => 'Roundtable Lounge',
                'starts_at' => $eventStartsAt->copy()->addMinutes(360),
                'ends_at' => $eventStartsAt->copy()->addMinutes(420),
            ],
        ])->mapWithKeys(function (array $session) use ($event) {
            $record = EventSession::updateOrCreate(
                [
                    'event_id' => $event->id,
                    'title' => $session['title'],
                ],
                [
                    'description' => $session['description'],
                    'speaker' => $session['speaker'],
                    'room' => $session['room'],
                    'starts_at' => $session['starts_at'],
                    'ends_at' => $session['ends_at'],
                    'qa_enabled' => true,
                    'reactions_enabled' => true,
                ],
            );

            return [$session['key'] => $record];
        });
    }

    private function syncBooths(Event $event, Collection $tags): Collection
    {
        return collect([
            [
                'key' => 'cyberdefense',
                'name' => 'CyberDefense AG Booth',
                'company' => 'CyberDefense AG',
                'description' => 'Live demos of incident response workflows and hybrid SOC handoffs.',
                'staff' => [
                    'name' => 'Ava Keller',
                    'email' => 'cyberdefense.staff@demo.test',
                    'status' => 'available',
                    'context_badge' => 'Staffing CyberDefense AG',
                ],
                'tag_names' => ['Zero Trust', 'Cybersecurity'],
                'content_links' => [
                    ['label' => 'Incident response playbook', 'url' => 'https://demo.test/cyberdefense/playbook'],
                    ['label' => 'SOC handoff checklist', 'url' => 'https://demo.test/cyberdefense/checklist'],
                ],
            ],
            [
                'key' => 'cloudscale',
                'name' => 'CloudScale Solutions Booth',
                'company' => 'CloudScale Solutions',
                'description' => 'Cloud migration playbooks, landing zones, and governance accelerators.',
                'staff' => [
                    'name' => 'Ben Fischer',
                    'email' => 'cloudscale.staff@demo.test',
                    'status' => 'available',
                    'context_badge' => 'Hosting landing zone demos',
                ],
                'tag_names' => ['Cloud Migration', 'Platform Engineering'],
                'content_links' => [
                    ['label' => 'Landing zone blueprint', 'url' => 'https://demo.test/cloudscale/landing-zones'],
                    ['label' => 'Migration governance one-pager', 'url' => 'https://demo.test/cloudscale/governance'],
                ],
            ],
            [
                'key' => 'aisecurity',
                'name' => 'AI Security Labs Booth',
                'company' => 'AI Security Labs',
                'description' => 'Model monitoring, prompt governance, and analyst copilots.',
                'staff' => [
                    'name' => 'Chloe Martin',
                    'email' => 'aisecurity.staff@demo.test',
                    'status' => 'away',
                    'context_badge' => 'Back in 5 minutes',
                ],
                'tag_names' => ['AI/ML', 'Observability'],
                'content_links' => [
                    ['label' => 'Model monitoring guide', 'url' => 'https://demo.test/aisecurity/monitoring'],
                    ['label' => 'Prompt governance worksheet', 'url' => 'https://demo.test/aisecurity/governance'],
                ],
            ],
            [
                'key' => 'privacyfirst',
                'name' => 'PrivacyFirst GmbH Booth',
                'company' => 'PrivacyFirst GmbH',
                'description' => 'Consent, retention, and privacy-by-design tooling for enterprise teams.',
                'staff' => [
                    'name' => 'David Lang',
                    'email' => 'privacyfirst.staff@demo.test',
                    'status' => 'available',
                    'context_badge' => 'Hosting privacy office hours',
                ],
                'tag_names' => ['Data Privacy', 'Enterprise'],
                'content_links' => [
                    ['label' => 'Consent lifecycle map', 'url' => 'https://demo.test/privacyfirst/consent'],
                    ['label' => 'Retention policy template', 'url' => 'https://demo.test/privacyfirst/retention'],
                ],
            ],
        ])->mapWithKeys(function (array $boothData) use ($event, $tags) {
            $booth = Booth::updateOrCreate(
                [
                    'event_id' => $event->id,
                    'name' => $boothData['name'],
                ],
                [
                    'company' => $boothData['company'],
                    'description' => $boothData['description'],
                    'content_links' => $boothData['content_links'],
                ],
            );

            $booth->interestTags()->sync(
                collect($boothData['tag_names'])->map(fn (string $tagName) => $tags->get($tagName)->id)->all(),
            );

            $staff = $this->upsertUser([
                'name' => $boothData['staff']['name'],
                'email' => $boothData['staff']['email'],
                'company' => $boothData['company'],
                'role_title' => 'Booth Staff',
                'intent' => 'Looking for attendees who want a short product demo.',
            ]);

            $booth->staff()->sync([$staff->id]);

            $this->syncParticipant(
                $event,
                $staff,
                [
                    'participant_type' => 'physical',
                    'status' => $boothData['staff']['status'],
                    'context_badge' => $boothData['staff']['context_badge'],
                    'icebreaker_answer' => 'Ask me what teams struggle with before they talk to vendors.',
                    'open_to_call' => false,
                    'available_after_session' => false,
                    'notification_mode' => 'normal',
                    'last_active_at' => now()->subMinutes(3),
                ],
            );

            $this->syncUserInterestTags($event, $staff, $tags, $boothData['tag_names']);

            return [$boothData['key'] => $booth];
        });
    }

    private function replaceDemoNotifications(Event $event, User $user, array $notifications): void
    {
        $user->notifications()
            ->get()
            ->filter(fn ($notification) => ($notification->data['seed'] ?? null) === 'demo_event')
            ->each
            ->delete();

        foreach ($notifications as $notification) {
            $user->notify(new InAppNotification(
                type: $notification['type'],
                priority: $notification['priority'],
                message: $notification['message'],
                eventId: $event->id,
                data: $notification['data'],
            ));
        }
    }

    private function upsertConnection(
        Event $event,
        User $userA,
        User $userB,
        string $context,
        bool $isCrossWorld,
    ): Connection {
        [$firstUserId, $secondUserId] = collect([$userA->id, $userB->id])->sort()->values()->all();

        return Connection::updateOrCreate(
            [
                'event_id' => $event->id,
                'user_a_id' => $firstUserId,
                'user_b_id' => $secondUserId,
            ],
            [
                'context' => $context,
                'is_cross_world' => $isCrossWorld,
            ],
        );
    }

    private function icebreakerQuestions(): array
    {
        return [
            'What is one practical thing you are hoping to take back to your team today?',
            'Which part of hybrid collaboration still feels broken to you?',
            'What is the boldest technical bet your team is making this year?',
            'If you had ten minutes with the right person here, what would you ask them?',
        ];
    }

    private function attendeeDefinitions(): array
    {
        return [
            [
                'key' => 'participant',
                'minutes_ago' => 1,
                'user' => [
                    'name' => 'Taylor Brooks',
                    'email' => self::PARTICIPANT_EMAIL,
                    'company' => 'Northstar Security',
                    'role_title' => 'Security Architect',
                    'intent' => 'Looking for zero-trust rollout lessons from teams already in production.',
                ],
                'pivot' => [
                    'participant_type' => 'physical',
                    'status' => 'available',
                    'context_badge' => 'Near Stage A',
                    'icebreaker_answer' => 'Ask me where rollout sequencing went wrong the first time.',
                    'open_to_call' => true,
                    'available_after_session' => true,
                    'notification_mode' => 'normal',
                ],
                'tag_names' => ['Zero Trust', 'Cybersecurity', 'Platform Engineering'],
            ],
            [
                'key' => 'maya',
                'minutes_ago' => 4,
                'user' => [
                    'name' => 'Maya Patel',
                    'email' => 'maya@demo.test',
                    'company' => 'SignalLayer',
                    'role_title' => 'Remote SOC Lead',
                    'intent' => 'Comparing remote incident response playbooks.',
                ],
                'pivot' => [
                    'participant_type' => 'remote',
                    'status' => 'available',
                    'context_badge' => 'Watching live from London',
                    'icebreaker_answer' => 'Tell me how your analysts avoid alert fatigue.',
                    'open_to_call' => true,
                    'available_after_session' => false,
                    'notification_mode' => 'quiet',
                ],
                'tag_names' => ['AI/ML', 'Cybersecurity', 'Observability'],
            ],
            [
                'key' => 'jonas',
                'minutes_ago' => 6,
                'user' => [
                    'name' => 'Jonas Weber',
                    'email' => 'jonas@demo.test',
                    'company' => 'Helix Systems',
                    'role_title' => 'Platform Director',
                    'intent' => 'Looking for practical migration stories from regulated teams.',
                ],
                'pivot' => [
                    'participant_type' => 'physical',
                    'status' => 'in_session',
                    'context_badge' => 'In session: Zero Trust Architecture in 2026',
                    'icebreaker_answer' => 'Ask me why platform teams become the bottleneck by accident.',
                    'open_to_call' => false,
                    'available_after_session' => true,
                    'notification_mode' => 'normal',
                ],
                'tag_names' => ['Cloud Migration', 'Platform Engineering', 'Leadership'],
            ],
            [
                'key' => 'elena',
                'minutes_ago' => 8,
                'user' => [
                    'name' => 'Elena Rossi',
                    'email' => 'elena@demo.test',
                    'company' => 'Orbit Cloud',
                    'role_title' => 'Solutions Engineer',
                    'intent' => 'Exploring how AI copilots actually land in enterprise operations.',
                ],
                'pivot' => [
                    'participant_type' => 'remote',
                    'status' => 'at_booth',
                    'context_badge' => 'At booth: CloudScale Solutions',
                    'icebreaker_answer' => 'Ask me which migration decisions still create pager load six months later.',
                    'open_to_call' => true,
                    'available_after_session' => false,
                    'notification_mode' => 'normal',
                ],
                'tag_names' => ['Cloud Migration', 'AI/ML', 'Observability'],
            ],
            [
                'key' => 'marc',
                'minutes_ago' => 10,
                'user' => [
                    'name' => 'Marc Dubois',
                    'email' => 'marc@demo.test',
                    'company' => 'BluePeak Bank',
                    'role_title' => 'Head of Infrastructure',
                    'intent' => 'Looking for vendors who understand compliance pressure.',
                ],
                'pivot' => [
                    'participant_type' => 'physical',
                    'status' => 'busy',
                    'context_badge' => 'Wrapping up a hallway conversation',
                    'icebreaker_answer' => 'Ask me what changed once our executives started reading the incident reports.',
                    'open_to_call' => false,
                    'available_after_session' => false,
                    'notification_mode' => 'dnd',
                ],
                'tag_names' => ['Enterprise', 'Leadership', 'Data Privacy'],
            ],
            [
                'key' => 'nina',
                'minutes_ago' => 3,
                'user' => [
                    'name' => 'Nina Kaur',
                    'email' => 'nina@demo.test',
                    'company' => 'Atlas Grid',
                    'role_title' => 'Staff Platform Engineer',
                    'intent' => 'Trying to meet teams who have already automated policy guardrails.',
                ],
                'pivot' => [
                    'participant_type' => 'remote',
                    'status' => 'available',
                    'context_badge' => 'Available after the AI panel',
                    'icebreaker_answer' => 'Ask me what finally convinced our developers to trust the platform.',
                    'open_to_call' => true,
                    'available_after_session' => true,
                    'notification_mode' => 'normal',
                ],
                'tag_names' => ['DevOps', 'Platform Engineering', 'Observability'],
            ],
            [
                'key' => 'priya',
                'minutes_ago' => 5,
                'user' => [
                    'name' => 'Priya Mehta',
                    'email' => 'priya@demo.test',
                    'company' => 'Clearline Health',
                    'role_title' => 'Privacy Program Lead',
                    'intent' => 'Looking for people balancing product velocity with retention and consent rules.',
                ],
                'pivot' => [
                    'participant_type' => 'physical',
                    'status' => 'available',
                    'context_badge' => 'Heading to the privacy roundtable',
                    'icebreaker_answer' => 'Ask me where privacy review still causes product teams pain.',
                    'open_to_call' => true,
                    'available_after_session' => true,
                    'notification_mode' => 'normal',
                ],
                'tag_names' => ['Data Privacy', 'Enterprise', 'Leadership'],
            ],
        ];
    }
}
