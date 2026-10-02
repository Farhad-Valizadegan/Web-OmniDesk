# PB-039 - Workspace Sidebar Navigation

> Status: Deferred; placeholder removed
> Priority: Won't Yet
> Milestone: None

## Purpose

Complete the workspace sidebar so navigation behavior is functional, consistent, and no longer based on static placeholder buttons.

## Scope

- Add state-driven workspace navigation.
- Ensure only one navigation item is active at a time.
- Preserve Inbox as the default workspace view.
- Support navigation targets without hard-coded active classes.
- Keep unavailable views disabled or clearly unavailable until implemented.

## Acceptance Criteria

- Only one sidebar item can be active at a time.
- Inbox is active by default.
- Active styling is driven by application state.
- Clicking an implemented navigation item changes the active view.
- Non-implemented items do not falsely appear functional.
- Navigation behavior is covered by frontend tests.
- Existing Inbox behavior remains unchanged.

## Dependencies

- Existing workspace layout.
- PB-040 Starred view.
- PB-041 Labels view.
- PB-042 Settings view.

## Implementation Tasks

- Introduce a workspace navigation state/type.
- Replace hard-coded `active` classes.
- Connect sidebar buttons to navigation state.
- Define behavior for unavailable views.
- Add tests for active-state transitions.
- Run frontend test, lint, and build checks.

## Testing

- Verify Inbox is active initially.
- Verify only one navigation item is active.
- Verify active state changes correctly.
- Verify unavailable items do not present false functionality.
- Verify existing Inbox workflows still pass.

## Definition of Done

- Acceptance criteria are satisfied.
- Tests pass.
- Lint passes.
- Production build passes.
- No regression in Sprint 06 Inbox behavior.
- Documentation reflects the implemented navigation behavior.

## Sprint Planning Readiness

Ready for prioritization after PB-040, PB-041, and PB-042 dependencies are refined.
