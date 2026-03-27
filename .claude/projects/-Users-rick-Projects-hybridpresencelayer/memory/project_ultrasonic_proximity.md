---
name: Ultrasonic proximity detection (experimental)
description: Plan to add experimental sound-based booth proximity detection as a last/stretch feature
type: project
---

Add ultrasonic/sound-based proximity detection for booth check-in as an experimental feature, implemented last after all core features.

**Why:** QR-based check-in is the primary approach, but passive proximity detection would improve UX. Sound-based is the only option that works in a pure PWA (Web Audio API). User wants it as a stretch goal.

**How to apply:** Deprioritize this behind all other features. When implementing, use Web Audio API for ultrasonic tone emit/detect. Will require mic permission. Mark as experimental in UI.
