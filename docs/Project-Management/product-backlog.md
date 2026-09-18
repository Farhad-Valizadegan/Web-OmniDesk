# OmniDesk AI Product Backlog

> Status: Draft

## Purpose

This document contains the canonical prioritized Product Backlog for OmniDesk AI.

Backlog items represent capabilities, improvements, technical work, and verification work. They are selected for Sprint Planning only after sufficient refinement and prioritization.

Detailed refinement specifications for selected backlog items are stored under [backlog-items/](./backlog-items/). Those files expand individual items; they do not form a separate backlog. This document remains the single canonical Product Backlog.

## Identifier Governance

`PB-###` identifiers in this document are canonical and unique across the project.

Historical Sprint documents created before the current backlog numbering was established may contain superseded sprint-local `PB-*` references. Those legacy references are not canonical and must not be reused for new work. Where needed, historical Sprint documents contain an explicit traceability correction to the current Product Backlog.

Once a canonical Product Backlog ID is assigned, it must not be reassigned to unrelated work.

## Status Semantics

- **Candidate** describes proposed work that has not yet been accepted into this canonical Product Backlog.
- **Planned** means accepted into this Product Backlog; it does not mean Sprint-selected or implemented.
- **Refined** means sufficiently analyzed for prioritization and Sprint Planning.
- **In Progress** means active Sprint/execution work is underway.
- **Completed** requires the applicable Definition of Done and completion evidence.
- Sprint commitment is recorded by Sprint Planning and the Sprint plan, not by the `Planned` status alone.

Security backlog items use the separate canonical `SEC-###` namespace. They remain part of this same Product Backlog and follow the same refinement, prioritization, Sprint Planning, traceability, and identifier-governance rules as `PB-###` items.

## Priorities

- Must: Required for the initial product scope
- Should: Important but not blocking the initial product
- Could: Valuable future improvement
- Won't Yet: Explicitly outside the current scope

## Backlog Items
| ID | Backlog Item | Priority | Milestone | Status |
|---|---|---|---|---|
| PB-001 | Prepare the local development environment | Must | M0 | Completed |
| PB-002 | Establish Laravel backend foundation | Must | M0 | Completed |
| PB-003 | Establish React and TypeScript frontend foundation | Must | M0 | Completed |
| PB-004 | Configure PostgreSQL and database access | Must | M0 | Completed |
| PB-005 | Establish testing, formatting, and CI checks | Must | M0 | Completed |
| PB-006 | Implement user registration | Must | M1 | Completed |
| PB-007 | Implement login and logout | Must | M1 | Completed |
| PB-008 | Implement authenticated sessions | Must | M1 | Completed |
| PB-009 | Implement authorization and ownership checks | Must | M1 | Completed |
| PB-010 | Implement the initial workspace layout | Must | M1 | Completed |
| PB-011 | Implement the conversation list | Must | M3 | Completed |
| PB-012 | Implement conversation details and message display | Must | M3 | Completed |
| PB-013 | Implement search and filtering | Should | M3 | Completed |
| PB-014 | Implement user-managed local labels and tags | Should | TBD | Planned |
| PB-015 | Implement Gmail authorization | Must | M2 | Completed |
| PB-016 | Implement secure provider token storage | Must | M2 | Completed |
| PB-017 | Implement the Gmail connector | Must | M2 | Completed |
| PB-018 | Normalize Gmail communication data | Must | M2 | Completed |
| PB-019 | Implement initial synchronization | Must | M2 | Completed |
| PB-020 | Implement incremental synchronization | Must | M2 | Completed |
| PB-021 | Prevent duplicate synchronized records | Must | M2 | Completed |
| PB-022 | Record synchronization status and failures | Must | M2 | Completed |
| PB-023 | [Validate the Telegram integration mode](./backlog-items/PB-023-telegram-integration-mode.md) | Must | M4 | In Progress |
| PB-024 | Implement the Telegram connector | Must | M4 | Planned |
| PB-025 | Implement Telegram synchronization | Must | M4 | Planned |
| PB-026 | Isolate failures between providers | Must | M4 | Planned |
| PB-027 | Implement conversation summaries | Should | M5 | Planned |
| PB-028 | Implement editable reply suggestions | Should | M5 | Planned |
| PB-029 | Minimize context before AI processing | Must | M5 | Planned |
| PB-030 | Implement the AI provider abstraction | Must | M5 | Planned |
| PB-031 | Implement the AI suggestion lifecycle | Should | M5 | Planned |
| PB-032 | Prevent autonomous message sending | Must | M5 | Planned |
| PB-033 | Implement consistent API error responses | Must | M6 | Planned |
| PB-034 | Implement retry handling for temporary failures | Should | M6 | Planned |
| PB-035 | Verify security and ownership boundaries | Must | M6 | Planned |
| PB-036 | Add integration and end-to-end tests | Must | M6 | Planned |
| PB-037 | Prepare deployment and recovery documentation | Must | M6 | Planned |
| PB-038 | Prepare release notes and release candidate | Must | M6 | Planned |
| PB-039 | [Complete functional workspace sidebar navigation and active-state behavior](./backlog-items/PB-039-workspace-sidebar-navigation.md) | Should | TBD | Refined |
| PB-040 | [Implement a Starred workspace view](./backlog-items/PB-040-starred-workspace-view.md) | Should | TBD | Refined |
| PB-041 | [Implement a Labels workspace view with dynamic/custom label support](./backlog-items/PB-041-labels-workspace-view.md) | Should | TBD | Refined |
| PB-042 | [Define and implement the Settings workspace view or remove the placeholder until its scope is approved](./backlog-items/PB-042-settings-workspace-view.md) | Could | TBD | Refined |

