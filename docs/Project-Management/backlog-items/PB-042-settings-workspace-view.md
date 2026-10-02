# PB-042 - Settings Workspace View

> Status: Completed by removing the placeholder
> Priority: Won't Yet
> Milestone: None

## Purpose

Define the role of the Settings workspace view and prevent the sidebar from exposing a non-functional placeholder without an approved scope.

## Approved V1 Decision

A dedicated Settings workspace is **deferred from V1**.

The current application already exposes the only concrete user preference implemented today — light/dark theme — directly in the workspace header. Gmail connection management is also already handled by the existing Gmail connection panel.

Creating a Settings destination in V1 would therefore duplicate existing controls or introduce placeholder options without approved behavior.

The V1 rule is:

```text
Do not expose a functional Settings workspace until at least one approved
setting exists that belongs there and is not already intentionally managed
elsewhere.
```

For the current V1 implementation, the sidebar Settings item must be removed or explicitly disabled so it cannot appear functional.

## V1 Scope

Included:

- Remove or clearly disable the non-functional Settings sidebar destination.
- Preserve the existing light/dark theme control in the workspace header.
- Preserve Gmail connection/disconnection/synchronization controls in the existing Gmail integration panel.
- Keep navigation semantics consistent with PB-039.

Deferred:

- Dedicated workspace display-preference management.
- Centralized provider connection management.
- Account/profile preference editing.
- Notification preferences.
- Any persistence model or API created solely for future Settings capabilities.

## Acceptance Criteria

- The sidebar does not present Settings as a functional destination in V1.
- If the Settings item remains visible, it is clearly disabled and cannot enter an active navigation state.
- No empty or placeholder Settings screen is presented.
- The existing theme toggle remains functional in the workspace header.
- Existing Gmail integration controls remain in the Gmail connection panel.
- Gmail controls are not duplicated into Settings.
- No new settings persistence/API is introduced without an approved setting that requires it.
- Navigation active-state behavior remains consistent.
- Frontend tests cover the approved disabled/removed Settings behavior.

## Dependencies

- PB-039 Workspace Sidebar Navigation.
- Existing theme toggle behavior.
- Existing Gmail integration controls.

## Implementation Tasks

- Remove the Settings navigation item from V1, or render it explicitly disabled.
- Ensure PB-039 navigation state cannot select Settings while it is deferred.
- Preserve the current theme control in the workspace header.
- Preserve Gmail provider controls in the Gmail connection panel.
- Add tests proving no false Settings destination is exposed.
- Run frontend test, lint, and build checks.

## Deferred Settings Candidates

The following remain possible future backlog candidates but are not approved for V1:

- Workspace display preferences.
- Provider connection management.
- Account/profile preferences.
- Notification preferences.

Theme preference is not moved into Settings in V1 because the existing header control is already functional and does not require a dedicated settings workflow.

## Testing

- Verify Settings is removed or explicitly disabled.
- Verify Settings cannot become the active workspace view.
- Verify no placeholder Settings content is rendered.
- Verify theme switching still works.
- Verify Gmail connection/disconnection/synchronization behavior remains unchanged.
- Verify navigation back to Inbox and other implemented workspace views.
- Verify existing workspace tests still pass.

## Definition of Done

- The V1 Settings scope decision is documented.
- No misleading Settings destination remains.
- Existing theme and Gmail controls remain functional in their current locations.
- No unnecessary Settings persistence/API is introduced.
- Tests pass.
- Lint passes.
- Production build passes.
- Documentation reflects the deferred Settings decision.

## Sprint Planning Readiness

The V1 Settings scope is now resolved by explicit deferral.

PB-042 is ready for prioritization as a small UX/navigation cleanup item. It remains unassigned to a milestone or Sprint until it is compared with the other ready/refined backlog items during backlog prioritization and selected during Sprint Planning.
