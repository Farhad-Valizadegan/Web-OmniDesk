# Sprint 07 - PB-023 Telegram Validation

> Status: Completed
> Backlog Item: PB-023
> Milestone: M4 - Telegram Integration
> Validation Date: 2026-09-18

## Decision Summary

The repository/documentation validation selects the following M4 direction:

```text
Existing Telegram user account
    -> Telegram API / MTProto
    -> official TDLib client library
    -> long-lived Telegram adapter runtime
    -> narrow internal Laravel adapter interface
    -> provider-neutral Conversation / Message model
```

The Telegram Bot API is rejected as the primary M4 mechanism because it represents bot accounts rather than the user's normal Telegram account.

## Official-Documentation Findings

Telegram documents the Telegram API and TDLib as mechanisms for building customized Telegram clients. TDLib handles networking, local state, data consistency, authorization states, chat lists, message history, and asynchronous updates.

The current official TDLib flow supports user authorization through authorization-state updates, including phone/code/password states where applicable.

For retrieval:

- chats are maintained through TDLib chat-list/update behavior;
- `getChatHistory` retrieves message history;
- messages have identifiers within chats;
- TDLib exposes an update stream including `updateNewMessage`, providing a viable basis for incremental synchronization.

## Runtime Boundary Decision

Preferred PB-024 boundary:

```text
Laravel application
    -> internal Telegram Adapter contract
    -> long-lived TDLib runtime
    -> Telegram
```

The long-lived adapter is preferred over loading TDLib directly inside ordinary Laravel/PHP request execution.

Reasons:

- TDLib is fully asynchronous.
- authorization is a state machine.
- incoming updates are continuous.
- TDLib maintains persistent local state.
- process restart/recovery must be controlled independently from one HTTP request.
- native-library failures should remain isolated from the Laravel web request lifecycle.

This does not redefine the product as a microservice architecture. The Telegram runtime remains an infrastructure adapter behind the existing provider boundary.

## Technical Spike Added

A bounded TDLib JSON probe is stored at:

```text
spikes/telegram-tdlib/
```

The probe is intentionally non-production code.

It:

- loads the official TDLib JSON shared library;
- uses application credentials only from environment variables;
- handles existing-user authorization states;
- loads a bounded main-chat sample;
- retrieves limited message history;
- produces sanitized local evidence;
- does not persist message text or credentials in validation evidence.

## Stable Identifier / Normalization Findings

TDLib exposes a stable chat identifier and message identifiers suitable for provider-specific keys.

Proposed normalization boundary:

| Telegram / TDLib | OmniDesk |
|---|---|
| chat ID | provider conversation identifier |
| message ID + chat ID | provider message identity |
| chat type | provider-specific conversation metadata |
| sender_id | normalized sender reference |
| date | message timestamp |
| messageText / supported content | normalized message content |
| updateNewMessage | incremental synchronization event |

The final schema must preserve provider IDs without leaking TDLib-specific types into the frontend.

## Synchronization Direction

PB-025 should use:

1. bounded initial history retrieval;
2. TDLib update stream for continued updates;
3. provider IDs for duplicate prevention;
4. persisted synchronization/session state for restart recovery;
5. classified retry/backoff for transient provider errors;
6. independent Telegram failure state so Gmail remains operational.

## Error / Flood-Wait Direction

The spike will not intentionally generate abusive traffic to force flood limits.

Telegram errors must instead be classified into the existing provider-neutral categories:

- Authorization Failure
- Authentication Failure
- Permission Failure
- Rate Limit
- Provider Unavailable
- Invalid Provider Data
- Unsupported Capability
- Unknown Integration Error

PB-024/PB-025 must treat rate-limit/flood-wait responses as retryable only when the provider response supports safe retry timing.

## Terms / Privacy Finding

Telegram requires third-party API clients to use their own application identifier and comply with the Telegram API Terms.



## Security Consequences

Telegram authorization/session state is Highly Sensitive.

Before PB-024:

- SEC-019 must be refined/finalized against the long-lived adapter boundary.
- session data must remain backend-only.
- persistent TDLib/session material must be encrypted/protected at rest.
- every Telegram session must be bound to one OmniDesk user.
- adapter calls must be authenticated/authorized internally.
- secrets, phone numbers, login codes, 2FA passwords, and raw session material must not enter application logs.
- disconnect/revocation/local-deletion behavior must be explicit.

## Validation Status

### Completed without Telegram secrets

- Bot API rejected for the personal-inbox baseline.
- Telegram API / MTProto selected as protocol family.
- TDLib selected as preferred official client-library path.
- long-lived adapter boundary selected.
- chat/history API path identified.
- stable identifier strategy identified.
- incremental-update mechanism identified.
- provider-neutral error handling direction identified.
- secure local validation probe implemented.

### Runtime evidence completed

On 2026-10-01, the production-shaped local flow was exercised with a real Telegram user account:

- TDLib reached its ready/connected state after the phone and code flow;
- the adapter returned the account's chat list and message history;
- supported Telegram media loaded through the authenticated backend route;
- a full browser refresh preserved the connection and restored content without reauthorization;
- automated tests confirmed owner-scoped status, session, content, media, and disconnect behavior.

The evidence is recorded as outcomes only. No account secrets or private message content are committed. PB-023 is **Completed**. SEC-019 was subsequently completed with owner-isolation, stale-command removal, and crash-resilient disconnect cleanup tests.

## Official References

- https://core.telegram.org/
- https://core.telegram.org/api/obtaining_api_id
- https://core.telegram.org/api/auth
- https://core.telegram.org/tdlib
- https://core.telegram.org/tdlib/getting-started
- https://core.telegram.org/api/terms
- https://github.com/tdlib/td
