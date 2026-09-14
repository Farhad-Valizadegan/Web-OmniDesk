# OmniDesk AI Milestones

> Status: Approved
> Approved: 2026-09-09

## Purpose

This document defines the meaningful project outcomes for OmniDesk AI.

Milestones represent completed capabilities or important project results. They are not the same as SDLC phases, sprints, releases, or versions.

## Milestone Model

A milestone may contain multiple sprints.

A milestone may result in one release, multiple releases, or no public release.

A release may include work from more than one milestone.

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

### M1 - Authentication and Workspace Foundation

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
- Gmail data normalization

### M3 - Unified Inbox

**Outcome:** A user can view and organize synchronized communication in one workspace.

Includes:

- Conversation list
- Conversation details
- Message display
- Search and filtering
- Labels and tags
- Duplicate prevention
- Incremental synchronization

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

### M6 - Production Hardening and Deployment

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

| Milestone | Sprints | Main Result |
|---|---|---|
| M0 - Engineering Foundation | Sprint 0 and Sprint 1 | Development foundation |
| M1 - Authentication and Workspace Foundation | Sprint 02 and Sprint 03 | Secure communication workspace |
| M2 - Gmail Integration | Sprint 4 and Sprint 5 | Gmail connection and synchronization |
| M3 - Unified Inbox | Sprint 06 | Unified communication workspace |
| M4 - Telegram Integration | Sprint 07 | Telegram connection and synchronization |
| M5 - AI-Assisted Communication | Sprint 08 | User-controlled AI assistance |
| M6 - Production Hardening and Deployment | Sprint 09 | Release readiness |

### M0 Sprint Structure

#### Sprint 0 - Repository and Environment Preparation

Prepares the repository, development environment, backend, frontend, database, tools, quality checks, and implementation workflow.

#### Sprint 1 - Backend and Frontend Foundation

Establishes the Laravel backend, React frontend, REST API foundation, frontend-backend communication, testing, linting, formatting, and CI baseline.

### M1 Sprint Structure

#### Sprint 2 - Database and Authentication

Implements the database foundation, registration, login, logout, sessions, authentication, and ownership checks.

#### Sprint 3 - Workspace Foundation

Implements the initial workspace, conversation list, conversation details, message display, search, filtering, labels, and tags.

Sprint 3 contributes to M1 only. Unified Inbox completion is assigned to Sprint 06 under M3.

### M2 Sprint Structure

#### Sprint 4 - Gmail Provider Integration

Implements Gmail authorization, secure token storage, the Gmail connector, data mapping, and provider error classification.

#### Sprint 5 - Gmail Synchronization

Implements Gmail synchronization coordination, validation, normalization, duplicate prevention, and incremental synchronization.

Sprint 5 contributes to M2 only.

### M3 Sprint Structure

#### Sprint 6 - Unified Inbox

Completes the unified inbox experience, including conversation organization, search, filtering, labels, synchronization presentation, and user-facing inbox workflows.

Sprint 6 contributes to M3 only.

### M4 Sprint Structure

#### Sprint 7 - Telegram Integration

Implements the validated Telegram connection flow, connector, supported data normalization, synchronization, and failure isolation.

### M5 Sprint Structure

#### Sprint 8 - AI Assistance

Implements summaries, editable reply suggestions, context minimization, AI provider abstraction, suggestion lifecycle, and AI failure handling.

### M6 Sprint Structure

#### Sprint 9 - Hardening and Release Preparation

Completes security verification, integration testing, end-to-end testing, performance checks, deployment preparation, monitoring review, and release candidate preparation.

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

Sprint assignments, release mapping, and acceptance criteria will be reviewed and updated as implementation progresses.
