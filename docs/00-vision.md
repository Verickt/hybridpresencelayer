# Hybrid Presence Platform — Vision

## Problem

Hybrid events create two disconnected worlds:

- **Physical participants**: High sensory density, random encounters happen naturally, but networking is inefficient and unstructured
- **Remote participants**: Passive, invisible, isolated — no spontaneous interactions possible

The result: two parallel events that never truly merge. The missing piece isn't technology for content delivery — it's **social infrastructure** that creates relationships across the physical/digital divide.

## Core Insight

Weak ties (casual, spontaneous connections) are the most valuable networking outcome of events. They happen naturally in physical spaces through hallway conversations, coffee queues, and shared reactions. **They never happen digitally** because digital tools lack presence, context, and serendipity.

## What We Build

A **real-time social layer** over hybrid events that:

1. Makes every participant visible regardless of location
2. Contextualizes people by what they're doing right now
3. Actively creates encounters between relevant strangers
4. Makes networking measurable for organizers

## What We Don't Build

- Not a streaming platform (content delivery exists)
- Not a chat app (Slack/Teams exist)
- Not an event management tool (agenda/ticketing exists)
- Not a social network (LinkedIn exists)

We build the **missing layer between all of these**: the mechanism that turns co-presence into connection.

## Product Essence

The platform is a **connection mechanism** that:

- Creates **visibility** (who is here, what are they interested in)
- Recognizes **relevance** (who should meet whom, right now)
- Triggers **encounters** (at the right moment, with minimal friction)

## Target KPIs

| Metric | Target | Description |
|--------|--------|-------------|
| Cross-Pollination Rate | >15% | % of connections that cross the physical/remote divide |
| Match Acceptance Rate | >40% | % of suggestions that lead to interaction |
| Networking Density | Measured | D = 2L / n(n-1) — ratio of actual to possible connections |
| Time to First Connection | <5 min | From login to first meaningful interaction |
| Onboarding Completion | >90% | Users who complete setup within 60 seconds |

## Architecture (Conceptual)

The platform has three conceptual layers:

```
┌─────────────────────────────────┐
│     Interaction Layer           │  How two people interact
│     (Ping, Chat, Call)          │
├─────────────────────────────────┤
│     Connection Engine           │  Who should meet whom — now
│     (Matching, Suggestions)     │
├─────────────────────────────────┤
│     Presence Layer              │  Who is here, where, doing what
│     (Status, Context, Activity) │
└─────────────────────────────────┘
```

## Guiding Principles

### Frictionless
No setup barriers. No app install (PWA). No profile forms. Magic link login → 3 tags → you're in. Under 60 seconds.

### Context-Driven
Everything is anchored to the current moment: which session is happening, which booth someone is at, what topic is trending. Context decays — suggestions are time-sensitive.

### Interaction-First
Every feature exists to create an interaction. If a feature doesn't lead to two people connecting, it doesn't belong.

### Serendipity by Design
Random encounters are not random — they are systematically manufactured through timing, context overlap, and intelligent nudges. Structured randomness.

## Actors

### Physical Participant
- Limited attention span (sensory overload)
- Needs efficient, targeted networking
- Values quality over quantity
- Uses phone in quick bursts between sessions

### Remote Participant
- Isolated and invisible by default
- Needs active inclusion mechanisms
- Has more screen time but less engagement
- Must feel like a first-class participant, not a spectator

### Organizer
- Needs ROI justification for hybrid investment
- Wants steering capability (promote connections, boost booths)
- Needs data: who connected, which sessions drove networking, booth performance
- New KPIs beyond attendance: connection quality, cross-pollination

## Ideal Customer Profile (ICP)

### Primary
- **B2B conferences & association events** with 200–2,000 attendees
- 10–40% remote participation
- Meaningful sponsor/exhibitor booths
- Professional networking is a stated event goal

### Secondary
- Innovation summits, hackathons, industry meetups
- Recurring event series (quarterly, annual)

### Anti-ICP
- Pure entertainment events (concerts, festivals)
- Events with <50 attendees (too sparse for algorithmic matching)
- Events with >90% single-mode attendance (no hybrid gap to bridge)
- Events where sponsor ROI is irrelevant

## Business Model

### Pricing Structure
- **Per-event licensing** with attendee-tier pricing (organizers pay, participants never pay)
- **Free**: ≤50 attendees, basic features (presence + ping + chat)
- **Pro**: ≤500 attendees, full features, basic analytics
- **Enterprise**: Unlimited attendees, white-label, API access, SSO, advanced analytics, CRM integrations
- **Annual contracts** for recurring event series (discount vs. per-event)

### Revenue Expansion
- **Sponsor/Booth add-ons**: Premium booth features (lead scoring, priority placement in discovery, branded cards, analytics webhooks)
- **Post-event data packages**: Anonymized aggregate analytics for organizers (networking heatmaps, session-to-connection attribution)
- **API access**: Embeddable presence widget for organizers' own platforms

### Value Proposition by Actor
- **Organizers**: Measurable networking ROI, new KPIs beyond attendance, sponsor satisfaction data
- **Sponsors/Exhibitors**: Qualified leads from both worlds, session-to-booth attribution, response SLAs
- **Participants**: Zero cost, zero friction — meet people you wouldn't have met otherwise

## Go-to-Market

1. **Start with tech conferences** — highest tolerance for new tools, most hybrid-forward audiences
2. **Partner with event platforms** (Eventbrite, Luma, Hopin) as a "networking add-on", not a competitor
3. **Free for community/non-profit events** — build word-of-mouth and case studies
4. **High-touch sales for enterprise** — event agencies, conference operators, associations
5. **Use BSI Connection Challenge as live proof-of-concept**

## Competitive Positioning

We are NOT an event platform. We are the **social infrastructure layer** that sits on top of any event setup. The moat is:

1. **Interaction graph**: Accumulated data on what makes connections work at events
2. **Outcome measurement**: The system that proves networking ROI (cross-pollination rate, lead quality, follow-up completion)
3. **Organizer playbooks**: Event-type-specific matching configurations and engagement patterns
4. **Platform integrations**: Deep connectors into registration, CRM, and streaming stacks

## Technical Approach

- **PWA** (Progressive Web App) — no app store, instant access via QR/link
- **Laravel + Inertia + Vue 3** — server-driven SPA
- **Real-time** via Laravel Broadcasting (WebSockets/Pusher)
- **Mobile-first** design — one-hand operable
- **Offline queue** — actions sync when connectivity returns
