# Feature: Presence Layer

## Goal

Make every participant visible, locatable, and contextually understandable — regardless of whether they're physical or remote.

## Core Concept

Presence answers three questions simultaneously:
1. **Who** is here? (identity + interests)
2. **Where** are they? (session, booth, hallway, remote)
3. **How active** are they? (available, busy, engaged, idle)

## Features

### Live Status

Automatic and manual status indicators:

| Status | Icon | How It's Set | Meaning |
|--------|------|-------------|---------|
| Available | Green circle | Default when active | Open to connections |
| In Session | Purple circle | Auto (session check-in) | Attending a talk, limited availability |
| At Booth | Blue circle | Auto (booth check-in) | Visiting a stand |
| Busy | Red circle | Manual toggle | Do not disturb |
| Away | Grey circle | Auto (5 min inactivity) | Inactive but still at event |

- Status auto-transitions based on behavior (check into session → "In Session")
- Manual override always available (e.g., mark "Busy" during a meeting)
- Status resets to "Available" when leaving a session or after inactivity timeout ends

### Context Badge

A dynamic label showing what the participant is doing right now:

- "Watching: Keynote — Zero Trust Architecture"
- "At Booth: CyberDefense AG"
- "In Hallway" (physical, between sessions)
- "Browsing Sessions" (remote, looking at schedule)

Context badges update in real-time and are the primary discovery signal.

### Participant Type Indicator

Visual distinction between physical and remote participants:

- **Physical**: Pin/location icon — "here in the building"
- **Remote**: Globe icon — "joining digitally"
- Always visible on presence cards, never hidden
- Creates awareness of the hybrid nature of every interaction

### Activity Pulse

A subtle, non-numeric indicator of engagement level:

- Based on recent actions: pings sent, reactions given, sessions attended, connections made
- Displayed as a glow intensity or pulse animation on the avatar
- High activity = bright pulse (this person is actively networking)
- Low activity = dim/no pulse (this person is observing)
- Never shows exact numbers — avoids gamification anxiety

### Presence Feed

The main screen of the application. A real-time stream of participants:

- **Default sort**: Relevance (shared interests + current context + availability)
- **Filters**:
  - By status (available only)
  - By type (physical / remote / all)
  - By interest tags
  - By current session
- **Each card shows**:
  - Name + initials avatar (colored by interest cluster)
  - Participant type icon (physical/remote)
  - Status indicator
  - Context badge (current session/booth)
  - Top 3 interest tags
  - Icebreaker question (if set)
  - Activity pulse
  - Ping button (primary CTA)

### "Right Now" Highlight

A special section at the top of the feed:

- "3 people in your current session share your interests"
- "Lena just finished the same session as you"
- "5 remote participants are interested in your topic"

Time-sensitive, context-driven nudges that create urgency.

## Presence for Physical Participants

- Location context is inferred from session/booth check-ins
- No GPS or Bluetooth tracking (privacy-first)
- Between sessions: status shows "In Hallway" or "Available"
- QR scan at session entry/booth updates context automatically

## Presence for Remote Participants

- Session context from clicking "Join" on a session
- Active window detection: if the event tab is in focus, status is "Active"
- Idle detection: tab not in focus for 5+ minutes → "Away"
- Remote participants get enhanced presence indicators to compensate for physical invisibility

## Real-Time Updates

- All presence data updates in real-time via WebSockets
- Presence feed refreshes without page reload
- Status changes propagate to all viewers within 1-2 seconds
- Optimistic UI: status changes appear instant locally

## Low-Concurrency Design ("Empty Room" Problem)

When few participants are active, the feed can feel dead. The platform must feel alive even with 10 people online:

### Warmth Signals
- **Aggregate activity counters**: "42 people connected today" / "18 connections made this morning"
- **Session countdowns**: "Keynote starts in 12 minutes — 8 people with your interests are attending"
- **Recent activity**: "Lena just joined" / "3 new participants in the last hour"
- **Never show** exact online count when it's low — use "people here today" instead of "people online now"

### Designed Engagement Moments
- **Matchmaking bursts**: Organizer-triggered or scheduled (e.g., every 90 min) waves of suggestions pushed to all active participants simultaneously
- **Topic rooms**: Lightweight, ephemeral chat rooms around trending topics (auto-created when 3+ people share an interest with no active session)
- **Connection roulette**: Opt-in random 1:1 pairing with a 3-min call. Guardrails: only pairs willing participants, shows shared context before accepting

### When to Deploy
- Auto-detect low engagement: if <20% of registered participants are active and interaction rate drops below 1 ping/min
- Organizer can manually trigger a "serendipity wave" from the dashboard

## Remote-First Experience

Remote participants only have this tool — physical attendees have the real event plus this. The remote experience needs parity features:

- **"Remote Lounge"**: A dedicated space in the feed showing only remote participants, encouraging remote-to-remote connections
- **Curated remote networking windows**: Organizer schedules 15-min blocks ("Remote Networking Hour") where remote participants get extra suggestions and call prompts
- **Session co-watching signals**: "12 remote participants are watching this session with you" — creates shared presence feeling
- **Priority in cross-world suggestions**: Remote participants get slightly boosted visibility to physical participants to counteract natural in-person bias

## Privacy

- Participants can go "invisible" (hidden from feed, still see others)
- No location tracking beyond session/booth check-ins
- No behavioral data exposed to other participants
- Activity pulse is approximate, never exact metrics
- Organizer sees aggregate data only, not individual tracking

## Design Decisions

| Decision | Rationale |
|----------|-----------|
| Auto-status from behavior | Reduces manual effort; status is always current |
| No exact location tracking | Privacy-first; session/booth granularity is sufficient |
| Activity pulse not numeric | Avoids competitive anxiety; encourages participation |
| Relevance-sorted feed | Manual search is secondary; discovery should feel effortless |
| "Right Now" highlights | Creates urgency and serendipity in the moment |
| Invisible mode available | Respects those who want to observe before engaging |
