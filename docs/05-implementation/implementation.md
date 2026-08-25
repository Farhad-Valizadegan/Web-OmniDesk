# 5. Implementation

## Implementation Baseline

Phase 5 turns the approved architecture into an executable application. The implementation follows the Phase 4 decisions and keeps the MVP focused on Gmail, Telegram user-account integration, Unified Inbox, and AI assistance.

## Technology Stack

- Backend: Laravel (PHP)
- Frontend: React + TypeScript
- Database: PostgreSQL
- Queue / Cache: Redis
- Containerization: Docker Compose for local development
- API style: RESTful JSON

## Initial Implementation Order

1. Application foundation and local infrastructure
2. Authentication and user ownership boundaries
3. Core domain/database schema
4. Connector contracts and synchronization pipeline
5. Gmail connector
6. Telegram user-account connector
7. Unified Inbox API and frontend
8. AI assistance boundary and features
9. Security hardening and observability
10. Automated tests and CI

## Module Boundaries

- Identity & Access
- Connections
- Unified Inbox
- Synchronization
- Gmail Connector
- Telegram Connector
- AI Assistance
- Search & Organization

## Engineering Rules

- Controllers remain thin.
- Provider-specific SDK/client objects do not leak into the Unified Inbox domain.
- External calls are isolated behind connector/provider interfaces.
- Long-running sync and AI operations use background jobs where appropriate.
- Secrets and provider session material remain backend-only.
- AI output never causes an external send without explicit user confirmation.
- New design patterns are introduced only when justified by implementation needs.

## Phase 5 Exit Criteria

Implementation is ready to transition to the formal Testing phase when the MVP workflows are executable locally, critical paths have automated tests, integrations fail gracefully, and setup instructions are reproducible.
