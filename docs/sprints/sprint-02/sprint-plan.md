# Sprint 02 Plan - Database and Authentication

> Status: Completed with Carryover
> Completed: 2026-09-10
> Audited: 2026-09-13

## Sprint Goal

Implement the secure user foundation for OmniDesk AI.

Sprint 02 establishes user registration, authentication, sessions, authorization, and ownership boundaries for the backend and frontend.

## Related Milestone

M1 - Authentication and Workspace Foundation

## Related Backlog Items

| ID | Backlog Item | Priority |
|---|---|---|
| PB-006 | Implement user registration | Must |
| PB-007 | Implement login and logout | Must |
| PB-008 | Implement authenticated sessions | Must |
| PB-009 | Implement authorization and ownership checks | Must |
| PB-010 | Implement the initial workspace layout | Must |

## Objectives

Sprint 02 shall:

- Finalize the user database foundation.
- Implement user registration, login, logout, and authenticated sessions.
- Protect authenticated API routes.
- Enforce user ownership boundaries.
- Add safe validation and authentication error responses.
- Add frontend authentication screens and states.
- Preserve secure secret-management practices.
- Add backend and frontend verification.

## Planned Tasks

### Database Foundation

- Review the existing users migration.
- Confirm required user fields and email uniqueness.
- Confirm secure password hashing and timestamp handling.
- Run migrations against PostgreSQL.
- Add database tests where necessary.

### Backend Authentication

- Finalize the Laravel authentication approach.
- Implement registration, login, logout, and authenticated-user endpoints.
- Validate input and credentials.
- Hash passwords securely.
- Configure session handling.
- Protect authenticated routes.
- Return consistent, safe authentication errors.
- Avoid revealing sensitive authentication details.

### Authorization and Ownership

- Define authenticated-user access rules.
- Define resource ownership rules.
- Add authorization middleware or policies.
- Prevent access to another user's resources.
- Return safe forbidden responses.
- Add authorization tests.
- Confirm ownership checks apply to future protected resources.

### Frontend Authentication

- Create registration and login screens.
- Create logout behavior.
- Create authenticated and unauthenticated application states.
- Create authentication loading and error states.
- Store only approved client-side session information.
- Never store provider tokens in the frontend.

### Testing

- Test successful registration.
- Test invalid registration data.
- Test duplicate email registration.
- Test successful and failed login.
- Test logout.
- Test authenticated-user access.
- Test unauthenticated access rejection.
- Test authorization boundaries.
- Test frontend authentication states.

## API Scope

The planned authentication endpoints are:

| Method | Endpoint | Purpose |
|---|---|---|
| POST | `/api/register` | Create a user account |
| POST | `/api/login` | Authenticate a user |
| POST | `/api/logout` | End the authenticated session |
| GET | `/api/user` | Return the authenticated user |

The exact authentication implementation may be refined after reviewing Laravel and Sanctum requirements.

## Acceptance Criteria

- A user can register with valid data.
- Duplicate email registration is rejected safely.
- Passwords are never stored in plain text.
- A user can log in with valid credentials.
- Invalid credentials are rejected safely.
- A user can log out.
- Authenticated routes reject unauthenticated requests.
- The authenticated-user endpoint returns the current user.
- Users cannot access another user's protected resources.
- Authentication errors use consistent API responses.
- Frontend registration and login states are implemented.
- Frontend loading and error states are implemented.
- Provider tokens are not stored in the frontend.
- Backend authentication tests pass.
- Frontend tests pass.
- No secrets are committed.

## Expected Verification Commands

Run backend checks:

```powershell
Push-Location backend
php artisan migrate:status
php artisan test
Pop-Location
```

Run frontend checks:

```powershell
npm.cmd --prefix frontend run test
npm.cmd --prefix frontend run lint
npm.cmd --prefix frontend run build
```

Run repository checks:

```powershell
git diff --check
git status
```

## Definition of Done

Sprint 02 is complete when:

- All acceptance criteria are satisfied.
- Registration, login, logout, and sessions work.
- Authorization and ownership checks are implemented and tested.
- Backend and frontend tests pass.
- Linting and builds pass.
- No secrets are committed.
- Documentation is updated.
- Changes are reviewed, committed, and pushed.

## Dependencies

- M0 is complete.
- PostgreSQL is configured and available.
- Laravel Sanctum is installed and configured as needed.
- The Laravel backend is running.
- The frontend API client is available.
- The API route boundary is established.

## Risks and Controls

| Risk | Control |
|---|---|
| Authentication tokens or secrets are exposed | Keep secrets server-side and exclude local environment files from Git |
| Session configuration differs between environments | Document and verify local and CI session settings |
| Ownership checks are omitted from future resources | Use policies or middleware consistently and add authorization tests |
| Authentication behavior differs between frontend and backend | Verify API responses with backend and frontend tests |

## Out of Scope

- Gmail authorization
- Telegram authorization
- Provider synchronization
- Unified inbox implementation
- AI assistance
- Production deployment

## Traceability

Sprint 02 contributes to M1 and implements PB-006 through PB-010.

Implementation tasks must remain traceable to the related requirements, authentication and authorization design, acceptance criteria, automated tests, and completion evidence.

## Versioning

Sprint 02 does not automatically create a release or version tag.

A release tag may be created only after the related milestone outcome and release criteria are completed.

## Completion Review

Sprint 02 was implemented through pull request #2 and merged into `main`.

### Recorded Evidence

- Pull request: `#2`
- Merge commit: `ad7e78c`
- Final implementation commit: `8e745a7`
- GitHub Actions run: `34509484636`
- GitHub Actions conclusion: successful
- Registration, login, logout, and current-user endpoints: implemented
- Password hashing: implemented with Laravel hashing
- Sanctum authentication tests: passed
- Frontend authentication tests: passed
- Frontend lint and production build: passed

### Accepted Decisions

- Authentication uses Laravel Sanctum personal-access bearer tokens.
- The frontend stores the application bearer token in `sessionStorage`.
- This storage method is accepted for the current development version.
- The XSS exposure and a possible migration to HttpOnly first-party cookies remain documented security considerations.
- Registration requires a unique valid email, password confirmation, and a minimum password length of eight characters.
- Authentication and validation failures use Laravel JSON error responses.

### Satisfied Later

- Cross-user resource ownership was implemented and tested with conversation resources during Sprint 03.

### Carryover

- Add rate limiting to registration and login.
- Define access-token expiration and ability policies.
- Decide whether production authentication will retain bearer-token storage or migrate to HttpOnly cookies.
- Record the security hardening work in the remediation backlog.

## Sprint Decisions

| ID | Decision | Final State |
|---|---|---|
| S02-001 | Authentication implementation approach | Resolved: Laravel Sanctum personal-access bearer tokens |
| S02-002 | Session storage configuration | Resolved for development: bearer token stored in browser `sessionStorage` |
| S02-003 | Authentication API response format | Resolved: Laravel JSON success, validation, and authentication responses |
| S02-004 | Password policy | Resolved: minimum eight characters with confirmation and secure hashing |
| S02-005 | Sprint completion date | Resolved: 2026-09-10 |

## Sprint Status

Sprint 02 is completed with documented carryover.

The core authentication implementation passed verification. Ownership protection was satisfied during Sprint 03. Remaining rate-limiting, token-policy, and browser-storage hardening work is tracked in the Sprint 01–04 audit.