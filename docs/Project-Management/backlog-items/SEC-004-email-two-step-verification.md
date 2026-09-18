# SEC-004 - Implement Email-Based Two-Step Verification

> Status: Candidate
> Security Epic: SEC-EPIC-02 Identity & Authentication / SEC-EPIC-05 Session & MFA Security
> Priority: P1
> Release Classification: V1 Required

## Problem / Risk

A valid password currently grants direct access to the account.

## Security Objective

Require a second email-delivered one-time verification step before creating the normal authenticated session.

## Assets Protected

User account; private communications; provider connections.

## Threats Addressed

Credential stuffing; password reuse; stolen passwords; brute-force login.

## Dependencies

SEC-003; verified email ownership; production-capable mail delivery.

## Acceptance Criteria

- Generate a cryptographically secure one-time code.
- Store only a hash of the code server-side.
- Expire challenges after a short documented period.
- Limit verification attempts and resend frequency.
- Invalidate previous codes when a replacement is issued.
- Do not create the normal authenticated session until verification succeeds.
- Do not log OTP values or challenge secrets.

## Security Tests

- Correct, incorrect, expired, reused, and replaced OTP cases.
- Attempt-limit and resend-cooldown tests.
- Cross-user challenge isolation.
- No-auth-session-before-success test.

## Definition of Done

Email two-step verification works end-to-end and all abuse/security tests pass without plaintext OTP persistence.

## Related SDLC Artifacts

Authentication requirements; design; implementation; testing.

## V1 Inclusion Rationale

V1 Required. This item protects a material V1 security boundary or establishes evidence needed to release safely.

## Planning Rule

This backlog item is not assigned to a Sprint by this file. Sprint selection occurs only through normal backlog prioritization and Sprint Planning.
