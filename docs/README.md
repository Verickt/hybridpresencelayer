# Hybrid Presence Platform — Documentation

## Feature Documentation

| # | Document | Description |
|---|----------|-------------|
| 00 | [Vision](00-vision.md) | Product vision, ICP, business model, go-to-market, competitive positioning, KPIs |
| 01 | [Onboarding](01-onboarding.md) | Magic link auth, progressive identity enrichment, intent signals, QR identity |
| 02 | [Presence](02-presence.md) | Live status, context badges, activity pulse, presence feed, low-concurrency design, remote-first experience |
| 03 | [Discovery](03-discovery.md) | Smart suggestions, serendipity engine, matching algorithm, feedback loop |
| 04 | [Micro-Interactions](04-micro-interactions.md) | Ping, reactions, icebreakers, nudges |
| 05 | [Connection](05-connection.md) | Mutual match, instant chat, 3-min call, contact cards |
| 06 | [Sessions](06-sessions.md) | Check-in, live participants, Q&A, post-session matchmaking |
| 07 | [Booths](07-booths.md) | Virtual booth presence, visitor feed, lead capture |
| 08 | [Network Building](08-network-building.md) | Contact list, context per contact, export, post-event digest |
| 09 | [Notifications](09-notifications.md) | Push notifications, in-app alerts, frequency limits, user controls |
| 10 | [Organizer Dashboard](10-organizer-dashboard.md) | KPIs, participant segments, session/booth analytics, organizer actions |
| 11 | [PWA](11-pwa.md) | Installability, offline support, push, camera, performance, a11y, i18n, graceful degradation |
| 12 | [Feedback Loop](12-feedback-loop.md) | Implicit/explicit signals, algorithm adjustment, cold start, privacy |
| 13 | [MVP Scope](13-mvp-scope.md) | What's in/out for hackathon, user journey, success criteria, timeline |

## Delivery Standard

- All implementation work follows TDD: write failing tests first, then implement, then run the narrowest relevant suite.
- Do not stop at happy-path coverage. Every feature must cover relevant validation, authorization, not-found, duplicate/idempotent, rate-limit, and state-transition edge cases.
- For Inertia endpoints, add endpoint tests with `assertInertia` for component names, critical props, and sensitive data omissions.
- For user-facing pages and interactive flows, add Pest browser tests. At minimum: one smoke test (`assertNoSmoke`) and one real browser flow. Critical flows such as auth, messaging, check-in, and organizer actions need both success and failure-path browser coverage.