## Security Backlog Items

These items were accepted from the read-only Security & Privacy Gap Analysis. Security remains a cross-cutting concern; these items are not a separate Security Sprint and are selected into normal Sprints according to risk, dependencies, and Sprint Goals.

| ID | Security Backlog Item | Priority | Release Classification | Status |
|---|---|---|---|---|
| SEC-001 | [Establish Threat Model and Risk Register](./backlog-items/SEC-001-threat-model-risk-register.md) | P1 | V1 Required | Completed |
| SEC-002 | [Data Classification, Retention, and Deletion Model](./backlog-items/SEC-002-data-classification-retention-deletion.md) | P1 | V1 Required | Completed |
| SEC-003 | [Harden Browser Authentication Session Architecture](./backlog-items/SEC-003-browser-auth-session-hardening.md) | P1 | V1 Required | Planned |
| SEC-004 | [Implement Email-Based Two-Step Verification](./backlog-items/SEC-004-email-two-step-verification.md) | P1 | V1 Required | Planned |
| SEC-005 | [Secure Email Verification, Password Change, and Account Recovery](./backlog-items/SEC-005-account-verification-password-recovery.md) | P1 | V1 Required | Planned |
| SEC-006 | [Sensitive-Action Reauthentication](./backlog-items/SEC-006-sensitive-action-reauthentication.md) | P1 | V1 Required | Planned |
| SEC-007 | [Expand Authorization and Cross-User Isolation Testing](./backlog-items/SEC-007-authorization-isolation-regression-matrix.md) | P1 | V1 Required | Planned |
| SEC-008 | [Provider Credential and Encryption-Key Management Baseline](./backlog-items/SEC-008-provider-credential-key-management.md) | P1 | V1 Required baseline | Planned |
| SEC-009 | [Provider Permission and Scope Minimization](./backlog-items/SEC-009-provider-permission-scope-minimization.md) | P1 | V1 Required | Planned |
| SEC-010 | [Harden Untrusted Message and HTML Rendering](./backlog-items/SEC-010-untrusted-message-html-rendering.md) | P1 | V1 Required | Planned |
| SEC-011 | [Block Remote Message Content by Default](./backlog-items/SEC-011-remote-message-content-privacy.md) | P1 | V1 Required | Planned |
| SEC-012 | [Sensitive Endpoint Rate Limiting and Abuse Protection](./backlog-items/SEC-012-sensitive-endpoint-rate-limiting.md) | P1 | V1 Required | Planned |
| SEC-013 | [Security Audit Trail and Log Redaction](./backlog-items/SEC-013-security-audit-log-redaction.md) | P1 | V1 Required baseline | Planned |
| SEC-014 | [Production TLS, Security Headers, and Browser Security](./backlog-items/SEC-014-production-transport-browser-security.md) | P1 | V1 Required | Planned |
| SEC-015 | [Database, Redis, and Storage Hardening](./backlog-items/SEC-015-database-redis-storage-hardening.md) | P1 | V1 Required | Planned |
| SEC-016 | [Backup Security, Recovery, and Secure Deletion](./backlog-items/SEC-016-backup-recovery-secure-deletion.md) | P1 | V1 Required before production data | Planned |
| SEC-017 | [Security Controls in CI/CD](./backlog-items/SEC-017-security-ci-cd.md) | P1 | V1 Required baseline | Planned |
| SEC-018 | [Privacy and GDPR Engineering Baseline](./backlog-items/SEC-018-privacy-gdpr-engineering.md) | P1 | V1 Required | Planned |
| SEC-019 | [Telegram Credential and Session Security Gate](./backlog-items/SEC-019-telegram-credential-session-security.md) | P1 | V1 Required before PB-024 | Planned |
| SEC-020 | [AI Security and Privacy Gate](./backlog-items/SEC-020-ai-security-privacy-gate.md) | P1 | V1 Required before M5 AI features | Planned |
| SEC-021 | [Security Monitoring and Incident Response Baseline](./backlog-items/SEC-021-security-monitoring-incident-response.md) | P2 | V1 Recommended | Planned |

