# Feature: Booth / Stand Interaction

## Goal

Make exhibition booths hybrid. Physical booths get digital reach; remote participants get booth access they'd otherwise miss entirely. Booth operators get leads from both worlds.

## Core Concept

A booth is a networking node, not just a display. The platform treats booths like sessions: they have presence, participants, and generate connections. The difference is that booths are persistent (not time-boxed) and have a "staff" role.

## Features

### Virtual Booth Presence

Each booth has a digital representation in the platform:

- Booth card showing: name, company, description, interest tags
- Live visitor count: "12 physical · 8 remote visitors"
- Booth staff listed with availability status
- Related interest tags connecting booth to participant interests

### Booth Check-In

- **Physical**: Scan QR code at the booth, or tap "I'm at this booth"
- **Remote**: Click "Visit Booth" in the booth directory
- Check-in updates participant's context badge: "At Booth: CyberDefense AG"
- Creates a visit record for the booth operator (lead capture)

### Booth Visitor Feed

Real-time view of who's currently at or visiting a booth:

- Split by physical/remote
- Each visitor shows: name, interest tags, shared interests with booth topics
- Booth staff can see all visitors; visitors see each other
- Visitors can ping each other: "We're both interested in this — connect?"

### Booth Staff Interaction

Booth operators have a dedicated interface:

- See all current and recent visitors (physical + remote)
- Initiate pings to visitors (reversed flow — booth reaches out)
- Send booth-wide announcements: "Live demo starting in 5 minutes!"
- Priority indicator: highlight high-relevance visitors (based on tag overlap)

### Booth Ping

Two-way ping between booth staff and visitors:

- **Visitor → Booth**: "I'm interested — tell me more" (routed to available staff member)
- **Booth → Visitor**: "Thanks for visiting — let's connect" (from specific staff member)
- Both create standard mutual matches that lead to chat/call

### Booth Content

Digital materials accessible to all visitors:

- Links to resources (PDF, website, demo)
- Short description / pitch text
- No heavy content management — just links and text
- Available to remote visitors who can't pick up physical brochures

### Booth Discovery

How participants find relevant booths:

- Booth directory sorted by relevance to participant's interests
- "Recommended for you" section: booths matching participant tags
- "Popular now" section: booths with highest current visitor count
- Post-session: "The following booths relate to the session you just attended"

## Lead Capture

The booth operator's primary value proposition:

### What's Captured Per Visitor

- Name and email (from onboarding data)
- Interest tags
- Visit timestamp and duration
- Physical or remote
- Any interaction (ping, chat, call)
- Context: which session they came from

### Lead Dashboard (for Booth Staff)

- Total visitors: physical count + remote count
- Interaction rate: % of visitors who engaged (pinged, chatted)
- Lead list: exportable as CSV
- Hot leads: visitors who pinged or initiated chat
- Warm leads: visitors who checked in and stayed >2 minutes
- Cold leads: visitors who checked in briefly

### Lead Tiering & Scoring

Automated lead classification based on behavior:

| Tier | Criteria | Score Range |
|------|----------|-------------|
| **Hot** | Pinged booth staff, started chat/call, or visited >5 min | 80–100 |
| **Warm** | Checked in, viewed booth content, stayed 2–5 min | 40–79 |
| **Cold** | Checked in briefly (<2 min), no interaction | 1–39 |

**Scoring signals:**
- Session-to-booth attribution: "Visited after attending related session" (+20)
- Interest tag overlap with booth topics (+10 per shared tag)
- Ping or chat initiated (+30)
- Return visit (+15)
- Intent match: visitor's intent statement matches booth offering (+25)

### Staff Routing & Response SLAs

- Incoming visitor pings routed to the **least busy** available staff member
- If no staff responds within 2 minutes, escalate to next available
- Dashboard shows: average response time, unanswered pings, staff utilization
- Organizer can set SLA targets per booth tier (e.g., sponsors must respond within 3 min)

### Follow-Up Workflows

- Booth staff can tag leads with custom labels during the event ("demo requested", "decision maker", "follow up Q1")
- Post-event: automated email to hot leads: "Thanks for visiting [Booth] — here's what we discussed"
- CRM webhook: push lead data to HubSpot/Salesforce in real-time (Enterprise tier)
- Follow-up completion rate tracked in booth analytics

### Session-to-Booth Attribution

- Track which session a visitor attended before visiting the booth
- Dashboard shows: "60% of your hot leads came from the Zero Trust session"
- Enables sponsors to understand which content drives booth traffic
- Organizer uses this data to optimize session-booth pairings at future events

### Privacy

- Visitors are informed: "Visiting this booth shares your name and contact with the booth operator"
- Clear consent during booth check-in
- Visitors can "browse anonymously" (see booth content without checking in)
- Anonymous browsers don't appear in the visitor feed or lead list

## Physical-Remote Booth Dynamics

| Feature | Physical Visitor | Remote Visitor |
|---------|-----------------|----------------|
| Check-in | QR scan | Click "Visit" |
| See booth | Walk up | Digital booth card |
| Get materials | Physical handout | Digital links |
| Talk to staff | In person | Ping → Chat/Call |
| Lead captured | Yes | Yes |
| See other visitors | In the feed | In the feed |

The platform equalizes the booth experience — remote visitors generate the same lead data and interaction opportunities as physical visitors.

## Design Decisions

| Decision | Rationale |
|----------|-----------|
| Booths are like sessions | Consistent mental model; same presence/discovery patterns |
| Booth staff can initiate | Inverts the typical visitor-only ping flow; staff should be proactive |
| Anonymous browsing option | Respects privacy; not every glance should be a lead |
| Lead capture is explicit | Visitors know their data is shared; builds trust |
| Minimal content management | We're not a CMS; links are enough |
| Post-session booth suggestions | Natural flow from learning to exploring vendors |
