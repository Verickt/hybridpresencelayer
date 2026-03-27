# MVP — Notifications & Engagement

## Build
1. **Notification Types** — ping received, mutual match, suggestion, session starting, post-session, booth alert, nudge, milestone
2. **Push Notifications (PWA)** — rich notifications with action buttons, grouped by type
3. **In-App Notifications** — bell icon with unread count, notification drawer, actionable, auto-mark read
4. **Haptic Feedback** — vibration on ping/match, respects silent mode
5. **Frequency Limits** — high (unlimited), medium (4/hour, 20/day), low (2/hour, 10/day)
6. **Respect Boundaries** — busy/in-session/away/quiet/DND rules for delivery
7. **User Controls** — per-type toggle, quiet mode, DND, frequency slider

## Stub (documented only)
- **Smart Batching** — group multiple suggestions/pings within short windows
- **Progressive Quieting** — golden hour, active phase, taper phase, re-engagement cycle
