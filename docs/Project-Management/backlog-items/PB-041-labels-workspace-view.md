# PB-041 - Labels Workspace View

> Status: Refined
> Priority: Should
> Milestone: TBD

## Purpose

Provide a dedicated Labels workspace view that supports provider-neutral organization and dynamic/custom labels rather than relying only on hard-coded Gmail system labels.

## Approved Label Source and API Rule

The Labels workspace must use a provider-neutral label catalog owned by OmniDesk AI.

The current conversation `labels` JSON field remains the per-conversation provider-label membership source, but it is not sufficient by itself for the Labels workspace because provider-specific identifiers such as Gmail custom label IDs do not reliably contain user-readable names.

A normalized provider-label catalog must therefore be maintained for the authenticated user.

For Gmail, label metadata is synchronized from the official Gmail Labels API and stored in the local catalog. The catalog maps the provider-specific label identifier to a user-readable display name and classification.

Conceptual normalized label record:

```text
provider
provider_label_id
display_name
kind
```

Where:

- `provider` identifies the source provider, such as `gmail`.
- `provider_label_id` is treated as an opaque provider identifier by the frontend.
- `display_name` is the user-readable label name shown in the UI.
- `kind` distinguishes provider system labels from provider custom/user labels where the provider exposes that distinction.

The frontend must not contain a fixed Gmail label list or derive presentation names by interpreting provider identifiers.

### Available Labels API

Expose available labels through a provider-neutral endpoint:

```text
GET /api/labels
```

Conceptual response:

```json
{
  "data": [
    {
      "provider": "gmail",
      "id": "INBOX",
      "display_name": "Inbox",
      "kind": "system"
    }
  ]
}
```

The `id` value is opaque to the frontend. The UI displays `display_name` and does not apply Gmail-specific formatting rules.

### Conversation Filtering Rule

Conversation filtering must identify both the provider and label identifier so identifiers from different providers cannot collide.

Conceptual request:

```text
GET /api/conversations?label_provider=gmail&label_id=INBOX
```

The backend translates this generic filter into the provider-specific stored conversation label membership.

Future providers can populate the same catalog and API contract without changing Labels workspace behavior.

PB-014 user-managed local labels/tags remains a separate capability. This PB does not redefine PB-014.

## Scope

- Add a Labels workspace view.
- Add a normalized provider-label catalog for synchronized provider labels.
- Synchronize Gmail system and custom label metadata into the catalog.
- Expose available labels dynamically through a provider-neutral API.
- Display provider label names using backend-supplied `display_name`.
- Filter conversations using provider plus opaque label identifier.
- Avoid hard-coded Gmail label options and Gmail-specific display formatting in the frontend.
- Integrate the view with workspace sidebar navigation.
- Preserve existing Inbox behavior.
- Keep PB-014 local user-managed labels/tags outside this item.

## Acceptance Criteria

- The Labels navigation item opens a dedicated Labels view.
- Available labels come from the provider-neutral labels API rather than a fixed frontend list.
- Gmail system labels are displayed with user-readable names supplied by synchronized label metadata.
- Gmail custom/user-created labels are displayed with their actual provider-supplied names.
- The frontend treats provider label identifiers as opaque values.
- Selecting a label filters conversations by both provider and label identifier.
- Labels belonging to different providers cannot collide solely because they share the same provider-specific identifier.
- Empty, loading, and error states are handled.
- The active sidebar item reflects the Labels view.
- Existing Inbox label filtering behavior is migrated to the same dynamic label source.
- Frontend and backend tests cover system labels, custom labels, catalog retrieval, and filtering.

## Dependencies

- PB-039 Workspace Sidebar Navigation.
- Existing conversation label membership data.
- Provider label metadata retrieval during synchronization.
- Authenticated-user ownership/scoping for the provider-label catalog.

## Implementation Tasks

- Define a normalized provider-label persistence model/catalog.
- Scope label catalog records to the authenticated user and provider.
- Synchronize Gmail label metadata from the official Gmail Labels API.
- Store provider label identifier, display name, and kind/classification.
- Add `GET /api/labels` for the authenticated user's available provider labels.
- Update conversation filtering to accept provider plus label identifier.
- Remove the hard-coded `labelFilterOptions` list from the frontend.
- Remove Gmail-specific label-name formatting from workspace presentation where catalog display names are available.
- Add Labels workspace view.
- Connect Labels to sidebar navigation.
- Preserve existing conversation label chips using catalog display names.
- Add backend tests for ownership, catalog retrieval, custom labels, and provider-scoped filtering.
- Add frontend tests for dynamic labels, filtering, loading, error, and empty states.
- Run backend and frontend verification.

## Testing

- Verify Gmail system label metadata is synchronized into the normalized catalog.
- Verify Gmail custom label IDs resolve to their actual user-readable names.
- Verify `GET /api/labels` returns only labels available to the authenticated user.
- Verify the frontend renders `display_name` rather than interpreting provider identifiers.
- Verify selecting a label filters conversations by provider and label identifier.
- Verify identical provider-label identifiers from different providers do not collide.
- Verify unknown or newly synchronized labels do not break the UI.
- Verify Labels view opens correctly.
- Verify navigation back to Inbox.
- Verify existing Inbox and conversation tests still pass.

## Definition of Done

- Acceptance criteria are satisfied.
- Dynamic system and custom provider labels work without hard-coded frontend assumptions.
- Provider-specific identifiers are treated as opaque data by the frontend.
- Provider label metadata is normalized behind a provider-neutral API contract.
- Backend tests pass.
- Frontend tests pass.
- Lint passes.
- Production build passes.
- No regression in existing Inbox functionality.
- Documentation reflects the final label behavior.

## Sprint Planning Readiness

The provider-neutral label source and API rule are now resolved.

PB-041 is ready for prioritization. It remains unassigned to a milestone or Sprint until it is compared with the other ready/refined backlog items during backlog prioritization and selected during Sprint Planning.
