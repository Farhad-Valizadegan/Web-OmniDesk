# Telegram Runtime Boundary and Session Security

> Status: Draft
> Related backlog: SEC-019, PB-023, PB-024, PB-025

## Purpose

This document defines the production runtime boundary and security model for the Telegram user-account integration.

Telegram authorization, session state, TDLib lifecycle, and provider updates must remain outside the Laravel HTTP request lifecycle.

## Validated Integration Direction

The validated direction is:

- Telegram user account
- Official Telegram Client API / MTProto
- TDLib-based adapter
- Authenticated internal adapter interface
- Laravel synchronization service
- Provider-neutral conversations and messages
- React workspace

The Telegram Bot API is not the primary integration mode for the personal inbox use case.

## Runtime Boundary

Telegram integration must run as a long-lived dedicated adapter process.

Laravel communicates with the adapter through a narrow authenticated internal interface.

The adapter is responsible for:

- TDLib initialization
- Telegram authorization state
- Session lifecycle
- Restart recovery
- Chat and message retrieval
- Incremental update consumption
- Telegram error classification
- Rate-limit and flood-wait handling
- Secret and log redaction

Laravel is responsible for:

- Authenticated OmniDesk user ownership
- Provider connection lifecycle
- Synchronization jobs
- Data normalization
- Database persistence
- API authorization
- Frontend responses

The React client must never communicate directly with TDLib or Telegram.

## Session Isolation

Each OmniDesk user must have an isolated Telegram session.

The adapter must not reuse one user's session for another user.

The session key must be derived from an internal OmniDesk connection identifier, not from a phone number or externally supplied identifier.

The production storage structure must be equivalent to:

- private application storage
  - telegram
    - connections
      - internal connection identifier
        - TDLib session

Session directories must:

- remain outside the public web root;
- be excluded from Git;
- be inaccessible to the frontend;
- use restricted operating-system permissions;
- remain isolated per provider connection;
- be removed during disconnect cleanup.

## Sensitive Assets

The following values are highly sensitive:

- Telegram API hash;
- Telegram authorization keys;
- TDLib database files;
- TDLib binlog files;
- phone numbers;
- authentication codes;
- Telegram two-factor passwords;
- adapter authentication credentials.

These values must not be:

- returned to the frontend;
- written to application logs;
- written to evidence files;
- committed to the repository;
- included in exception messages;
- included in analytics or telemetry;
- sent to an AI provider.

## Credential Storage

Telegram application credentials must be supplied through protected server-side configuration.

The production adapter must receive only the credentials required for its configured runtime.

The Laravel database must not store:

- Telegram API hash in plaintext;
- authentication codes;
- two-factor passwords;
- raw TDLib session files;
- raw authorization keys.

If persistent application-level credential storage is required, it must use approved encryption and key-management controls defined by SEC-008.

## Laravel-to-Adapter Authentication

Every internal adapter request must be authenticated.

The adapter must verify:

- caller identity;
- connection identifier;
- request integrity;
- request freshness or replay protection;
- authorization to access the requested connection.

The adapter must reject requests where the connection does not belong to the authenticated OmniDesk user.

The internal interface must not be publicly exposed.

## Authorization State Handling

The adapter must support these states without exposing sensitive values:

- waiting for TDLib parameters;
- waiting for phone number;
- waiting for authentication code;
- waiting for two-factor password;
- authorization ready;
- authorization closing;
- authorization closed;
- authorization failure.

Interactive authorization input must use a controlled backend flow.

The frontend may submit temporary authorization input only through authenticated and protected endpoints. These values must be used transiently and must not be persisted or logged.

## Restart Recovery

The adapter must reopen the correct isolated session after restart.

Restart recovery must:

- identify the internal connection;
- load only that connection's session directory;
- resume authorization if the session remains valid;
- report reauthorization requirements safely;
- preserve the last successful synchronization cursor;
- avoid duplicate messages.

A restart must never cause one user to receive another user's data.

## Synchronization State

Synchronization state must be stored separately from raw Telegram session material.

The application may store provider-neutral synchronization metadata in:

- provider_connections.sync_state

Examples include:

- last successful synchronization timestamp;
- last processed update marker;
- bounded history cursor;
- adapter state version;
- recovery status;
- last provider error category.

Raw TDLib database files and binlogs must remain outside the application database unless a later approved design explicitly requires encrypted storage there.

## Disconnect and Revocation

Disconnect must:

1. Stop or isolate the user's adapter session.
2. Request Telegram logout or session termination where supported.
3. Delete the user's TDLib session directory.
4. Remove the user's Telegram provider connection.
5. Remove or retain synchronized messages according to the approved data-lifecycle policy.
6. Clear synchronization state.
7. Write only a safe audit event.

Disconnect must not affect another user's Telegram connection.

## Error and Rate-Limit Handling

The adapter must classify Telegram failures into provider-neutral categories:

- authorization failure;
- authentication failure;
- permission failure;
- rate limit or flood wait;
- provider unavailable;
- invalid provider data;
- unsupported capability;
- session corruption;
- unknown integration error.

Flood-wait responses must not be retried in a tight loop.

Retry behavior must use bounded backoff and must preserve existing synchronized data.

Sensitive provider diagnostics must not be exposed in API responses.

## Data Normalization

Telegram-specific types must remain inside the adapter and mapping layer.

The normalized mapping is:

- Telegram chat to Conversation.provider = telegram
- Telegram message to Message.provider = telegram
- Telegram chat identifier to Conversation.provider_conversation_id
- Telegram message identifier to Message.provider_message_id
- Telegram message text to Message.body
- Telegram sender metadata to Message.sender fields and approved metadata
- Telegram timestamp to Message.sent_at

Provider identifiers must be used for duplicate prevention.

## AI Boundary

Telegram-derived content must not automatically enter the AI pipeline.

Before enabling summaries, reply suggestions, embeddings, inference, training, or fine-tuning over Telegram content:

- applicable Telegram terms must be reviewed;
- privacy requirements must be documented;
- user consent and product controls must be defined;
- data minimization must be applied;
- the exact AI provider data flow must be approved.

Retrieval and display are separate from AI processing.

## Security Tests

The implementation must provide tests for:

- cross-user session isolation;
- encrypted sensitive configuration;
- absence of secrets in serialized models;
- absence of secrets in logs;
- restart and session recovery;
- invalid authorization handling;
- disconnect cleanup;
- provider ownership enforcement;
- flood-wait classification;
- duplicate prevention;
- safe error responses.

## Decision

The production Telegram integration must use:

- long-lived dedicated TDLib adapter;
- authenticated internal interface;
- per-user isolated session storage;
- Laravel synchronization boundary.

The validation probe remains test tooling and must not be reused as the production runtime.

## Gate

PB-024 cannot begin until:

- this design is approved;
- SEC-019 acceptance criteria are satisfied;
- session storage and deletion tests exist;
- the Laravel-to-adapter authentication mechanism is selected;
- the production adapter lifecycle is documented.