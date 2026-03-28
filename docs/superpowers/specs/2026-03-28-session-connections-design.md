# Session Connections — Design Spec

## Problem

Sessions collect rich behavioral signals (reactions, questions, upvotes) but none of it feeds back into connection-making. Reactions go into the void. Q&A has no answers or discussion. The organizer sees only aggregated counts. Sessions should be the strongest connection engine in the platform — shared real-time experience is the best signal of compatibility.

## Design Principles

- Sessions are a **connection engine**, not a passive broadcast
- Reactions are **signal**, not decoration
- Q&A is a **discussion space**, not a question wall
- The organizer is an **active moderator**, not a metrics viewer
- Cross-world (physical ↔ remote) connections are always prioritized

---

## 1. Enhanced Reactions — From Decoration to Connection Signal

### During Session (Awareness Only)

When a participant reacts, the system groups reactions within ~30-second windows into "moment clusters." If multiple people react in the same moment:

- A subtle badge appears at the bottom of the participant's screen: **"3 others felt that too"**
- The badge updates as more people react to the same moments
- **No tap-through, no ping flow** — just awareness. Don't distract from the session content.
- Badge auto-dismisses after 15 seconds if the moment passes
- Creates anticipation: "I want to meet these people after"

### Post-Session (Full Connection Flow)

All reaction affinity data feeds into the Post-Session Connection Screen (Section 4). Reaction clusters become a match reason: "You both 🔥'd 4 moments."

### Data Model

No new tables. Moment clusters are computed by querying `session_reactions` grouped by `event_session_id` + time window (~30 seconds) + reaction type. Grouping is computed on-the-fly, not stored.

---

## 2. Q&A Becomes a Discussion Thread

### Threaded Replies

- Any participant can **reply** to a question (not just speaker/organizer)
- Replies are **one level deep** — no nested replies, keeps it simple
- Speaker and organizer replies get a visual badge ("Speaker" / "Organizer")
- Questions remain sortable by vote count

### Reply Voting

- Participants can upvote individual replies
- Best answers rise to the top within a question thread
- Unique constraint prevents duplicate votes per user per reply

### Answer Lifecycle

- Organizer or speaker can mark a question as **"Answered"**
- Visual checkmark indicator on answered questions
- Answered questions move down the list to keep unanswered ones prominent
- A question can be answered verbally (organizer marks it) or via text reply (or both)

### Real-Time

- New replies broadcast on the existing `session.{sessionId}` WebSocket channel
- New event: `SessionQuestionReplyPosted`
- Participants see replies appear live under questions they're viewing

### Connection Signal

- Q&A interactions feed into session affinity scoring:
  - Replying to each other's questions
  - Upvoting each other's replies
  - Upvoting the same questions
- Match reason: "You discussed the same topics"

### New Data Models

**`session_question_replies`**

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| session_question_id | FK | Parent question |
| user_id | FK | Reply author |
| body | text | Max 500 chars |
| created_at | timestamp | |
| updated_at | timestamp | |

**`session_question_reply_votes`**

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| session_question_reply_id | FK | |
| user_id | FK | |
| created_at | timestamp | |

Unique constraint on `(session_question_reply_id, user_id)`.

### Changes to Existing Models

- `session_questions.is_answered` — now actively used (set by organizer/speaker)

---

## 3. Organizer Live Moderation Dashboard

### Access

- Organizers see a **"Moderate"** button on the session detail page
- New page: `SessionModerate.vue`
- No new roles needed — uses existing organizer role on `event_user` pivot

### Layout — Three Panels

**Left: Reaction Heatmap**
- Timeline showing reaction density over the session duration
- Spikes indicate high-engagement moments
- Color-coded by reaction type
- Live participant count with physical/remote split

**Center: Q&A Feed**
- All questions ranked by votes
- Organizer actions:
  - **Pin** — highlights question for the speaker to address (broadcast to all participants)
  - **Mark as Answered** — checkmark, moves down the list
  - **Reply** — badged as "Organizer"
  - **Hide** — soft delete, removed from participant view (not permanent deletion)

