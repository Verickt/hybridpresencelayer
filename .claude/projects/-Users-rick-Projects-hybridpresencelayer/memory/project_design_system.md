---
name: Design system built in Paper
description: Frontend design is created in Paper (design tool) — agents should reference Paper designs when implementing Vue components
type: project
---

The frontend design system for this app is built in Paper (the design tool MCP). When implementing Vue components, agents should use `mcp__paper__` tools to inspect the design for correct spacing, colors, typography, and component structure.

**Why:** Design fidelity matters — the hackathon presentation needs to look polished.

**How to apply:** Before implementing any Vue page or component, check the Paper design for that screen. Use `mcp__paper__get_screenshot` and `mcp__paper__get_tree_summary` to understand the design, then implement to match.
