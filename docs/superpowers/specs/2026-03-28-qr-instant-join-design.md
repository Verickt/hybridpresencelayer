# QR Instant Join — Design Spec

## Problem
The current join flow requires entering an email, receiving a magic link, and clicking it — too slow for attendees physically at an event. Scanning a QR code at the entrance should get you in immediately.

## Overview
Organizers project the homepage on a screen at the event entrance. It displays a full-screen QR code. Attendees scan it with their phone, enter their name, and land directly in the onboarding wizard. No email required to start — email is collected as an optional step during onboarding.

## Flow

### 1. Homepage (`/`) — Projector View
- Full-screen layout designed to be projected
- Event name and initial/logo at the top
- Large QR code encoding the absolute URL of `/event/{slug}/join`
- Small link at the bottom: "Joining remotely? Tap here" → same `/event/{slug}/join` URL
- Uses `Event::first()` to determine the active event (existing pattern)
- If no event exists, show "No event is currently active"
- If user is already logged in, redirect to feed/dashboard (existing behavior)

### 2. Quick Join Page (`GET /event/{slug}/join`)
- Mobile-optimized page
- Event name/branding at top
- Single input: "What's your name?"
- "Join" button
- If user is already authenticated and attached to this event, redirect to feed
- If user is authenticated but not attached, attach and redirect to onboarding

### 3. Quick Join Submission (`POST /event/{slug}/join`)
- Validate: `name` required, string, max 255
- Create user with name only (`email` null, `password` null)
- Attach user to event via `event_user` pivot with defaults (`participant_type` null, `status` = 'available')
- Log in via `Auth::login($user)`
- Redirect to `event.onboarding.type`

### 4. Onboarding Email Step (new step between icebreaker and ready)
- Step 4 of 5 in the wizard (type → tags → icebreaker → **email** → ready)
- Heading: "Stay connected"
- Subtext: "Add your email so people can reach you after the event."
- Single email input field
- "Continue" button saves email to user record
- "Skip" link proceeds to ready screen without saving
- If email already exists on another user account, show validation error: "This email is already in use"
- `StepProgress` component updated: total steps = 5

### 5. Auth Considerations
- Session-based auth — user is logged in immediately after name submission
- No email verification required at any point
- Users who later add an email can use the existing magic link flow to log back in on a different device
- The existing magic link flow on `/` remains accessible via the "Joining remotely?" link, which leads to the same `/event/{slug}/join` page

## Files to Create
- `app/Http/Controllers/QuickJoinController.php` — handles GET (show form) and POST (create user, login, redirect)
- `resources/js/pages/Event/QuickJoin.vue` — "What's your name?" mobile page
- `resources/js/pages/Event/Onboarding/EmailCollection.vue` — optional email step

## Files to Modify
- `app/Http/Controllers/EventLandingController.php` — rework to render QR projector view instead of email form
- `resources/js/pages/JoinEvent.vue` — redesign as full-screen QR display
- `app/Http/Controllers/OnboardingController.php` — add `email()` and `saveEmail()` methods
- `routes/web.php` — add `GET/POST /event/{slug}/join` routes, add email onboarding routes
- `resources/js/components/onboarding/StepProgress.vue` — verify it works with 5 steps (it takes props, should be fine)

## Routes
```
GET  /event/{slug}/join          → QuickJoinController@show
POST /event/{slug}/join          → QuickJoinController@store
GET  /event/{slug}/onboarding/email  → OnboardingController@email
POST /event/{slug}/onboarding/email  → OnboardingController@saveEmail
```

## Validation Rules
- Quick join: `name` — required, string, max:255
- Email collection: `email` — nullable, email, max:255, unique:users,email

## Edge Cases
- User scans QR but is already logged in → redirect to feed
- User enters an email that belongs to an existing account → validation error
- Event has ended or doesn't exist → 404
- Multiple events in DB → homepage uses `Event::first()` (existing pattern)
