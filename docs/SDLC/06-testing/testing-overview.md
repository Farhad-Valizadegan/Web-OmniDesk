# Testing Overview

> Status: Active

## Purpose

This document describes how OmniDesk verifies software quality across Sprints and release preparation.

Testing is not treated as a single activity that begins only after implementation. In the project's Agile workflow, relevant testing occurs continuously inside each Sprint and is expanded during production-hardening and release preparation.

## Testing Principles

- Test work should be selected according to the Sprint Goal and implementation risk.
- Completed backlog items require relevant automated or manual verification.
- Ownership, authentication, provider boundaries, and failure handling require explicit regression coverage.
- Provider integrations should be testable without depending exclusively on live provider calls.
- Existing tests must continue passing when new functionality is introduced.
- CI is the shared verification gate for changes targeting `main`.
- End-to-end and release-focused verification supplements, rather than replaces, lower-level tests.

## Current Automated Verification

### Backend

The Laravel backend currently uses PHPUnit-based unit and feature tests covering areas including:

- authentication and registration
- authorization and ownership boundaries
- conversation and message APIs
- Gmail authorization and callback behavior
- Gmail token refresh and secure connection handling
- Gmail full and incremental synchronization
- deletion and duplicate-handling behavior
- provider error classification and retry behavior
- provider connector contracts
- health checks and development fixtures

Backend formatting is checked with Laravel Pint.

### Frontend

The React + TypeScript frontend currently uses Vitest and Testing Library for areas including:

- authentication/application behavior
- conversation rendering and selection
- search and filtering interactions
- Gmail connection states
- API-client behavior
- loading, empty, and error states where implemented

Frontend quality checks also include ESLint and a production Vite build.

## Continuous Integration

GitHub Actions runs on pull requests targeting `main` and on pushes to `main`.

The current CI pipeline verifies:

### Backend job

1. PostgreSQL service startup.
2. PHP setup.
3. Composer dependency installation.
4. Laravel Pint formatting check.
5. Laravel environment preparation.
6. Database migrations.
7. Backend automated tests.

### Frontend job

1. Node.js setup.
2. `npm ci` dependency installation.
3. Frontend automated tests.
4. ESLint.
5. Production build.

## Test Levels

### Unit Tests

Used for isolated logic such as provider error classification, retry behavior, and connector contracts.

### Feature / API Tests

Used for authenticated application behavior, data ownership, API responses, provider connection flows, and synchronization behavior.

### Frontend Component and Interaction Tests

Used to verify rendering, user interaction, API integration boundaries, loading states, error states, and filtering/navigation behavior.

### Integration Tests

Used where multiple internal components must be verified together, especially provider synchronization, persistence, authentication, and API behavior.

### End-to-End Tests

Broader end-to-end coverage is planned under production hardening. These tests should verify critical user journeys across frontend, backend, database, and supported provider boundaries where practical.

### Manual Verification

Manual verification is appropriate for provider OAuth behavior, browser callback flows, visual behavior, real-account integration checks, and release validation that cannot be adequately represented by automated tests alone.

## Security and Privacy Verification

Security-relevant verification should include, where applicable:

- authentication enforcement
- user ownership isolation
- OAuth state validation
- encrypted provider credential storage
- prevention of secret exposure in API responses and frontend state
- safe provider error handling
- message-content rendering protections

## Planned Security Verification Coverage

The current automated-test inventory above describes tests that exist now. The security backlog defines additional verification that remains **planned** until the corresponding control is designed and implemented.

Future security verification should reference the accepted SEC backlog items rather than duplicate their full acceptance criteria:

- SEC-003 - browser authentication/session security.
- SEC-004 - email two-step verification.
- SEC-005 - email verification, password change/reset, and account recovery.
- SEC-006 - sensitive-action reauthentication.
- SEC-007 - object-level authorization and cross-user isolation regression coverage.
- SEC-008 - provider credential protection and production key-management verification.
- SEC-009 - provider least-privilege/scope verification.
- SEC-010 - untrusted message/HTML rendering.
- SEC-011 - remote-content privacy behavior.
- SEC-012 - rate limiting and abuse protection for sensitive endpoints.
- SEC-013 - security audit trail and log-redaction verification.
- SEC-014 - production TLS, security headers, and browser-security validation.
- SEC-015 - database, Redis, storage, and persisted-sensitive-data protection.
- SEC-016 - backup protection, restore, retention, and deletion verification.
- SEC-017 - dependency, supply-chain, secret-scanning/static-analysis, and CI/CD security controls as approved.
- SEC-018 - privacy, deletion/export, retention, and user-control behavior where implemented.
- SEC-019 - Telegram credential/session protection after PB-023 establishes the runtime boundary.
- SEC-021 - monitoring and incident-response verification appropriate to the deployed environment.

A listed verification category is not evidence that a corresponding test already exists. Test existence and completion must be supported by repository or deployment evidence.

## Regression Rule

A change is not considered complete if it introduces a regression in previously verified functionality.

Relevant existing backend and frontend tests must remain passing, and new behavior should receive focused regression coverage where practical.

## Sprint Relationship

A Sprint may include requirements refinement, analysis, design, implementation, testing, and documentation as required by the Sprint Goal.

Testing is therefore part of Sprint execution rather than a mandatory post-implementation phase gate.

Sprint completion requires the relevant verification defined by the Sprint plan and Definition of Done.

## Production-Hardening Relationship

M5 - Production Hardening expands verification beyond normal Sprint-level checks and includes:

- security verification
- integration and end-to-end testing
- performance checks
- deployment validation
- recovery and operational verification
- release-candidate validation

## Evidence

Sprint documents record Sprint-specific verification evidence such as:

- test results
- lint/build status
- CI runs
- relevant manual verification
- pull requests and merge commits

Release documentation should record release-level verification separately from Sprint history.
