# Sprint 01 Plan - Backend and Frontend Foundation

> Status: Draft

## Sprint Goal

Complete and verify the Laravel backend and React frontend foundation for OmniDesk AI.

Sprint 01 establishes the technical base required for authentication, workspace features, provider integrations, synchronization, and AI assistance.

## Related Milestone

M0 - Engineering Foundation

## Related Backlog Items

| ID | Backlog Item | Priority |
|---|---|---|
| PB-002 | Establish Laravel backend foundation | Must |
| PB-003 | Establish React and TypeScript frontend foundation | Must |
| PB-005 | Establish testing, formatting, and CI checks | Must |

## Current Baseline

The following foundation currently exists:

- Laravel backend in `backend/`
- React and TypeScript frontend in `frontend/`
- PostgreSQL database connection
- Laravel database migrations
- Laravel example tests
- Vite development server
- Frontend production build configuration
- Backend and frontend environment templates
- Git repository and GitHub remote

## Objectives

Sprint 01 shall:

- Verify the Laravel backend foundation.
- Verify the React and TypeScript frontend foundation.
- Establish the backend API boundary.
- Establish frontend-backend communication.
- Configure development commands.
- Configure code formatting and linting.
- Verify backend and frontend tests.
- Establish a basic CI baseline.
- Preserve environment and secret-management rules.

## Planned Tasks

### Backend Foundation

- Verify Laravel application startup.
- Verify Laravel environment configuration.
- Verify PostgreSQL connectivity.
- Verify database migrations.
- Review backend directory structure.
- Review route organization.
- Establish the initial API route boundary.
- Add a simple health-check endpoint.
- Add a feature test for the health-check endpoint.
- Confirm backend error response behavior.
- Confirm environment files are protected from Git.

### Frontend Foundation

- Verify React and TypeScript startup.
- Review frontend directory structure.
- Establish frontend routing structure.
- Establish the initial application layout.
- Create a shared API client location.
- Configure the frontend API base URL through environment variables.
- Add a basic backend connectivity check.
- Add loading and error states for the connectivity check.
- Confirm the application works without authentication.

### Quality Tools

- Verify PHP formatting configuration.
- Verify PHP static analysis configuration where available.
- Verify Laravel test execution.
- Verify TypeScript compilation.
- Verify frontend linting.
- Verify frontend production build.
- Document the main development commands.
- Remove unused starter content where appropriate.

### Continuous Integration

- Define a basic CI workflow.
- Run backend tests in CI.
- Run frontend type checking in CI.
- Run frontend linting in CI.
- Run frontend production build in CI.
- Confirm CI does not require committed secrets.
- Confirm CI failures are visible to the project owner.

## Acceptance Criteria

- The Laravel backend starts successfully.
- The React frontend starts successfully.
- The backend connects to PostgreSQL.
- Database migrations complete successfully.
- The health-check endpoint returns a successful response.
- A backend test verifies the health-check endpoint.
- The frontend can request the health-check endpoint.
- The frontend displays loading, success, and failure states.
- TypeScript checks pass.
- Frontend linting passes.
- The frontend production build passes.
- Backend tests pass.
- Basic CI checks are defined.
- No password, token, or secret is committed.
- Existing documentation remains accessible after the SDLC folder move.

## Expected Verification Commands

Run backend commands from the project root:

```powershell
Push-Location backend
php artisan migrate:status
php artisan test
Pop-Location
```

Run frontend checks from the project root:

```powershell
npm.cmd --prefix frontend run lint
npm.cmd --prefix frontend run build
```

Verify repository status:

```powershell
git diff --check
git status
```

## Definition of Done

Sprint 01 is complete when:

- All acceptance criteria are satisfied.
- Backend tests pass.
- Frontend linting passes.
- TypeScript compilation passes.
- Frontend production build passes.
- PostgreSQL connectivity is verified.
- The health-check endpoint is tested.
- Frontend-backend communication is tested.
- CI checks are documented or implemented.
- No secrets are committed.
- Relevant documentation is updated.
- Changes are committed and pushed to GitHub.
- The sprint completion status is recorded in this file.

## Dependencies

- M0 must remain complete.
- PostgreSQL must be installed and running.
- The local PostgreSQL database `omni_desk_ai` must exist.
- The local PostgreSQL user `omni_desk_app` must exist.
- PHP and Composer must be available.
- Node.js and npm must be available.
- The backend and frontend directories must remain separate.
- The approved System Design artifacts must remain available.

## Risks and Controls

| Risk | Control |
|---|---|
| Frontend cannot reach the backend | Configure and test the API base URL |
| Database configuration differs between environments | Maintain `.env.example` and document required variables |
| Secrets are committed accidentally | Verify `.gitignore` and inspect staged files |
| Starter code remains unused | Review and remove unnecessary starter content |
| CI depends on local secrets | Use safe test configuration and secret-free checks |
| Backend and frontend boundaries become coupled | Keep API communication behind a shared client boundary |

## Out of Scope

The following are outside Sprint 01:

- User registration
- Login and logout
- Authentication screens
- Provider authorization
- Gmail integration
- Telegram integration
- Synchronization
- Unified inbox features
- AI assistance
- Autonomous message sending
- Production deployment
- Final hosting decisions

## Traceability

Sprint 01 contributes to:

- M0 - Engineering Foundation
- System Design Overview
- Implementation Plan
- Product Backlog
- Backend and frontend foundation requirements
- Testing and quality requirements
- Security and secret-management requirements

## Versioning

Sprint 01 does not automatically create a release or version tag.

A version tag may be created after the M0 outcome is complete and the release criteria have been reviewed.

## Unresolved Sprint Decisions

| ID | Decision | Current State |
|---|---|---|
| S01-001 | Exact health-check response format | To be finalized during implementation |
| S01-002 | Final frontend API client library | To be decided |
| S01-003 | CI provider configuration | To be finalized |
| S01-004 | PHP formatting tool | To be finalized |
| S01-005 | Frontend routing library | To be finalized |
| S01-006 | Sprint completion date | Not yet determined |

## Sprint Status

Sprint 01 has not yet been completed.

Completion will be recorded after the acceptance criteria and verification commands have passed.
