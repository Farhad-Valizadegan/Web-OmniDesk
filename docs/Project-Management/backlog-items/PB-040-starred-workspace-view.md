# PB-040 - Starred Workspace View

> Status: Refined
> Priority: Should
> Milestone: TBD

## Purpose

Provide a dedicated workspace view for conversations marked as starred or otherwise designated for quick access.

## Approved Starred-State Rule

The Starred workspace view uses a provider-neutral, application-level starred state.

The normalized conversation model is the authoritative source for the workspace filter:

```text
is_starred = true
```

Provider-specific starred or favorite semantics are mapped into this normalized state during synchronization or normalization.

For Gmail, the provider-specific `STARRED` state may be used as an input to the normalized starred state, but frontend workspace behavior must not depend directly on Gmail label identifiers.

Future providers, including Telegram or other connectors, may map their own equivalent state into the same normalized application-level field without changing Starred workspace behavior.

The Starred view therefore applies this rule:

```text
Starred view = conversations where normalized is_starred = true
```

## Scope

- Add a Starred workspace view.
- Reuse the existing provider-neutral conversation model and API where possible.
- Use the normalized application-level `is_starred` state as the filtering source.
- Keep provider-specific starred semantics behind normalization/connector boundaries.
- Preserve existing Inbox behavior.
- Integrate the view with workspace sidebar navigation.

## Acceptance Criteria

- The Starred navigation item opens a dedicated Starred view.
- Only conversations where normalized `is_starred = true` are displayed.
- Conversations where `is_starred = false` are excluded.
- The frontend does not filter directly on Gmail-specific `STARRED` label identifiers.
- Provider-specific starred state is translated into the normalized application-level state before the Starred workspace consumes it.
- The view uses the same conversation presentation pattern as Inbox where appropriate.
- Empty, loading, and error states are handled.
- Returning to Inbox restores the normal conversation view.
- The active sidebar item reflects the current view.
- Frontend tests cover Starred navigation and filtering behavior.

## Dependencies

- PB-039 Workspace Sidebar Navigation.
- Existing conversation list and message display.
- Normalized conversation data must expose an application-level `is_starred` state.
- Provider synchronization/normalization must map supported provider-specific starred semantics into `is_starred`.

## Implementation Tasks

- Add or confirm `is_starred` on the normalized conversation representation.
- Map Gmail `STARRED` semantics into normalized `is_starred` during synchronization/normalization.
- Keep provider-specific identifiers out of Starred workspace filtering logic.
- Add Starred filtering support to the provider-neutral conversation API/query layer.
- Add Starred view state and rendering.
- Reuse conversation list/detail components where practical.
- Connect Starred to sidebar navigation.
- Add tests for normalization, API filtering, navigation, filtering, and empty state.
- Run backend and frontend test, lint, and build checks as applicable.

## Testing

- Verify Gmail starred state maps correctly into normalized `is_starred`.
- Verify the Starred API/query returns only conversations with `is_starred = true`.
- Verify Starred opens correctly.
- Verify starred conversations are shown.
- Verify unstarred conversations are excluded.
- Verify the frontend does not depend on Gmail-specific label IDs.
- Verify empty-state behavior.
- Verify navigation back to Inbox.
- Verify existing Inbox behavior still passes regression tests.

## Definition of Done

- Acceptance criteria are satisfied.
- The normalized `is_starred` rule is implemented consistently.
- Provider-specific starred semantics remain isolated behind normalization/connector boundaries.
- Tests pass.
- Lint passes.
- Production build passes.
- No regression in existing Inbox functionality.
- Documentation reflects the implemented Starred behavior.

## Sprint Planning Readiness

The starred-state source and filtering rule are now resolved.

PB-040 is ready for prioritization. It remains unassigned to a milestone or Sprint until it is compared with the other ready/refined backlog items during backlog prioritization and selected during Sprint Planning.
