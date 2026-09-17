# OmniDesk AI Milestones

> Status: Approved
> Approved: 2026-09-09
> Agile allocation corrected: 2026-09-17

## Purpose

This document defines the meaningful project outcomes for OmniDesk AI.

Milestones represent completed capabilities or important project results. They are not the same as SDLC phases, sprints, releases, or versions.

## Milestone Model

A milestone may contain multiple sprints.

A milestone may result in one release, multiple releases, or no public release.

A release may include work from more than one milestone.

Future Sprint numbers are not reserved in advance. Sprint assignments are made only during Sprint Planning after backlog items are refined and prioritized.

## Milestones

### M0 - Engineering Foundation

**Outcome:** The project is ready for reliable development.

Includes:

- Repository and development environment
- Laravel backend foundation
- React and TypeScript frontend foundation
- PostgreSQL configuration
- Redis decision and initial setup if required
- Basic testing and code-quality tools
- CI baseline
- Environment and secret-management conventions

### M1 - Authentication and Workspace

**Outcome:** A user can securely access a basic communication workspace.

Includes:

- Registration and login
- Session management
- Authorization and ownership checks
- Basic application layout
- User profile
- Empty workspace state
- Initial database models and migrations

### M2 - Gmail Integration

**Outcome:** A user can connect Gmail and retrieve Gmail communication data.

Includes:

- Gmail authorization
- Secure token storage
- Gmail connector
- Provider error handling
- Initial Gmail synchronization
- Incremental Gmail synchronization
- Duplicate prevention
- Synchronization status and failure handling
- Gmail data normalization

### M3 - Unified Inbox

**Outcome:** A user can view and organize synchronized communication in one workspace.

Includes:

- Conversation list
- Conversation details
- Message display
- Search and filtering
- Provider labels and label-based filtering

User-managed local labels and tags are a separate planned capability in PB-014 and are not required for M3 completion.

### M4 - Telegram Integration

**Outcome:** A user can connect Telegram and retrieve supported Telegram communication data.

Includes:

- Telegram integration decision
- Telegram connector
- Authorization or connection flow
- Telegram data normalization
- Telegram synchronization
- Provider-specific failure isolation

### M5 - AI-Assisted Communication

**Outcome:** A user can request and review AI-generated assistance.

Includes:

- Conversation summaries
- Editable reply suggestions
- Context minimization
- AI provider abstraction
- Suggestion lifecycle
- User ownership checks
- AI failure handling
- No autonomous message sending

### M6 - Production Hardening

**Outcome:** The application is sufficiently verified and prepared for release.

Includes:

- Security verification
- Integration and end-to-end testing
- Performance checks
- Backup and recovery planning
- Monitoring and logging
- Deployment configuration
- Release candidate preparation
- Documentation review

## Sprint Allocation

Completed historical allocations are recorded below. Future milestone work remains unassigned until Sprint Planning.

| Milestone | Sprint Allocation | Main Result |
|---|---|---|
| M0 - Engineering Foundation | Sprint 00 and Sprint 01 | Development foundation |
| M1 - Authentication and Workspace | Sprint 02 and Sprint 03 | Secure communication workspace foundation |
| M2 - Gmail Integration | Sprint 04 and Sprint 05 | Gmail connection and synchronization |
| M3 - Unified Inbox | Sprint 06 | Unified communication workspace |
| M4 - Telegram Integration | TBD through Sprint Planning | Telegram connection and synchronization |
| M5 - AI-Assisted Communication | TBD through Sprint Planning | User-controlled AI assistance |
| M6 - Production Hardening | TBD through Sprint Planning | Release readiness |

### Completed Sprint Structure

#### Sprint 00 - Repository and Environment Preparation

Prepared the repository, development environment, backend, frontend, database, tools, quality checks, and implementation workflow.

#### Sprint 01 - Backend and Frontend Foundation

Established the Laravel backend, React frontend, REST API foundation, frontend-backend communication, testing, linting, formatting, and CI baseline.

#### Sprint 02 - Database and Authentication

Implemented the database foundation, registration, login, logout, sessions, authentication, and ownership checks.

#### Sprint 03 - Workspace Foundation

Implemented the initial workspace, provider-neutral conversation/message foundation, conversation list/detail behavior, search, filtering foundation, and label metadata foundation.

Sprint 03 contributes to M1 only. Unified Inbox completion was assigned to Sprint 06 under M3.

#### Sprint 04 - Gmail Provider Integration

Implemented Gmail authorization, secure token storage, the Gmail connector, account/provider normalization foundation, and provider error classification.

#### Sprint 05 - Gmail Synchronization

Implemented Gmail synchronization coordination, validation, normalization, duplicate prevention, and incremental synchronization.

Sprint 05 contributes to M2 only.

#### Sprint 06 - Unified Inbox

Completed the unified inbox experience, including conversation organization, search, filtering, provider labels, synchronization presentation, and user-facing inbox workflows.

Sprint 06 contributes to M3 only.

## Future Sprint Assignment Rule

M4, M5, and M6 do not reserve Sprint 07, Sprint 08, or Sprint 09.

Before future work enters a Sprint:

1. Backlog items are refined.
2. Dependencies and unresolved decisions are reviewed.
3. Priority is compared with other ready work.
4. Sprint Planning selects a limited scope.
5. Only then is a Sprint number and Sprint Goal created.

This rule also applies to PB-039 through PB-042. Their refinement does not pre-commit them to the next Sprint.

## Relationship to Releases and Versions

Milestones describe project outcomes.

Sprints are short execution cycles that contribute to milestone outcomes.

Releases describe published increments.

A sprint does not automatically create a release.

A milestone does not automatically equal one version.

Versions identify releases using Semantic Versioning:

```text
MAJOR.MINOR.PATCH
```

Example:

- Several sprints may contribute to M2.
- M2 may be released as version 0.3.0.
- Later corrections may produce version 0.3.1.
- A major stable release may eventually become version 1.0.0.

## Approval Rule

A milestone is complete only when its defined outcome and acceptance conditions have been satisfied.

Sprint assignments, release mapping, and acceptance criteria are reviewed and updated as implementation progresses.