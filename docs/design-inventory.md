# Hybrid Presence Layer — Design Inventory

Complete design system and screen inventory for Paper. Organized by **design phases** with primitives first, then composed domain components, then screens grouped by workflow.

---

## Phase 1: Design System Foundations & Primitives

### Typography (Inter)
| Token | Size | Weight | Use |
|-------|------|--------|-----|
| Display | 34px | Bold | Hero text, event name |
| H1 | 28px | Bold | Page titles |
| H2 | 22px | Semibold | Section headers |
| H3 | 18px | Semibold | Card titles, names |
| Body L | 16px | Regular | Primary body text |
| Body | 14px | Regular | Secondary text, descriptions |
| Caption | 12px | Medium | Labels, timestamps, badges |
| Overline | 11px | Semibold (uppercase) | Category labels |

### Color Palette
| Token | Value | Use |
|-------|-------|-----|
| Indigo 50 | #EEF2FF | Subtle backgrounds |
| Indigo 100 | #E0E7FF | Hover states, tag backgrounds |
| Indigo 200 | #C7D2FE | Borders, dividers |
| Indigo 400 | #818CF8 | Secondary accent |
| Indigo 500 | #6366F1 | Primary accent |
| Indigo 600 | #4F46E5 | Primary buttons, links |
| Indigo 700 | #4338CA | Pressed states |
| Indigo 900 | #312E81 | Dark accent text |
| Neutral 50 | #FAFAFA | Page background |
| Neutral 100 | #F5F5F5 | Card backgrounds |
| Neutral 200 | #E5E5E5 | Borders, dividers |
| Neutral 300 | #D4D4D4 | Disabled states |
| Neutral 400 | #A3A3A3 | Placeholder text |
| Neutral 500 | #737373 | Secondary text |
| Neutral 600 | #525252 | Body text |
| Neutral 800 | #262626 | Primary text |
| Neutral 900 | #171717 | Headings |
| Green 500 | #22C55E | Available status, success |
| Amber 500 | #F59E0B | Warning, busy status |
| Red 500 | #EF4444 | Error, destructive |
| Blue 500 | #3B82F6 | Info, in-session status |
| Purple 500 | #A855F7 | At-booth status |

### Interest-Cluster Avatar Colors
| Cluster | Background | Text |
|---------|------------|------|
| Tech/Engineering | Indigo 100 | Indigo 700 |
| Business/Strategy | Emerald 100 | Emerald 700 |
| Design/Creative | Pink 100 | Pink 700 |
| Marketing/Growth | Amber 100 | Amber 700 |
| Science/Research | Cyan 100 | Cyan 700 |
| Community/People | Rose 100 | Rose 700 |

### Spacing
4px base grid: 4, 8, 12, 16, 20, 24, 32, 40, 48, 64

### Radii
| Token | Value | Use |
|-------|-------|-----|
| sm | 6px | Small chips, tags |
| md | 10px | Cards, inputs |
| lg | 14px | Modals, sheets |
| xl | 20px | Large containers |
| full | 999px | Avatars, pills |

### Shadows
| Token | Value | Use |
|-------|-------|-----|
| subtle | 0 1px 2px rgba(0,0,0,0.05) | Cards |
| medium | 0 4px 12px rgba(0,0,0,0.1) | Elevated cards, dropdowns |
| strong | 0 8px 24px rgba(0,0,0,0.15) | Modals, sheets |

### Reusable Primitives

These are the building blocks that compose into all domain-specific components.

#### Avatar
- **Sizes**: sm (32px), md (40px), lg (56px), xl (80px)
- **Content**: 2-letter initials from name
- **Background**: Interest-cluster color
- **States**: Default, activity pulse (animated glow), invisible (dimmed)

#### Badge
- **Variants**: Status dot (8px, color-coded), type badge (pin/globe icon), count badge (red circle + number), context badge (pill with icon + label)
- **Status colors**: Available (green), In Session (blue), At Booth (purple), Busy (amber), Away (gray)
- **Context badge examples**: "Watching: [Session]", "At Booth: [Booth]", "In Hallway"

#### Chip
- **Variants**: Interest tag (default/matched/selected), filter chip (active/inactive), reaction chip (emoji)
- **Default**: Neutral 100 bg, Neutral 600 text
- **Matched**: Indigo 100 bg, Indigo 700 text
- **Selected/Active**: Indigo 500 bg, white text

