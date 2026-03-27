# MVP — Presence Layer

## Build
1. **Live Status** — 5 states (Available, In Session, At Booth, Busy, Away), auto-transitions from behavior
2. **Context Badges** — dynamic labels ("Watching: Keynote...", "At Booth: X", "In Hallway")
3. **Participant Type Indicator** — pin icon (physical) / globe icon (remote), always visible
4. **Activity Pulse** — glow/pulse animation on avatar based on engagement level
5. **Presence Feed** — main screen, relevance-sorted, filterable by status/type/tags/session, participant cards with all info + ping button
6. **Remote-First Features** — co-watching signals, virtual booth interaction, cross-world suggestion boost
7. **Privacy** — invisible mode (hidden from feed, still see others)
8. **Real-time Updates** — WebSocket-driven, optimistic UI, 1-2s propagation

## Stub (documented only)
- **"Right Now" Highlight** — contextual nudges at top of feed
- **Low-Concurrency Design** — warmth signals, matchmaking bursts, topic rooms, connection roulette
- **Remote Lounge** — dedicated remote-only feed space
- **Scheduled Remote Networking Windows** — organizer-scheduled networking blocks

## Note
Shared interaction spaces for sessions/booths (remote + physical together) covered in 06-sessions and 07-booths.
