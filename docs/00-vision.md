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

## Technical Approach

- **PWA** (Progressive Web App) — no app store, instant access via QR/link
- **Laravel + Inertia + Vue 3** — server-driven SPA
- **Real-time** via Laravel Broadcasting (WebSockets/Pusher)
- **Mobile-first** design — one-hand operable
- **Offline queue** — actions sync when connectivity returns
