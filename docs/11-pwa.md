# Feature: Progressive Web App (PWA)

## Goal

Deliver a native-app experience without app store friction. Participants access the platform instantly via a URL or QR code — no download, no install, no update.

## Why PWA

| Consideration | Native App | PWA |
|--------------|-----------|-----|
| Distribution | App store review + download | URL / QR code |
| Time to access | 2-5 minutes | Instant |
| Install friction | High (especially for one-day event) | Near zero |
| Push notifications | Yes | Yes (with permission) |
| Offline capability | Yes | Yes (service worker) |
| Camera access (QR) | Yes | Yes (modern browsers) |
| WebRTC (video calls) | Yes | Yes |
| Home screen | Default | "Add to Home Screen" prompt |

For a single-day or multi-day event, asking participants to install a native app is too high a barrier. PWA gives 95% of native capabilities with 0% of the friction.

## PWA Features

### Installability

- Web App Manifest with proper icons, splash screen, theme colors
- "Add to Home Screen" prompt after first session check-in
- Standalone display mode (no browser chrome when installed)
- Custom splash screen matching event branding

### Offline Support

Service worker caches critical assets and data:

**Always available offline:**
- App shell (UI framework, navigation)
- Participant's own profile and contact list
- Last-seen presence feed (stale data indicator shown)
- Session schedule

**Queued for sync:**
- Pings sent while offline → sent when reconnected
- Reactions → sent when reconnected
- Session check-ins → sent when reconnected
- Chat messages → sent when reconnected

**Not available offline:**
- Real-time presence updates
- Live participant feed
- Video calls
- Push notifications

### Push Notifications

- Requested during onboarding (after first meaningful action, not immediately)
- Uses Web Push API (VAPID keys)
- Notification grouping and batching (see [09-notifications.md](09-notifications.md))
- Action buttons in notifications (Ping Back, View, Dismiss)
- Badge count on home screen icon

### Camera Access

- QR code scanning for session/booth check-in
- Uses `getUserMedia` API
- Fallback: manual session selection if camera permission denied
- No photos or video recording through camera (privacy)

### Performance

- Target: Lighthouse PWA score >90
- First Contentful Paint <1.5s
- Time to Interactive <3s
- App shell cached aggressively; data fetched dynamically
- Images: initials-based avatars (SVG, no image loading)

### Responsive Design

- **Mobile-first**: Designed for one-hand operation on phone
- **Tablet**: Optimized for larger screens but same layout
- **Desktop**: Remote participants may use desktop; layout adapts
- Breakpoints: 320px / 768px / 1024px

### Device Support

- iOS Safari 16.4+ (PWA push notification support)
- Android Chrome 90+
- Desktop Chrome/Edge/Firefox latest
- No IE11, no legacy browser support

## Mobile-First UX Patterns

### Navigation

- Bottom tab bar (4 tabs max):
  1. **Feed** — Presence feed + suggestions
  2. **Sessions** — Schedule + check-in
  3. **Connections** — Contact list + active chats
  4. **Profile** — Settings + QR code

### Gestures

- Pull-to-refresh on feed
- Swipe on suggestion cards (right = ping, left = dismiss)
- Long press on participant card for quick actions
- Tap anywhere to dismiss overlays

### Large Tap Targets

- Minimum 48x48px touch targets
- Ping button is the largest element on any card
- Generous spacing between interactive elements
- No hover-dependent interactions

## Service Worker Strategy

```
Cache Strategy:
├── App Shell → Cache First (update in background)
├── API Data → Network First (fall back to cache)
├── Static Assets → Cache First
├── Images → Cache First with TTL
└── Real-time Data → Network Only
```

### Background Sync

When the participant takes actions while offline:

1. Action is stored in IndexedDB with timestamp
2. Service worker registers a sync event
3. When connectivity returns, queued actions are sent in order
4. Conflicts resolved server-side (last-write-wins for status, append for pings)
5. UI shows "Syncing..." indicator until complete

## Event Branding

PWA manifest supports per-event customization:

- Event name as app title
- Event logo as app icon
- Event color scheme as theme color
- Custom splash screen
- These are set by the organizer during event setup

## Accessibility (a11y)

Accessibility is non-negotiable for a PWA that serves diverse event audiences:

### Requirements
- **WCAG 2.1 AA** compliance as baseline
- All interactive elements keyboard-navigable
- Screen reader support: ARIA labels on all cards, buttons, notifications
- Focus management: logical tab order, visible focus indicators
- Reduced motion: respect `prefers-reduced-motion` — disable activity pulse animations, card swipe effects
- Color-blind safe: never rely on color alone for status indicators (add shape/icon: green circle + checkmark, red circle + X)
- Sufficient contrast: all text meets 4.5:1 ratio, interactive elements meet 3:1
- Touch target minimum 48x48px (already specified)

### Screen Reader Experience
- Presence cards announce: "[Name], [status], [participant type], [shared interests count] shared interests, [intent if set]"
- Notifications read as: "[Type]: [message]" with available actions
- Feed updates announced via ARIA live regions (polite, not assertive — avoid overwhelming)

## Internationalization (i18n)

Events are global. The platform must support multiple languages:

### Phase 1 (MVP+1)
- UI strings externalized (no hardcoded text)
- English and German (for BSI context)
- Date/time formatting respects locale
- Interest tags are per-event (organizer defines in event language)

### Phase 2
- French, Spanish, Italian (major European conference languages)
- RTL support consideration for Arabic/Hebrew
- Icebreaker questions localized per event language

### Implementation
- Laravel localization for backend strings
- Vue i18n for frontend
- Organizer selects event language(s) during setup
- Participant sees UI in their browser/device language if available, otherwise event default

## iOS Push Notification Reality

Web Push on iOS requires the PWA to be added to the home screen (iOS 16.4+). Most users won't do this:

### Mitigation Strategy
- **Prompt timing**: Ask to "Add to Home Screen" only after the first meaningful interaction (first ping or connection), not during onboarding
- **Explain the value**: "Add to home screen to get notified when someone wants to connect"
- **Fallback engagement**: For users who decline, use in-app notification badge + pull-to-refresh
- **SMS fallback** (Enterprise tier): Optional SMS notifications for critical events (mutual match, post-session)
- **Email fallback**: Batch digest emails every 30 min for users without push enabled

## Graceful Degradation

Conference venues often have poor WiFi. The platform must handle degraded connectivity:

- **Chat-first fallback**: If WebRTC fails for video calls, offer audio-only, then fall back to chat
- **Stale-state indicators**: When data is >30s old, show "Last updated X seconds ago" with visual dimming
- **Precomputed suggestions**: Cache next 5 suggestions locally so discovery works during brief outages
- **Idempotent sync**: All queued actions (pings, check-ins, reactions) are idempotent — safe to retry on reconnect
- **Network quality indicator**: Subtle icon showing connection strength; suggest text chat over calls on weak connections

## Design Decisions

| Decision | Rationale |
|----------|-----------|
| PWA over native | Zero install friction; critical for event adoption |
| Offline queue for actions | Conference venues often have poor WiFi |
| Camera for QR only | Clear scope; no feature creep |
| 4-tab navigation | Keeps navigation simple; everything in 1-2 taps |
| Swipe gestures on cards | Fast interaction on mobile; feels natural |
| No legacy browser support | Modern APIs (WebRTC, Push, Service Worker) are required |
| Per-event branding | Each event feels custom; builds organizer buy-in |
