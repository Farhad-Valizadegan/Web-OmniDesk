# OmniDesk Implementation Plan

> Status: Draft

## Purpose

This document defines the implementation approach for OmniDesk.

It translates the approved System Design artifacts and approved milestones into incremental, testable implementation work.

## Implementation Principles

Implementation shall proceed:

- Incrementally
- In small verifiable stages
- From foundation to user-facing capabilities
- With security considered from the beginning
- With provider-specific logic isolated
- With tests developed alongside implementation
- With requirements traceability maintained
- With unresolved decisions recorded explicitly
- With each sprint producing a reviewable increment

## Technical Baseline

The initial implementation uses:

- Laravel backend
- React and TypeScript frontend
- PostgreSQL database
- Redis where justified
- REST and JSON communication
- Modular monolith architecture
- MVC where appropriate
- Object-oriented design
- Provider connector abstractions
- Background jobs for long-running operations

## Relationship to Milestones

Implementation work follows the approved project milestones:

| Milestone | Outcome |
|---|---|
| M0 | Engineering foundation is ready |
| M1 | Users can securely access a basic workspace |
| M2 | Gmail can be connected and synchronized |
| M3 | Users can view and organize unified communication |
| M4 | Telegram can be connected and synchronized |
| M5 | The application is verified and prepared for release |

Milestones represent outcomes. They are not SDLC phases, sprints, releases, or versions.

## Sprint Structure

Historical and current Sprint assignments are recorded below. Future milestone work does not reserve Sprint numbers before Sprint Planning.

| Sprint | Main Focus | Milestone |
|---|---|---|
| Sprint 0 | Repository and environment preparation | M0 |
| Sprint 1 | Backend and frontend foundation | M0 |
| Sprint 2 | Database and authentication | M1 |
| Sprint 3 | Workspace foundation | M1 |
| Sprint 4 | Gmail provider integration | M2 |
| Sprint 5 | Gmail synchronization | M2 |
| Sprint 6 | Unified inbox completion | M3 |
| Sprint 7 | Security Foundation and Telegram Validation | M4 primary; cross-cutting security/privacy |

Future M4, M5, and M5 implementation direction remains unassigned until backlog refinement, prioritization, and Sprint Planning create a later Sprint.

Milestone != Sprint. Sprint != Release. Release != Version.

The per-Sprint completion criteria below describe acceptance expectations for selected work; they do not extend a Sprint beyond its defined timebox.

## Sprint 0 - Repository and Environment Preparation

### Objective

Prepare a repeatable local development workflow.

### Tasks

- Confirm repository structure.
- Confirm the main branch.
- Define feature branch naming.
- Define commit message conventions.
- Confirm environment file handling.
- Confirm backend runtime requirements.
- Confirm frontend runtime requirements.
- Confirm PostgreSQL availability.
- Decide whether Redis is required.
- Review System Design artifacts.
- Review unresolved design decisions.
- Confirm testing and formatting tools.
- Confirm the initial implementation scope.

### Completion Criteria

- The project opens locally.
- Backend requirements are documented.
- Frontend requirements are documented.
- Database connectivity is confirmed.
- Environment configuration is documented.
- Secret files are excluded from Git.
- Sprint 1 tasks are ready.

## Sprint 1 - Backend and Frontend Foundation

### Objective

Create the basic Laravel and React application structure.

### Tasks

- Configure Laravel application structure.
- Configure the REST API boundary.
- Configure React and TypeScript.
- Configure frontend routing and application layout.
- Configure shared API communication.
- Configure code formatting and linting.
- Configure backend tests.
- Configure frontend tests.
- Establish basic CI checks.

### Completion Criteria

- Backend starts successfully.
- Frontend starts successfully.
- Frontend can communicate with the backend.
- Basic automated checks run successfully.
- No secrets are committed.

## Sprint 2 - Database and Authentication

### Objective

Implement the secure user foundation.

### Tasks

- Create initial database migrations.
- Implement user registration.
- Implement login and logout.
- Implement session validation.
- Implement authenticated user access.
- Implement authorization middleware.
- Implement ownership checks.
- Add backend authentication tests.
- Add frontend authentication screens and states.

### Completion Criteria

- A user can register.
- A user can log in and log out.
- Protected endpoints reject unauthenticated requests.
- Users cannot access another user's resources.
- Authentication tests pass.

## Sprint 3 - Workspace and Communication Features

### Objective

Create the initial communication workspace.

### Tasks

- Implement workspace layout.
- Implement conversation list.
- Implement conversation details.
- Implement message display.
- Implement search and filtering foundations.
- Establish conversation organization foundations.
- Add empty, loading, and error states.
- Add backend and frontend tests.

### Completion Criteria

- Users can view their communication data.
- Users can open a conversation.
- Users can view messages.
- Conversation organization foundations are established.
- Ownership checks remain effective.

## Sprint 4 - Gmail Provider Integration

### Objective

Implement the Gmail connector behind the provider boundary.

### Tasks

- Finalize required Gmail scopes.
- Implement Gmail authorization flow.
- Validate authorization state.
- Store provider credentials securely.
- Implement Gmail connector interfaces.
- Map Gmail data to internal concepts.
- Classify Gmail failures.
- Add provider integration tests.

