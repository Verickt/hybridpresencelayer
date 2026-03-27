# MVP — Vision

## Actors
All three actor types included:
- **Physical Participant**
- **Remote Participant**
- **Organizer**

## Business Model
Skipped entirely. No pricing, tiers, or payment logic.

## Analytics / KPIs
Included as part of organizer features. Track:
- Cross-pollination rate
- Match acceptance rate
- Networking density
- Time to first connection
- Onboarding completion

## Architecture
All three conceptual layers included:
1. **Presence Layer** — status, context, activity
2. **Connection Engine** — matching, suggestions
3. **Interaction Layer** — ping, chat, call

## Technical Approach
- PWA (no app store)
- Laravel + Inertia + Vue 3
- Real-time via Laravel Broadcasting
- Mobile-first
