# Sprint 04 Plan - Gmail Provider Integration

> Status: Draft

## Sprint Goal

Implement the secure Gmail provider connection foundation for OmniDesk AI.

Sprint 04 covers Gmail authorization, secure credential storage, provider isolation, account identity retrieval, and provider error handling. Gmail synchronization belongs to Sprint 05.

## Related Milestone

M2 - Gmail Integration

Sprint 04 contributes to M2 only.

## Related Backlog Items

| ID | Backlog Item | Priority |
|---|---|---|
| PB-020 | Define Gmail integration boundary | Must |
| PB-021 | Implement Gmail OAuth authorization | Must |
| PB-022 | Store Gmail credentials securely | Must |
| PB-023 | Implement Gmail provider connector | Must |
| PB-024 | Normalize Gmail account data | Must |
| PB-025 | Classify Gmail provider errors | Must |
| PB-026 | Implement Gmail connect and disconnect flow | Must |
| PB-027 | Add Gmail authorization tests | Must |
| PB-028 | Add Gmail integration interface states | Should |

## Objectives

Sprint 04 shall:

- Define the Gmail provider boundary and connector interface.
- Configure Gmail OAuth through environment variables.
- Implement authorization redirect and callback handling.
- Validate the OAuth state parameter.
- Associate Gmail connections with authenticated users.
- Retrieve and normalize the Gmail account identity.
- Encrypt access and refresh credentials at rest.
- Implement Gmail connection status and disconnect operations.
- Classify authorization, permission, rate-limit, and provider errors.
- Keep Gmail provider code isolated from core inbox models.
- Add frontend Gmail connection states.
- Never store Gmail tokens or client secrets in the frontend.
- Keep Gmail synchronization outside this sprint.
- Keep Telegram and AI functionality outside this sprint.
- Preserve existing authentication and inbox functionality.

## API Scope

| Method | Endpoint | Purpose |
|---|---|---|
| GET | `/api/integrations/gmail/authorize` | Start Gmail authorization |
| GET | `/api/integrations/gmail/callback` | Handle Gmail OAuth callback |
| GET | `/api/integrations/gmail` | Return Gmail connection status |
| DELETE | `/api/integrations/gmail` | Disconnect Gmail |

## Security Requirements

- Gmail client secrets remain server-side.
- Access and refresh credentials are encrypted.
- OAuth state is validated.
- Credentials are never returned in API responses.
- Credentials are never written to logs.
- Gmail scopes use least privilege.
- Authorization initiation and connection management require authentication; the OAuth callback is protected by validated state bound to the initiating user.
- Provider connections enforce user ownership.
- Provider errors do not reveal sensitive details.
- No secrets are committed to Git.

## Testing Requirements

- Test authenticated authorization access.
- Test unauthenticated authorization rejection.
- Test OAuth callback validation.
- Test invalid OAuth state rejection.
- Test connection ownership.
- Test account identity retrieval.
- Test connection persistence.
- Test disconnect behavior.
- Test encrypted credential storage.
- Test hidden credentials.
- Test safe provider error handling.
- Test frontend connection and error states.
- Run all existing backend and frontend tests.

## Acceptance Criteria

- An authenticated user can start Gmail authorization.
- The OAuth callback validates the state parameter.
- A valid Gmail connection is associated with the authenticated user.
- The Gmail account identity is retrieved and normalized.
- Credentials are encrypted and hidden from responses.
- Unauthenticated users cannot start authorization or manage connections, and callbacks without valid user-bound OAuth state are rejected.
- Users cannot access another user's connection.
- A user can disconnect Gmail.
- Provider errors are handled safely.
- Frontend connection states are implemented.
- Frontend tokens are not stored.
- Gmail synchronization is not implemented in Sprint 04.
- Backend tests pass.
- Frontend tests, lint, and build pass.
- No secrets are committed.
- Documentation is updated.

## Dependencies

- M0, M1, and Sprint 03 are complete.
- PostgreSQL and Laravel are configured.
- The frontend API client is available.
- A Gmail Cloud project is configured.
- OAuth redirect URIs are configured.
- Gmail credentials are available through environment variables.

## Out of Scope

- Gmail message synchronization
- Incremental synchronization
- Gmail push notifications
- Telegram integration
- AI assistance
- Autonomous message sending
- Production deployment

## Versioning

Sprint 04 does not automatically create a release tag.

A release tag may be created only after the related milestone outcome and release criteria are complete.

## Unresolved Sprint Decisions

| ID | Decision | Current State |
|---|---|---|
| S04-001 | Gmail OAuth library | To be finalized |
| S04-002 | Required Gmail scopes | To be finalized |
| S04-003 | Provider connection schema | To be finalized |
| S04-004 | Credential encryption approach | To be finalized |
| S04-005 | OAuth callback error format | To be finalized |
| S04-006 | Sprint completion date | Not yet determined |

## Sprint Status

Sprint 04 has not yet been completed.

Completion will be recorded after the acceptance criteria and verification commands have passed.