#### Card Shell
- **Style**: Neutral 100 bg, radius-md, shadow-subtle
- **Variants**: Default, elevated (shadow-medium), highlighted (indigo border), dimmed (ended/inactive)
- **Layout**: Flexible content area — all domain cards compose on this

#### List Item
- **Style**: Full-width row, 16px vertical padding, optional divider
- **Layout**: Leading (avatar/icon) + content (title, subtitle, caption) + trailing (action/badge)

#### Stat Tile
- **Content**: Label (caption), value (display/H1), trend indicator (↑↓), subtitle
- **Sizes**: Large (hero), medium (grid), small (inline)

#### Button
- **Variants**: Primary (indigo fill), secondary (indigo outline), ghost (text only), icon-only (circular)
- **Sizes**: sm (32px), md (40px), lg (48px)
- **States**: Default, hover, pressed, disabled, loading

#### Input
- **Variants**: Text input, search input, textarea (with char counter)
- **States**: Default, focused (indigo border), error (red border), disabled

#### Toggle
- **States**: On (indigo), Off (neutral)
- **Size**: 48×24px (mobile-friendly)

#### Bottom Sheet
- **Style**: Slides up from bottom, radius-lg top corners, shadow-strong, drag handle
- **Behavior**: Swipe to dismiss, backdrop overlay

#### Banner
- **Variants**: Info (blue), success (green), warning (amber), contextual (indigo — for suggestions/post-session)
- **Layout**: Icon + text + optional action + dismiss

#### Tab Bar (Bottom Navigation)
- **Tabs**: 4 items, icon + label, 48px+ tap targets
- **States**: Active (indigo icon + label), inactive (neutral 400)
- **Badge**: Count indicator on tab icon

#### Top Bar
- **Layout**: Left (back arrow or event name), center (page title), right (bell icon with badge)

#### Toast / Snackbar
- **Types**: Success (green), Error (red), Info (blue), Warning (amber)
- **Position**: Top, auto-dismiss after 4s
- **Content**: Icon + message + optional action

#### Empty State
- **Layout**: Centered illustration/icon + headline + description + optional CTA
- **Style**: Neutral 400 icon, Body L headline, Body description

#### Skeleton
- **Style**: Pulsing neutral-200 blocks matching component layout
- **Variants**: Card skeleton, list item skeleton, stat tile skeleton

#### Notification Row
- **Layout**: List item with avatar(s) + message + timestamp + optional action button
- **States**: Unread (bold, indigo-50 bg), read (normal), actionable (with button)

---

## Phase 2: Composed Domain Components

Built from the primitives above. No backend logic here — only what the user sees.

### People List Pattern
One reusable pattern for all contexts where participants are shown: presence feed, search results, session participants, booth visitors, post-session suggestions.

**Participant Row** (List Item):
- Leading: Avatar (md) with status dot + type badge (pin/globe)
- Content: Name (H3), Company/Role (Body, optional), up to 3 interest tag chips (shared tags highlighted), context badge (if active)
- Trailing: Ping button
- **Ping button states**: Default (wave icon), Sent (checkmark + "Sent"), Mutual (celebration → match), Disabled (rate limited)

**Promoted Participant Card** (Card Shell, elevated):
- Same content as Participant Row but as a card with match reason text and optional TTL indicator
- Used for: suggestions, "Right Now" matches, "People you might want to meet"
- **Actions**: Accept (ping), Dismiss, Later

**People List** (container):
- Search bar (collapsible) + filter chips (status, type, tags, session) + scrollable list of Participant Rows
- Optional promoted section at top (Promoted Participant Cards)

### Mutual Match Moment
- Both avatars side by side (lg size)
- "It's a match!" text (H1)
- Shared context (chips + context badges)
- Icebreaker prompt (Body L)
- Actions: Chat (primary button), Call (secondary), Save for Later (ghost)

### Chat View
- Top bar: Avatar (sm) + name + context badge
- Message list: Sent bubbles (right, indigo bg) / received (left, neutral bg)
- Icebreaker banner (dismissible, top of empty chat)
- Input: Textarea with 500-char counter
- Typing indicator: Animated dots in received bubble position

