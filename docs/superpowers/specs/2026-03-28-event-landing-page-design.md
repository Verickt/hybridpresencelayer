# Event Landing Page — Design Spec

## Problem
The `/` route shows a generic Laravel Welcome page. For a hackathon scenario, participants arriving at the app (as if scanning a QR code) should immediately see the ongoing event and be able to join with one tap + email.

## Routing Behavior

| Visitor | `GET /` behavior |
|---|---|
| Unauthenticated | Render `JoinEvent` page with event info + email form |
| Authenticated participant | Redirect to `/event/{slug}/feed` |
| Authenticated organizer | Redirect to `/event/{slug}/dashboard` |

- `/login` remains unchanged for organizers/booth staff direct access.

## Backend

### `EventLandingController@__invoke`
- If authenticated: reuse existing dashboard redirect logic (participant → feed, organizer → dashboard)
- If unauthenticated: load `Event::first()`, render `Pages/JoinEvent` with event props (`name`, `date_range`, `location`, `slug`)
- If no event exists: show a "no event" empty state

### Magic Link Flow
- The `JoinEvent` page form POSTs to the existing `/magic-link` endpoint (`MagicLinkController::send`)
- No changes needed to magic link sending logic
- After magic link authentication, participants land on the onboarding flow (screens 02-05) or feed if already onboarded

## Frontend

### `JoinEvent.vue` (new page, matches Paper screen 01)
- Event avatar/icon (first letter of event name)
- Event name, date range, location
- "Join the conversation" heading
- Subtitle: "Enter your email to get a magic link. No password needed."
- Email input field
- "Send Magic Link" primary button
- Footer text: "or scan your invitation QR code"
- Posts to `/magic-link` with the event context

### `Welcome.vue`
- Remove — replaced by `JoinEvent.vue`

## What's NOT in scope
- Onboarding flow (screens 02-05) — separate feature
- QR code scanning — the landing page itself IS the QR code destination
- Multi-event support — uses `Event::first()`
