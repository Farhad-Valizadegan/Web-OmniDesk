# ADR-0006 — Telegram User-Account Integration Direction

## Status
Accepted for MVP implementation baseline; exact TDLib deployment details may evolve during implementation.

## Context
OmniDesk AI is intended to unify a user's own communication streams. Telegram offers both the Bot API and the user-authorized client API based on MTProto. The Bot API is designed around bot participation and does not provide general access to a user's complete personal Telegram inbox. Telegram's user authorization flow, by contrast, executes API calls with the authorized user's identity.

## Decision
For the personal Telegram inbox use case, OmniDesk AI will target an officially documented user-authorized Telegram client integration using Telegram's MTProto ecosystem, with TDLib preferred as the implementation boundary where practical.

The Telegram Bot API may be supported later as a separate connector for bot-specific workflows, but it is not treated as equivalent to personal-account synchronization.

## Consequences
- Telegram authorization differs from Gmail OAuth and requires Telegram-specific session/authentication handling.
- Telegram credentials/session material must remain backend-side and be protected as sensitive secrets.
- The `CommunicationConnector` contract must isolate Telegram-specific client/session details from the Unified Inbox domain.
- The implementation should validate login, 2FA, session persistence, incremental updates, rate limits, and logout/revocation behavior before marking the connector production-ready.
- The MVP should avoid undocumented scraping or browser automation.

## References
- Telegram User Authorization: https://core.telegram.org/api/auth
- Telegram Bot API: https://core.telegram.org/bots/api
- Telegram Bots FAQ: https://core.telegram.org/bots/faq
- TDLib: https://core.telegram.org/tdlib