### Video Call View
- Full-screen video feeds
- Overlay: Countdown timer (H1, prominent), icebreaker prompt (at start, dismissible)
- Bottom bar: Mute toggle, end call (red), extend button (+3 min, max 9 min)
- Timer states: Running (white), last 30s (amber), extending (reset animation)

### Session Card (Card Shell)
- Title (H3), time (Body), speaker (Body), description (Caption, truncated)
- Attendee count: physical icon + count / remote icon + count
- Tag overlap indicator (e.g., "2 of your interests")
- States: Upcoming (default), Live (indigo border + "LIVE" badge), Ended (dimmed), Checked-in (checkmark badge)
- Actions: Check-in button ("I'm here" / "Join"), reminder toggle

### Session Detail Components
- Header: Session card content (expanded)
- Participant tabs: Physical / Remote, each a People List sorted by interest overlap
- "People you might want to meet": Promoted Participant Cards section
- Q&A panel: Question rows (text + asker mini avatar + upvote count + answered badge), submit input, ping on asker
- Reaction bar: 5 emojis (💡👏❓🔥🤔) floating at bottom, reactions animate upward
- Share Interest button: Ephemeral hand-raise chip, expires when session ends
- Post-session banner: "Meet people from [Session]" + 3-5 Promoted Participant Cards, 15-min countdown

### Booth Card (Card Shell)
- Booth/company name (H3), description (Body, truncated), interest tags
- Visitor count: physical icon + count / remote icon + count
- Staff availability: green dot + "Staff available" or gray "No staff"
- States: Default, Currently visiting (indigo border), Recommended badge, Popular badge
- Actions: "Visit Booth" button

### Booth Detail Components
- Header: Booth card content (expanded) + company logo
- Visitor tabs: Physical / Remote People Lists, shared interests highlighted
- Staff section: Staff avatars with ping buttons, priority badge on high-relevance visitors
- Content section: Resource links, pitch text
- Announcement banner: Staff-sent messages (Banner component)
- Consent notice: Banner shown on check-in ("Your visit is recorded for the exhibitor")
- Anonymous browsing toggle

### Connection Row (List Item)
- Avatar (md) + name + company/role
- "How you met" context (Caption: "Matched during Keynote")
- Shared interest tag chips (highlighted)
- Quick actions: Chat, Call
- Expandable: Interaction history, personal notes (editable), contact details (email, phone, LinkedIn), icebreaker answer

### Notification Drawer
- Triggered by bell icon (Top Bar, with count badge)
- Groups notifications by type, most recent first
- Notification row variants:
  1. **Ping received**: Avatar + "X pinged you" + ping-back button
  2. **Mutual match**: Both avatars + "It's a match!" + chat/call/save
  3. **Suggestion**: Promoted Participant Card inline
  4. **Session starting**: Session card mini + "5 min" badge
  5. **Post-session**: "Meet people from [Session]" + cards
  6. **Booth alert**: Booth name + staff message
  7. **Nudge**: "You haven't connected yet" + suggestion
  8. **Milestone**: "You've made 5 connections!" + stat

### Profile View
- Avatar (xl) + name (H1) + participant type badge
- Interest tags (3 chips)
- Icebreaker answer
- QR identity code
- Intent / availability signals ("Open to call", "Available after session", "Looking for [X]")
- Progressive identity fields: Company, role, LinkedIn, phone (editable, shown as prompt if incomplete)
- Settings section: Notification per-type toggles, quiet mode, DND, frequency slider, invisible mode, serendipity mode toggle, participant type switcher (Physical ↔ Remote)

### Organizer Stat Dashboard
- Hero stats row: Active participants (physical/remote split), connections made, interaction rate, cross-pollination rate
- Session analytics: Per-session stat tiles (check-ins, reaction rate, Q&A count, post-session connections, networking score)
- Booth performance: Per-booth stat tiles (visitors, interaction rate, leads), comparison chart
- Action buttons: Boost booth, highlight session, announcement composer, serendipity wave trigger, matching weight sliders

