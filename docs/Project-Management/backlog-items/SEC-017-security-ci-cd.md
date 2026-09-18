# SEC-017 - Security Controls in CI/CD

> Status: Planned
> Security Epic: SEC-EPIC-14 Supply-Chain & CI/CD Security / SEC-EPIC-17 Security Testing & Verification
> Priority: P1
> Release Classification: V1 Required baseline

## Problem / Risk

The current CI validates tests, formatting, lint, and build quality but lacks a defined security-scanning baseline.

## Security Objective

Detect vulnerable dependencies, leaked secrets, and common security regressions before merge/release.

## Assets Protected

Source code; dependencies; repository secrets; build pipeline; release artifacts.

## Threats Addressed

Supply-chain compromise; vulnerable packages; committed secrets; insecure code changes; CI dependency compromise.

## Dependencies

Existing CI pipeline; repository permissions.

## Acceptance Criteria

- Add Composer dependency vulnerability review.
- Add npm dependency vulnerability review.
- Add secret scanning.
- Add an appropriate SAST baseline.
- Document failure/exception handling for scanner findings.
- Review third-party GitHub Actions pinning and permission minimization.

## Security Tests

- Validate scanners execute in pull requests and detect controlled test fixtures or documented known findings without exposing secrets.

## Definition of Done

The PR/release pipeline runs the approved security checks with a documented triage and exception process.

## Related SDLC Artifacts

CI/CD; Testing; Deployment; Security Operations.

## V1 Inclusion Rationale

V1 Required baseline. Selection remains risk- and dependency-driven rather than automatically assigned to one Security Sprint.

## Planning Rule

This backlog item is not assigned to a Sprint by this file. Sprint selection occurs only through normal backlog prioritization and Sprint Planning.
