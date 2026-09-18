# SEC-006 - Sensitive-Action Reauthentication

> Status: Planned
> Security Epic: SEC-EPIC-02 Identity & Authentication / SEC-EPIC-05 Session & MFA Security
> Priority: P1
> Release Classification: V1 Required

## Problem / Risk

A stolen but otherwise valid session could perform high-impact security and provider operations.

## Security Objective

Require recent user verification before sensitive operations.

## Assets Protected

Account security settings; provider connections; exported/deleted private data.

## Threats Addressed

Session theft; unauthorized provider revocation/connection; destructive account changes.

## Dependencies

SEC-003; SEC-004/SEC-005.

## Acceptance Criteria

- Define which V1 operations require step-up authentication.
- Define the recent-authentication validity window.
- Require reauthentication before password changes, MFA changes, provider connection/disconnection, account deletion, and future private-data export.
- Fail securely when the recent-authentication requirement is not met.

## Security Tests

- Stale-session denial tests.
- Successful recently-reauthenticated action tests.
- Cross-user and replay negative tests.

## Definition of Done

All approved V1 sensitive actions enforce the documented reauthentication policy.

## Related SDLC Artifacts

Security requirements; authorization design; implementation; tests.

## V1 Inclusion Rationale

V1 Required. This item protects a material V1 security boundary or establishes evidence needed to release safely.

## Planning Rule

This backlog item is not assigned to a Sprint by this file. Sprint selection occurs only through normal backlog prioritization and Sprint Planning.
