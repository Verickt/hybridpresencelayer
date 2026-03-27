# Feature: Micro-Interactions

## Goal

Eliminate the social barrier of "reaching out to a stranger." Every interaction starts with a gesture so small it feels risk-free.

## Core Concept

Traditional networking requires a bold first move: walk up, introduce yourself, start a conversation. This is hard enough in person — it's nearly impossible digitally. Micro-interactions solve this by making the first step trivial:

- **One tap** to express interest
- **Zero commitment** required
- **System facilitates** the next step

## Features

### Ping

The atomic unit of interaction. A single tap that says: "I'm interested in connecting."

**How it works:**
1. Participant sees someone in the feed or a suggestion card
2. Taps the Ping button (single tap, no confirmation)
3. Recipient receives a notification: "Alex pinged you"
4. Recipient can: Ping back (creates mutual match) / Ignore / Block

**Ping characteristics:**
- No message attached — pure signal, zero composition effort
- Shows sender's profile card (name, tags, context, icebreaker)
- Ping is visible only to the recipient — no public embarrassment
- Maximum 10 outgoing pings per hour (prevents spam)
- Ping expires after 30 minutes if not responded to

**Mutual Ping → Connection:**
When both participants ping each other, the system elevates to a Connection (see [05-connection.md](05-connection.md)).

### Session Reactions

Real-time reactions during sessions that serve as both engagement and discovery signals:

| Reaction | Meaning | Icon |
|----------|---------|------|
| Lightbulb | "Great insight!" | 💡 |
| Clap | "Well said!" | 👏 |
| Question | "I have a question" | ❓ |
| Fire | "This is exciting" | 🔥 |
| Think | "Hmm, interesting" | 🤔 |

**How reactions work:**
- Visible to all session participants as a live reaction stream
- Reactions are anonymous in the stream but attributable on profile hover
- Reacting to the same moment as someone creates a "shared moment" signal for matching
- The ❓ reaction optionally opens the Q&A flow (see [06-sessions.md](06-sessions.md))

### Icebreaker Prompt

System-generated conversation starters to break awkward first messages:

**Trigger points:**
- When viewing someone's profile card
- After a mutual ping / connection
- In the chat interface as a suggested first message

**Types:**
1. **Profile-based**: "You both tagged 'Zero Trust' — ask them what they're working on"
2. **Context-based**: "You're both watching the same session — what do you think of the speaker's take?"
3. **Icebreaker-based**: Shows the other person's chosen icebreaker question as a prompt
4. **Random**: Fun, low-stakes questions: "Tabs or spaces?", "Best conference snack?"

### Share Interest

A contextual micro-interaction tied to a specific topic:

- While in a session or at a booth, tap "I'm interested in this"
- Creates a visible signal: "Alex is interested in Zero Trust Architecture"
- Other participants interested in the same thing see each other
- Functions as a lightweight, topic-scoped "hand raise"

**Difference from tags:**
Tags are set during onboarding and persist. "Share Interest" is ephemeral — tied to a moment and expires after the session.

### Nudge

System-initiated micro-interactions for re-engagement:

| Scenario | Nudge | CTA |
|----------|-------|-----|
| Remote + idle 5 min | "4 people match your interests right now" | [View] |
| Physical + between sessions | "Coffee break! Meet someone new?" | [Show me] |
| Received ping, no response | "Alex is still available — interested?" | [Ping back] |
| Event milestone | "You've made 5 connections! Keep going?" | [Discover more] |

**Nudge rules:**
- Maximum 1 nudge per 15 minutes
- Nudges respect "Busy" status — never sent when busy
- Users can reduce nudge frequency in settings (Normal / Quiet / Off)
- Nudges are never sent during active sessions

## Interaction Flow Diagram

```
See someone interesting
        │
        ▼
   [Ping] ← one tap
        │
        ▼
  Recipient notified
        │
        ├── Ping back → Mutual Match → Connection
        │                                  │
        │                           [Chat / Call]
        │
        ├── Ignore → Nothing happens
        │
        └── View profile → Maybe ping later
```

## Anti-Harassment Measures

- **Rate limiting**: Max 10 pings/hour, max 30/day
- **Block**: Instant, permanent, no notification to blocked party
- **Report**: Available on every interaction, reviewed by organizer
- **No message in ping**: Cannot send unsolicited text — only a signal
- **Mutual requirement**: Chat only opens after mutual match
- **Cooldown**: If someone ignores 3 pings from you, you can't ping them again

## Design Decisions

| Decision | Rationale |
|----------|-----------|
| Ping has no message | Zero composition effort = maximum adoption |
| Mutual match required for chat | Prevents unwanted messages; both parties opt in |
| Reactions are semi-anonymous | Encourages participation without social pressure |
| Rate limiting on pings | Prevents spam while allowing generous networking |
| Nudges respect busy status | Trust the user's stated boundaries |
| Icebreakers are suggested, not forced | Some people prefer their own opener |
