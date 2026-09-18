# SEC-002 - Data Classification, Retention, and Deletion Model

> Status: Completed
> Security Epic: SEC-EPIC-07 Sensitive Data Protection / SEC-EPIC-11 Privacy & GDPR Engineering / SEC-EPIC-15 Backup, Recovery & Data Lifecycle
> Priority: P1
> Release Classification: V1 Required

## Problem / Risk

OmniDesk AI intentionally creates a local normalized workspace from external communication providers. That makes data minimization, retention, deletion, caching, logging, backup handling, and third-party transfer explicit security/privacy concerns.

The repository identifies several relevant entities and privacy principles, but one authoritative V1 lifecycle inventory is still required.

## Security Objective

Define what V1 data exists, why it is needed, how sensitive it is, where it moves, whether it must be persisted, and what event removes it.

## Dependencies

- SEC-001 threat/risk model.
- Final Telegram implementation mode for exact Telegram session-data details.
- Final AI provider/design for exact AI data-transfer and retention details.
- Deployment planning for exact backup-retention periods.

## Classification Model

### Public

Information intentionally public and not tied to a private user context.

Examples:

- public project documentation.
- public application marketing/help content.

### Internal

Operational data that should not be public but normally does not expose private communication content by itself.

Examples:

- safe health/status information.
- non-sensitive deployment metadata.
- sanitized synchronization status/failure categories.
- non-sensitive feature configuration.

### Confidential

Personal or user-specific information that can cause privacy harm if disclosed.

Examples:

- user name and email.
- Gmail/Telegram account identifiers.
- sender/recipient addresses.
- conversation metadata and subjects.
- provider labels.
- synchronization metadata tied to a user.
- IP/device metadata when collected.
- audit events tied to a user.

### Highly Sensitive

Data capable of exposing private communications, authenticating as the user, granting provider access, or revealing substantial private content.

Examples:

- message bodies.
- attachments.
- Gmail access/refresh tokens.
- future Telegram authorization/session material.
- authentication/session credentials.
- password-reset and MFA/OTP secrets.
- encryption keys.
- AI prompts/context containing communication content.
- AI-generated outputs containing private communication content.
- backups containing any of the above.

## V1 Data Inventory

| Data Category | Classification | Origin | V1 Purpose | Persistence Direction | External Transfer |
|---|---|---|---|---|---|
| User name/email | Confidential | user | account identity/authentication | PostgreSQL | mail provider where required for verification/recovery |
| Password hash | Highly Sensitive authentication data | user-derived | credential verification | PostgreSQL | none |
| Auth session/token state | Highly Sensitive | OmniDesk | authenticated access | session/token store | browser receives only the credential mechanism required by the approved auth design |
| CAPTCHA challenge state | Internal/Sensitive temporary | OmniDesk | abuse prevention | temporary cache only | Google only when Google CAPTCHA is configured |
| Gmail account identifiers/email/avatar | Confidential | Google | identify connected account | PostgreSQL where needed | displayed to owning user |
| Gmail access token | Highly Sensitive | Google OAuth | provider API access | encrypted persistence | Google APIs only |
| Gmail refresh token | Highly Sensitive | Google OAuth | renew/revoke provider access | encrypted persistence | Google OAuth APIs only |
| Gmail OAuth state | Highly Sensitive temporary authorization state | OmniDesk | bind callback to initiating user | temporary cache only | round-trip through browser/provider authorization flow |
| Conversation identifiers/subject/status/labels | Confidential | provider + OmniDesk | unified workspace/search/filtering | PostgreSQL | owning user; future AI only when required |
| Message sender/recipient metadata | Confidential | provider | unified message display | PostgreSQL | owning user; future AI only when required |
| Message body | Highly Sensitive | provider | unified message display/search/approved AI features | PostgreSQL under current normalized-data model | owning user; AI only when approved/required |
| Message provider metadata | Confidential to Highly Sensitive depending on field | provider | synchronization/normalization | persist only required fields | normally none beyond provider/user-facing behavior |
| Attachment metadata | Confidential | provider | display/download support if approved | persist only required metadata | owning user |
| Attachment content | Highly Sensitive | provider | only if attachment feature is approved | TBD; avoid persistence unless required | owning user; external malware scanning only if explicitly designed |
| Synchronization cursor/history/page state | Confidential operational | provider/OmniDesk | incremental sync/duplicate prevention | PostgreSQL or approved transient store | provider APIs |
| Safe synchronization errors | Internal/Confidential | OmniDesk/provider | diagnosis/retry | logs/audit store if needed | operations only |
| Local labels/tags | Confidential | user | organization | PostgreSQL when implemented | owning user |
| AI prompt/context | Highly Sensitive | OmniDesk/user/provider data | user-requested AI feature | prefer transient/minimized processing | selected AI provider when approved |
| AI generated result | Confidential/Highly Sensitive | AI provider | summary/reply assistance | only if V1 feature requires persistence | owning user |
| Security audit event | Confidential | OmniDesk | investigation/account security | durable audit store | authorized operations only |
| Logs | Internal; may become Confidential if user-linked | OmniDesk | operations/diagnostics | bounded retention | authorized operations only |
| Backups | Highest classification of contained data | OmniDesk | disaster recovery | encrypted backup storage | approved backup provider/storage only |

## Data Minimization Rules

