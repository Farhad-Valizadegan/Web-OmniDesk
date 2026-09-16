# Web-OmniDesk-AI

OmniDesk AI is an AI-powered unified communication workspace for managing supported communication providers in one application.

## V1 Scope

The current V1 baseline includes:

- Gmail integration
- Telegram integration
- A unified communication workspace
- Search, filtering, and message organization
- User-controlled AI assistance such as conversation summaries and reply suggestions
- Explicit user confirmation before AI-assisted sending
- Secure synchronization, authentication, testing, and deployment preparation

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