### Security Prioritization Rules

- No security item is automatically assigned to a Sprint merely because it is P1 or V1 Required.
- SEC-001 and SEC-002 establish the security/risk/data baseline and should precede dependent controls.
- SEC-019 is a security gate before PB-024 Telegram connector implementation.
- SEC-020 is a security gate before M5 AI features.
- SEC-016 must be satisfied before production data relies on backup/recovery.
- Security findings are integrated into normal development Sprints rather than collected into one oversized Security Sprint.
- P0 findings, if discovered later, become immediate release-blocking candidates and trigger reprioritization.

## Backlog Item Expectations

Each selected backlog item should have:

- A clear description
- A related milestone when the milestone relationship has been approved
- A priority
- Acceptance criteria
- Known dependencies
- An implementation task breakdown
- Relevant tests
- A definition of done

## Scope Exclusions

The following items are outside the current approved scope:

- Autonomous message sending
- Unsupported provider integrations
- Unvalidated Telegram capabilities
- Unapproved AI features
- Production infrastructure decisions before deployment planning

## Backlog Management

The backlog is refined before Sprint Planning.

Items may be:

- Added
- Removed
- Reprioritized
- Split into smaller items
- Combined when appropriate
- Deferred until more information is available

A backlog item is not committed to a Sprint until it has been reviewed and selected during Sprint Planning.

Newly discovered work from a completed Sprint is added to the Product Backlog rather than silently expanding or reopening that Sprint.

## Relationship to Milestones and Sprints

Milestones describe meaningful project outcomes.

Backlog items describe individual capabilities or work items.

Sprints select a limited group of ready backlog items and produce a reviewable Increment.

One milestone may contain many backlog items and several Sprints.

PB-014 is intentionally not assigned to M3. M3 was completed with provider-label presentation and filtering; user-managed local labels and tags remain separate planned work and require prioritization before milestone or Sprint assignment.

The workspace-navigation items PB-039 through PB-042 have been refined but are not assigned to a Sprint or milestone. They were deprioritized behind the active M4 Telegram validation work and may be reconsidered during a future refinement, prioritization, and Sprint Planning cycle.

## Current Prioritization for the Next Planning Cycle

The next planning cycle must de-risk both the V1 security foundation and the M4 Telegram integration before implementation expands.

### Priority Band 0 - Security Foundation

These baseline items are completed:

1. SEC-001 - Establish Threat Model and Risk Register.
2. SEC-002 - Data Classification, Retention, and Deletion Model.

They do not require a separate Security Sprint. Their purpose is to establish the authoritative risk and data-lifecycle baseline that later security implementation depends on.

SEC-001 and SEC-002 may be selected in the same Sprint as a bounded technical-validation item when the Sprint Goal is coherent and capacity allows.

### Priority Band 1 - M4 Telegram Validation

The highest-priority product item remains:

1. PB-023 - Validate the Telegram integration mode.

PB-023 is already refined. Its technical spike must resolve the Telegram runtime/integration boundary before PB-024 connector implementation.

Security dependency:

- SEC-019 - Telegram Credential and Session Security Gate is mandatory before PB-024.
- SEC-019 depends on the PB-023 validation outcome for the final runtime/session boundary.
- Therefore SEC-019 must be refined/finalized against PB-023 runtime evidence after that evidence satisfies PB-023 acceptance criteria, before PB-024 can be selected for implementation. This dependency does not add SEC-019 to Sprint 07 or pre-assign it to a future Sprint.

Downstream M4 order remains:

1. PB-023 - Validate Telegram integration mode.
2. SEC-019 - Finalize Telegram credential/session security gate using the validated mode.
3. PB-024 - Implement Telegram connector.
4. PB-025 - Implement Telegram synchronization.
5. PB-026 - Isolate failures between providers.

This dependency order does not pre-assign all items to one Sprint.

### Priority Band 2 - Identity and Application Security

