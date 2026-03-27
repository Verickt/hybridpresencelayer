# Feature: Session Integration

## Goal

Transform sessions (talks, panels, workshops) from passive content consumption into active networking nodes. A session isn't just something you watch — it's a context that connects you to relevant people.

## Core Concept

Sessions create the strongest contextual signal: "These people care about the same topic right now." The platform uses this signal to drive discovery and connections during and after sessions.

## Features

### Session Check-In

How participants indicate they're attending a session:

- **Physical**: Scan QR code at session room entrance, or tap "I'm here" in the session view
- **Remote**: Click "Join" on the session in the event schedule
- Check-in updates the participant's context badge and status to "In Session"
- Check-out is automatic when checking into a different session or after session end time

### Live Session Participants

A real-time view of who's in the current session:

- Split view: Physical attendees (pin icon) and Remote attendees (globe icon)
- Count displayed prominently: "47 here · 23 remote"
- Participant list sorted by interest overlap with the viewer
- Each participant shows: name, type, shared tags, ping button
- Highlights "people you might want to meet" based on matching algorithm

### Session Q&A

Interactive question functionality tied to the session:

- Any participant (physical or remote) can submit a question
- Questions are visible to all session participants
- Upvote system: best questions rise to the top
- Speaker/moderator can mark questions as "answered"
- Questions are attributed to the asker with their profile card

**Networking hook:**
- Asking a question makes you visible: "Sarah asked about zero-trust implementation"
- Other participants can ping question-askers directly
- Questions become discovery signals: "3 people are interested in your question"

### Session Reactions Stream

A live visualization of participant engagement:

- Reactions (💡👏❓🔥🤔) flow across the session view in real-time
- Creates a sense of shared experience for remote participants
- Reaction peaks are logged and can trigger suggestions: "This moment excited 15 people"
- Optional: displayed on a screen in the physical room (organizer choice)

### Post-Session Matchmaking

The highest-value moment: immediately after a session ends:

- Push notification: "Session ended — here are people you should meet"
- Shows 3-5 participants from the same session with highest relevance scores
- Boosted by: shared reactions at the same moments, similar questions asked, tag overlap
- Time-limited: suggestions expire 15 minutes after session end
- Physical participants get: "Meet them at the coffee area nearby"
- Remote participants get: "Start a 3-min call now?"

### Session Schedule View

The event's session schedule within the platform:

- List of all sessions with times, speakers, descriptions
- Each session shows: attendee count preview, interest tag overlap
- "X people with your interests are attending" — encourages relevant attendance
- Reminder notifications 5 minutes before sessions the participant marked
- No full agenda management — links to external agenda if available

## Session Data Flow

```
Session Created (by organizer)
    │
    ▼
Participants Check In (QR / tap / click)
    │
    ▼
Live Participant List Updated
    │
    ├── Reactions flow in real-time
    ├── Questions submitted and upvoted
    ├── Discovery suggestions generated
    │
    ▼
Session Ends
    │
    ▼
Post-Session Matchmaking (15-min window)
    │
    ▼
Connections formed from session context
```

## Physical vs Remote Session Experience

| Aspect | Physical | Remote |
|--------|----------|--------|
| Check-in | QR scan or manual | Click "Join" |
| Content | Watch in person | Not provided by platform (external stream) |
| Reactions | Sent from phone | Sent from browser |
| Q&A | Submit from phone | Submit from browser |
| Participant view | See who else is here | See who else is watching |
| Post-session | "Meet at coffee area" | "Start a 3-min call" |

**Important**: The platform does NOT stream session content. It's the social layer around sessions, not a replacement for the content delivery platform.

## Design Decisions

| Decision | Rationale |
|----------|-----------|
| No content streaming | Not our job; avoid competing with established platforms |
| QR check-in for physical | Fast, frictionless, doesn't require manual selection |
| Post-session window is 15 min | Long enough to act, short enough to feel urgent |
| Questions are attributed | Makes Q&A a networking tool, not just a content tool |
| Reaction stream is visual | Creates shared experience feeling for remote participants |
| Session suggestions are boosted | Same session = strong contextual signal for matching |

## Organizer Controls

- Create/edit sessions (title, description, time, room, speaker)
- View per-session engagement metrics (check-ins, reactions, questions, connections)
- Toggle Q&A on/off per session
- Toggle reaction stream display for physical room screen
- Export session analytics
