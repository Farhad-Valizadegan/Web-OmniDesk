# OmniDesk Backend Agent Guidelines

These instructions apply to automated coding assistants working in the `backend` directory.

## Project Context

OmniDesk is a Laravel-based backend for a provider-neutral unified communication workspace.

Current Version 1 providers are Gmail and Telegram. Gmail integration and read-only Telegram integration through the local TDLib adapter are implemented in `v1.0.0-mvp.1`. Telegram uses isolated per-user runtimes for authorization, chat/message access, and supported media. Provider message sending remains outside the current MVP scope. See `../docs/Releases/v1/v1.0.0-mvp.1.md` for the released scope and known limitations.

## Required Working Rules

- Preserve the provider-neutral domain model.
- Keep provider-specific behavior behind connector/service boundaries.
- Do not expose OAuth credentials, access tokens, refresh tokens, client secrets, or other provider secrets to frontend code or API responses.
- Preserve authenticated-user ownership boundaries for conversations, messages, and provider connections.
- Use official provider APIs and documented authorization flows.
- Do not introduce undocumented scraping or browser automation as a core provider integration.
- Do not expand Version 1 scope without an explicit backlog/scope decision.

## Development Workflow

Repository changes should follow the project workflow:

1. Work on a dedicated branch.
2. Make the smallest coherent change.
3. Run relevant local verification.
4. Commit and push the branch.
5. Open a pull request.
6. Require CI to pass before merge.
7. Merge through the repository workflow.
8. Synchronize local `main` after merge.

Do not mutate the user's shell execution policy or install global system dependencies automatically.

Do not install new Composer packages unless the requested change requires them and the dependency decision is explicit.

## Backend Verification

From the repository root on Windows PowerShell:

```powershell
Push-Location backend
php artisan test
vendor\bin\pint.bat --test
Pop-Location
```

Linux/CI formatting check:

```sh
cd backend
./vendor/bin/pint --test
php artisan test
```

For schema changes, also verify migrations against PostgreSQL.

## Architecture

Current backend baseline:

- Laravel
- PostgreSQL
- Redis where required for queue/cache responsibilities
- Laravel Sanctum authentication
- REST/JSON API boundaries
- modular-monolith architecture
- provider connector/adapter abstractions

## Documentation

Engineering decisions, requirements, Sprint history, backlog state, and release documentation live under `../docs`.

Do not use Sprint documents as the canonical Product Backlog. Canonical `PB-###` identifiers are defined in `../docs/Project-Management/product-backlog.md`.
