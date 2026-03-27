# MVP — Progressive Web App (PWA)

## Build
1. **Installability** — web app manifest, icons, splash screen, standalone display mode
2. **Push Notifications** — Web Push API (VAPID), action buttons, badge count
3. **Camera Access** — QR scanning via getUserMedia, fallback to manual selection
4. **Responsive Design** — mobile-first, breakpoints 320/768/1024px
5. **Mobile-First UX** — bottom tab bar (Feed/Sessions/Connections/Profile), swipe gestures, large tap targets (48x48px min)
6. **Event Branding** — per-event name, logo, colors, splash screen via manifest

## Stub (documented only)
- **Offline Support** — service worker caching, IndexedDB queue, background sync
- **Performance Targets** — Lighthouse PWA >90, FCP <1.5s, TTI <3s
- **Service Worker Strategy** — cache-first/network-first strategies, background sync
- **Accessibility** — WCAG 2.1 AA, screen reader support, reduced motion, color-blind safe
- **Internationalization** — externalized strings, English + German, locale-aware formatting
- **iOS Push Mitigation** — add-to-homescreen prompt timing, SMS/email fallbacks
- **Graceful Degradation** — chat fallback for calls, stale indicators, precomputed suggestions, network quality indicator
