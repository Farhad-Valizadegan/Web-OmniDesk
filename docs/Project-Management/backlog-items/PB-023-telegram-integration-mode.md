# PB-023 - Validate the Telegram Integration Mode

> Status: Refined
> Priority: Must
> Milestone: M4

## Purpose

Validate the technically and operationally appropriate Telegram integration mode for OmniDesk AI before implementation begins.

OmniDesk AI is intended to let an authenticated user bring their existing Telegram communication into the same workspace as Gmail. The integration therefore needs access to the user's own Telegram account and conversation history rather than behaving only as a standalone bot account.

## Current Validation Direction

The current preferred integration direction is:

```text
Telegram user account
    -> Telegram client API / MTProto
    -> TDLib-compatible client implementation
    -> OmniDesk provider adapter
    -> normalized conversations/messages
```

The Telegram Bot API is not the default integration mode for M4 because bots are separate bot accounts and do not represent the user's normal Telegram account or unrestricted personal inbox.

Telegram's official client API supports user authorization, and TDLib is Telegram's official fully functional client library for building third-party Telegram clients.

This remains a validation item rather than an implementation commitment. PB-023 must confirm the approach with a small technical spike before PB-024 is selected for connector implementation.

## Validation Questions

PB-023 must answer the following before the Telegram connector is implemented:

1. Can OmniDesk authenticate an existing Telegram user account through an approved Telegram client API flow?
2. Can the selected client approach retrieve the chats and message history required by the V1 unified workspace?
3. Can Telegram session/authentication state be stored securely without exposing phone numbers, codes, passwords, API credentials, or session secrets?
4. Can incoming and historical Telegram data be normalized into the existing provider-neutral conversation/message model?
5. Can synchronization be implemented incrementally without relying on scraping or browser automation?
6. Can Telegram failures and rate-limit conditions be isolated from Gmail and other providers?
7. What runtime boundary is appropriate between Laravel and the Telegram client implementation?
8. What Telegram terms, privacy, and AI-processing constraints affect V1 behavior?

## Candidate Integration Modes

### Option A - Telegram Bot API

**Decision for M4 validation:** Not suitable as the primary integration mode for the OmniDesk personal workspace use case.

Reasons:

- A bot is a separate Telegram account.
- Bots do not act as the authenticated user's normal Telegram client.
- Bot interaction is constrained by bot-specific chat and authorization rules.
- The V1 product requirement is to retrieve the user's supported Telegram communication into their own unified workspace.

The Bot API may remain relevant for a future bot-specific feature, but it is not the baseline for PB-024.

### Option B - Telegram Client API / MTProto

**Decision for technical validation:** Preferred protocol family.

Reasons:

- Supports authorization as a Telegram user.
- API calls after authorization execute with that user's identity.
- Supports Telegram client functionality required for a personal communication workspace.
- Aligns with the requirement to retrieve supported conversations/messages from the user's existing Telegram account.

### Option C - TDLib

**Decision for technical validation:** Preferred official client-library path.

TDLib is Telegram's official cross-platform client library and handles networking, update ordering, local state, and Telegram authorization flows.

The implementation spike should evaluate whether TDLib should run:

- behind a small dedicated adapter/service boundary consumed by Laravel, or
- through another maintainable integration mechanism compatible with the existing architecture.

The exact library/process boundary is not locked by refinement alone. PB-023 implementation must record the final engineering decision before PB-024 starts.

## Authentication Model to Validate

The user-account flow may require states such as:

- Telegram API application credentials (`api_id` and `api_hash`).
- Phone number.
- Verification code.
- Two-factor authentication password when enabled.
- Telegram authorization/session state.

Security requirements:

- Never expose `api_hash`, authorization keys, session material, verification codes, or 2FA passwords to the frontend after they are no longer needed for the immediate authorization step.
- Encrypt persistent sensitive Telegram credentials/session material at rest.
- Scope Telegram connections to the authenticated OmniDesk user.
- Prevent one OmniDesk user from accessing another user's Telegram session.
- Do not log sensitive authorization values.
- Define an explicit disconnect/session-revocation workflow.

## Data Access to Validate

The spike must demonstrate that the selected mode can retrieve enough data to support the existing normalized workspace model.

Minimum validation:

- discover user chats/conversations;
- retrieve message history for at least one supported normal chat type;
- identify stable Telegram conversation/chat identifiers;
- identify stable message identifiers;
- obtain sender identity required for display;
- obtain message timestamps;
- represent message text;
- distinguish provider as `telegram`;
- observe updates or another supported mechanism suitable for incremental synchronization.

Unsupported Telegram capabilities must not be inferred into V1.

Secret chats, calls, stories, advanced channel management, reactions, editing, message sending, and other Telegram-specific features are outside PB-023 unless they are required to prove the core retrieval architecture.

## Normalization Boundary

Telegram-specific concepts must remain behind the Telegram provider adapter.

Conceptual mapping:

```text
Telegram chat
    -> normalized Conversation

Telegram message
    -> normalized Message

Telegram user/chat identifiers
    -> provider-specific identifiers stored behind normalized records

Telegram update/error
    -> provider-specific sync/error handling
```

