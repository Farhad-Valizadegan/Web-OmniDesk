# Sprint 03 Plan - Workspace Foundation

> Status: Completed with Carryover
> Completed: 2026-09-12
> Audited: 2026-09-13

## Sprint Goal

Implement the initial unified communication workspace using internal conversation and message data.

Sprint 03 establishes the frontend and backend foundation for viewing conversations, viewing messages, searching, filtering, and organizing communication records.

## Related Milestone

M1 - Authentication and Workspace

Sprint 03 contributes to M1 only. Unified Inbox completion belongs to M3 and will be implemented in Sprint 06. Gmail integration belongs to M2 and is implemented in Sprints 04 and 05.

## Related Backlog Items

| ID | Backlog Item | Priority |
|---|---|---|
| PB-011 | Define conversation data model | Must |
| PB-012 | Define message data model | Must |
| PB-013 | Implement conversation list endpoint | Must |
| PB-014 | Implement conversation detail endpoint | Must |
| PB-015 | Implement message display | Must |
| PB-016 | Implement inbox search and filtering | Should |
| PB-017 | Implement labels and tags foundation | Should |
| PB-018 | Implement unified inbox workspace interface | Must |
| PB-019 | Add fixture data for inbox development | Must |

## Objectives

Sprint 03 shall:

- Define the initial conversation and message structures.
- Create backend endpoints for conversations and messages.
- Create development fixture data.
- Implement the conversation list interface.
- Implement the conversation detail interface.
- Display messages in a conversation.
- Implement server-side conversation search exposed through the frontend.
- Implement basic filtering.
- Establish labels and tags as internal metadata.
- Connect the workspace interface to backend data.
- Add loading, empty, and error states.
- Add backend feature tests.
- Add frontend component and interaction tests.
- Keep provider-specific authentication outside this sprint.
- Keep Gmail synchronization outside this sprint.
- Keep Telegram integration outside this sprint.
- Keep AI assistance outside this sprint.

## Planned Tasks

### Data Model

- Define the conversation entity.
- Define the message entity.
- Define conversation-to-message relationships.
- Define sender and recipient representation.
- Define message timestamps.
- Define read and unread state.
- Define conversation status.
- Define labels and tags representation.
- Define provider-neutral fields.
- Add database migrations.
- Add model factories.
- Add fixture data.

### Backend API

- Implement authenticated conversation list endpoint.
- Implement authenticated conversation detail endpoint.
- Implement authenticated message list endpoint.
- Validate pagination parameters.
- Validate search parameters.
- Validate filter parameters.
- Return consistent JSON responses.
- Apply authenticated-user ownership rules.
- Prevent access to another user's conversations.
- Add safe not-found responses.
- Add safe unauthorized responses.
- Add basic response pagination.

### Inbox Features

- Display the conversation list.
- Display sender information.
- Display subject or conversation title.
- Display the latest message preview.
- Display timestamps.
- Display read and unread state.
- Display labels.
- Display conversation status.
- Open a conversation.
- Display messages in chronological order.
- Search conversations.
- Filter conversations by read state.
- Filter conversations by label.
- Filter conversations by status.
- Display an empty state.
- Display a loading state.
- Display an error state.

### Frontend Integration

- Add conversation API functions.
- Add message API functions.
- Add authenticated request handling.
- Add workspace inbox state.
- Add selected-conversation state.
- Add search state.
- Add filter state.
- Avoid storing provider tokens in the frontend.
- Keep provider-specific data behind backend APIs.
- Preserve the existing authentication flow.

### Testing

- Test authenticated conversation access.
- Test unauthenticated conversation rejection.
- Test conversation ownership boundaries.
- Test conversation list responses.
- Test conversation detail responses.
- Test message list responses.
- Test search behavior.
- Test filtering behavior.
- Test empty results.
- Test frontend conversation rendering.
- Test frontend conversation selection.
- Test frontend search interaction.
- Test frontend filter interaction.
- Test frontend loading state.
- Test frontend error state.

## API Scope

The planned endpoints are:

| Method | Endpoint | Purpose |
|---|---|---|
| GET | `/api/conversations` | Return the authenticated user's conversations |
| GET | `/api/conversations/{conversation}` | Return one owned conversation |
| GET | `/api/conversations/{conversation}/messages` | Return messages for one owned conversation |

The exact schema and pagination format may be refined during implementation.

## Acceptance Criteria

