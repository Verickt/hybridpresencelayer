# MVP Scope (Hackathon)

## Principle

Build the smallest thing that proves the core hypothesis: **a real-time presence layer creates connections between physical and remote participants that wouldn't otherwise happen.**

## In Scope (MVP)

### 1. Magic Link Login
- Email-based passwordless auth
- Pre-filled name from invitation data
- Participant type selection (physical / remote)
- 3 interest tags from event-defined tag cloud
- Target: onboarding < 60 seconds

### 2. Presence Feed
- Real-time participant list
- Status indicators (Available / In Session / Away)
- Context badge (current session)
- Physical/Remote type indicator
- Interest tags visible on each card
- Sorted by relevance (interest overlap)

### 3. Ping
- One-tap "I'm interested" action
- Notification to recipient
- Ping-back creates mutual match
- Rate limiting (10/hour)

### 4. Mutual Connection
- When both parties ping: elevated to Connection
- Chat opens between matched participants
- Connection saved to contact list with context

### 5. Session Context
- Session schedule view
- Session check-in (manual tap for MVP, no QR)
- See who's in your session
- Post-session suggestion: "Meet these people from your session"

### 6. Simple Suggestions
- Interest-overlap based matching
- Context boost for shared sessions
- Max 3 active suggestions
- "Right Now" card at top of feed

### 7. Organizer Dashboard (Basic)
- Active participant count (physical/remote)
- Total connections made
- Cross-pollination rate
- Interaction rate

### 8. PWA Shell
- Installable PWA with manifest
- Mobile-first responsive design
- Push notification support
- Bottom tab navigation (Feed / Sessions / Connections / Profile)

## Out of Scope (Post-MVP)

| Feature | Why Deferred |
|---------|-------------|
| QR code scanning | Requires camera API setup; manual check-in works |
| 3-minute video calls | WebRTC complexity; chat proves the concept |
| Booth system | Adds complexity; sessions prove the session-context model |
| Icebreaker questions | Nice-to-have; pings work without them |
| Progressive identity enrichment | Core matching works with 3 tags; company/role/intent are enhancement |
| Intent & availability signals | "Available" status is sufficient for MVP; intent is refinement |
| Session reactions | Enhancement; core value is connections, not engagement metrics |
| Session Q&A | Separate feature; many tools already do this |
| Contact export | Post-event feature; focus on during-event first |
| Post-event follow-up nudges | During-event value proven first; follow-up is retention play |
| Serendipity mode | Algorithm refinement; basic interest matching first |
| Matchmaking bursts | Requires organizer tooling; passive suggestions first |
| Connection roulette | Novel feature; validate core matching first |
| Remote Lounge | Validate cross-world matching before adding remote-specific spaces |
| Feedback loop | Implicit; MVP uses static weights |
| Offline queue | PWA shell first; offline sync is enhancement |
| Advanced organizer actions | Basic metrics first; actions require tuning |
| Organizer event setup wizard | Manual DB setup for MVP/hackathon; wizard is productization |
| Nudge system | Can be added once engagement patterns are understood |
| i18n | English only for MVP; localization after product-market fit |
| CRM integrations | Post-MVP enterprise feature |
| Lead scoring & booth analytics | Booth system itself is post-MVP |

## MVP User Journey

```
Participant receives event link
    → Clicks magic link → Authenticated
    → Selects "Physical" or "Remote"
    → Picks 3 interest tags
    → Lands on Presence Feed
    → Sees relevant participants
    → Sees "Right Now" suggestion card
    → Pings someone interesting
    → Receives ping back → Match!
    → Opens chat → Has a conversation
    → Connection saved to list
    → Checks into a session
    → Session ends → Post-session matches shown
    → Makes more connections
```

## MVP Success Criteria

| Metric | Target |
|--------|--------|
| Onboarding < 60s | >80% of test participants |
| At least 1 connection per participant | >60% of participants |
| Cross-pollination rate | >10% (lower bar for MVP) |
| Match acceptance rate | >30% (lower bar for MVP) |
| "Would use again" in feedback | >70% |

## Hackathon Timeline (24h)

| Phase | Duration | Deliverable |
|-------|----------|-------------|
| 1. Setup & Data Model | 3h | Database schema, models, factories, auth |
| 2. Core Features | 10h | Presence feed, ping, matching, chat, sessions |
| 3. Dashboard & PWA | 6h | Organizer dashboard, PWA manifest, push |
| 4. Polish & Demo | 5h | UI polish, seed data, demo preparation |
