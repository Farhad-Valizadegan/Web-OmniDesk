# SEC-001 - Establish Threat Model and Risk Register

> Status: Refined
> Security Epic: SEC-EPIC-01 Security Architecture & Threat Modeling
> Priority: P1
> Release Classification: V1 Required

## Problem / Risk

Security controls already exist across authentication, Gmail OAuth, provider-token storage, authorization, synchronization, rendering, testing, deployment planning, and privacy requirements, but there is no single authoritative threat/risk model tying those controls together.

Without that model, later security work can become checklist-driven instead of risk-driven.

## Security Objective

Make assets, trust boundaries, entry points, threat scenarios, existing controls, required controls, and residual risk explicit enough to drive prioritization and release decisions.

## Current Repository Evidence

The current repository already contains controls and design evidence relevant to this item:

- Laravel authentication with hashed passwords.
- CAPTCHA and login/registration throttling.
- Finite Sanctum bearer-token lifetime.
- Gmail access/refresh tokens encrypted at rest through Laravel encrypted casts and hidden from serialization.
- Gmail OAuth state stored temporarily and consumed once.
- Authenticated-user scoping for Gmail connection operations.
- Conversation/message ownership checks and negative cross-user tests.
- Safe provider error handling.
- Security/privacy requirements under Requirements Engineering.
- System-boundary, data-flow, authentication/authorization, data-model, and deployment-design documents.
- CI coverage for backend tests and frontend quality checks.

These controls reduce risk, but they are not yet connected through one threat-to-risk-to-control traceability model.

## Assets Protected

### Highly Sensitive

- Gmail access tokens.
- Gmail refresh tokens.
- Future Telegram authorization/session material.
- Authentication/session credentials.
- Password-reset and MFA/OTP secrets when introduced.
- Encryption keys and application secrets.

### Confidential

- Email and Telegram message bodies.
- Conversation metadata.
- Contact/sender/recipient information.
- Attachments when supported.
- AI prompts/context and generated results containing communication data.
- Provider account identifiers.

### Internal / Operational

- Synchronization state.
- Provider failure information.
- audit/security event metadata.
- queue/cache state.
- deployment and configuration metadata.

## Trust Boundaries

The threat model must explicitly cover at least these boundaries:

1. Browser -> React application.
2. React application -> Laravel API.
3. Laravel API -> PostgreSQL.
4. Laravel API / workers -> Redis or queue/cache infrastructure.
5. Laravel/worker -> Gmail/Google OAuth APIs.
6. Laravel/worker -> future Telegram client/API runtime.
7. Laravel/worker -> future AI provider.
8. Application runtime -> filesystem/object storage/backups.
9. CI/CD -> repository/build/deployment environments.
10. Administrator/developer -> production infrastructure and secrets.

## Primary Entry Points

- Registration.
- Login.
- CAPTCHA.
- Authenticated REST API routes.
- Gmail authorization initiation.
- Gmail OAuth callback.
- Gmail synchronization endpoints.
- Conversation/message retrieval.
- Provider disconnect.
- Future Telegram authorization/session endpoints.
- Future AI endpoints.
- Future attachment/file endpoints.
- CI/CD inputs and dependency updates.

## Threat Categories

Use STRIDE where useful, supplemented by privacy and supply-chain analysis.

### Spoofing

- Credential stuffing or stolen-password login.
- Stolen bearer-token replay.
- Stolen provider-token/session replay.
- OAuth/provider impersonation.

### Tampering

- Unauthorized modification of user-owned resources.
- Queue/job payload manipulation.
- audit/log tampering.
- CI/CD or dependency tampering.

### Repudiation

- Inability to prove security-sensitive actions occurred.
- insufficient provider-connect/disconnect and authentication event evidence.

### Information Disclosure

- Cross-user conversation/message leakage.
- provider-token leakage.
- sensitive logging.
- remote email content leaking user activity/IP data.
- AI context over-disclosure.
- backup/database compromise.

### Denial of Service

- authentication abuse.
- provider synchronization abuse.
- provider quota exhaustion.
- expensive AI requests.
- queue/worker exhaustion.

### Elevation of Privilege

- IDOR/BOLA.
- ownership-check bypass.
- insecure administrative capability.
- compromised session used for sensitive actions without reauthentication.

### Privacy-Specific Threats

- unnecessary provider-data replication.
- excessive retention.
- tracking pixels/remote resources.
- unintended third-party AI disclosure.
- incomplete disconnect/account-deletion cleanup.

### Supply-Chain / Operational Threats

- vulnerable Composer/npm dependencies.
- malicious package/version.
- compromised GitHub Action.
- leaked CI/deployment secrets.
- insecure production database/Redis exposure.

## Initial Risk Register

