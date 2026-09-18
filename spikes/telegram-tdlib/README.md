# PB-023 Telegram TDLib Validation Spike

> Scope: technical validation only; not production connector code.

## Purpose

This spike validates the user-account Telegram path selected for PB-023 without introducing Telegram-specific types into the OmniDesk core application.

The probe uses Telegram's official TDLib JSON interface directly. It does not use the Bot API, scraping, browser automation, or a third-party Telegram client library.

## What the Probe Verifies

When run with a dedicated test Telegram account, the probe verifies:

- TDLib can initialize with an OmniDesk-owned Telegram `api_id` and `api_hash`.
- the existing Telegram user account can complete TDLib authorization;
- 2FA/password state is handled when Telegram requires it;
- the main chat list can be loaded;
- at least one non-secret chat can be identified;
- limited message history can be retrieved;
- stable chat/message identifiers exist;
- TDLib's asynchronous update stream is available for later incremental synchronization.

The evidence file intentionally excludes message text, chat titles, phone numbers, codes, passwords, API hashes, authorization keys, and raw Telegram identifiers.

## Prerequisites

1. Obtain an application-specific `api_id` and `api_hash` from Telegram's API development tools.
2. Build or install TDLib's JSON shared library for the local OS.
3. Use a Telegram test account that is safe for development validation.
4. Python 3.10+.

Official references:

- https://core.telegram.org/api/obtaining_api_id
- https://core.telegram.org/tdlib
- https://core.telegram.org/tdlib/getting-started
- https://github.com/tdlib/td/tree/master/example/python

## Environment

PowerShell example:

```powershell
$env:TDJSON_PATH = "C:\path\to\tdjson.dll"
$env:TELEGRAM_API_ID = "<your-api-id>"
$env:TELEGRAM_API_HASH = "<your-api-hash>"
$env:TELEGRAM_TDLIB_RUNTIME = ".\spikes\telegram-tdlib\runtime"

python .\spikes\telegram-tdlib\tdlib_probe.py
```

Do not place Telegram credentials in committed files.

## Interactive Authorization

The probe requests sensitive login values through hidden console input when TDLib asks for them:

- phone number;
- Telegram authentication code;
- optional login email/code where required;
- Telegram 2FA password.

These values are submitted directly to TDLib and are not written to the evidence file.

The probe refuses to create a new Telegram account. PB-023 validates an existing account only.

## Evidence

Successful execution creates:

```text
spikes/telegram-tdlib/runtime/evidence.json
```

The `runtime/` directory is ignored by Git.

Expected evidence categories:

- authorization reached `authorizationStateReady`;
- bounded chat-list retrieval succeeded;
- one supported chat type was observed;
- `getChatHistory` returned messages;
- message/chat identifier fingerprints were generated;
- message metadata required for normalization was present;
- no message body or secret was persisted into evidence.

The evidence file itself should remain local unless manually reviewed and sanitized before committing.

## Incremental Synchronization Decision

TDLib is event-driven. Its documented update stream includes `updateNewChat`, `updateNewMessage`, content changes, chat-position changes, and other updates. PB-025 should consume provider updates behind the Telegram adapter and combine them with bounded history retrieval for initial synchronization and restart recovery.

## Rate-Limit / Error Decision

The spike does not intentionally trigger Telegram flood limits. Deliberately generating abusive traffic would be inappropriate.

PB-024/PB-025 must classify TDLib `error` responses and implement retry/backoff for temporary failures and Telegram flood-wait/rate-limit conditions.

## Runtime Boundary Decision

The recommended production direction is a **long-lived dedicated Telegram adapter process using TDLib**, consumed by Laravel through a narrow internal interface.

Reasoning:

- TDLib is asynchronous and stateful.
- TDLib maintains local state/database files.
- authorization and update streams outlive one Laravel HTTP request.
- isolating the native library prevents TDLib lifecycle/state from being coupled to PHP-FPM request lifecycles.
- the application remains a modular monolith at the product/domain level; this adapter is an infrastructure boundary, not a separate product domain.

The Python probe is validation tooling only. It does not prescribe Python as the production adapter implementation language.

## Security Boundary

Production design must not reuse this local spike directory as the final session store.

SEC-019 must define:

- per-user encrypted session storage;
- key ownership/rotation;
- Laravel-to-adapter authentication;
- cross-user session isolation;
- restart recovery;
- disconnect/logout semantics;
- secret/log redaction;
- filesystem/database permissions.

PB-024 remains blocked until SEC-019 is finalized against the validated runtime boundary.
