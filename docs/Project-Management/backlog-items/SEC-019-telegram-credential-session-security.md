# SEC-019 - Telegram Credential and Session Security Gate

> Status: Completed
> Security Epic: SEC-EPIC-06 Provider Credential & OAuth Security / SEC-EPIC-07 Sensitive Data Protection
> Priority: P1
> Release Classification: V1 Required before PB-024

## Problem / Risk

A Telegram client integration may introduce highly privileged persistent session material capable of accessing the user's Telegram account.

## Security Objective

Prevent Telegram implementation from creating a weaker credential boundary than Gmail.

## Assets Protected

Telegram API credentials; authorization/session state; user messages; account identity.

## Threats Addressed

Telegram session theft; account takeover; cross-user session leakage; credential logging; insecure disconnect.

## Dependencies

PB-023; SEC-001; SEC-008; final Telegram runtime decision.

## Acceptance Criteria

- Classify Telegram authorization/session material as Highly Sensitive.
- Keep Telegram session material backend-only and encrypted at rest.
- Scope every Telegram connection/session to the authenticated OmniDesk user.
- Define authorization-state handling, restart recovery, disconnect/revocation, and secret deletion behavior.
- Prevent session/API credentials from logs and repository history.
- Document the Laravel-to-Telegram runtime trust boundary.

## Security Tests

- Cross-user session isolation tests.
- Encrypted-storage verification.
- Restart/session-recovery tests.
- Disconnect/revocation tests.
- Secret-redaction tests.

## Definition of Done

PB-024 cannot begin until the approved Telegram credential/session architecture and its security tests are documented.

## Current Implementation Evidence

- Application API ID and API Hash remain server-side configuration.
- Users submit only their phone number, Telegram code, and optional two-step password.
- Phone values and TDLib database keys use Laravel encrypted casts.
- Every provider connection receives a separate runtime directory and process lock.
- Authenticated status, content, history, media, and disconnect routes resolve the current user's connection.
- Feature tests cover cross-user status isolation, staged authorization, connected-content access, and unauthenticated rejection.

Real TDLib authorization, content retrieval, and refresh recovery were validated on 2026-10-01. Automated tests cover owner isolation across multiple application users. Disconnect now records a durable cleanup state before deleting data, retries interrupted cleanup on the next status request, removes only the owning connection's private runtime, and purges stale authorization commands before restarting a worker. The final repository scan found no tracked environment file, private key, provider runtime, or assigned production secret.

## Completion Evidence

- Telegram-focused tests: 12 passed with 45 assertions.
- Full backend suite: 119 tests and 503 assertions passed after the crash-cleanup slice.
- PHP formatting checks passed.
- Real account authorization, content loading, and browser-refresh recovery passed.
- Local environment files and Telegram runtime/session paths are ignored by Git.

## Related SDLC Artifacts

PB-023; System Analysis; System Design; Provider Security; Testing.

## V1 Inclusion Rationale

Completed as the V1 security gate before PB-024. Broader production backup, key-management, and infrastructure controls remain owned by SEC-008, SEC-016, and related security backlog items.

## Planning Rule

This backlog item is not assigned to a Sprint by this file. Sprint selection occurs only through normal backlog prioritization and Sprint Planning.
