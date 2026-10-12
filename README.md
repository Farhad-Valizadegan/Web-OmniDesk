# OmniDesk

OmniDesk is a unified communication workspace for managing supported communication providers in one application.

## V1 Scope

The current V1 baseline includes:

- Gmail integration
- Read-only Telegram integration through the local TDLib adapter
- A unified communication workspace
- Search, filtering, and message organization
- Secure synchronization, authentication, testing, and deployment preparation

Provider message sending is outside the current MVP scope.

The current released milestone is [`v1.0.0-mvp.2`](./docs/Releases/v1/v1.0.0-mvp.2.md), released on 2026-10-12. It is a pre-release and does not claim production readiness.

Additional communication providers and advanced capabilities are future-scope ideas and are **not** part of V1 unless the authoritative scope is explicitly changed.

## Engineering Baseline

- **Backend:** Laravel
- **Frontend:** React + TypeScript
- **Database:** PostgreSQL
- **Caching / Queue:** Redis
- **API style:** REST / JSON where appropriate
- **Architecture style:** Modular Monolith
- **Integration approach:** provider connector / adapter abstractions
- **Development approach:** Agile, iterative, incremental, with a lightweight Scrum-style workflow

## Documentation

Start with the [Documentation Map](./docs/README.md).

Key areas:

- [Project Definition](./docs/Project-Definition/)
- [SDLC](./docs/SDLC/)
- [Project Management](./docs/Project-Management/)
- [Sprints](./docs/Sprints/)
- [Releases](./docs/Releases/)

The repository documentation is organized to demonstrate the project's engineering lifecycle, decision-making, implementation, verification, delivery, and evolution without treating the folder structure as an industry-mandated standard.

## License

OmniDesk is proprietary software. Copyright (c) 2026 Farhad Valizadegan.
All rights reserved. No permission to use, copy, modify, distribute,
sublicense, or sell the copyright holder's code or documentation is granted
without prior written authorization, except where applicable law permits.
See [LICENSE](./LICENSE).

Third-party components remain under their respective licenses. This notice
does not revoke permissions previously granted for copies distributed under MIT.
