# Feature: Feedback Loop

## Goal

The matching algorithm improves in real-time based on participant behavior. Every action is a signal.

## Signals

### Explicit Signals

| Action | Signal Strength | Effect |
|--------|----------------|--------|
| Ping someone | Strong positive | Boost similar profiles |
| Ping back (mutual) | Very strong positive | Heavily boost these attributes |
| "Not interested" on suggestion | Strong negative | Suppress this pair; reduce shared attribute weight |
| "Later" on suggestion | Neutral | Re-suggest in different context |
| Block someone | Absolute negative | Never suggest again |

### Implicit Signals

| Behavior | Signal | Effect |
|----------|--------|--------|
| View someone's profile (>3s) | Weak positive | Slight boost for similar profiles |
| Suggestion expired (no action) | Weak negative | Slightly reduce similar suggestions |
| Chat lasted >5 messages | Strong positive | Boost conversation-likely matches |
| 3-min call extended | Very strong positive | These attributes produce deep connections |
| Left chat after 1 message | Weak negative | These attributes may not gel |
| Attended same session | Context signal | Boost session-topic matching weight |
| Visited same booth | Context signal | Boost booth-interest matching weight |

## How Feedback Adjusts Matching

The discovery algorithm's weights (w1, w2, w3) are global defaults. Feedback creates per-participant adjustments:

```
PersonalScore(A, B) = GlobalScore(A, B) + personal_adjustment(A)
```

Where `personal_adjustment` is built from A's interaction history:
- Positive signals with people who share tag X → boost tag X weight for A
- Negative signals with people from context Y → reduce context Y weight for A
- Adjustments are small and cumulative — no single action causes a dramatic shift

## Cold Start

With zero behavioral data (new participant):
- Matching relies entirely on interest tags (w1 dominates)
- First 3 suggestions are diverse: one tag-match, one context-match, one serendipity
- Feedback from first interactions rapidly calibrates the model

## North-Star Funnel Metrics

The feedback loop should optimize for the full connection funnel, not just matches:

```
Suggestion → Ping → Match → Conversation → Follow-up (7-day)
```

Each stage generates signal:
- High suggestion→ping but low ping→match = suggestions are interesting but not reciprocal (improve bidirectional scoring)
- High match→conversation but low conversation→follow-up = in-event experience is good but post-event is weak (improve follow-up nudges)
- Low suggestion→ping overall = matching quality needs work OR notification timing is off

## Privacy

- Feedback data is never shown to other participants
- Implicit tracking (profile views, time spent) is aggregated, not exposed
- "Not interested" is silent — the other person never knows
- All behavioral data deleted 30 days after event ends
- No cross-event profiling — each event starts fresh

## Design Decisions

| Decision | Rationale |
|----------|-----------|
| Implicit + explicit signals | Explicit is sparse; implicit fills gaps |
| Per-participant adjustments | Everyone has different networking goals |
| Small cumulative changes | Prevents over-fitting to single interactions |
| No cross-event data | Privacy-first; each event is independent |
| Cold start diversity | Avoids echo chamber from first suggestion |
