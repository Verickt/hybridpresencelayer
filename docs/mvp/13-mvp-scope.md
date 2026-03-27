# MVP Scope (Hackathon) — Final Decisions

## Principle

Build the full platform. Features that are too work-intensive to fully implement get stubbed with documentation showing the idea. It's a hackathon — it doesn't have to be finished.

## Build vs Stub Summary

### 00 — Vision
- **Build**: All three actor types (physical participant, remote participant, organizer), all three architecture layers (presence, connection engine, interaction)
- **Skip**: Business model / pricing entirely

### 01 — Onboarding (All Build)
- Magic link auth
- Pre-filled identity from organizer import
- Participant type selection (physical/remote, switchable)
- Interest tags (pick 3 from organizer-defined cloud)
- Icebreaker selection (optional)
- QR identity code (scan-to-connect)
- Progressive identity enrichment (all phases)
- Intent & availability signals
- Organizer configuration (tags, icebreakers, import, open vs invite-only)

### 02 — Presence Layer
- **Build**: Live status (5 states, auto-transitions), context badges, participant type indicator, activity pulse, presence feed (main screen, filters, cards), remote-first features (co-watching, booth interaction, cross-world boost), privacy (invisible mode), real-time updates (WebSockets)
- **Stub**: "Right Now" highlight, low-concurrency design (warmth signals, matchmaking bursts, topic rooms, connection roulette), remote lounge, scheduled remote networking windows

### 03 — Discovery & Serendipity Engine
- **Build**: Smart suggestions (max 3, TTL, decline logic), context-triggered suggestions, "Right Now" cards, serendipity mode, search, matching algorithm (scoring + weights), anti-patterns (no repeats, no popularity bias, diversity enforcement), feedback loop signals, post-session connection window
- **Stub**: Interest cluster view (bubble visualization), scheduled matchmaking bursts, AI-powered conversation starters, event-ops integration

### 04 — Micro-Interactions
- **Build**: Ping (one-tap, mutual match), session reactions (💡👏❓🔥🤔), icebreaker prompts, share interest (ephemeral hand-raise), anti-harassment (rate limits, block, report, cooldown)
- **Stub**: Nudges (system re-engagement prompts)

### 05 — Connection & Communication
- **Build**: Mutual match, instant chat, 3-minute video call (WebRTC), connection card exchange, connection list
- **Stub**: Post-event contact export, physical↔physical location hints

### 06 — Sessions (All Build)
- Session check-in (QR / tap / click)
- Live session participants (split physical/remote, ping)
- Session Q&A (submit, upvote, attributed, networking hook)
- Session reactions stream (live across both worlds)
- Post-session matchmaking (15-min window)
- Session schedule view
- Organizer controls (create/edit, metrics, toggles, export)

### 07 — Booths
- **Build**: Virtual booth presence, booth check-in, visitor feed, staff interaction, booth ping, booth content, booth discovery, lead capture, lead dashboard, session-to-booth attribution, privacy (anonymous browsing)
- **Stub**: Lead tiering & scoring, staff routing & response SLAs, follow-up workflows (labels, emails, CRM)

### 08 — Network Building
- **Build**: Automatic contact list, context per contact, contact card
- **Stub**: Post-event summary, export options, follow-up nudges, suggested follow-up messages, connection strength indicator, cross-event identity

### 09 — Notifications
- **Build**: All notification types, push notifications (PWA), in-app notifications, haptic feedback, frequency limits, respect boundaries (busy/session/away/quiet/DND), user controls
- **Stub**: Smart batching, progressive quieting

### 10 — Organizer Dashboard
- **Build**: Real-time overview, key metrics/KPIs, session analytics, booth performance, organizer actions (boost, highlight, announce, serendipity wave, tune weights), event setup flow (6-step wizard), access control
- **Stub**: Participant segments, topic/interest clusters, timeline view, data export, deeper funnel metrics

### 11 — PWA
- **Build**: Installability (manifest, splash, standalone), push notifications, camera access (QR), responsive design, mobile-first UX (bottom tabs, gestures, large tap targets), event branding
- **Stub**: Offline support, performance targets, service worker strategy, accessibility (WCAG 2.1 AA), i18n, iOS push mitigation, graceful degradation

### 12 — Feedback Loop
- **Build**: Explicit signals (ping, not interested, block), cold start (tag-based, diverse first 3), privacy (30-day delete, no cross-event)
- **Stub**: Implicit signals, per-participant adjustment, north-star funnel metrics

### Experimental (Last)
- **Ultrasonic proximity detection** — sound-based booth proximity via Web Audio API, marked experimental

## MVP User Journey

```
Participant receives event link
    → Clicks magic link → Authenticated
    → Selects "Physical" or "Remote"
    → Picks 3 interest tags
    → Optionally picks icebreaker question
    → Lands on Presence Feed
    → Sees relevant participants with context badges
    → Sees "Right Now" suggestion card
    → Pings someone interesting
    → Receives ping back → Match!
    → Opens chat or starts 3-min call
    → Connection saved with context
    → Checks into a session (QR or tap)
    → Reacts to session moments with other participants
    → Submits/upvotes Q&A questions
    → Session ends → Post-session matches shown
    → Visits a booth (physical QR or remote click)
    → Interacts with booth staff and other visitors
    → Makes more connections
    → Organizer monitors everything on dashboard
```

## Success Criteria

| Metric | Target |
|--------|--------|
| Onboarding < 60s | >80% of test participants |
| At least 1 connection per participant | >60% of participants |
| Cross-pollination rate | >10% |
| Match acceptance rate | >30% |
| "Would use again" in feedback | >70% |
