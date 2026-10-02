# Security and Privacy Requirements
> Status: Draft
## Purpose
This document defines the security and privacy requirements for OmniDesk.
## Security and Privacy Requirement Catalog
| ID | Category | Requirement | Priority |
|---|---|---|---|
| SPR-001 | Authentication | The system shall require authentication before allowing access to protected user data. | Must Have |
| SPR-002 | Authorization | The system shall ensure that users can access only their own communication data and connections. | Must Have |
| SPR-003 | Session Security | The system shall protect authenticated sessions against unauthorized use. | Must Have |
| SPR-004 | Credential Protection | The system shall not store external provider passwords in plain text. | Must Have |
| SPR-005 | Token Protection | The system shall protect external access tokens during storage and processing. | Must Have |
| SPR-006 | Least Privilege | The system shall request only the permissions required by supported features. | Must Have |
| SPR-007 | Connection Revocation | The system shall support disconnecting and invalidating an external provider connection. | Must Have |
| SPR-008 | Data Isolation | The system shall isolate communication data between different users. | Must Have |
| SPR-009 | Input Validation | The system shall validate user input and external provider data before processing. | Must Have |
| SPR-010 | Output Safety | The system shall protect rendered communication content against unsafe content execution. | Must Have |
| SPR-011 | Error Handling | The system shall not expose credentials, tokens, or sensitive implementation details in error messages. | Must Have |
| SPR-012 | Logging | The system shall prevent credentials, tokens, and unnecessary message content from being written to logs. | Must Have |
| SPR-016 | Data Minimization | The system should store only communication data and metadata required by supported features. | Should Have |
| SPR-017 | Data Removal | The system should support removal of locally stored provider data when the user disconnects an account, where applicable. | Should Have |
| SPR-018 | Transport Security | The system shall use secure communication channels for authentication, synchronization, and external API requests. | Must Have |
| SPR-019 | Dependency Security | The project shall track and review security-relevant dependencies. | Should Have |
| SPR-020 | Security Testing | Security-sensitive functionality shall be covered by appropriate automated or manual tests. | Must Have |
| SPR-021 | Email Two-Step Verification | The system shall require the approved email-delivered one-time verification step before creating the normal authenticated session, as governed by SEC-004. | Must Have |
| SPR-022 | Account Verification and Recovery | The system shall protect email ownership verification, password change, password reset, and account recovery against replay, enumeration, and unauthorized use, as governed by SEC-005. | Must Have |
| SPR-023 | Sensitive-Action Reauthentication | The system shall require recent authentication or approved step-up verification before security-sensitive operations defined by SEC-006. | Must Have |
| SPR-024 | Persisted Sensitive Data Protection | The system shall define and apply an approved protection model for highly sensitive persisted communication data; the exact V1 storage/encryption architecture remains governed by SEC-015 and related key-management decisions. | Must Have |
| SPR-025 | Backup Protection | Production backups containing sensitive application data shall be protected, access-controlled, recoverable, and lifecycle-managed as governed by SEC-016. | Must Have |
## Clarification of Data Minimization and Removal


SPR-016 concerns avoiding unnecessary durable storage while still permitting communication data and metadata required by approved V1 functionality such as synchronization, search, filtering, and message display.

SPR-017 remains **Should Have** and applies specifically to removal of locally stored provider data on disconnect where applicable. It must not be read as equivalent to all lifecycle operations. Provider authorization revocation, credential/session deletion, account deletion, retention expiration, backup expiration, and user-requested deletion are distinct behaviors governed by SEC-002 and the relevant provider/security controls.

Current Gmail behavior is implementation evidence for Gmail only: disconnect attempts remote Google revocation, prefers the refresh token when available, falls back to the access token where required, performs local Gmail data/connection cleanup even when remote revocation fails, and returns an explicit warning for remote revocation failure. Telegram behavior must not be inferred from this Gmail-specific implementation and remains governed by PB-023/SEC-019.

## Protected Information
The system may process:
- User account information
- External provider connection information
- Access tokens or authorization data
- Messages and conversation metadata
- Synchronization and error information
## Security Principles
1. External provider passwords shall not be collected or stored by OmniDesk.
2. Access tokens shall be treated as sensitive secrets.
3. Users shall not access another user's communication data.
5. Logs shall contain only the minimum information required for diagnosis.
6. Errors shall be useful without revealing sensitive information.
7. External data shall be treated as untrusted input.
8. Security controls shall apply to all supported providers.
## Privacy Principles
1. Communication data shall be processed only for documented product features.
2. The system should minimize the amount of stored communication data.
3. The system shall not use communication data for unrelated purposes.
5. Provider disconnection shall invalidate access where applicable.
6. Privacy-related decisions shall be reviewed before production deployment.

## Current V1 Security Implementation

The following security decisions are implemented in the current V1 codebase. This section records implementation evidence only; it does not imply that all V1 Required security backlog items are complete:

- OmniDesk user authentication uses Laravel Sanctum personal access bearer tokens for the REST API.
- The web client keeps the bearer token in `sessionStorage`; authentication tokens are not stored in `localStorage`.
- Sanctum bearer tokens have a finite lifetime configured through `SANCTUM_TOKEN_EXPIRATION`. The documented default is 480 minutes, and production deployments may set a different finite value through the environment.
- A `401 Unauthenticated` response causes the web client to remove the stored bearer token and return to the unauthenticated application state.
- Gmail provider access and refresh tokens are stored using Laravel's encrypted model casts and are hidden from model serialization.
- Provider tokens are not included in Gmail status responses and must not be logged or exposed in exception responses.
- Gmail disconnect attempts Google authorization revocation through the official Google OAuth client. The refresh token is preferred when available; otherwise the access token is used.
- If Google revocation fails, local Gmail conversations, messages, and the local provider connection are still removed to honor the local disconnect and data-removal request. The API returns an explicit warning response rather than silently treating remote revocation as successful.
- Gmail disconnect and provider operations remain scoped to the authenticated user's Gmail connection.

The following approved V1 requirements remain planned rather than implemented:

- SEC-003 browser authentication/session hardening.
- SEC-004 email-based two-step verification.
- SEC-005 email verification, password change, and account recovery security.
- SEC-006 sensitive-action reauthentication.
- SEC-015 final production storage/data-plane protection baseline.
- SEC-016 production backup protection/recovery lifecycle.

## Relationship to Later Documents
These security and privacy requirements will provide input for:
- System Analysis
- Security Architecture
- Authentication and Authorization Design
- Data Protection Design
- Integration Design
- Testing
- Deployment Planning
- Requirements Traceability Matrix
