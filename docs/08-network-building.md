# Feature: Network Building

## Goal

Ensure every connection made during the event survives after it. The platform captures not just who you met, but the context of why — making follow-up natural and effective.

## Features

### Automatic Contact List

Every mutual match is automatically saved to the participant's contact list:

- No manual "add contact" step — connections are saved by default
- List is accessible during and after the event
- Organized chronologically with most recent first
- Quick actions on each contact: [Chat] [Call] [View Card] [Export]

### Context Per Contact

Each contact entry includes rich context:

- **How you met**: "Matched during Zero Trust keynote" / "Mutual ping from suggestion"
- **Shared interests**: Tags in common at time of connection
- **Session context**: Which session(s) you were both in
- **Interaction history**: Ping time, chat messages (count), call (duration)
- **Notes**: Optional personal notes field ("Wants to discuss implementation Q1")

This context transforms a list of names into actionable relationships.

### Contact Card

A structured digital business card per connection:

- Name, email (always present from onboarding)
- Optional fields (added by the participant): company, role, phone, LinkedIn, website
- Participant type during event (physical/remote)
- Interest tags
- Icebreaker answer

### Post-Event Summary

Automated email digest sent 24 hours after event ends:

- Total connections made
- Breakdown: physical ↔ physical, remote ↔ remote, cross-world
- List of all connections with context
- "Strongest connections" (most interaction)
- Sessions attended
- CTA: "Export your contacts" / "Follow up with your connections"

### Export Options

- **vCard (.vcf)**: Individual or bulk, importable to phone/email client
- **CSV**: Full contact list with all context fields
- **LinkedIn**: "Find [Name] on LinkedIn" deep links (not automated, privacy-respecting)
- **CRM webhook** (Enterprise): Push connections to HubSpot/Salesforce with full context
- Export available during event and for 30 days after

## Post-Event Relationship Nurturing

The event ends, but relationships shouldn't. The platform extends value beyond the event day:

### Follow-Up Nudges (7-day window)
- Day 1 (24h after event): Full summary digest email with all connections and context
- Day 3: "You haven't followed up with 4 connections — here's a suggested message for each"
- Day 7: "Last chance to export your contacts before they're archived"

### Suggested Follow-Up Messages
- Auto-generated based on interaction context:
  - "Great chatting about zero-trust at the BSI conference. Would love to continue the conversation about [shared topic]."
- Participant can edit, send via their own email client, or dismiss
- One-click "Send via email" opens pre-composed mailto: link

### Connection Strength Indicator
- Post-event, each connection is scored by interaction depth:
  - **Strong**: Extended call + 5+ chat messages + shared session
  - **Medium**: Mutual match + some chat
  - **Light**: Mutual match only
- Helps participants prioritize who to follow up with first

### Cross-Event Identity (Future Consideration)
A key product decision: should participants accumulate a "network passport" across events?

**Arguments for:**
- Reduces cold-start at future events ("You already know 5 people here")
- Creates compounding value and platform lock-in
- Enables "You met Sarah at BSI 2025 — she's attending this event too"

**Arguments against:**
- Privacy concerns around cross-event profiling
- Current design promises "each event starts fresh"
- Complicates data retention and GDPR compliance

**Decision**: Deferred to post-MVP. If implemented, must be strictly opt-in with granular control over which events share identity.

## Design Decisions

| Decision | Rationale |
|----------|-----------|
| Auto-save all connections | Removes friction; people forget to save contacts |
| Context is mandatory | A name without context is useless after 1 week |
| Personal notes field | Lets participants add their own memory anchors |
| Post-event digest | Re-engages after the event; drives follow-up |
| 30-day export window | Long enough for follow-up; creates urgency to act |
| No auto-LinkedIn connect | Privacy boundary; people should control their social graph |
