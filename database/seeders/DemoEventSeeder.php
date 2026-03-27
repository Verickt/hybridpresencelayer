<?php

namespace Database\Seeders;

use App\Models\Booth;
use App\Models\Event;
use App\Models\EventSession;
use App\Models\IcebreakerQuestion;
use App\Models\InterestTag;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoEventSeeder extends Seeder
{
    public function run(): void
    {
        $organizer = User::factory()->organizer()->create([
            'name' => 'Event Organizer',
            'email' => 'organizer@demo.test',
        ]);

        $event = Event::factory()->live()->create([
            'organizer_id' => $organizer->id,
            'name' => 'BSI Cyber Security Conference 2026',
            'description' => 'The premier hybrid cybersecurity event bridging physical and remote participants.',
            'venue' => 'Congress Center Basel',
            'allow_open_registration' => true,
        ]);

        // Interest tags
        $tagNames = [
            'Zero Trust', 'Cloud Migration', 'DevOps', 'AI/ML', 'Startup',
            'Enterprise', 'Cybersecurity', 'Data Privacy', 'IoT', 'Blockchain',
            'Remote Work', 'Leadership', 'Open Source', 'Edge Computing', 'API Design',
            'Platform Engineering', 'Observability', 'FinTech', 'HealthTech', 'GreenTech',
        ];

        $tags = collect($tagNames)->map(fn (string $name) => InterestTag::create(['name' => $name])
        );

        $event->interestTags()->attach($tags->pluck('id'));

        // Icebreaker questions
        $icebreakers = [
            "What's the boldest tech bet you've made this year?",
            'What brought you to this event?',
            "What's one thing you hope to learn today?",
            "What's the most underrated technology right now?",
            'If you could solve one problem in your industry, what would it be?',
        ];

        foreach ($icebreakers as $question) {
            IcebreakerQuestion::create(['event_id' => $event->id, 'question' => $question]);
        }

        // Sessions
        $sessions = [
            ['title' => 'Keynote — Zero Trust Architecture in 2026', 'speaker' => 'Dr. Sarah Chen', 'room' => 'Main Stage', 'offset' => 0],
            ['title' => 'Workshop: Cloud Migration Strategies', 'speaker' => 'Marcus Weber', 'room' => 'Room A', 'offset' => 60],
            ['title' => 'Panel: AI in Cybersecurity', 'speaker' => 'Various', 'room' => 'Room B', 'offset' => 60],
            ['title' => 'Talk: DevOps Security Best Practices', 'speaker' => 'Lena Fischer', 'room' => 'Room A', 'offset' => 120],
            ['title' => 'Fireside Chat: The Future of Data Privacy', 'speaker' => 'Prof. Alex Muller', 'room' => 'Main Stage', 'offset' => 180],
        ];

        foreach ($sessions as $session) {
            EventSession::create([
                'event_id' => $event->id,
                'title' => $session['title'],
                'description' => "An engaging session about {$session['title']}.",
                'speaker' => $session['speaker'],
                'room' => $session['room'],
                'starts_at' => $event->starts_at->addMinutes($session['offset']),
                'ends_at' => $event->starts_at->addMinutes($session['offset'] + 45),
            ]);
        }

        // Booths
        $boothData = [
            ['name' => 'CyberDefense AG', 'tags' => ['Zero Trust', 'Cybersecurity']],
            ['name' => 'CloudScale Solutions', 'tags' => ['Cloud Migration', 'DevOps']],
            ['name' => 'AI Security Labs', 'tags' => ['AI/ML', 'Cybersecurity']],
            ['name' => 'PrivacyFirst GmbH', 'tags' => ['Data Privacy', 'Enterprise']],
        ];

        foreach ($boothData as $data) {
            $booth = Booth::create([
                'event_id' => $event->id,
                'name' => $data['name'].' Booth',
                'company' => $data['name'],
                'description' => "Visit {$data['name']} to learn about our solutions.",
                'content_links' => [['label' => 'Website', 'url' => 'https://example.com']],
            ]);

            $boothTagIds = $tags->filter(fn ($t) => in_array($t->name, $data['tags']))->pluck('id');
            $booth->interestTags()->attach($boothTagIds);

            // Create booth staff
            $staff = User::factory()->create(['name' => "{$data['name']} Staff"]);
            $booth->staff()->attach($staff);
            $event->participants()->attach($staff, ['participant_type' => 'physical', 'status' => 'available']);
        }

        // Create demo participants
        $physicalParticipants = User::factory(15)->withProfile()->create();
        $remoteParticipants = User::factory(10)->withProfile()->create();

        foreach ($physicalParticipants as $participant) {
            $event->participants()->attach($participant, ['participant_type' => 'physical', 'status' => 'available']);
            $participant->interestTags()->attach(
                $tags->random(3)->pluck('id'),
                ['event_id' => $event->id]
            );
        }

        foreach ($remoteParticipants as $participant) {
            $event->participants()->attach($participant, ['participant_type' => 'remote', 'status' => 'available']);
            $participant->interestTags()->attach(
                $tags->random(3)->pluck('id'),
                ['event_id' => $event->id]
            );
        }
    }
}