**Right: Engagement Summary**
- Total reactions count
- Reactions/minute trend
- Active participants (physical vs remote)
- Question count
- Top question by votes
- Quick pulse of session health

### Real-Time

- Organizer subscribes to existing `session.{sessionId}` WebSocket channel
- Receives all reaction and question events
- New broadcast event: `SessionQuestionPinned` — notifies participants when a question is pinned

### New Data on Questions

- `session_questions.is_pinned` — boolean, default false
- `session_questions.is_hidden` — boolean, default false
- `session_questions.answered_by` — nullable FK to users (who marked it answered)

---

## 4. Post-Session Connection Screen

### Trigger

- When a session ends, checked-in participants see a transition screen
- Not a push notification — it's the natural next page after the session
- Available for **15 minutes** after session ends (matches existing suggestion TTL)

### What Participants See

**"People you vibed with"** — ranked list scored by:

1. Reaction affinity (reacted to same moments)
2. Q&A interaction (replied to each other, upvoted same questions/replies)
3. Interest tag overlap (existing signal)
4. Cross-world bonus (physical ↔ remote boosted)

Each person shows:
- Name, avatar, interest tags
- Participant type badge ("Remote" / "In the room")
- **Match reason** — "You both 🔥'd 4 moments" or "You discussed the same question"
- One-tap ping button (existing ping flow)

### Cross-World Emphasis

- At least 1 of top 3 suggestions must cross physical ↔ remote (existing rule)
- Visual callout with prominent participant type badges

### Fallback

If no strong matches exist:
- Serendipity fallback: 1-2 random participants from the session
- Reason: "You were in the same session — say hi?"

### Data Model

No new tables. Session affinity is computed on-the-fly from:
- `session_reactions` timestamps (moment cluster overlap)
- `session_question_replies` (who replied to whom)
- `session_question_reply_votes` (who upvoted whose replies)
- `session_question_votes` (who upvoted the same questions)

15-minute window is short enough that live computation is fine.

### New Page

`PostSessionConnections.vue` — shown automatically when session ends for checked-in participants.

---

## 5. Updated Matching Engine

### Current Formula

```
relevance = (0.40 × interest_overlap + 0.35 × context_match) × availability
```

### New Formula

```
relevance = (0.30 × interest_overlap + 0.25 × context_match + 0.25 × session_affinity) × availability
```

### Session Affinity Breakdown

- **Reaction sync (60% of session_affinity)** — How many "moments" (30-sec windows) did both users react in? Normalized by total moments in the session.
- **Q&A interaction (40% of session_affinity)** — Replies to each other's questions, upvotes on each other's replies, upvotes on the same questions.

### When It Applies

- Session affinity only scores > 0 when both users attended the same session
- Outside sessions, old weights effectively apply (session_affinity = 0)
- During post-session window, availability multiplier overridden to **1.0** for recent session participants (they just finished, they're available)

### Serendipity Mode

Unchanged — still deliberately matches zero-overlap users for cross-disciplinary ties.

---

## Summary of Changes

### New Tables
- `session_question_replies`
- `session_question_reply_votes`

### Modified Tables
- `session_questions` — add `is_pinned`, `is_hidden`, `answered_by`

### New Pages
- `SessionModerate.vue` — organizer live moderation dashboard
- `PostSessionConnections.vue` — post-session connection screen

### Modified Files
- `MatchingService.php` — new `session_affinity` weight + `computeSessionAffinity()` method
- `SessionDetail.vue` — reaction cluster badge, threaded Q&A with replies
- `SessionQuestionController.php` — reply CRUD, pin/hide/answer actions
- `SessionController.php` — moderate view, post-session redirect

### New Events (WebSocket)
- `SessionQuestionReplyPosted`
- `SessionQuestionPinned`

### New Controllers/Methods
- `SessionQuestionReplyController` — store reply, vote on reply
- `SessionModerateController` — pin, hide, mark answered
- `PostSessionConnectionController` — fetch session-affinity-ranked suggestions