### Event Setup Wizard
- 6-step flow with breadcrumb progress bar:
  1. Event details (name, dates, location, description, branding upload)
  2. Attendee import (CSV upload, manual add, field mapping)
  3. Content config (define interest tags, icebreaker questions, session schedule)
  4. Booth setup (add booths, assign staff, booth details)
  5. Matching tuning (weight sliders for interest/context/availability, preview)
  6. Review & launch (summary, launch button)

### Lead Dashboard (Booth Staff)
- Stat tiles: Total visitors, interaction rate
- Lead list: Rows with name, email, tags, visit duration, type (physical/remote), interaction count
- Lead temperature: Hot (red), Warm (amber), Cold (blue) badge
- Export CSV button
- Session-to-booth attribution summary

---

## Phase 3: Screens by Workflow

Organized by the 5 core attendee workflows + organizer flows. Each screen lists which composed components it uses.

### Workflow 1: Onboarding (5 screens)

| # | Screen | Components Used | Key States |
|---|--------|----------------|------------|
| 1 | **Magic Link Entry** | Input, Button (primary), Event Branding (logo + name) | Default, sending, sent, error |
| 2 | **Type Selection** | Card Shell ×2 (Physical/Remote as selectable cards), Avatar preview, Button | Selected/unselected |
| 3 | **Interest Tag Picker** | Chip grid (organizer-defined tags), counter ("3 of 3"), Button | 0/1/2/3 selected |
| 4 | **Icebreaker Selection** | List Items (pre-defined questions), "Skip" ghost button | None/one selected |
| 5 | **Ready Screen** | Avatar (xl), QR code, Button ("Enter Event") | Success state |

### Workflow 2: Discover People (5 screens)

| # | Screen | Components Used | Key States |
|---|--------|----------------|------------|
| 6 | **Presence Feed** | Top Bar, People List (with search + filters), Promoted Participant Cards (suggestions, max 3), Tab Bar | Default, filtered, empty, loading/skeleton |
| 7 | **Participant Detail** | Bottom Sheet, Avatar (lg), chips, context badge, intent signals, Ping Button, block/report | Default, pinged, mutual match, blocked |
| 8 | **Mutual Match Moment** | Mutual Match Moment component (full screen overlay) | Celebration → actions |
| 9 | **Search Results** | People List (search-focused, no filters) | Results, empty |
| 10 | **Progressive Identity Prompt** | Bottom Sheet, Input fields (company, role, LinkedIn, phone, intent), availability signal chips | Prompted, dismissed, completed |

### Workflow 3: Session-Context Networking (4 screens)

| # | Screen | Components Used | Key States |
|---|--------|----------------|------------|
| 11 | **Sessions Schedule** | Top Bar, Session Card list, Tab Bar | Upcoming/live/ended, checked-in |
| 12 | **Session Detail** | Session header, Participant tabs, Q&A panel, Reaction bar, Share Interest button, Post-session banner | Pre-session, live, post-session (15-min window) |
| 13 | **QR Scanner** | Camera viewfinder overlay, manual fallback list | Scanning, permission denied, success |
| 14 | **Post-Session Suggestions** | Banner + Promoted Participant Cards (3-5), countdown | Active (15 min), expired |

### Workflow 4: Booth-Context Networking (4 screens)

| # | Screen | Components Used | Key States |
|---|--------|----------------|------------|
| 15 | **Booth Discovery** | Top Bar, Booth Card list ("Recommended", "Popular", All), Tab Bar | Default, filtered |
| 16 | **Booth Detail** | Booth header, Visitor tabs, Staff section, Content section, Announcement banner, Consent notice | Visiting, not visiting, anonymous |
| 17 | **Booth Check-in Consent** | Banner (consent text + confirm/decline) | Shown on first visit |

### Workflow 5: Connection Follow-up (3 screens)

| # | Screen | Components Used | Key States |
|---|--------|----------------|------------|
| 18 | **Connections List** | Top Bar, Connection Row list (filterable), Tab Bar | Has connections, empty |
| 19 | **Chat Conversation** | Chat View (full screen) | Empty (icebreaker), active, typing |
| 20 | **Video Call** | Video Call View (full screen) | Ringing, connected, extending, ended |

### Profile & Settings (1 screen)

| # | Screen | Components Used | Key States |
|---|--------|----------------|------------|
| 21 | **Profile / Settings** | Profile View (full screen) | Complete, incomplete (prompting), editing |

