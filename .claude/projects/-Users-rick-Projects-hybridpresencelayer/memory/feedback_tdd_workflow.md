---
name: TDD workflow and testing requirements
description: Strict TDD loop required for all features — Pest backend tests, browser extension for frontend verification, E2E for critical flows
type: feedback
---

All features must follow strict TDD. Never write implementation before the failing test.

**Why:** User explicitly requires TDD workflow. Both backend and frontend testing are mandatory, plus E2E for critical user flows.

**How to apply:**
- Backend: Write Pest test first → run to confirm FAIL → implement → run to confirm PASS → lint with Pint → commit
- Frontend: After implementing Vue components, use Claude Code browser extension (mcp__claude-in-chrome__*) to visually verify rendering, interactions, responsiveness, and check for console errors
- E2E: Use Pest browser tests for critical flows (auth → onboarding, ping → match → chat, session check-in → Q&A)
- Never skip the "confirm it fails" step — it validates the test is actually testing something
- One commit per test+implementation cycle