The frontend must not depend directly on MTProto or TDLib-specific types.

## Runtime and Architecture Validation

The project remains a Laravel-based modular monolith at the application level.

PB-023 must determine the smallest maintainable Telegram runtime boundary compatible with that architecture.

A dedicated Telegram adapter process is acceptable if TDLib/native-runtime requirements make direct PHP integration impractical. Such a process is an implementation detail behind the provider abstraction and does not by itself redefine OmniDesk as a microservice architecture.

The validation must document:

- process/library choice;
- Laravel-to-Telegram adapter interface;
- session storage location;
- lifecycle/startup expectations;
- local development requirements;
- failure/restart behavior.

## Synchronization Validation

PB-023 must identify a viable strategy for PB-025.

The selected Telegram client approach must support:

- initial retrieval;
- continued/incremental updates or another provider-supported delta strategy;
- duplicate prevention using stable provider identifiers;
- retry/backoff for temporary failures;
- Telegram rate-limit/flood-wait handling;
- recovery after process restart;
- isolation from Gmail synchronization state.

PB-023 does not implement the full synchronization pipeline.

## Terms, Privacy, and AI Constraint

Telegram requires third-party clients to comply with its API Terms of Service and privacy/security requirements.

The current Telegram API Terms also contain restrictions relating to use of Telegram-derived data for artificial-intelligence or machine-learning purposes.

Therefore:

- M4 retrieval/inbox integration must not assume that Telegram content can automatically be passed into the later M5 AI pipeline.
- Before Telegram-derived content is used for summaries, reply suggestions, embeddings, model inference, training, fine-tuning, or similar AI processing, the applicable Telegram terms must be reviewed against the exact planned data flow.
- That compliance decision belongs in the AI/Telegram integration boundary and must be resolved before enabling AI processing of Telegram content.

PB-023 may proceed with retrieval validation independently of that later AI decision.

## Acceptance Criteria

- The Bot API is explicitly accepted or rejected as the primary M4 integration mode with rationale.
- A user-account Telegram client approach is validated as technically feasible or documented as infeasible.
- A concrete preferred implementation path is recorded for PB-024.
- The spike successfully authenticates a test Telegram user account using an approved Telegram API/client flow.
- The spike retrieves at least one supported chat and its message history.
- Stable provider chat/message identifiers required for normalization are identified.
- A feasible incremental synchronization/update mechanism is identified.
- Required secrets/session material and secure-storage rules are documented.
- Rate-limit/flood-wait and provider-error handling requirements are documented.
- The Laravel/Telegram runtime boundary is documented.
- Telegram API Terms/privacy constraints are reviewed and recorded.
- AI use of Telegram-derived data is not silently assumed.
- No scraping or browser automation is selected as the core integration mechanism.
- PB-024 receives enough architectural information to begin connector implementation without reopening the basic integration-mode decision.

## Implementation / Validation Tasks

- Register or confirm a Telegram API application and obtain development `api_id` / `api_hash` securely.
- Create a minimal technical spike using the preferred Telegram client path.
- Exercise the user authorization state flow.
- Verify 2FA handling where applicable.
- Retrieve a limited chat list.
- Retrieve limited message history.
- Record identifiers and fields needed for normalization.
- Verify an update/incremental-data mechanism.
- Trigger or document representative Telegram errors and flood-wait behavior.
- Determine the Laravel-to-Telegram adapter boundary.
- Define secure session storage and disconnect behavior.
- Record local development/runtime prerequisites.
- Review official Telegram API Terms relevant to the integration.
- Produce the final PB-023 integration decision.
- Update PB-024/PB-025 refinement assumptions if the spike changes them.

## Testing / Evidence

PB-023 is primarily a technical validation item. Evidence should include:

- successful authorization proof without committing secrets;
- successful retrieval of a test chat;
- successful retrieval of test message history;
- confirmation of stable provider IDs;
- confirmation of update/incremental retrieval capability;
- documented behavior for invalid/expired authorization;
- documented 2FA flow;
- documented rate-limit/flood-wait handling;
- documented restart/session recovery behavior.

No real user message content, verification code, password, session key, `api_hash`, or other secret may be committed to the repository as validation evidence.

## Definition of Done

- Acceptance criteria are satisfied.
- The Telegram primary integration mode is explicitly decided.
- The implementation boundary for PB-024 is documented.
- Authentication and session-security requirements are documented.
- Basic chat/message retrieval is proven by a technical spike.
- Synchronization feasibility is established for PB-025.
- Telegram-specific failures are understood sufficiently for PB-026 refinement.
- Terms/privacy findings are recorded.
- No secrets or private Telegram message content are committed.
- Documentation is updated to reflect the validated decision.

## Sprint Planning Readiness

PB-023 is now sufficiently refined for Sprint Planning.

It remains unassigned to a Sprint until Sprint Planning selects it.

PB-024, PB-025, and PB-026 remain downstream work and must not be treated as automatically committed merely because PB-023 is selected.
