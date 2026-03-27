# Feature: Connection & Communication

## Goal

Once two people express mutual interest, provide lightweight, time-boxed communication formats that respect the event context. No long threads. No email. Quick, meaningful exchanges.

## Core Concept

Connection is the product's core outcome. Everything else (presence, discovery, pings) exists to create connections. A connection is formed when both participants express interest, and it provides structured ways to interact.

## Connection Lifecycle

```
Stranger → Ping → Mutual Match → Connection → Interaction → Saved Contact
```

## Features

### Mutual Match

When both participants have pinged each other:

1. Both receive a notification: "It's a match! You and Lena want to connect"
2. Match card shows shared context: interests, current session, icebreaker
3. Immediate options: Start Chat / Start 3-Min Call / Save for Later

**Match timing:**
- If both ping within 30 minutes: instant match notification
- If A pinged, then B pings hours later: delayed match, both notified
- Match never expires — once matched, always connected for this event

### Instant Chat

Lightweight, event-scoped text chat:

- Opens immediately after mutual match
- System pre-populates a suggested icebreaker message (editable)
- Chat is minimalist: text only, no file sharing, no emojis beyond reactions
- Chat history persists for the event duration + 7 days
- No read receipts (reduces pressure)
- Typing indicator shown (creates presence feel)

**Chat constraints:**
- Only available between matched participants
- No group chats (keeps interactions 1:1 and focused)
- No media attachments (keeps it lightweight and professional)
- Messages limited to 500 characters (encourages concise communication)

### 3-Minute Video Call

The signature interaction format — a time-boxed video call:

**How it works:**
1. Either matched participant can initiate: "Start 3-Min Call"
2. Other participant receives notification with accept/decline
3. Call starts with a 3-minute countdown timer visible to both
4. At 3 minutes: gentle notification "Time's up!"
5. Both can choose: [End Call] or [Extend +3 Min]
6. Maximum total duration: 9 minutes (3 original + 2 extensions)

**Why 3 minutes:**
- Long enough for a meaningful exchange
- Short enough to feel risk-free ("it's only 3 minutes")
- Creates urgency that makes conversations focused
- Mirrors the natural length of a hallway conversation
- Extension option respects great conversations

**Call features:**
- Video + audio (camera on by default, can toggle)
- Icebreaker prompt shown at the start
- Countdown timer visible but not intrusive
- No recording, no transcription (trust and privacy)
- Works on mobile browser (WebRTC via PWA)

### Connection Card Exchange

After any interaction (chat or call), participants can exchange digital contact cards:

- Auto-generated from onboarding data: Name, email, interest tags
- Optionally add: Company, role, phone, LinkedIn URL
- Exchange is mutual — both get each other's card
- Card includes interaction context: "Connected during Zero Trust session"

### Connection List

A persistent record of all connections made during the event:

- Chronological list of all mutual matches
- Each entry shows:
  - Person's name and avatar
  - How you connected (which session, which suggestion)
  - Shared interest tags
  - Last interaction timestamp
  - Quick actions: [Chat] [Call] [View Card]
- Filterable by: interest, session, date, interaction type

### Post-Event Contact Export

After the event, participants can export their connections:

- **vCard (.vcf)**: Import into phone contacts
- **CSV**: Import into CRM or spreadsheet
- **Email digest**: Summary sent to registered email with all connections
- Export includes context per contact (how/where you met)

## Physical-Remote Connection Dynamics

| Scenario | Experience |
|----------|-----------|
| Physical ↔ Physical | Chat or call (can also meet in person via location hints) |
| Remote ↔ Remote | Chat or 3-min call |
| Physical ↔ Remote | Chat or 3-min call (the platform's key differentiator) |

For Physical ↔ Physical connections, the system suggests: "You're both here — want to meet at the coffee area?" with a simple location hint (not GPS, just "I'm near Stage A").

## Design Decisions

| Decision | Rationale |
|----------|-----------|
| Mutual match required | Both parties opt in; prevents unwanted contact |
| 3-minute time box | Removes commitment fear; creates focused conversations |
| No read receipts | Reduces social pressure and obligation |
| No group chat | Keeps focus on 1:1 relationship building |
| Message length limit | Event context demands brevity |
| No call recording | Privacy and trust; encourages authentic conversation |
| Context saved with connections | Connections without context are forgotten within a week |
| 7-day chat retention | Long enough to follow up; short enough to feel ephemeral |

## Edge Cases

- **One participant drops from call**: Reconnection attempt for 30 seconds, then call ends
- **Both try to call simultaneously**: Merge into single call
- **Chat during session**: Messages are queued, notification shown post-session
- **Connection export after event ends**: Available for 30 days post-event
