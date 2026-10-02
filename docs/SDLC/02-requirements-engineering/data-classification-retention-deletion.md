# Data Classification, Retention, and Deletion Baseline

> Status: Approved Baseline
> Source Backlog Item: SEC-002
> Baseline Date: 2026-09-18

## Purpose

This document is the authoritative V1 data-sensitivity and lifecycle baseline for OmniDesk.

It defines which data categories exist, their sensitivity, why they are processed, where they may persist, and which lifecycle events must remove or expire them.

## Classification Levels

### Public

Content intentionally public and not tied to a private user context.

### Internal

Operational information that should not be public but ordinarily does not expose private communication content by itself.

### Confidential

User-specific or personal information whose disclosure can cause privacy harm.

### Highly Sensitive

Data capable of authenticating as a user, granting provider access, exposing substantial private communication content, or decrypting protected data.

## V1 Data Inventory

| Data Category | Classification | Purpose | Persistence Direction |
|---|---|---|---|
| user name/email | Confidential | account identity | PostgreSQL |
| password hash | Highly Sensitive | credential verification | PostgreSQL |
| auth/session credential | Highly Sensitive | authenticated API access | approved auth/session store |
| CAPTCHA/OAuth challenge state | Sensitive temporary | abuse/authorization protection | temporary cache only |
| Gmail account identifiers | Confidential | connected-account identity | PostgreSQL |
| Gmail access/refresh tokens | Highly Sensitive | provider access/renewal/revocation | encrypted backend persistence |
| Telegram API credentials | Highly Sensitive | TDLib application access | trusted backend configuration only |
| Telegram session/auth state | Highly Sensitive | existing-account access | future SEC-019 protected store |
| conversation metadata | Confidential | unified workspace/search/filter | PostgreSQL |
| sender/recipient metadata | Confidential | message presentation | PostgreSQL |
| message body | Highly Sensitive | unified message display/search | PostgreSQL under current normalized model |
| attachment metadata | Confidential | future attachment display/download | persist only when required |
| attachment content | Highly Sensitive | future approved attachment workflows | avoid persistence unless required |
| synchronization state | Confidential operational | initial/incremental synchronization | PostgreSQL/approved transient store |
| safe synchronization errors | Internal/Confidential | retry/diagnosis | bounded log/audit storage |
| local labels/tags | Confidential | organization | PostgreSQL when implemented |
| security audit events | Confidential | investigation/account security | durable bounded audit store |
| application logs | Internal; possibly Confidential | operations | bounded retention |
| backups | inherits highest contained level | disaster recovery | encrypted/restricted backup storage |

## Minimization Rules

- Every persisted field requires a documented product/operational purpose.
- Processing does not automatically justify persistence.
- Provider-specific payloads must not be copied wholesale when normalized fields are sufficient.
- Temporary authorization/challenge data must remain temporary.
- Secrets must never be duplicated into logs or frontend-visible responses.
- Attachment content must not be persisted merely because provider metadata exposes it.

## Deletion Triggers

### Provider Disconnect

- revoke provider access where supported;
- delete provider credentials/session material;
- remove provider synchronization cursors/state;
- remove or apply the approved retention rule to synchronized provider data.

Gmail currently performs local cleanup and attempts remote Google authorization revocation.

Telegram disconnect/revocation semantics must be finalized under SEC-019.

### Account Deletion

The final account-deletion implementation must:

- invalidate OmniDesk authentication/session state;
- disconnect/revoke provider access where possible;
- delete provider connections;
- delete or apply approved retention rules to user-owned communication data;
- retain only records with explicit justified retention requirements.

### Expiration

Automatically expire:

- OAuth state;
- CAPTCHA state;
- OTP/MFA/recovery challenges;
- authentication/session credentials according to policy;
- logs and backups according to their approved retention schedules.

## Cache / Queue Rules

- Cached data inherits the underlying sensitivity.
- Cache/queue data must not bypass ownership checks.
- temporary secrets require strict TTLs.
- queue payloads should prefer identifiers over unnecessary full message content.
- failed-job diagnostics must not serialize provider tokens/session secrets or unnecessary message bodies.

## Logging Rules

Logs must not intentionally contain:

- plaintext passwords;
- reset tokens;
- OTP/MFA codes;
- bearer/session credentials;
- Gmail/Telegram provider tokens/session material;
- Telegram phone/login codes or 2FA passwords;
- encryption keys;
- full Authorization headers;
- full message bodies absent a separately reviewed exception.

## Backup Rules

Until SEC-016 is completed:

- any backup containing production communication data or credentials is Highly Sensitive;
- access must be restricted;
- retention must be bounded;
- restore activity must be controlled/auditable;
- backup expiration behavior must be documented because live deletion cannot guarantee immediate deletion from historical backups.

## Retention Values Still Requiring Production Decisions

Exact durations remain unresolved for:

- synchronized communication retained while a provider remains connected;
- security audit events;
- application logs;
- backups;
- future attachment content.

These are not silently assigned arbitrary durations in this baseline. Exact values must be set during the relevant privacy/deployment/security implementation work.

## SEC-002 Completion Evidence

SEC-002 is satisfied at the baseline/requirements level because:

- V1 data categories are inventoried and classified;
- temporary versus durable treatment is explicit;
- provider credential/session data is Highly Sensitive;
- disconnect/account-delete/expiration triggers are defined;
- unresolved exact retention durations are explicit rather than hidden;
