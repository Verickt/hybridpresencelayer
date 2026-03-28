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

## 0. Prerequisites — Schema & Channel Fixes

These must be addressed before building the features below.

### Speaker Identity

`event_sessions.speaker` is currently a free-text field. Add a nullable `speaker_user_id` FK to `event_sessions`. When set, that user gets the "Speaker" badge on replies and can mark questions as answered. When null, only the event organizer can moderate.

### WebSocket Channel Authorization

The `session.{sessionId}` private channel currently only authorizes users with an active check-in (`checked_out_at IS NULL`). This breaks two things:

1. **Organizer moderation** — the organizer needs channel access without checking in
2. **Post-session window** — participants who check out lose access immediately

Fix: authorize the channel for any user who (a) is the event organizer, (b) has an active check-in, OR (c) has a check-in record for this session and the session ended within the last 15 minutes.

### Durable Attendance

Manual checkout or session end currently removes post-session eligibility. Add a `SessionEndedJob` dispatched when a session's `ends_at` passes. This job:
- Auto-stamps `checked_out_at` on all remaining active check-ins for that session
- Broadcasts `SessionEnded` event on `session.{sessionId}`
- Triggers pre-computation of engagement summaries (see Section 5)

Post-session eligibility = "has any `SessionCheckIn` record for this session" regardless of checkout timing.

---

## 1. Enhanced Reactions — From Decoration to Connection Signal

### During Session (Awareness Only)

When a participant reacts, the system groups reactions within ~30-second windows into "moment clusters." If multiple people react in the same moment:

- A subtle badge appears at the bottom of the participant's screen: **"3 others felt that too"**
- The badge updates as more people react to the same moments
- **No tap-through, no ping flow** — just awareness. Don't distract from the session content.
- Badge auto-dismisses after 15 seconds if the moment passes
- Creates anticipation: "I want to meet these people after"

**Computation:** Client-side. The frontend already receives all reaction broadcasts on `session.{sessionId}`. The client tracks reactions in 30-second windows locally and shows the badge count. No additional server events needed.

### Post-Session (Full Connection Flow)

All reaction affinity data feeds into the Post-Session Connection Screen (Section 4). Reaction clusters become a match reason: "You both 🔥'd 4 moments."

### Data Model

No new tables. Moment clusters are computed from `session_reactions` grouped by `event_session_id` + time window (~30 seconds). The `SessionEndedJob` pre-computes per-user "moment fingerprints" (which 30-sec windows did each user react in) for efficient pairwise comparison at matching time (see Section 5).

---

## 2. Q&A Becomes a Discussion Thread

### Pattern Reuse

Booths already have threaded replies, votes, pinning, and moderation. Session Q&A follows the **same data and controller patterns** to avoid building a parallel system. The UX differs (sessions are live and time-bound vs. booths are persistent), but the underlying CRUD, voting, and moderation logic is shared.

### Threaded Replies

- Any participant can **reply** to a question (not just speaker/organizer)
- Replies are **one level deep** — no nested replies, keeps it simple
- Speaker replies get a "Speaker" badge (requires `speaker_user_id` on session)
- Organizer replies get an "Organizer" badge (checked via `event.organizer_id`)
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
- Organizer identified via `event.organizer_id` (no new roles needed)

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

- Organizer subscribes to `session.{sessionId}` channel (authorized via organizer check — see Section 0)
- Receives all reaction and question events
- New broadcast event: `SessionQuestionPinned` — notifies participants when a question is pinned

### New Data on Questions

- `session_questions.is_pinned` — boolean, default false
- `session_questions.is_hidden` — boolean, default false
- `session_questions.answered_by` — nullable FK to users (who marked it answered)

---

## 4. Post-Session Connection Screen

### Trigger

- `SessionEndedJob` broadcasts `SessionEnded` event on `session.{sessionId}`
- Frontend listens for this event and transitions checked-in participants to the post-session screen
- Available for **15 minutes** after session ends (matches existing suggestion TTL)

### Integration with Existing Suggestion System

Post-session connections are **not a new matching stack**. They are `Suggestion` records created by the existing `SuggestionService` with a new trigger type `'session_affinity'` and session-specific reasons. This reuses the existing TTL, ranking, exclusion logic, and decline/accept lifecycle.

