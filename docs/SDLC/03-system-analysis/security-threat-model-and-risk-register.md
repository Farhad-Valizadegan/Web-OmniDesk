# Security Threat Model and Risk Register

> Status: Approved Baseline
> Source Backlog Item: SEC-001
> Baseline Date: 2026-09-18

## Purpose

This document is the authoritative V1 threat-model baseline for OmniDesk.

It converts the SEC-001 refinement into an SDLC analysis artifact used to prioritize security requirements, design controls, tests, and release gates.

## Protected Assets

### Highly Sensitive

- OmniDesk authentication/session credentials.
- provider access/refresh tokens.
- Telegram authorization/session material.
- password-reset and MFA/OTP secrets.
- encryption keys and application secrets.

### Confidential

- message bodies and attachments.
- conversation metadata.
- sender/recipient/contact information.
- provider account identifiers.

### Operational

- synchronization state.
- provider error data.
- audit/security-event metadata.
- queue/cache state.
- deployment/configuration metadata.

## Trust Boundaries

1. Browser -> React client.
2. React client -> Laravel REST API.
3. Laravel -> PostgreSQL.
4. Laravel/workers -> Redis/queue/cache.
5. Laravel/workers -> Gmail/Google OAuth.
6. Laravel -> future long-lived Telegram/TDLib adapter.
7. Telegram adapter -> Telegram API/MTProto.
9. Application -> filesystem/object storage/backups.
10. CI/CD -> build/deployment environment.
11. developer/operator -> production infrastructure/secrets.
12. hosting/cloud platform -> application host -> managed database/storage -> snapshots/backups -> infrastructure administrators.

## Entry Points

- registration;
- login;
- CAPTCHA;
- authenticated REST endpoints;
- Gmail OAuth initiation/callback;
- Gmail synchronization/disconnect;
- conversation/message retrieval;
- future Telegram authorization/adapter endpoints;
- future attachment/file endpoints;
- CI/CD inputs and dependency changes.

## Threat Analysis

### Spoofing

- credential stuffing/stolen passwords;
- stolen bearer-token replay;
- stolen provider credential/session replay;
- provider/OAuth impersonation.

### Tampering

- unauthorized resource modification;
- queue/job payload tampering;
- log/audit tampering;
- dependency/CI tampering.

### Repudiation

- insufficient evidence for sensitive account/provider actions;
- missing security-event traceability.

### Information Disclosure

- cross-user data leakage;
- provider-token/session leakage;
- sensitive logging;
- remote-content tracking;
- backup/database compromise;
- privileged infrastructure/operator access to application-readable communication data.

### Denial of Service

- authentication abuse;
- synchronization abuse;
- provider quota exhaustion;
- queue/worker exhaustion.

### Elevation of Privilege

- IDOR/BOLA;
- ownership-check bypass;
- compromised session used for sensitive operations;
- insecure future administrative capabilities.

### Privacy Threats

- excess data replication;
- unnecessary retention;
- tracking pixels/remote resources;
- incomplete disconnect/account deletion.

### Supply-Chain / Operational Threats

- vulnerable Composer/npm/native dependencies;
- compromised GitHub Actions;
- leaked CI/deployment secrets;
- insecure database/Redis exposure.

## Risk Register

| Risk | Scenario | Existing Controls | Residual Concern | Priority | Control Backlog |
|---|---|---|---|---|---|
| RISK-001 | XSS steals browser-readable auth token | finite token lifetime; constrained rendering | token remains JavaScript-readable | P1 | SEC-003, SEC-010 |
| RISK-002 | password compromise gives account access | hashing; CAPTCHA; throttling | no second factor | P1 | SEC-004 |
| RISK-003 | recovery path enables takeover | no unsafe custom recovery observed | lifecycle incomplete | P1 | SEC-005 |
| RISK-004 | valid stolen session performs high-impact action | authentication/ownership checks | no recent-authentication gate | P1 | SEC-006 |
| RISK-005 | cross-user object access | scoped queries; ownership tests | future resources may regress | P1 | SEC-007 |
| RISK-006 | DB compromise exposes provider access | encrypted Gmail tokens | key lifecycle/rotation incomplete | P1 | SEC-008 |
| RISK-007 | over-broad provider permission enlarges blast radius | Gmail readonly scope | formal provider-scope mapping incomplete | P1 | SEC-009 |
| RISK-008 | malicious message content executes | rendering safeguards exist | systematic validation required | P1 | SEC-010 |
| RISK-009 | remote message content tracks user | active content constrained | remote-resource policy incomplete | P1 | SEC-011 |
| RISK-011 | logs expose private/secrets | requirements prohibit it | centralized redaction/audit verification absent | P1 | SEC-013 |
| RISK-012 | weak production browser/transport config | HTTPS required by design | concrete headers/TLS/CORS baseline incomplete | P1 | SEC-014 |
| RISK-013 | DB/Redis/storage or privileged infrastructure access exposes sensitive application data | restricted access required by design | production hardening and the persisted-message protection model remain incomplete; privileged infrastructure access may reach application-readable data | P1 | SEC-015 |
| RISK-014 | backups become weakest copy | backup need documented | encryption/access/retention/restore incomplete | P1 | SEC-016 |
| RISK-015 | vulnerable dependency/secret reaches main | CI tests/lint/build | dedicated security gates absent | P1 | SEC-017 |
| RISK-016 | privacy lifecycle inconsistent | minimization/privacy requirements exist | lifecycle implementation incomplete | P1 | SEC-018 |
| RISK-017 | Telegram session theft compromises account | PB-023 security constraints | production session boundary not implemented | P1 | SEC-019 |
| RISK-019 | compromise is not detected/contained | safe failures/CI exist | monitoring/incident process incomplete | P2 | SEC-021 |

## Infrastructure Trust Assumption

The current V1 server-side architecture assumes trust in the approved application-hosting, database/storage, snapshot, and backup operational boundary. Where OmniDesk must process readable message content, sufficiently privileged infrastructure/platform administrators may technically be capable of accessing plaintext application data.

Reducing this trust would require a materially different architecture, such as application-level encryption with externally separated keys, client-side encryption, confidential computing, or equivalent controls. These are not automatically V1 requirements. The V1 protection decision is governed by SEC-015, with key-lifecycle implications under SEC-008 and backup implications under SEC-016.

## Current Assessment

No confirmed P0 vulnerability is recorded in the current baseline.

P1 items remain release- or capability-gating work according to exposure and dependency timing. P2/P3 work remains subject to continuous reprioritization.

## Review Triggers

Re-run threat modeling when:

- Telegram integration/session architecture changes;
- attachment handling is introduced;
- authentication/session architecture changes;
- a provider is added;
- deployment topology changes;
- new externally reachable APIs appear;
- a material vulnerability/security incident is discovered.

## Traceability Rule

Every retained P1 risk must trace to:

```text
Risk
-> Security Requirement
-> SEC/PB backlog item
-> Design control
-> Implementation
-> Security test
-> Release evidence
```

## SEC-001 Completion Evidence

SEC-001 is satisfied at the analysis/baseline level because:

- security assets are classified;
- trust boundaries and entry points are explicit;
- STRIDE/privacy/supply-chain categories are applied;
- the initial repository-specific risk register exists;
- existing controls/residual concerns are captured;
- P1 risks trace to backlog items;
- review triggers are defined.

The model remains a living artifact and must be updated when a review trigger occurs.
