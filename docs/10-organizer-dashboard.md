# Feature: Organizer Dashboard

## Goal

Give event organizers real-time visibility into the social health of their event. New KPIs beyond attendance: connection quality, cross-pollination, and engagement depth.

## Core Concept

The organizer dashboard is the command center for understanding whether the event's networking goals are being met. It answers: "Is this event creating real connections?"

## Features

### Real-Time Overview

A single-screen summary of the event's social health:

- **Total Active Participants**: physical + remote, with breakdown
- **Connections Made**: total mutual matches, trend line (per hour)
- **Interaction Rate**: % of participants who have pinged or been pinged
- **Cross-Pollination Rate**: % of connections that cross physical/remote boundary
- **Current Activity**: pings/minute, active chats, active calls

### Key Metrics (KPIs)

#### Networking Density
```
D = 2L / n(n-1)
```
- L = number of connections (mutual matches)
- n = number of active participants
- Displayed as a gauge: 0-100%
- Target: depends on event size, but higher = healthier

#### Cross-Pollination Rate
```
CPR = cross_world_connections / total_connections × 100
```
- A connection is "cross-world" if one participant is physical and the other is remote
- Target: >15%
- The platform's core promise metric — are we bridging the divide?

#### Match Acceptance Rate
```
MAR = accepted_suggestions / total_suggestions × 100
```
- How often discovery suggestions lead to a ping or interaction
- Target: >40%
- Low MAR = matching algorithm needs tuning

#### Engagement Depth
```
Average interactions per participant per hour
```
- Includes: pings, reactions, chats, calls
- Segmented by participant type (physical vs remote)
- Identifies if remote participants are as engaged as physical ones

#### Time to First Connection
```
Median time from onboarding completion to first mutual match
```
- Target: <5 minutes
- Long TTFC = onboarding or discovery flow needs improvement

### Participant Segments

Visual breakdown of engagement levels:

| Segment | Definition | Action |
|---------|-----------|--------|
| Super Connectors | >10 connections | Showcase as success stories |
| Active Networkers | 3-10 connections | Healthy engagement |
| Casual Participants | 1-2 connections | Normal for short events |
| Observers | 0 connections, but active | Nudge with suggestions |
| Ghosts | Onboarded but inactive | Re-engagement nudge |

### Session Analytics

Per-session breakdown:

- Check-in count (physical + remote)
- Reaction rate and peaks
- Questions submitted and upvoted
- Post-session connections generated
- "Networking score": how many connections did this session catalyze?

### Booth Performance

Per-booth breakdown:

- Total visitors (physical + remote)
- Interaction rate (% of visitors who engaged)
- Leads generated (by tier: hot/warm/cold)
- Staff response time
- Comparison across booths (ranking)

### Topic/Interest Clusters

- Visual map of interest tag distribution
- Cluster sizes and overlaps
- Identify underserved interests (high demand, few matches)
- Identify trending topics (most reactions, most connections)

### Timeline View

Chronological event activity:

- Activity graph: interactions over time
- Peak moments: highest activity periods
- Correlation with sessions: which sessions drive the most networking?
- Physical vs remote activity overlay

## Organizer Actions

The dashboard isn't just read-only — organizers can intervene:

| Action | Effect |
|--------|--------|
| Boost a booth | Increase suggestion frequency for that booth |
| Highlight a session | Promote upcoming session in participant suggestions |
| Send event-wide announcement | Push notification to all participants |
| Trigger serendipity wave | Generate extra random cross-world suggestions |
| Adjust matching weights | Tune w1/w2/w3 for the discovery algorithm |

## Data Export

- **Real-time**: Dashboard is live during the event
- **Post-event report**: PDF/CSV export with all metrics
- **Raw data**: Anonymous interaction data for custom analysis
- **Comparison**: If recurring event, compare metrics across editions

## Event Setup Flow

Before the event goes live, organizers configure everything through a setup wizard:

### Step 1: Event Details
- Event name, dates, description, branding (logo, colors, splash screen)
- Venue info (for physical component)
- Streaming platform URL (for remote component — the platform doesn't stream content)

### Step 2: Attendee Import
- Upload CSV or connect via API: Eventbrite, Luma, Meetup integrations
- Fields: name, email, company, role, participant type (physical/remote), LinkedIn URL
- Generates magic links per attendee, ready for email distribution
- Supports open registration mode (no pre-import needed)

### Step 3: Content Configuration
- Define interest tag cloud (20–40 tags)
- Define icebreaker questions (3–5)
- Create session schedule (title, time, room, speaker, description)
- Import sessions from external agenda tool (iCal, API)

### Step 4: Booth Setup
- Create booths (name, company, description, content links, interest tags)
- Assign booth staff accounts
- Configure lead capture settings and SLA targets
- Set booth tier (standard, premium/sponsor) — affects discovery placement

### Step 5: Matching Tuning
- Set matching algorithm weights (or use defaults)
- Schedule matchmaking bursts (or leave to auto-detect)
- Configure serendipity level (conservative, balanced, adventurous)

### Step 6: Review & Launch
- Preview event as participant, organizer, booth staff
- Send test magic links
- Go live

## Deeper Funnel Metrics

Beyond top-level KPIs, track the full connection funnel:

| Stage | Metric | Target |
|-------|--------|--------|
| Suggestion shown | Suggestions/participant/hour | 2–4 |
| Suggestion → Ping | Suggestion-to-ping rate | >40% |
| Ping → Mutual Match | Ping-to-match rate | >25% |
| Match → Conversation | Match-to-conversation rate | >60% |
| Conversation → Follow-up | 7-day follow-up rate | >30% |
| Sponsor lead → Accepted | Lead acceptance rate | >20% |

These funnel metrics identify exactly where the experience breaks down and what needs fixing.

## Access Control

- **Organizer**: Full dashboard access, all actions, event setup
- **Booth Staff**: Only their booth's performance data
- **Speakers**: Only their session's engagement data
- **Participants**: No access to dashboard (they see their own stats only)

## Design Decisions

| Decision | Rationale |
|----------|-----------|
| Cross-Pollination Rate as primary KPI | The platform's core value prop; must be front and center |
| Actionable dashboard | Organizers need to steer, not just observe |
| Segment-based view | Easier to act on segments than individual data points |
| Anonymous raw data export | Enables analysis without privacy violation |
| Booth comparison ranking | Creates healthy competition; drives booth engagement |
| Serendipity wave as manual trigger | Gives organizers a "networking boost button" for dead moments |
