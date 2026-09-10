\# Sprint 01 Plan - Backend and Frontend Foundation



> Status: Draft



\## Sprint Goal



Complete and verify the Laravel backend and React frontend foundation for OmniDesk AI.



Sprint 01 establishes the technical base required for future authentication, workspace, provider integration, synchronization, and AI features.



\## Related Milestone



M0 - Engineering Foundation



\## Related Backlog Items



| ID | Backlog Item | Priority |

|---|---|---|

| PB-002 | Establish Laravel backend foundation | Must |

| PB-003 | Establish React and TypeScript frontend foundation | Must |

| PB-005 | Establish testing, formatting, and CI checks | Must |



\## Current Baseline



The following foundation currently exists:



\- Laravel backend in `backend/`

\- React and TypeScript frontend in `frontend/`

\- PostgreSQL database connection

\- Laravel database migrations

\- Laravel example tests

\- Vite development server

\- Frontend production build configuration

\- Backend and frontend environment templates

\- Git repository and GitHub remote



\## Objectives



Sprint 01 shall:



\- Verify the Laravel backend foundation.

\- Verify the React and TypeScript frontend foundation.

\- Establish the backend API boundary.

\- Establish frontend-backend communication.

\- Configure development commands.

\- Configure code formatting and linting.

\- Verify backend and frontend tests.

\- Establish a basic CI baseline.

\- Preserve environment and secret-management rules.



\## Planned Tasks



\### Backend Foundation



\- Verify Laravel application startup.

\- Verify Laravel environment configuration.

\- Verify PostgreSQL connectivity.

\- Verify database migrations.

\- Review backend directory structure.

\- Review route organization.

\- Establish the initial API route boundary.

\- Add a simple health-check endpoint.

\- Add a feature test for the health-check endpoint.

\- Confirm backend error response behavior.

\- Confirm environment files are protected from Git.



\### Frontend Foundation



\- Verify React and TypeScript startup.

\- Review frontend directory structure.

\- Establish frontend routing structure.

\- Establish the initial application layout.

\- Create a shared API client location.

\- Configure the frontend API base URL through environment variables.

\- Add a basic backend connectivity check.

\- Add loading and error states for the connectivity check.

\- Confirm the application works without authentication.



\### Quality Tools



\- Verify PHP formatting configuration.

\- Verify PHP static analysis configuration where available.

\- Verify Laravel test execution.

\- Verify TypeScript compilation.

\- Verify frontend linting.

\- Verify frontend production build.

\- Document the main development commands.

\- Remove unused starter content where appropriate.



\### Continuous Integration



\- Define a basic CI workflow.

\- Run backend tests in CI.

\- Run frontend type checking in CI.

\- Run frontend linting in CI.

\- Run frontend production build in CI.

\- Confirm CI does not require committed secrets.

\- Confirm CI failures are visible to the project owner.



\## Acceptance Criteria



\- The Laravel backend starts successfully.

\- The React frontend starts successfully.

\- The backend connects to PostgreSQL.

\- Database migrations complete successfully.

\- The health-check endpoint returns a successful response.

\- A backend test verifies the health-check endpoint.

\- The frontend can request the health-check endpoint.

\- The frontend displays loading, success, and failure states.

\- TypeScript checks pass.

\- Frontend linting passes.

\- The frontend production build passes.

\- Backend tests pass.

\- Basic CI checks are defined.

\- No password, token, or secret is committed.

\- Existing documentation remains accessible after the SDLC folder move.



\## Expected Verification Commands



Run backend commands from the `backend` directory:



```powershell

Push-Location backend

php artisan migrate:status

php artisan test

Pop-Location

