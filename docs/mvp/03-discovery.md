# MVP — Discovery & Serendipity Engine

## Build
1. **Smart Suggestions** — max 3 active, 15-min TTL, declined not repeated, accepted boost similar
2. **Context-Triggered Suggestions** — fired on session end, booth exit, idle, new participant, break time
3. **"Right Now" Cards** — time-sensitive match cards at top of feed, auto-dismiss
4. **Serendipity Mode** — opt-in toggle, 1 outside-bubble suggestion per hour, zero tag overlap + high activity
5. **Search** — by name, tag, company; relevance-ranked; secondary to discovery
6. **Matching Algorithm** — interest overlap + context match + availability scoring with configurable weights
7. **Anti-Patterns** — no repeated suggestions (2h suppress), no popularity bias, diversity enforcement (1 in 3 cross-boundary)
8. **Feedback Loop** — implicit/explicit signals from ping, dismiss, expire, conversation started
9. **Post-Session Connection Window** — 15-min high-quality matching window after sessions end

## Stub (documented only)
- **Interest Cluster View** — bubble/cloud visualization of event's social landscape
- **Scheduled Matchmaking Bursts** — organizer-triggered connection waves at breaks
- **AI-Powered Conversation Starters** — generated talking points from shared context
- **Event-Ops Integration** — venue signage, hybrid stations, host prompts, screen displays