### Notifications (1 screen)

| # | Screen | Components Used | Key States |
|---|--------|----------------|------------|
| 22 | **Notification Drawer** | Notification Drawer (overlay from Top Bar bell) | Has unread, all read, empty |

### State Variants Sheet (1 artboard, multiple states)

| Variant | Applied To | Description |
|---------|-----------|-------------|
| Empty states | Feed, connections, chat, suggestions, search, session participants, notifications | Icon + headline + description + CTA |
| Skeleton loading | All card types, lists, stat tiles | Pulsing placeholders |
| Offline/reconnecting | Top banner | "Reconnecting..." amber banner |
| Rate limit warning | Toast | "You've reached your ping limit" |
| PWA install prompt | Banner | "Add to Home Screen" with event branding |

---

## Phase 4: Organizer & Booth Staff (separate design track)

### Organizer Screens (1024px desktop-first, responsive)

| # | Screen | Components Used |
|---|--------|----------------|
| 23 | **Dashboard — Overview** | Stat Tiles (hero), activity summary, quick actions |
| 24 | **Dashboard — Sessions** | Per-session Stat Tiles, charts |
| 25 | **Dashboard — Booths** | Per-booth Stat Tiles, comparison chart |
| 26 | **Dashboard — Actions** | Buttons (boost, highlight, announce, serendipity wave), matching weight sliders |
| 27 | **Event Setup Wizard** | 6-step wizard (one long artboard with all steps) |

### Booth Staff Screens (mobile)

| # | Screen | Components Used |
|---|--------|----------------|
| 28 | **Lead Dashboard** | Stat Tiles, lead list with temperature badges, export button |
| 29 | **Visitor Management** | People List with priority badges, announcement composer |

---

## State Matrices

### Participant States
| State | Status Dot | Context Badge | Feed Visibility | Notification Delivery |
|-------|-----------|---------------|-----------------|----------------------|
| Available | Green | Optional | Visible | All |
| In Session | Blue | "Watching: [X]" | Visible | Medium |
| At Booth | Purple | "At Booth: [X]" | Visible | Medium |
| Busy | Amber | Optional | Visible | Low |
| Away | Gray | None | Visible | Low |
| Invisible | None | None | Hidden | Per settings |
| DND | Amber | None | Visible | None |

### Ping → Connection Lifecycle
```
Stranger → Pinged (30-min expiry) → Expired (silent)
                                   → Mutual Match → Connected (chat/call enabled)
         → Dismissed (silent, 2h suppress)
         → Blocked (permanent, mutual invisibility)
```

### Session Lifecycle
```
Upcoming (reminder at -5min) → Live (check-in enabled, reactions active) → Ended → Post-Session Window (15 min, suggestions active)
```

### Suggestion Lifecycle
```
Generated → Active (max 3, 15-min TTL) → Accepted (boost similar)
                                        → Dismissed (2h suppress)
                                        → Expired (auto-dismiss)
```

---

## Artboard Plan for Paper

### Phase 1: Foundations (3 artboards)
1. **DS — Typography & Colors** (800×600)
2. **DS — Primitives A** (800×1200) — Avatar, badge, chip, button, input, toggle
3. **DS — Primitives B** (800×1200) — Card shell, list item, stat tile, banner, bottom sheet, tab bar, top bar, toast, empty state, skeleton, notification row

### Phase 2: Attendee Screens (22 artboards at 375×812)
4–25. Screens 1–22 from workflows above

### Phase 3: State Variants (1 artboard)
26. **States & Edge Cases** (800×1600) — All empty states, skeletons, offline, rate limit, PWA install

### Phase 4: Organizer & Staff (4 artboards)
27–28. Dashboard overview + actions (1440×900)
29. Event setup wizard (1440×2400, long scroll)
30. Lead dashboard + visitor management (375×812 ×2 or 1440×900)

**Total: ~30 artboards**

---

## Responsive Breakpoints

| Breakpoint | Target | Layout |
|------------|--------|--------|
| 320px | Small phones | Single column, compact |
| 375px | Standard phones (primary design target) | Single column, balanced |
| 768px | Tablets | Two-column feed, side panels |
| 1024px | Desktop (organizer) | Multi-column dashboard |
