# SEC-011 - Block Remote Message Content by Default

> Status: Candidate
> Security Epic: SEC-EPIC-07 Sensitive Data Protection / SEC-EPIC-11 Privacy & GDPR Engineering
> Priority: P1
> Release Classification: V1 Required

## Problem / Risk

Automatically loading remote images/resources from email can disclose message-open activity, IP/network metadata, and tracking identifiers.

## Security Objective

Make message viewing privacy-preserving by default.

## Assets Protected

User IP/network metadata; message-open behavior; privacy state.

## Threats Addressed

Tracking pixels; sender correlation; unwanted third-party network requests.

## Dependencies

SEC-010.

## Acceptance Criteria

- Block externally hosted message resources by default.
- Do not issue hidden network requests simply because a message is opened.
- Provide an explicit user action if remote content loading is supported.
- Clearly indicate that remote content is blocked and why.
- Keep data/image handling within the approved rendering policy.

## Security Tests

- Verify remote image/resource fixtures do not cause a network request by default.
- Verify explicit load behavior only occurs after user action.

## Definition of Done

Opening provider messages does not automatically contact sender-controlled infrastructure.

## Related SDLC Artifacts

Frontend design; privacy requirements; security tests.

## V1 Inclusion Rationale

V1 Required. This item protects a material V1 security boundary or production-readiness concern.

## Planning Rule

This backlog item is not assigned to a Sprint by this file. Sprint selection occurs only through normal backlog prioritization and Sprint Planning.