The following P1 security items remain high priority but require their normal refinement/implementation timing:

- SEC-003 - Browser authentication session hardening.
- SEC-004 - Email-based two-step verification.
- SEC-005 - Email verification/password recovery.
- SEC-006 - Sensitive-action reauthentication.
- SEC-007 - Authorization/isolation regression matrix.
- SEC-008 - Provider credential/key-management baseline.
- SEC-009 - Provider permission minimization.
- SEC-010 - Untrusted message/HTML rendering.
- SEC-011 - Remote message content privacy.
- SEC-012 - Sensitive endpoint abuse protection.
- SEC-013 - Security audit/log redaction.

Risk review confirms these remain P1. No evidence currently justifies downgrading them.

### Priority Band 3 - Production and Release Security

These items become release/deployment gates according to exposure timing:

- SEC-014 - Production TLS/security headers/browser security.
- SEC-015 - Database/Redis/storage hardening.
- SEC-016 - Backup security/recovery/deletion.
- SEC-017 - Security controls in CI/CD.
- SEC-018 - Privacy/GDPR engineering baseline.

They remain V1-oriented, but deployment-specific implementation should be synchronized with the actual deployment architecture rather than performed prematurely.

### Priority Band 4 - AI Security Gate

Before M5 AI functionality:

- SEC-020 - AI Security and Privacy Gate.

SEC-020 remains P1 before AI functionality is enabled. It must resolve context minimization, authorization, prompt-injection risk, provider data-use/retention, and Telegram-derived-data constraints.

### Priority Band 5 - Workspace and Organization Improvements

After required M4/security-gate work is sufficiently resolved:

- PB-039 - Workspace sidebar navigation.
- PB-040 - Starred workspace view.
- PB-041 - Labels workspace view.
- PB-014 - User-managed local labels and tags.

Dependency guidance remains:

- PB-039 precedes PB-040 and PB-041.
- PB-041 remains separate from PB-014.
- PB-041 should be reconsidered after Telegram integration semantics are known.

### Priority Band 6 - Deferred Settings Cleanup and Operational Maturity

- PB-042 remains Could priority; dedicated Settings workspace remains deferred.
- SEC-021 remains P2 / V1 Recommended and should follow SEC-013 because monitoring/incident response depends on useful security event evidence.

### Current Planning-Ready Items

Sprint 07 is already in progress. SEC-001 and SEC-002 are completed baseline work; PB-023 remains the active validation item.
- PB-039 - Workspace sidebar navigation, subject to current prioritization.
- PB-040 - Starred workspace view, subject to current prioritization.
- PB-041 - Labels workspace view, subject to current prioritization.
- PB-042 - Settings placeholder cleanup, subject to current prioritization.

For the immediate next Sprint, required de-risking work takes precedence over optional workspace improvements.

### Sprint 07 Selection

Sprint Planning is complete for the next bounded Sprint.

Sprint 07 selected:

1. PB-023 - Validate Telegram integration mode.
2. SEC-001 - Establish Threat Model and Risk Register.
3. SEC-002 - Data Classification, Retention, and Deletion Model.

Sprint Goal: establish the V1 security/data-risk baseline and validate the Telegram integration mode before connector implementation expands the attack surface.

No other P1 security item, M4 implementation item, or workspace enhancement is automatically committed to Sprint 07.

PB-024/PB-025/PB-026 remain outside Sprint 07. SEC-019 must be finalized against the PB-023 outcome before PB-024 can be selected into a later Sprint.

## Unresolved Decisions
| ID | Decision | Current State |
|---|---|---|
| BACKLOG-001 | Final backlog prioritization | Security foundation and M4 validation ordering updated; reprioritized continuously through refinement and Sprint Planning |
| BACKLOG-002 | Acceptance criteria for planned items | Defined during refinement before Sprint selection |
| BACKLOG-003 | Future Sprint assignments | Sprint 07 assigned through completed Sprint Planning; later Sprint numbers remain unassigned until future Sprint Planning |
| BACKLOG-004 | Telegram scope | TDLib/MTProto long-lived-adapter direction selected; PB-023 runtime evidence remains pending before SEC-019 finalization and PB-024 implementation |
| BACKLOG-005 | Final AI provider | Unresolved |
| BACKLOG-006 | Priority and milestone assignment for PB-039 through PB-042 | Prioritization resolved: deferred behind M4; milestone remains TBD until future selection |
| BACKLOG-007 | Milestone and priority for PB-014 local labels/tags | Should priority retained; deferred behind M4; milestone remains TBD until future selection |
