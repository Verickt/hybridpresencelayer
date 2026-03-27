# Feature: Discovery & Serendipity Engine

## Goal

Surface the right people at the right time without requiring manual search. Discovery should feel like fortunate coincidence, not database querying.

## Core Concept

The Discovery Engine combines three signals to generate suggestions:

```
Relevance Score = w1 × Interest_Overlap + w2 × Context_Match + w3 × Availability
```

- **Interest Overlap** (w1): Shared tags between two participants
- **Context Match** (w2): Both in same session, same booth interest, complementary goals
- **Availability** (w3): Real-time status — available people score higher

Weights are tuned per event and adjusted by the feedback loop.

## Features

### Smart Suggestions

Proactive recommendations shown in the presence feed and as notifications:

- "You and Marcus both tagged 'Zero Trust' and 'Cloud Migration'"
- "Sarah asked a question about your topic in the current session"
- "3 people at the DevOps booth share 2 of your interests"

**Suggestion rules:**
- Maximum 3 active suggestions at a time (avoid overwhelm)
- Each suggestion has a TTL (time to live) — expires after 15 minutes or when context changes
- Declined suggestions are not repeated
- Accepted suggestions boost similar future matches

### Context-Triggered Suggestions

Suggestions fired at specific event moments:

| Trigger | Suggestion Type | Example |
|---------|----------------|---------|
| Session ends | Post-session match | "4 people in that session share your interests — connect now?" |
| Booth exit | Related person | "Lisa is also interested in CyberDefense AG's offering" |
| Idle for 3+ minutes | Re-engagement | "You've been quiet — 5 people match your interests right now" |
| New participant joins | Welcome match | "Alex just joined and shares your interest in AI/ML" |
| Break time | Serendipity | "Coffee break! Here's someone you might enjoy meeting" |

### "Right Now" Cards

Time-sensitive, context-anchored suggestion cards:

- Appear at the top of the presence feed
- Full card with person's info, shared context, and action button
- Auto-dismiss after 15 minutes or when the context changes
- Maximum 1 "Right Now" card visible at a time

Example:
```
┌─────────────────────────────────────────┐
│  ⚡ Right Now                            │
│                                         │
│  Lena M. (Remote 🌐)                   │
│  Watching: Keynote — Zero Trust         │
│  Shares: #ZeroTrust #CloudMigration     │
│                                         │
│  "What's the boldest tech bet           │
│   you've made this year?"               │
│                                         │
│  [👋 Ping]  [Later]  [Not interested]   │
└─────────────────────────────────────────┘
```

### Serendipity Mode

An opt-in feature for adventurous participants:

- Toggle: "Surprise me with someone I wouldn't normally meet"
- When active: one suggestion per hour is deliberately outside the participant's interest bubble
- Algorithm picks someone with zero tag overlap but high activity
- Creates weak ties across disciplines — the most valuable event connections
- Can be turned off at any time

### Interest Cluster View

A visual overview of the event's social landscape:

- Bubble/cloud visualization where clusters are interest groups
- Size = number of participants with that interest
- Proximity = overlap between interests
- Tap a cluster to see participants in that group
- Cross-world indicator: shows physical/remote mix per cluster

### Search (Secondary)

Manual search exists but is deliberately secondary:

- Search by name, interest tag, or company
- Results are ranked by relevance score (same algorithm)
- Search is accessible but never the primary discovery path
- The platform's value is in what you *don't* have to search for

## Matching Algorithm Details

### Input Signals

1. **Explicit**: Interest tags selected during onboarding
2. **Identity**: Company, role, intent statement (from progressive enrichment)
3. **Behavioral**: Sessions attended, booths visited, pings sent/received
4. **Temporal**: Current session, time of day, event phase
5. **Social**: Mutual connections, shared interaction history
6. **Availability**: Current status, recent activity, intent signals ("open to call now")

### Scoring

For each pair of participants (A, B):

```
Interest_Overlap = |tags_A ∩ tags_B| / max(|tags_A|, |tags_B|)
Context_Match = same_session × 0.5 + same_booth × 0.3 + complementary_goals × 0.2
Availability = status_score(A) × status_score(B)

status_score:
  Available = 1.0
  In Session = 0.3
  At Booth = 0.5
  Away = 0.1
  Busy = 0.0

Final = w1 × Interest_Overlap + w2 × Context_Match + w3 × Availability
```

Default weights: w1=0.4, w2=0.35, w3=0.25

### Anti-Patterns

- **No repeated suggestions**: Once declined, a pair is suppressed for 2 hours
- **No popularity bias**: Active participants don't monopolize suggestions
- **No cold start**: Even with 0 behavioral data, interest tags provide enough signal
- **Diversity enforcement**: At least 1 in 3 suggestions crosses physical/remote boundary

## Feedback Loop

Every suggestion generates implicit or explicit feedback:

| Action | Signal | Effect |
|--------|--------|--------|
| Ping sent | Strong positive | Boost similar matches |
| Card dismissed ("Later") | Neutral | Re-suggest later in different context |
| "Not interested" | Negative | Suppress this pair, reduce weight of shared attributes |
| No action (expired) | Weak negative | Slightly reduce similar suggestions |
| Conversation started | Very strong positive | Heavily boost similar matches |

## Design Decisions

| Decision | Rationale |
|----------|-----------|
| Max 3 suggestions | Prevents decision fatigue; quality over quantity |
| 15-minute TTL | Suggestions must feel urgent and contextual |
| Serendipity mode is opt-in | Respects those who want focused networking |
| Search is secondary | Forces the product to prove its matching value |
| Cross-boundary enforcement | The product's core promise is bridging worlds |
| No popularity bias | Prevents "rich get richer" dynamics |

## Orchestrated Connection Moments

The discovery engine's highest-value mode isn't passive suggestions — it's **designed moments** where the system actively orchestrates connections:

### Scheduled Matchmaking Bursts
- Organizer pre-schedules or triggers "Connection Waves" at natural breaks (coffee, lunch, session gaps)
- All active participants simultaneously receive 1-2 high-quality matches
- Creates event-wide energy: "Everyone is connecting right now"
- Physical participants get location hints: "Meet at the coffee area"
- Remote participants get instant call prompts

### Post-Session Connection Window (15 min)
- Already defined — but elevated to the product's **signature moment**
- This is when matching quality is highest (shared context, shared reactions, high intent)
- Push notification with countdown: "Your session matches expire in 12 minutes"

### AI-Powered Conversation Starters
- Don't just match people — give them a reason to talk
- "You both attended the Zero Trust keynote and reacted to the same moment. Here's a question: What surprised you most about the speaker's approach?"
- Generated from: shared sessions, shared reactions, complementary intents, overlapping interests
- Shown on match cards and at the start of chats/calls

### Event-Ops Integration (Beyond Software)
The best hybrid networking requires physical touchpoints that the software alone can't provide:
- **Venue signage**: QR codes at coffee areas: "Scan to meet someone with your interests"
- **Hybrid stations**: Physical kiosks showing the remote participant feed — makes remote people visible in the venue
- **Host prompts**: Organizer playbook with scripted moments: "Everyone, open the app — we're doing a Connection Wave!"
- **Screen displays**: Show live connection counter, reaction streams, and "Right Now" highlights on venue screens
