# Feature: Onboarding

## Goal

Get participants from zero to active in under 60 seconds. No forms, no friction, no choices that feel like work.

## User Flow

```
Event Invitation (email/QR)
  → Magic Link (no password)
    → Pre-filled name/email from invitation data
      → Participant Type: "I'm here in person" / "I'm joining remotely"
        → Pick 3 Interest Tags (from event-specific tag cloud)
          → Choose 1 Icebreaker Question (optional, skippable)
            → Land on Presence Feed (active participant)
```

## Features

### Magic Link Authentication

- Participants receive a unique link via event invitation email
- Clicking the link authenticates immediately — no password, no registration form
- Link is single-use and time-limited (valid for event duration + 24h)
- If the participant returns later, a new magic link can be requested via email
- QR codes at the physical venue encode the same magic link flow

### Pre-filled Identity

- Name and email are pre-populated from the event registration system
- Participants confirm or edit — never type from scratch
- No profile photo required — system generates initials-based avatar
- Avatar color is derived from primary interest cluster (creates visual grouping)

### Participant Type Selection

- Binary choice: Physical or Remote
- This single selection drives the entire UX:
  - **Physical**: Gets QR scanner, location-based features, booth proximity
  - **Remote**: Gets virtual booth access, video call prompts, enhanced presence indicators
- Can be changed during the event (e.g., someone arrives late in person)

### Interest Tags

- Event organizer pre-defines a tag cloud (20-40 tags) relevant to the event
- Participants select exactly 3 tags (minimum 1, maximum 5)
- Tags are the primary input for the matching algorithm
- Tags are visible on the participant's presence card
- Examples: "Zero Trust", "Cloud Migration", "DevOps", "AI/ML", "Startup", "Enterprise"

### Icebreaker Selection

- Optional step (can be skipped)
- Choose from 3-5 pre-defined icebreaker questions
- Examples:
  - "What's the boldest tech bet you've made this year?"
  - "What brought you to this event?"
  - "What's one thing you hope to learn today?"
- The selected icebreaker is shown when someone views your profile
- Creates a conversation starter that removes social awkwardness

### QR Identity Code

- Each participant gets a unique QR code in their profile
- Physical participants can scan each other's codes to instantly connect
- QR codes are displayed at "Hybrid Stations" (physical kiosks) to bridge worlds
- Scanning triggers a mutual connection — no approval needed

## Design Decisions

| Decision | Rationale |
|----------|-----------|
| No password | Eliminates the #1 friction point for event apps |
| No profile photo | Removes vanity/effort barrier; initials avatars are sufficient |
| Exactly 3 tags | Forces prioritization; more tags = weaker signal |
| Pre-filled data | Zero typing on mobile = faster completion |
| No profile editing later | Keeps attention on connecting, not profile grooming |
| Icebreaker is optional | Respects introverts; system still works without it |

## Edge Cases

- **Participant not in invitation list**: Allow open registration with email verification
- **Participant loses magic link**: "Send me a new link" flow on login page
- **Participant switches type mid-event**: Toggle in settings, UX adapts immediately
- **Event has multiple days**: Session persists across days, no re-onboarding

## Progressive Identity Enrichment

The 60-second onboarding captures the minimum. Richer identity is collected progressively without interrupting the experience:

### Phase 1: Onboarding (0–60s)
- Name, email (pre-filled), participant type, 3 interest tags

### Phase 2: First Idle Moment (after first ping or 5 min)
- Soft prompt: "Help people find you — add your company and role?"
- Optional: company name, role/title
- Dismissible, re-accessible from profile

### Phase 3: First Connection (after first mutual match)
- Prompt to add intent: "What are you here for?" (free-text, 100 chars)
- Examples shown: "Looking for cloud migration partners", "Hiring senior engineers", "Exploring zero-trust vendors"
- Intent is shown on profile card and used as a matching signal

### Phase 4: Registration Data Import (automatic)
- If organizer uploaded attendee list with company/role/LinkedIn: auto-populate
- Participant confirms on first profile view — never asked to re-type
- LinkedIn URL enables optional profile photo import (with consent)

### Why Progressive
- 60s onboarding stays sacred — no extra fields
- Richer data dramatically improves matching quality
- Each prompt arrives at a natural pause, not during active networking
- All additional fields are optional — the product works without them

## Intent & Availability Signals

Beyond status (Available/Busy/Away), participants can broadcast intent:

- **"Open to a 3-min call now"** — shown as a badge, boosts call-ready suggestions
- **"Available after this session"** — queues them for post-session matching
- **"Looking for [X]"** — free-text intent from Phase 3, visible on card

Intent signals are more actionable than status alone. "Available" tells you nothing about whether someone wants to talk. "Looking for cloud migration partners" tells you everything.

## Organizer Configuration

- Define the interest tag cloud per event
- Define icebreaker questions per event
- Option to import participant list (name, email, company, role, LinkedIn) for pre-population
- Option to allow open registration or invitation-only
- Map registration fields to profile fields for automatic enrichment