For each V1 field:

1. The feature requiring the field must be identified.
2. Persistence must be justified independently from processing.
3. Provider-specific metadata must not be copied wholesale when normalized fields are sufficient.
4. Temporary authorization/challenge data should remain temporary.
5. Tokens/session secrets must never be duplicated into logs or frontend-visible responses.
6. AI context must be selected per request rather than exporting entire mailboxes or conversation stores.
7. Attachment content must not be persisted merely because metadata is available.

## Current Retention / Deletion Decisions

### Gmail Disconnect

Current implementation already removes:

- local Gmail messages.
- local Gmail conversations.
- local Gmail provider connection.

It also attempts remote Google authorization revocation.

This behavior remains the current minimum disconnect baseline.

### Authentication Tokens

Bearer tokens have a finite configured lifetime and the presented token is deleted on logout.

Future session-architecture changes under SEC-003 may replace the mechanism while preserving finite lifetime and logout invalidation requirements.

### OAuth State / CAPTCHA State

These are temporary values with short TTLs and one-time consumption semantics. They should not become durable business records.

## Retention Decisions Still Required

The following remain unresolved and must be finalized before V1 release where applicable:

- default retention period for synchronized messages while a provider remains connected.
- account deletion behavior across all locally stored user data.
- security audit retention.
- application log retention.
- backup retention and deletion.
- AI prompt/context retention at OmniDesk.
- AI provider retention/configuration.
- AI generated-result retention.
- Telegram session-data lifecycle.
- attachment content retention if attachments enter V1.

## Deletion Triggers

The lifecycle model must support these triggers:

### Provider Disconnect

- revoke provider access where supported.
- remove provider credentials/session material.
- remove or apply the approved retention rule to locally synchronized provider data.
- remove provider-specific synchronization cursors/state.

### Account Deletion

- invalidate OmniDesk authentication/session state.
- disconnect/revoke provider access where possible.
- delete user-owned provider connections.
- delete or irreversibly detach user-owned communication data according to the approved policy.
- delete local labels/settings/AI results.
- preserve only records that have an explicit justified retention requirement.

### Expiration

- expire OTP/recovery/OAuth/CAPTCHA challenge data automatically.
- expire authentication/session credentials according to the approved policy.
- expire logs/backups according to their retention schedules.

## Cache and Queue Rules

- Cached values inherit the sensitivity of the underlying data.
- Cache/queue use must not bypass ownership checks.
- Temporary secrets in cache require strict TTLs.
- Queue payloads should carry identifiers rather than unnecessary full message content where practical.
- Failed-job diagnostics must not serialize provider tokens or unnecessary message bodies.

## Logging Rules

Logs must not intentionally contain:

- plaintext passwords.
- password reset tokens.
- OTP/MFA codes.
- bearer/session credentials.
- Gmail/Telegram provider tokens/session secrets.
- encryption keys.
- full authorization headers.
- full message bodies unless a future explicit, reviewed exception justifies it.

## Backup Rules

Until SEC-016 is implemented:

- backups are treated as Highly Sensitive if they contain production communication data or credentials.
- backup retention must not exceed the approved data-lifecycle policy without justification.
- deletion from live storage does not imply immediate disappearance from backups; backup expiration behavior must be documented transparently.
- restore access must be restricted and auditable.

## AI Data Rules

Before M5 AI features:

- send only context required for the user-requested operation.
- do not assume Telegram-derived content may be sent to an AI provider.
- review AI-provider retention/training/data-use behavior.
- avoid persistence of prompts/context unless a specific V1 feature requires it.
- classify generated content according to the sensitivity of the source context.

## Acceptance Criteria

- V1 data categories are classified.
- Origin, purpose, persistence direction, and external transfer are documented.
- Temporary-vs-durable treatment is explicit for sensitive authorization/challenge state.
- Current Gmail disconnect lifecycle is recorded.
- unresolved retention/deletion decisions are explicitly identified.
- cache, queue, logging, backup, and AI lifecycle rules are defined.
- downstream security/privacy backlog dependencies are traceable.

## Security Tests

Later implementation must include:

- disconnect deletion tests for each provider.
- account deletion tests.
- expired temporary-secret cleanup tests.
- log-redaction tests.
- backup lifecycle verification.
- AI context-minimization tests where AI is introduced.

## Definition of Done

This item is ready for Sprint Planning when the V1 classification/lifecycle model is specific enough to drive implementation and identify remaining decisions.

Completion requires the authoritative lifecycle artifact and concrete retention/deletion values needed for production release to be reviewed and accepted.

## Related SDLC Artifacts

- Project Scope.
- Security and Privacy Requirements.
- Data Flow Diagram.
- Data Model and ERD.
- Deployment Architecture.
- Provider Integration Design.
- AI Assistance Design.
- Maintenance/backup procedures.

## V1 Inclusion Rationale

V1 Required. The product stores private communication data and provider credentials locally, so lifecycle behavior must be explicit before production release.

## Completion Evidence

SEC-002 is completed for the V1 baseline.

The authoritative SDLC artifact is [Data Classification, Retention, and Deletion Baseline](../../SDLC/02-requirements-engineering/data-classification-retention-deletion.md).

Exact operational retention durations remain intentionally delegated to the relevant deployment/privacy/security implementation backlog items rather than invented in this baseline.