- An authenticated user can retrieve their conversations.
- An unauthenticated user cannot retrieve conversations.
- A user cannot retrieve another user's conversations.
- An authenticated user can retrieve messages for an owned conversation.
- Conversation data is provider-neutral.
- Messages are displayed in chronological order.
- Search returns matching conversations.
- Filters return the correct conversations.
- Read and unread state is visible.
- Labels or tags are visible.
- Loading, empty, and error states are implemented.
- Fixture data is available for development and testing.
- Provider tokens are not exposed to the frontend.
- Gmail authorization is not implemented in this sprint.
- Gmail synchronization is not implemented in this sprint.
- Telegram integration is not implemented in this sprint.
- AI assistance is not implemented in this sprint.
- Backend tests pass.
- Frontend tests pass.
- Frontend lint passes.
- Frontend build passes.
- No secrets are committed.
- Documentation is updated.

## Expected Verification Commands

Run backend checks:

```powershell
Push-Location backend
php artisan migrate:status
php artisan test
Pop-Location
```

Run frontend checks:

```powershell
npm.cmd --prefix frontend run test
npm.cmd --prefix frontend run lint
npm.cmd --prefix frontend run build
```

Run repository checks:

```powershell
git diff --check
git status
```

## Definition of Done

Sprint 03 is complete when:

- All acceptance criteria are satisfied.
- Conversation and message structures are implemented.
- Authenticated API access is implemented.
- Ownership checks are implemented and tested.
- The unified inbox workspace is connected to backend data.
- Search and filtering are functional.
- Backend and frontend tests pass.
- Frontend lint and build pass.
- No secrets are committed.
- Documentation is updated.
- Changes are reviewed, committed, and pushed.

## Dependencies

- M0 is complete.
- M1 is complete.
- PostgreSQL is configured and available.
- Laravel authentication is available.
- Sanctum authentication is available.
- The frontend API client is available.
- The authenticated workspace exists.

## Out of Scope

- Gmail OAuth authorization.
- Gmail access and refresh tokens.
- Gmail synchronization.
- Telegram authorization.
- Telegram synchronization.
- AI-generated replies.
- AI-generated summaries.
- Sending messages through providers.
- Production deployment.
- Production email processing.
- Background synchronization jobs.

## Versioning

Sprint 03 does not automatically create a release or version tag.

A release tag may be created only after the related milestone outcome and release criteria are completed.

## Completion Review

Sprint 03 was implemented through pull request #3 and merged into `main`.

### Recorded Evidence

- Pull request: `#3`
- Merge commit: `c4b8a4c`
- Final implementation commit: `81d4ef7`
- GitHub Actions run: `34697818299`
- GitHub Actions conclusion: successful
- Provider-neutral conversation and message models: implemented
- Ownership-protected conversation APIs: implemented and tested
- Search and backend filtering: implemented and tested
- Development fixture data: implemented and tested
- Frontend conversation and message display: implemented
- Frontend tests, lint, and production build: passed

### Accepted Decisions

- Conversations and messages use provider-neutral database models.
- Search is performed server-side against conversation subject and provider.
- Filters use the `status`, `is_read`, and `label` query parameters.
- Labels are stored as JSON metadata.
- Development inbox data is created through the idempotent `InboxFixtureSeeder`.

### Satisfied Later

- Frontend pagination was implemented during Sprint 05.

### Carryover

- Add frontend status filtering.
- Add frontend label filtering.
- Display conversation status in the inbox.
- Add explicit frontend tests for conversation selection.
- Add explicit frontend tests for read-state filtering.
- Add explicit frontend tests for inbox loading and error states.
- Record these items in the remediation backlog.

## Sprint Decisions

| ID | Decision | Final State |
|---|---|---|
| S03-001 | Conversation database schema | Resolved: provider-neutral conversations with ownership, status, read state, labels, and provider identifiers |
| S03-002 | Message database schema | Resolved: provider-neutral messages related to conversations and ordered by `sent_at` |
| S03-003 | Search implementation approach | Resolved: authenticated server-side subject and provider search |
| S03-004 | Filter response format | Resolved: paginated JSON responses using status, read-state, and label query parameters |
| S03-005 | Fixture data strategy | Resolved: idempotent `InboxFixtureSeeder` |
| S03-006 | Sprint completion date | Resolved: 2026-09-12 |

## Sprint Status

Sprint 03 is completed with documented carryover.

The provider-neutral inbox foundation passed verification. Frontend pagination was satisfied during Sprint 05. Remaining status display, frontend filters, and interaction-test work is tracked in the Sprint 01–04 audit.