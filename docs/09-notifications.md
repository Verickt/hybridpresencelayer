# Feature: Notifications & Engagement

## Goal

Direct attention to high-value moments without overwhelming participants. Every notification should lead to an interaction.

## Notification Types

| Type | Trigger | Message Example | Priority |
|------|---------|-----------------|----------|
| Ping Received | Someone pings you | "Alex wants to connect" | High |
| Mutual Match | Both parties pinged | "It's a match! You and Lena..." | High |
| Suggestion | Discovery engine match | "You and Marcus share 3 interests" | Medium |
| Session Starting | 5 min before marked session | "Keynote starts in 5 min — 12 matches attending" | Medium |
| Post-Session | Session just ended | "4 people in that session match you" | High |
| Booth Alert | Booth staff announcement | "Live demo at CyberDefense in 5 min" | Low |
| Nudge | Idle re-engagement | "5 people match your interests right now" | Low |
| Milestone | Achievement | "You've made your 10th connection!" | Low |

## Delivery Channels

### Push Notifications (PWA)

- Primary channel for mobile participants
- Requires notification permission grant (prompted during onboarding)
- Rich notifications with action buttons: [View] [Ping Back] [Dismiss]
- Grouped by type to prevent notification flooding

### In-App Notifications

- Bell icon with unread count in the app header
- Notification drawer with full list
- Each notification is actionable (tap to navigate to relevant screen)
- Auto-mark as read when the relevant action is taken

### Haptic Feedback (Mobile)

- Subtle vibration on ping received and mutual match
- No vibration for low-priority notifications
- Respects device silent mode settings

## Notification Rules

### Frequency Limits

| Priority | Max per hour | Max per day |
|----------|-------------|-------------|
| High | Unlimited | Unlimited |
| Medium | 4 | 20 |
| Low | 2 | 10 |

### Respect Boundaries

- **Busy status**: Only high-priority notifications delivered
- **In Session**: Notifications queued, delivered at session end (except pings)
- **Away**: All notifications queued, delivered on return
- **Quiet Mode**: User-toggled, only high-priority delivered
- **Do Not Disturb**: User-toggled, no notifications at all

### Smart Batching

- If multiple suggestions are generated within 2 minutes, batch into one: "3 new suggestions"
- If multiple pings arrive within 1 minute, batch: "Alex, Lena, and 2 others pinged you"
- Post-session suggestions always batched into a single card

## Progressive Quieting

Notification effectiveness degrades over time. The system adapts:

- **Golden Hour** (first 30 min after onboarding): Higher suggestion frequency — participant is fresh and open
- **Active Phase** (30 min – 2h): Normal frequency per the limits above
- **Taper Phase** (2h+): Reduce medium/low notifications by 50% — only surface highest-quality matches
- **Re-engagement**: If participant goes idle >30 min then returns, restart the Golden Hour cycle

This prevents the "ignore all notifications" behavior that sets in after 2+ hours.

## User Controls

- **Notification preferences**: Per-type toggle (on/off)
- **Quiet Mode**: Reduces to high-priority only
- **Do Not Disturb**: Silences all notifications
- **Frequency**: Normal / Quiet / Off (global slider)

## Design Decisions

| Decision | Rationale |
|----------|-----------|
| PWA push as primary | No app install needed; works on all mobile browsers |
| Batching | Prevents notification fatigue; respects attention |
| Session queuing | Don't interrupt someone learning |
| Every notification is actionable | No information-only notifications; each one drives interaction |
| User controls granular | Trust participants to set their own boundaries |
| No email notifications during event | Too slow for real-time context; email is for post-event |