### Completion Criteria

- A user can connect Gmail.
- Provider credentials remain server-side.
- Gmail-specific logic remains behind the connector boundary.
- Gmail data can be retrieved through the integration boundary.
- Provider failures are classified safely.

## Sprint 5 - Gmail Synchronization

### Objective

Synchronize Gmail provider data and make it available to the unified workspace.

### Tasks

- Implement synchronization coordination.
- Validate external provider data.
- Normalize conversations and messages.
- Prevent duplicate records.
- Support initial synchronization.
- Support incremental synchronization.
- Record synchronization status.
- Preserve existing data during failures.
- Add synchronization tests.

### Completion Criteria

- Gmail data appears in the unified workspace.
- Duplicate records are prevented.
- Synchronization status is visible.
- Temporary failures can be retried.
- Existing valid data is preserved.

## Sprint 6 - Unified Inbox

### Objective

Complete the unified inbox experience under M3 using the provider-neutral workspace and synchronized Gmail data.

### Tasks

- Complete conversation organization workflows.
- Implement and verify status filtering.
- Implement and verify label filtering.
- Display conversation status consistently.
- Complete inbox selection, loading, and error states.
- Add the remaining frontend interaction tests.
- Present synchronized Gmail conversations and messages.
- Preserve ownership boundaries and provider-neutral APIs.

### Completion Criteria

- Users can organize synchronized communication in the unified workspace.
- Status and label filters work end to end.
- Conversation status is visible.
- Required frontend interaction, loading, and error tests pass.
- Existing backend and frontend verification remains passing.
- No provider credentials are exposed.
- Sprint 06 verification evidence is recorded.

## Sprint 7 - Security Foundation and Telegram Validation

### Objective

Establish the V1 security/data-risk baseline and validate the Telegram integration mode without implementing the production Telegram connector.

### Selected Scope

- PB-023 - Validate the Telegram integration mode.
- SEC-001 - Establish Threat Model and Risk Register.
- SEC-002 - Data Classification, Retention, and Deletion Model.

### Tasks

- Validate the Telegram API / MTProto + TDLib direction.
- Run the bounded TDLib technical probe.
- Capture sanitized runtime evidence.
- Verify chat/history retrieval and restart/session-recovery behavior.
- Maintain the authoritative threat/risk baseline.
- Maintain the authoritative data-classification/lifecycle baseline.
- Preserve the dependency that SEC-019 must be finalized against the validated runtime/session boundary before PB-024.

### Completion Criteria

- SEC-001 and SEC-002 baseline artifacts remain completed and authoritative.
- PB-023 runtime evidence satisfies its acceptance criteria.
- No secrets or private Telegram message content are committed.
- SEC-019 can be refined/finalized against the validated runtime boundary before PB-024.
- PB-024, PB-025, and PB-026 remain outside Sprint 07.

PB-023 is currently **In Progress** because real runtime evidence is still required. Sprint 07 is recorded as **In Progress**; its closure is governed by the defined timebox and Sprint review, not by waiting indefinitely for PB-023 completion.

## Future M4 Implementation Direction

After PB-023 runtime validation:

1. refine/finalize SEC-019 against the actual Telegram runtime/session boundary;
2. prioritize the resulting work;
3. select later M4 work only through Sprint Planning;
4. implement PB-024 before PB-025;
5. preserve PB-026 provider-failure isolation as separate planned work.

This direction does not reserve a future Sprint number.

## Future M5 Implementation Direction - Production Hardening and Release Preparation

Future M5 work may include security verification, integration/end-to-end testing, performance checks, backup/recovery validation, deployment preparation, monitoring/logging readiness, release documentation, and release-candidate preparation.

No Sprint number is assigned here. Production evidence must be created only when the relevant deployment environment and controls actually exist.

## Definition of Done

An implementation item is complete when:

- Acceptance criteria are satisfied.
- Relevant automated tests are added or updated.
- Authorization and ownership are verified.
- Error handling is implemented.
- Sensitive data is protected.
- Documentation is updated where necessary.
- Code formatting and static checks pass.
- The change is reviewed.
- The change does not break unrelated features.

## Versioning

The project uses Semantic Versioning:

```text
MAJOR.MINOR.PATCH
```

Maturity labels may include Development Build, Alpha, Beta, Release Candidate, and Stable.

A sprint does not automatically create a release.

A milestone does not automatically equal one version.

## Traceability

Implementation tasks should be traceable to approved requirements, System Analysis artifacts, System Design artifacts, milestones, sprint goals, acceptance criteria, and automated tests.

## Unresolved Implementation Decisions

| ID | Decision | Current State |
|---|---|---|
| IMP-001 | Final sprint duration | To be decided |
| IMP-002 | Exact branch strategy | To be finalized |
| IMP-003 | Exact Redis usage | To be decided during implementation |
| IMP-004 | Exact Gmail scopes | Resolved in Sprint 04 |
| IMP-005 | Exact Telegram integration mode | Telegram API/MTProto through official TDLib behind a long-lived adapter is the selected direction; PB-023 runtime evidence remains pending in Sprint 07 |
| IMP-007 | Exact release schedule | To be finalized during release planning |
| IMP-008 | Production deployment method | To be finalized during deployment planning |
