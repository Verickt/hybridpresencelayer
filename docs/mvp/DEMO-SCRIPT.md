# Demo Script — Hybrid Presence Platform

> **Duration**: ~8 minutes live walkthrough
> **Pre-requisites**: Run `php artisan migrate:fresh --seed --seeder=DemoEventSeeder` and `npm run build` before the demo. App is served at `http://hybridpresencelayer.test`.

---

## Setup (before you start talking)

- Open two browser windows side by side (or two tabs you can switch between)
- **Window 1**: Logged in as **participant** (`participant@demo.test` / `password`)
- **Window 2**: Logged in as **organizer** (`organizer@demo.test` / `password`)
- Have Window 1 on the **Presence Feed** and Window 2 on the **Organizer Dashboard**

---

## Act 1: The Problem (1 min — just talk, no screen)

> "Hybrid events create two disconnected worlds. Physical attendees bump into people naturally — hallway chats, coffee queues. Remote attendees? They're invisible. They watch content but can't participate in the social fabric."
>
> "The missing piece isn't video — it's social infrastructure. We built it."

---

## Act 2: The Presence Feed (1.5 min — Window 1)

**Show**: `/event/bsi-cyber-security-conference-2026/feed`

> "This is the Presence Feed — the main screen every participant sees. 12 people are live right now at BSI Cyber Security Conference 2026."

**Point out**:
- **Stats bar**: Total visible (12), Open to connect (5), filter state
- **Format/Status filters**: Filter by physical/remote, by availability status
- **Participant cards**: Each card shows name, company, physical/remote badge, status (Available/Busy), context badge ("Heading to the privacy roundtable"), interest tags, and icebreaker quote
- **Ping button**: One tap. No message to compose. Pure signal: "I'm interested."

> "Notice physical AND remote participants in the same feed. That's the core promise — remote participants are first-class, not afterthoughts."

---

## Act 3: Smart Suggestions (1 min — Window 1)

**Navigate to**: `/event/bsi-cyber-security-conference-2026/suggestions`

> "The platform doesn't just show you who's here — it tells you who you should meet."

**Point out**:
- **Match card**: Shows Nina Kaur from Atlas Grid, with reason ("Shares cloud migration and observability interests")
- **TTL countdown**: "24m left" — suggestions expire, creating gentle urgency
- **Pass / Connect buttons**: Decline or accept with one tap

> "Max 3 suggestions at a time. They expire. No spam. The algorithm enforces diversity — at least 1 in 3 crosses the physical/remote boundary."

---

## Act 4: Connections & Chat (1 min — Window 1)

**Navigate to**: `/event/bsi-cyber-security-conference-2026/connections`

> "When two people ping each other, it's a match. Here are Taylor's connections."

**Point out**:
- **Maya Patel** — Cross-world badge (physical met remote), with context: "Met after the Zero Trust session"
- **Marc Dubois** — "Continued an in-person conversation about platform engineering debt"
- **Chat buttons** on each connection

> "Every connection has context — who, where, why. This is what makes event networking measurable."

---

## Act 5: Sessions (1 min — Window 1)

**Navigate to**: `/event/bsi-cyber-security-conference-2026/sessions`

> "Five sessions are scheduled. Notice the LIVE badge on Zero Trust Architecture — that session is happening now."

**Click View** on "Zero Trust Architecture in 2026" to show the session detail.

**Point out**:
- Session check-in (QR scan or tap)
- Who's checked in (split physical/remote)
- Live reactions stream (across both worlds)
- Q&A with upvotes, attributed to the asker
- **Post-session matchmaking**: After a session ends, participants who share interests get a 15-minute connection window

> "Remote participants see the same reactions, the same Q&A. They feel present."

---

## Act 6: Booths (1 min — Window 1)

**Navigate to**: `/event/bsi-cyber-security-conference-2026/booths`

> "Four exhibitor booths. Notice the match badges — CyberDefense AG has 2 interest matches with our participant."

**Click View** on CyberDefense AG to show booth detail.

**Point out**:
- Booth description and interest tags (Zero Trust, Cybersecurity)
- Visitor feed (physical + remote visitors equally visible)
- Discussion threads — questions, votes, staff answers
- Lead capture — booth staff see every visitor, whether physical or remote

> "This is the sponsor value proposition: remote visitors generate the same lead data as physical visitors. Session-to-booth attribution tells sponsors which sessions drive their traffic."

---

## Act 7: Profile & QR Scanner (30 sec — Window 1)

**Navigate to**: `/event/bsi-cyber-security-conference-2026/profile`

> "Your event profile: name, company, role, participation type, intent statement, interest tags, and a QR scanner for instant session/booth check-in."

---

## Act 8: Organizer Dashboard (1 min — Window 2)

**Switch to Window 2** (organizer): `/event/bsi-cyber-security-conference-2026/dashboard`

> "Now the organizer's view. Real-time KPIs that no event tool provides today."

**Point out**:
- **Total active**: 12 participants
- **Connections**: 3 made
- **Interaction rate**: 33.3%
- **Cross-pollination**: 66.7% — this measures how many connections cross the physical/remote boundary
- **Session analytics**: 5 sessions with attendance metrics
- **Booth performance**: 4 booths with visitor and lead data

> "The organizer can also trigger actions: send an announcement to all participants, or trigger a serendipity wave — everyone gets a match suggestion simultaneously during coffee break."

---

## Act 9: Search (15 sec — Window 1)

**Navigate to**: `/event/bsi-cyber-security-conference-2026/search`

> "And of course, if you know who you're looking for — search by name, company, or interest tag."

---

## Closing (30 sec — no screen)

> "Three layers — presence, connection engine, interaction — that create relationships across the physical/remote divide that would never happen otherwise."
>
> "Under 60 seconds from link click to active participant. No app install. No passwords. No friction."
>
> "Built with Laravel, Vue, WebSockets, and WebRTC. Fully real-time. Mobile-first PWA."

---

## If Judges Ask...

| Question | Answer |
|----------|--------|
| "How is this different from Hopin?" | Hopin streams content. We don't touch content delivery. We're the networking layer that sits on top of any event setup. |
| "How is this different from LinkedIn?" | LinkedIn is a persistent social network. We're ephemeral — scoped to a single event, focused on real-time context. |
| "How do you prevent harassment?" | No unsolicited messages — chat requires mutual match. Ping has no message. Rate limiting, block, report, cooldowns. |
| "Why PWA over native?" | For a one-day event, asking people to install an app is too much friction. PWA gives push notifications, camera, home screen — zero install. |
| "What's the business model?" | Per-event licensing for organizers. Premium sponsor analytics tier. Free for participants. |
| "What about scale?" | WebSocket broadcasting handles thousands of concurrent users. The matching algorithm runs server-side with configurable weights. |
