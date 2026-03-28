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
            'name' => 'Event Organisator',
            'email' => self::ORGANIZER_EMAIL,
            'company' => 'Hybrid Presence Layer',
            'role_title' => 'Organisator',
            'intent' => 'Den Event reibungslos am Laufen halten.',
            'is_organizer' => true,
        ]);

        $event = Event::updateOrCreate(
            ['slug' => self::EVENT_SLUG],
            [
                'organizer_id' => $organizer->id,
                'name' => 'BSI Cyber Security Conference 2026',
                'description' => 'Ein vorbereiteter Demo-Event zum Erkunden der hybriden Präsenz-Erfahrung.',
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
                'context_badge' => 'Event-Zentrale',
                'icebreaker_answer' => 'Fragen Sie mich, was hybrides Networking wirklich funktionieren lässt.',
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
                'body' => 'Wie führt man Zero Trust in einer Legacy-Umgebung ein, ohne die Delivery zu blockieren?',
                'is_answered' => true,
            ],
            [
                'key' => 'zero-trust-buy-in',
                'user' => $attendees->get('jonas'),
                'session' => $liveSession,
                'body' => 'Was hat die Geschäftsleitung letztlich überzeugt, den zweiten Rollout zu finanzieren?',
                'is_answered' => false,
            ],
            [
                'key' => 'opening-networking',
                'user' => $organizer,
                'session' => $sessions->get('opening-keynote'),
                'body' => 'Was ist die kleinste Networking-Massnahme, die messbaren Folgewert erzeugt hat?',
                'is_answered' => true,
            ],
            [
                'key' => 'ai-noise',
                'user' => $attendees->get('maya'),
                'session' => $aiPanel,
                'body' => 'Wo liegt die Grenze zwischen nützlichen KI-Copiloten und zusätzlichem Alert-Rauschen?',
                'is_answered' => false,
            ],
            [
                'key' => 'platform-guardrails',
                'user' => $attendees->get('nina'),
                'session' => $sessions->get('platform-workshop'),
                'body' => 'Wie baut man Compliance-Leitplanken ein, ohne dass Entwickler bei jedem Merge warten müssen?',
                'is_answered' => false,
            ],
            [
                'key' => 'privacy-review',
                'user' => $attendees->get('priya'),
                'session' => $privacySession,
                'body' => 'Wie verkürzen Teams die Datenschutz-Prüfzyklen, ohne kritische Freigaben zu überspringen?',
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
                'title' => 'Landing-Zone-Teardown',
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
                'body' => 'Landing-Zone-Teardown',
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
                'body' => 'Kann ich die Incident-Response-Zusammenfassung nach der Live-Demo erhalten?',
                'is_pinned' => true,
                'follow_up_requested_at' => $now->copy()->subMinutes(3),
                'last_activity_at' => $now->copy()->subMinutes(3),
            ],
            [
                'key' => 'cloud-rollout',
                'booth' => $cloudBooth,
                'user' => $attendees->get('elena'),
                'body' => 'Wie lange dauert ein regulierter Landing-Zone-Rollout normalerweise?',
                'is_pinned' => false,
                'follow_up_requested_at' => null,
                'last_activity_at' => $now->copy()->subMinutes(6),
            ],
            [
                'key' => 'privacy-office-hours',
                'booth' => $privacyBooth,
                'user' => $attendees->get('priya'),
                'body' => 'Teilen Sie die Aufbewahrungsvorlage nach der Sprechstunde?',
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
            [$boothThreads->get('cyber-recap'), $cyberStaff, 'Ja. Wir veröffentlichen die Zusammenfassung und die Übergabe-Checkliste noch heute.', $now->copy()->subMinutes(2)],
            [$boothThreads->get('cloud-rollout'), $cloudStaff, 'Bei regulierten Teams sehen wir normalerweise 6 bis 10 Wochen für den ersten Rollout, dann schnellere Wellen.', $now->copy()->subMinutes(5)],
            [$boothThreads->get('privacy-office-hours'), $privacyStaff, 'Ja. Stellen Sie hier nach der Session Ihre Fragen und wir antworten asynchron weiter.', $now->copy()->subMinutes(9)],
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
            'Nach der Zero-Trust-Session getroffen und beschlossen, Rollout-Playbooks zu vergleichen.',
            true,
        );

        $participantToMarc = $this->upsertConnection(
            $event,
            $attendees->get('participant'),
            $attendees->get('marc'),
            'Ein persönliches Gespräch über Platform-Engineering-Schulden fortgesetzt.',
            false,
        );

        $jonasToElena = $this->upsertConnection(
            $event,
            $attendees->get('jonas'),
            $attendees->get('elena'),
            'Zusammengeführt wegen KI-Observability-Abwägungen zwischen Remote- und Vor-Ort-Teams.',
            true,
        );

        foreach ([
            [$participantToMaya, $attendees->get('participant'), 'Gerne vergleiche ich Notizen nach der Keynote.'],
            [$participantToMaya, $attendees->get('maya'), 'Perfekt. Ich habe eine Remote-Team-Checkliste, die helfen könnte.'],
            [$participantToMarc, $attendees->get('marc'), 'Schicken Sie mir die Session-Notizen und ich teile unsere Backlog-Vorlage.'],
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
            [$attendees->get('participant'), $attendees->get('nina'), 0.87, 'Gemeinsame Interessen an Cloud-Migration und Observability', 'pending'],
            [$attendees->get('participant'), $attendees->get('priya'), 0.82, 'Beide fokussiert auf Datenschutz und Enterprise-Rollout', 'accepted'],
            [$attendees->get('maya'), $attendees->get('jonas'), 0.79, 'Überschneidung bei Live-Sessions plus KI- und Observability-Tags', 'pending'],
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
                'message' => 'Zero-Trust-Architektur im Jahr 2026 hat soeben 3 aktive Teilnehmende erreicht.',
                'data' => ['seed' => 'demo_event', 'session_id' => $liveSession->id],
            ],
            [
                'type' => 'booth',
                'priority' => 'medium',
                'message' => 'PrivacyFirst GmbH hat aktive Besucher am Stand.',
                'data' => ['seed' => 'demo_event', 'booth_id' => $privacyBooth->id],
            ],
        ]);

        $this->replaceDemoNotifications($event, $attendees->get('participant'), [
            [
                'type' => 'match',
                'priority' => 'high',
                'message' => 'Maya Hartmann hat Ihre Kontaktanfrage angenommen.',
                'data' => ['seed' => 'demo_event', 'connection_id' => $participantToMaya->id],
            ],
            [
                'type' => 'session',
                'priority' => 'high',
                'message' => 'Ihre Zero-Trust-Frage hat neue Stimmen erhalten.',
                'data' => ['seed' => 'demo_event', 'session_id' => $liveSession->id],
            ],
            [
                'type' => 'suggestion',
                'priority' => 'medium',
                'message' => 'Nina Ammann scheint gerade ein starker Match zu sein.',
                'data' => ['seed' => 'demo_event', 'suggested_user_id' => $attendees->get('nina')->id],
            ],
            [
                'type' => 'booth',
                'priority' => 'medium',
                'message' => 'CloudScale Solutions hat soeben eine Live-Stand-Demo gestartet.',
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
                'title' => 'Eröffnungs-Keynote: Warum hybrides Networking immer noch scheitert',
                'description' => 'Zeigt auf, warum die soziale Ebene wichtiger ist als der Stream.',
                'speaker' => 'Dr. Sarah Brunner',
                'room' => 'Hauptbühne',
                'starts_at' => $eventStartsAt->copy()->addMinutes(5),
                'ends_at' => $eventStartsAt->copy()->addMinutes(40),
            ],
            [
                'key' => 'zero-trust-live',
                'title' => 'Zero-Trust-Architektur im Jahr 2026',
                'description' => 'Ein praxisnaher Durchgang zu Rollout-Planung, Governance und Stakeholder-Buy-in.',
                'speaker' => 'Markus Weber',
                'room' => 'Bühne A',
                'starts_at' => $eventStartsAt->copy()->addMinutes(75),
                'ends_at' => $eventStartsAt->copy()->addMinutes(160),
            ],
            [
                'key' => 'ai-panel',
                'title' => 'KI in Cybersecurity-Betrieb',
                'description' => 'Eine Podiumsdiskussion über Copiloten, Analysten-Ermüdung und Signalqualität.',
                'speaker' => 'Elena Gerber',
                'room' => 'Bühne B',
                'starts_at' => $eventStartsAt->copy()->addMinutes(190),
                'ends_at' => $eventStartsAt->copy()->addMinutes(250),
            ],
            [
                'key' => 'platform-workshop',
                'title' => 'Platform Engineering für regulierte Teams',
                'description' => 'Wie man die Delivery beschleunigt, ohne die Compliance-Nachverfolgbarkeit zu verlieren.',
                'speaker' => 'Nina Ammann',
                'room' => 'Workshop-Raum',
                'starts_at' => $eventStartsAt->copy()->addMinutes(275),
                'ends_at' => $eventStartsAt->copy()->addMinutes(335),
            ],
            [
                'key' => 'privacy-roundtable',
                'title' => 'Datenschutz-Führungsrunde',
                'description' => 'Eine moderierte Diskussion über Datenschutzstrategie für Remote- und Vor-Ort-Teams.',
                'speaker' => 'Priska Meier',
                'room' => 'Roundtable-Lounge',
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
                'description' => 'Live-Demos zu Incident-Response-Workflows und hybriden SOC-Übergaben.',
                'staff' => [
                    'name' => 'Eva Keller',
                    'email' => 'cyberdefense.staff@demo.test',
                    'status' => 'available',
                    'context_badge' => 'Am Stand von CyberDefense AG',
                ],
                'tag_names' => ['Zero Trust', 'Cybersecurity'],
                'content_links' => [
                    ['label' => 'Incident-Response-Playbook', 'url' => 'https://demo.test/cyberdefense/playbook'],
                    ['label' => 'SOC-Übergabe-Checkliste', 'url' => 'https://demo.test/cyberdefense/checklist'],
                ],
            ],
            [
                'key' => 'cloudscale',
                'name' => 'CloudScale Solutions Booth',
                'company' => 'CloudScale Solutions',
                'description' => 'Cloud-Migrations-Playbooks, Landing Zones und Governance-Beschleuniger.',
                'staff' => [
                    'name' => 'Beat Fischer',
                    'email' => 'cloudscale.staff@demo.test',
                    'status' => 'available',
                    'context_badge' => 'Zeigt Landing-Zone-Demos',
                ],
                'tag_names' => ['Cloud Migration', 'Platform Engineering'],
                'content_links' => [
                    ['label' => 'Landing-Zone-Blueprint', 'url' => 'https://demo.test/cloudscale/landing-zones'],
                    ['label' => 'Migrations-Governance-Übersicht', 'url' => 'https://demo.test/cloudscale/governance'],
                ],
            ],
            [
                'key' => 'aisecurity',
                'name' => 'AI Security Labs Booth',
                'company' => 'AI Security Labs',
                'description' => 'Modell-Monitoring, Prompt-Governance und Analysten-Copiloten.',
                'staff' => [
                    'name' => 'Chiara Müller',
                    'email' => 'aisecurity.staff@demo.test',
                    'status' => 'away',
                    'context_badge' => 'In 5 Minuten zurück',
                ],
                'tag_names' => ['AI/ML', 'Observability'],
                'content_links' => [
                    ['label' => 'Modell-Monitoring-Leitfaden', 'url' => 'https://demo.test/aisecurity/monitoring'],
                    ['label' => 'Prompt-Governance-Arbeitsblatt', 'url' => 'https://demo.test/aisecurity/governance'],
                ],
            ],
            [
                'key' => 'privacyfirst',
                'name' => 'PrivacyFirst GmbH Booth',
                'company' => 'PrivacyFirst GmbH',
                'description' => 'Einwilligung, Aufbewahrung und Privacy-by-Design-Tools für Enterprise-Teams.',
                'staff' => [
                    'name' => 'David Lang',
                    'email' => 'privacyfirst.staff@demo.test',
                    'status' => 'available',
                    'context_badge' => 'Leitet Datenschutz-Sprechstunde',
                ],
                'tag_names' => ['Data Privacy', 'Enterprise'],
                'content_links' => [
                    ['label' => 'Einwilligungs-Lifecycle-Übersicht', 'url' => 'https://demo.test/privacyfirst/consent'],
                    ['label' => 'Aufbewahrungsrichtlinien-Vorlage', 'url' => 'https://demo.test/privacyfirst/retention'],
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
                'role_title' => 'Standpersonal',
                'intent' => 'Suche Teilnehmende, die eine kurze Produktdemo möchten.',
            ]);

            $booth->staff()->sync([$staff->id]);

            $this->syncParticipant(
                $event,
                $staff,
                [
                    'participant_type' => 'physical',
                    'status' => $boothData['staff']['status'],
                    'context_badge' => $boothData['staff']['context_badge'],
                    'icebreaker_answer' => 'Fragen Sie mich, womit Teams kämpfen, bevor sie mit Anbietern sprechen.',
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
            'Was ist eine praktische Erkenntnis, die Sie heute für Ihr Team mitnehmen möchten?',
            'Welcher Teil der hybriden Zusammenarbeit fühlt sich für Sie immer noch kaputt an?',
            'Was ist die mutigste technische Wette, die Ihr Team dieses Jahr eingeht?',
            'Wenn Sie zehn Minuten mit der richtigen Person hier hätten, was würden Sie fragen?',
        ];
    }

    private function attendeeDefinitions(): array
    {
        return [
            [
                'key' => 'participant',
                'minutes_ago' => 1,
                'user' => [
                    'name' => 'Thomas Bründler',
                    'email' => self::PARTICIPANT_EMAIL,
                    'company' => 'Nordstar Security',
                    'role_title' => 'Security-Architekt',
                    'intent' => 'Suche Zero-Trust-Rollout-Erfahrungen von Teams, die bereits in Produktion sind.',
                ],
                'pivot' => [
                    'participant_type' => 'physical',
                    'status' => 'available',
                    'context_badge' => 'Nähe Bühne A',
                    'icebreaker_answer' => 'Fragen Sie mich, wo die Rollout-Reihenfolge beim ersten Mal schiefging.',
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
                    'name' => 'Maya Hartmann',
                    'email' => 'maya@demo.test',
                    'company' => 'SignalLayer',
                    'role_title' => 'Remote-SOC-Leiterin',
                    'intent' => 'Vergleiche Remote-Incident-Response-Playbooks.',
                ],
                'pivot' => [
                    'participant_type' => 'remote',
                    'status' => 'available',
                    'context_badge' => 'Schaut live aus London zu',
                    'icebreaker_answer' => 'Erzählen Sie mir, wie Ihre Analysten Alert-Ermüdung vermeiden.',
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
                    'role_title' => 'Plattform-Direktor',
                    'intent' => 'Suche praxisnahe Migrationsberichte von regulierten Teams.',
                ],
                'pivot' => [
                    'participant_type' => 'physical',
                    'status' => 'in_session',
                    'context_badge' => 'In Session: Zero-Trust-Architektur im Jahr 2026',
                    'icebreaker_answer' => 'Fragen Sie mich, warum Plattform-Teams versehentlich zum Engpass werden.',
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
                    'name' => 'Elena Gerber',
                    'email' => 'elena@demo.test',
                    'company' => 'Orbit Cloud',
                    'role_title' => 'Solutions-Ingenieurin',
                    'intent' => 'Erkunde, wie KI-Copiloten tatsächlich im Enterprise-Betrieb ankommen.',
                ],
                'pivot' => [
                    'participant_type' => 'remote',
                    'status' => 'at_booth',
                    'context_badge' => 'Am Stand: CloudScale Solutions',
                    'icebreaker_answer' => 'Fragen Sie mich, welche Migrationsentscheidungen sechs Monate später noch Pager-Last verursachen.',
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
                    'name' => 'Marc Steiner',
                    'email' => 'marc@demo.test',
                    'company' => 'BluePeak Bank',
                    'role_title' => 'Leiter Infrastruktur',
                    'intent' => 'Suche Anbieter, die Compliance-Druck verstehen.',
                ],
                'pivot' => [
                    'participant_type' => 'physical',
                    'status' => 'busy',
                    'context_badge' => 'Beendet gerade ein Flurgespräch',
                    'icebreaker_answer' => 'Fragen Sie mich, was sich änderte, als unsere Geschäftsleitung die Incident-Reports zu lesen begann.',
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
                    'name' => 'Nina Ammann',
                    'email' => 'nina@demo.test',
                    'company' => 'Atlas Grid',
                    'role_title' => 'Staff-Plattform-Ingenieurin',
                    'intent' => 'Möchte Teams treffen, die Policy-Leitplanken bereits automatisiert haben.',
                ],
                'pivot' => [
                    'participant_type' => 'remote',
                    'status' => 'available',
                    'context_badge' => 'Verfügbar nach dem KI-Panel',
                    'icebreaker_answer' => 'Fragen Sie mich, was unsere Entwickler schliesslich überzeugte, der Plattform zu vertrauen.',
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
                    'name' => 'Priska Meier',
                    'email' => 'priya@demo.test',
                    'company' => 'Clearline Health',
                    'role_title' => 'Datenschutzprogramm-Leiterin',
                    'intent' => 'Suche Personen, die Produktgeschwindigkeit mit Aufbewahrungs- und Einwilligungsregeln in Einklang bringen.',
                ],
                'pivot' => [
                    'participant_type' => 'physical',
                    'status' => 'available',
                    'context_badge' => 'Auf dem Weg zur Datenschutz-Führungsrunde',
                    'icebreaker_answer' => 'Fragen Sie mich, wo die Datenschutzprüfung Produktteams immer noch Probleme bereitet.',
                    'open_to_call' => true,
                    'available_after_session' => true,
                    'notification_mode' => 'normal',
                ],
                'tag_names' => ['Data Privacy', 'Enterprise', 'Leadership'],
            ],
        ];
    }
}