The `PostSessionConnections.vue` page renders suggestions filtered by `trigger = 'session_affinity'` for the relevant session.

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
- One-tap ping button (existing ping flow — accepting a suggestion sends a ping)

### Cross-World Reranking

The "at least 1 of top 3 must cross physical ↔ remote" rule cannot be satisfied by weighting alone. After scoring, a **reranking step** ensures the quota:
1. Sort all candidates by score descending
2. Take top 3
3. If none cross physical ↔ remote, swap the lowest-scored same-world suggestion for the highest-scored cross-world candidate

This reranking applies to all suggestion generation, not just post-session.

### Fallback

If no strong matches exist:
- Serendipity fallback: 1-2 random participants from the session
- Reason: "You were in the same session — say hi?"

### New Page

`PostSessionConnections.vue` — shown automatically when `SessionEnded` event is received.

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

Note: weights sum to 0.80 (up from 0.75). The additional 0.05 reflects the richer signal set — session behavioral data adds real information. When `session_affinity = 0` (no shared session), the effective weights are 0.30 + 0.25 = 0.55, which is lower than today's 0.75 — this is intentional, as non-session matches have less signal confidence.

### Session Affinity — Pre-Computed

Session affinity is **not computed on-the-fly**. The `SessionEndedJob` pre-computes:

1. **Per-user moment fingerprints** — which 30-second windows did each user react in? Stored as a set of window indices per user.
2. **Q&A interaction edges** — for each user pair: did they reply to each other? Upvote each other? Upvote the same questions?

These are stored in a `session_engagement_edges` table:

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| event_session_id | FK | |
| user_a_id | FK | Lower ID first (same convention as connections) |
| user_b_id | FK | Higher ID |
| reaction_sync_score | float | 0-1, normalized moment overlap |
| qa_interaction_score | float | 0-1, normalized Q&A interaction |
| created_at | timestamp | |

Unique constraint on `(event_session_id, user_a_id, user_b_id)`.

This makes matching O(1) per pair — just look up the pre-computed edge.

### Session Affinity Breakdown

- **Reaction sync (60%)** — `|shared_windows| / max(user_a_windows, user_b_windows)`
- **Q&A interaction (40%)** — Weighted sum: replied to each other (0.4), upvoted each other's replies (0.3), upvoted same questions (0.3). Normalized to 0-1.

### When It Applies

- Session affinity only scores > 0 when both users attended the same session and an engagement edge exists
- Outside sessions, old weights effectively apply (session_affinity = 0)
- During post-session window, availability multiplier overridden to **1.0** for recent session participants

### Serendipity Mode

Unchanged — still deliberately matches zero-overlap users for cross-disciplinary ties.

---

## Summary of Changes

### New Tables
- `session_question_replies`
- `session_question_reply_votes`
- `session_engagement_edges` (pre-computed affinity scores per user pair per session)

### Modified Tables
- `event_sessions` — add `speaker_user_id` (nullable FK)
- `session_questions` — add `is_pinned`, `is_hidden`, `answered_by`

### New Pages
- `SessionModerate.vue` — organizer live moderation dashboard
- `PostSessionConnections.vue` — post-session connection screen (renders session-affinity suggestions)

### Modified Files
- `MatchingService.php` — new `session_affinity` weight, reads from `session_engagement_edges`
- `SuggestionService.php` — new trigger type `'session_affinity'`, session-specific reason generation
- `SessionDetail.vue` — client-side reaction cluster badge, threaded Q&A with replies
- `SessionQuestionController.php` — reply CRUD, pin/hide/answer actions
- `SessionController.php` — moderate view
- `routes/channels.php` — expanded `session.{sessionId}` authorization (organizer + post-session grace)

### New Jobs
- `SessionEndedJob` — auto-checkout, compute engagement edges, broadcast `SessionEnded`, trigger suggestion generation

### New Events (WebSocket)
- `SessionEnded` — triggers post-session transition on frontend
- `SessionQuestionReplyPosted`
- `SessionQuestionPinned`

### New Controllers/Methods
- `SessionQuestionReplyController` — store reply, vote on reply
- `SessionModerateController` — pin, hide, mark answered

### Cross-Cutting Fix
- Cross-world reranking constraint in `MatchingService.topMatches()` — applies to all suggestion generation