| Risk ID | Scenario | Existing Controls | Residual Concern | Initial Priority | Primary Backlog |
|---|---|---|---|---|---|
| RISK-001 | XSS steals browser-readable bearer token | sandboxed message rendering; finite token lifetime | token remains readable from JavaScript/sessionStorage | P1 | SEC-003, SEC-010 |
| RISK-002 | Stolen/reused password gives account access | password hashing; CAPTCHA; throttling | no second factor | P1 | SEC-004 |
| RISK-003 | Recovery workflow becomes takeover path | no insecure custom recovery observed | full recovery/verification lifecycle not implemented | P1 | SEC-005 |
| RISK-004 | Stolen valid session performs high-impact action | authentication + ownership checks | no explicit recent-authentication gate | P1 | SEC-006 |
| RISK-005 | User A accesses User B data | user-scoped queries; ownership checks; negative tests | future resource types may regress | P1 | SEC-007 |
| RISK-006 | Database compromise exposes provider access | encrypted provider tokens; hidden serialization | key lifecycle/rotation boundary undefined | P1 | SEC-008 |
| RISK-007 | Excess provider permission increases blast radius | Gmail uses readonly mailbox scope | all provider scopes not yet formally mapped/justified | P1 | SEC-009 |
| RISK-008 | Malicious provider HTML executes active content | existing sandbox/sanitization controls | custom rendering boundary requires systematic security validation | P1 | SEC-010 |
| RISK-009 | Remote email resources disclose open/IP metadata | sandboxing limits active content | remote-resource privacy policy incomplete | P1 | SEC-011 |
| RISK-010 | Sensitive/expensive APIs abused | auth endpoints throttled; provider errors handled | sync/MFA/recovery/AI rate limits incomplete | P1 | SEC-012 |
| RISK-011 | Logs leak secrets/private content | requirements prohibit sensitive logs; safe error tests exist | centralized audit/redaction verification absent | P1 | SEC-013 |
| RISK-012 | Production transport/browser config is weak | deployment design requires HTTPS | concrete production headers/TLS/CORS baseline unresolved | P1 | SEC-014 |
| RISK-013 | PostgreSQL/Redis/storage compromise | deployment docs require restricted access | production topology/hardening unresolved | P1 | SEC-015 |
| RISK-014 | Backup copy becomes weakest data store | backup planning acknowledged | encryption/access/retention/restore not yet defined | P1 | SEC-016 |
| RISK-015 | Vulnerable/leaked dependency or secret reaches main | CI tests/lint/build | dedicated dependency/secret/SAST security gates absent | P1 | SEC-017 |
| RISK-016 | Privacy/GDPR behavior is inconsistent | privacy principles and data minimization requirements exist | lifecycle/legal-processing engineering model incomplete | P1 | SEC-018 |
| RISK-017 | Telegram session theft compromises Telegram account | PB-023 identifies sensitive session/auth state | final Telegram runtime/session boundary not yet validated | P1 | SEC-019 |
| RISK-018 | AI leaks context or follows malicious provider instructions | AI is user-initiated; autonomous send prohibited | AI boundary not yet implemented/security-gated | P1 before M5 | SEC-020 |
| RISK-019 | Compromise is not detected/contained quickly | safe failures and CI exist | monitoring/incident-response baseline incomplete | P2 | SEC-021 |

## Risk Assessment Method

For V1 planning:

- Likelihood: Low / Medium / High.
- Impact: Low / Medium / High / Critical.
- Priority is based on likelihood, impact, exploitability, dependency timing, and V1 exposure.
- P0 is reserved for confirmed critical/release-blocking conditions.
- P1 is high-priority V1 security work or a mandatory gate before an exposed capability.
- P2 is medium-risk or operational maturity work.
- P3 is hardening/future work.

No P0 issue is currently confirmed by this refinement.

## Acceptance Criteria

- Security-sensitive assets are inventoried.
- Principal trust boundaries and entry points are documented.
- STRIDE/privacy/supply-chain threat categories are applied.
- A repository-specific initial risk register exists.
- Existing controls and residual concerns are recorded.
- High-risk scenarios trace to security backlog items.
- Review triggers are defined for new providers, AI features, attachments, deployment changes, authentication changes, and newly discovered threats.

## Review Triggers

Reassess this threat model when:

- Telegram integration architecture is finalized.
- AI provider/data flow is finalized.
- attachment handling is introduced.
- authentication/session architecture changes.
- a new provider is added.
- deployment topology changes.
- new externally reachable APIs are added.
- a security incident or material vulnerability is discovered.

## Security Tests

- Review the threat model against implemented routes, providers, data stores, workers, CI/CD, and planned AI boundaries.
- During later implementation, trace each P1 risk to at least one implemented control and verification activity.

## Definition of Done

This item is ready for Sprint Planning when the threat model, initial risk register, traceability, and review triggers are sufficiently specific to drive implementation decisions.

Completion requires the authoritative threat/risk artifact to be reviewed and accepted during the selected Sprint.

## Related SDLC Artifacts

- Project Definition: scope, stakeholders, assumptions/constraints.
- Requirements Engineering: security/privacy requirements.
- System Analysis: system boundary and data flows.
- System Design: authentication, data model, deployment, provider and AI boundaries.
- Testing: security verification.
- Project Management: security backlog and risk prioritization.

## V1 Inclusion Rationale

V1 Required. OmniDesk centralizes communication content and provider credentials, so security prioritization must be based on an explicit threat/risk model rather than isolated controls.

## Planning Readiness

SEC-001 is **ready for Sprint Planning**.

It should be selected before or alongside dependent security implementation work, but it does not require a separate Security Sprint.
