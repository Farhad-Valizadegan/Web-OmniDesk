# SEC-008 - Provider Credential and Encryption-Key Management Baseline

> Status: Candidate
> Security Epic: SEC-EPIC-06 Provider Credential & OAuth Security / SEC-EPIC-08 Encryption & Key Management
> Priority: P1
> Release Classification: V1 Required baseline

## Problem / Risk

Provider tokens are encrypted, but the V1 key lifecycle, environment separation, and rotation/recovery procedures are not yet defined.

## Security Objective

Ensure provider credentials remain protected even if storage or operational boundaries are compromised.

## Assets Protected

Gmail access/refresh tokens; Telegram session material; OAuth client secrets; application encryption keys.

## Threats Addressed

Database theft; secret leakage; key compromise; credential reuse across environments.

## Dependencies

SEC-001; deployment design.

## Acceptance Criteria

- Document where encryption keys originate and how production keys are protected.
- Separate development/test/staging/production secrets.
- Define key rotation, revocation, backup/recovery, and incident procedures.
- Keep provider secrets out of frontend code, logs, and repository history.
- Verify encrypted-at-rest handling for provider credentials/session material.

## Security Tests

- Encrypted-storage verification.
- Serialization/log-redaction tests.
- Environment-separation configuration checks.

## Definition of Done

A practical V1 credential/key-management procedure is documented and verified; stronger KMS/HSM controls remain risk-driven future options.

## Related SDLC Artifacts

Security architecture; deployment; operations; incident response.

## V1 Inclusion Rationale

V1 Required baseline. This item protects a material V1 security boundary or establishes evidence needed to release safely.

## Planning Rule

This backlog item is not assigned to a Sprint by this file. Sprint selection occurs only through normal backlog prioritization and Sprint Planning.
