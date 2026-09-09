# Design Decisions and Trade-offs

> Status: Draft

## Purpose

This document records the main technical design decisions for OmniDesk AI.

It explains the selected direction, considered alternatives, rationale, trade-offs, and consequences of each decision.

These decisions guide implementation while allowing refinement when new technical or operational constraints are identified.

## Decision Status

The decisions in this document represent the current System Design direction.

They are not final implementation details unless explicitly stated.

Unresolved decisions remain open for later validation.

## Decision DD-001: Modular Monolith

### Context

OmniDesk AI requires several related capabilities, including authentication, communication management, synchronization, provider integration, and AI assistance.

### Considered Options
- Modular monolith
- Microservices
- Serverless architecture

### Decision

Use a modular monolith as the initial application architecture.

### Rationale
- The project is initially small to medium in scope.
- Core modules share data and business concepts.
- A single deployment is easier to develop and operate.
- Module boundaries can still enforce separation of responsibilities.
- The architecture can evolve if independent scaling becomes necessary.

### Trade-offs

Advantages:
- Lower operational complexity
- Easier local development
- Simpler testing and debugging
- Easier transactions across related operations
- Lower infrastructure requirements

Disadvantages:
- Modules share the same runtime.
- A failure in the application process may affect multiple modules.
- Independent scaling is more limited than with microservices.
- Strong internal boundaries must be maintained through design and testing.

### Consequences

The implementation must preserve clear module boundaries and avoid unnecessary coupling.

## Decision DD-002: Laravel Backend

### Context

The backend must provide authentication, REST APIs, persistence access, background processing, validation, and integration workflows.

### Considered Options
- Laravel
- Symfony
- Node.js backend
- Python backend

### Decision

Use Laravel as the backend framework.

### Rationale
- Laravel provides mature routing, validation, authentication, queues, and ORM support.
- The project already has a PHP and Laravel direction.
- Laravel supports rapid development while preserving maintainable structure.
- The framework is suitable for REST API development and modular application services.

### Trade-offs

Advantages:
- Mature ecosystem
- Strong developer productivity
- Integrated authentication and validation features
- Good support for queues and scheduled tasks
- Suitable testing tools

Disadvantages:
- The application depends on PHP and Laravel conventions.
- Framework upgrades require maintenance.
- Some highly specialized workloads may require separate services.

### Consequences

Backend implementation should use Laravel conventions while keeping business logic outside controllers where practical.

## Decision DD-003: React and TypeScript Frontend

### Context

The application requires an interactive unified communication workspace.

### Considered Options
- React with TypeScript
- Vue with TypeScript
- Server-rendered Laravel views
- Angular

### Decision

Use React and TypeScript for the web client.

### Rationale
- The workspace requires interactive filtering, searching, editing, and state changes.
- TypeScript improves type safety and maintainability.
- React supports component-based user interface development.
- The frontend can communicate with the backend through stable REST endpoints.

### Trade-offs

Advantages:
- Rich interactive user experience
- Reusable components
- Strong TypeScript support
- Large ecosystem
- Clear separation between frontend and backend

Disadvantages:
- Additional frontend build tooling is required.
- Client-side state and API synchronization require careful design.
- Accessibility must be actively maintained.

### Consequences

The frontend must not contain provider credentials or provider-specific integration logic.

## Decision DD-004: PostgreSQL for Durable Persistence

### Context

OmniDesk AI must store users, provider accounts, conversations, messages, labels, synchronization records, and AI suggestions.

### Considered Options
- PostgreSQL
- MySQL
- SQLite
- Document database

### Decision

Use PostgreSQL as the primary durable persistence store.

### Rationale
- The data has clear relationships and ownership boundaries.
- Relational constraints support data integrity.
- PostgreSQL provides strong support for indexing, transactions, and structured queries.
- The database is suitable for synchronization and search-related workloads.

### Trade-offs

Advantages:
- Strong relational integrity
- Transaction support
- Mature indexing capabilities
- Suitable for structured domain data
- Good long-term scalability

Disadvantages:
- Database schema and migrations require careful planning.
- Search requirements may later require additional specialized support.
- Operational management is more involved than using SQLite.

### Consequences

Database migrations, indexes, foreign keys, and uniqueness constraints must be designed before implementation.

## Decision DD-005: Redis Only Where Justified

### Context

The system may need queues, temporary operation state, caching, and rate-limit support.

### Considered Options
- No Redis
- Redis for all application state
- Redis only for selected temporary or asynchronous workloads

### Decision

Use Redis only for caching, queues, rate limiting, or temporary state where justified.

### Rationale
- Durable business data belongs in PostgreSQL.
- Redis is useful for background jobs and temporary state.
- Limiting Redis usage reduces unnecessary infrastructure and consistency risks.
- Authorization must not depend only on cached data.

### Trade-offs

Advantages:
- Supports efficient background processing
- Can improve response time for suitable data
- Supports rate limiting and temporary state

Disadvantages:
- Adds operational complexity.
- Cached data may become stale.
- Incorrect use can weaken consistency or privacy.

### Consequences

Redis must not become the source of truth for durable communication data.

## Decision DD-006: REST and JSON API

### Context

The React client requires a stable communication boundary with the Laravel backend.

### Considered Options
- REST and JSON
- GraphQL
- Server-rendered views
- WebSockets as the primary API

### Decision

Use versioned REST endpoints with JSON responses.

### Rationale
- REST is suitable for resource-based operations.
- JSON is supported by React and Laravel.
- Versioned endpoints provide a clear evolution path.
- REST is simple to test with standard tools.

### Trade-offs

Advantages:
- Simple client-server contract
- Easy testing and debugging
- Clear resource-oriented design
- Suitable for external integration boundaries

Disadvantages:
- Some complex screens may require multiple requests.
- Real-time updates are not provided automatically.
- API consistency must be maintained manually.

### Consequences

The initial API version is `/api/v1`.

## Decision DD-007: Provider Connector Boundary

### Context

Gmail and Telegram have different authorization mechanisms, APIs, data formats, limits, and failure behaviors.

### Considered Options
- Direct provider logic inside application services
- Separate provider connectors behind a common boundary
- Independent external integration services

### Decision

Use provider connectors behind a common Provider Integration Boundary.

### Rationale
- Provider-specific behavior remains isolated.
- Core application services can use provider-independent concepts.
- New providers can be added with limited impact on core modules.
- Provider failures can be classified consistently.

### Trade-offs

Advantages:
- Better extensibility
- Clear separation of responsibilities
- Easier provider-specific testing
- Reduced coupling to external APIs

Disadvantages:
- Requires common interfaces and mapping rules.
- Provider capabilities may not map perfectly to common concepts.
- Additional translation logic is required.

### Consequences

The core workspace must not depend directly on Gmail or Telegram API details.

## Decision DD-008: Provider-Independent Communication Model

### Context

The unified workspace must display communication data from multiple providers.

### Considered Options
- Use provider-specific entities throughout the application
- Use a common communication model with provider metadata
- Store only raw provider responses

### Decision

Use provider-independent concepts such as provider accounts, conversations, messages, senders, recipients, and labels.

Preserve provider identifiers and required provider metadata for traceability.

### Rationale
- The unified workspace requires common concepts.
- Provider-specific details must remain isolated.
- External identifiers are required for synchronization and duplicate prevention.
- A normalized model simplifies search and organization.

### Trade-offs

Advantages:
- Consistent user experience
- Easier cross-provider search
- Easier future provider integration
- Clearer core business logic

Disadvantages:
- Some provider-specific features may not fit the common model.
- Mapping and normalization require additional logic.
- Provider metadata must be managed carefully.

### Consequences

Provider-specific metadata may be stored only when it supports a justified feature or traceability requirement.

## Decision DD-009: Background Synchronization

### Context

Provider synchronization may involve slow network requests, pagination, rate limits, and large data volumes.

### Considered Options
- Fully synchronous synchronization
- Background jobs
- External synchronization platform

### Decision

Use background processing for synchronization when the operation may exceed a normal request duration.

### Rationale
- Long-running provider requests should not block the user interface.
- Background jobs support retries and failure tracking.
- Synchronization status can be shown to the user.
- Provider failures can be isolated from normal workspace access.

### Trade-offs

Advantages:
- Better user experience
- Controlled retries
- Improved failure isolation
- Suitable for incremental synchronization

Disadvantages:
- Requires queue and worker management.
- Results are not immediately available.
- Duplicate prevention and idempotency are required.

### Consequences

Synchronization operations must record status, failures, timestamps, and safe diagnostic information.

## Decision DD-010: AI Provider Abstraction

### Context

AI provider selection remains unresolved and external AI services may differ in capability, cost, privacy policy, and availability.

### Considered Options
- Direct dependency on one AI provider
- AI provider abstraction
- No AI integration

### Decision

Use an AI provider abstraction behind the AI Assistance Module.

### Rationale
- The final provider has not been selected.
- Provider replacement should not require changes to core communication logic.
- AI failures should be isolated.
- Request and response handling should remain under application control.

### Trade-offs

Advantages:
- Provider flexibility
- Easier testing with mock providers
- Reduced vendor lock-in
- Centralized privacy and minimization controls

Disadvantages:
- Requires an internal common interface.
- Provider-specific capabilities may be hidden or limited.
- Additional abstraction maintenance is required.

### Consequences

AI requests must pass through the AI Assistance Module and Context Minimizer.

## Decision DD-011: User-Controlled AI Assistance

### Context

AI-generated summaries and reply suggestions may be useful but can be incomplete, inaccurate, or inappropriate.

### Considered Options
- Autonomous AI actions
- AI suggestions requiring user review
- No AI assistance

### Decision

AI features remain assistive and user-controlled.

Generated content must be reviewable, editable, accepted, or discarded by the user.

### Rationale
- Users must retain control over communication actions.
- AI output is untrusted content.
- Autonomous message sending introduces additional safety and authorization risks.
- The current scope excludes autonomous sending.

### Trade-offs

Advantages:
- Preserves user control
- Reduces automation risk
- Supports correction of inaccurate output
- Limits external side effects

Disadvantages:
- Users must review generated content.
- AI does not fully automate communication workflows.
- Additional suggestion lifecycle handling is required.

### Consequences

AI must never send external messages autonomously.

## Decision DD-012: Backend Protection of Secrets

### Context

Provider tokens, credentials, session secrets, and database credentials are sensitive.

### Considered Options
- Store secrets in the frontend
- Return provider tokens to the frontend
- Protect secrets on the backend

### Decision

Keep credentials, provider tokens, and sensitive configuration on the backend.

### Rationale
- Frontend code and browser storage cannot provide sufficient protection for provider secrets.
- Backend access allows centralized authorization and auditing.
- Tokens must not be exposed in API responses or logs.

### Trade-offs

Advantages:
- Reduced credential exposure
- Centralized security controls
- Better control over provider access

Disadvantages:
- Backend infrastructure must be protected.
- Token storage and encryption require additional implementation work.
- Provider operations must pass through backend services.

### Consequences

Token encryption, secret management, logging controls, and access restrictions must be finalized during security implementation.

## Decision DD-013: PlantUML for Diagram Source Files

### Context

System Design requires diagrams that remain editable and version-controlled.

### Decision

Store editable diagram source files in PlantUML format under the relevant `diagrams` directory.

Markdown documents may contain rendered PlantUML image links and links to the editable source files.

### Rationale
- Diagram source can be reviewed in Git.
- Diagrams remain editable without proprietary design software.
- Source files can be maintained alongside their documentation.

### Trade-offs

Advantages:
- Version-controlled diagram source
- Text-based and editable format
- Easy repository integration

Disadvantages:
- Rendered image URLs require correct PlantUML encoding.
- Complex diagrams may require careful layout tuning.
- Readers may need a PlantUML-compatible renderer.

### Consequences

Each diagram must have a valid `.puml` source file and a corresponding Markdown reference.

## Decision DD-014: Incremental Synchronization

### Context

Repeatedly importing all provider data is inefficient and may increase rate-limit usage.

### Considered Options
- Full synchronization every time
- Incremental synchronization
- Manual synchronization only

### Decision

Support initial synchronization followed by incremental synchronization where the provider supports it.

### Rationale
- Reduces unnecessary provider requests.
- Improves synchronization performance.
- Limits repeated processing of unchanged data.
- Supports scalable operation as communication history grows.

### Trade-offs

Advantages:
- Lower provider usage
- Faster repeated synchronization
- Better scalability

Disadvantages:
- Requires synchronization cursors or timestamps.
- Provider-specific behavior must be handled.
- Missed updates may require recovery synchronization.

### Consequences

The synchronization design must define checkpoints, duplicate prevention, retry handling, and recovery behavior.

## Decision DD-015: Preserve Existing Data During Failures

### Context

Provider, synchronization, persistence, and AI operations may fail.

### Decision

Failures must not unnecessarily delete or corrupt previously synchronized data.

### Rationale
- Existing user data must remain available.
- Temporary provider failures should not destroy local information.
- Users need reliable access to previously synchronized communication.

### Trade-offs

Advantages:
- Better reliability
- Safer recovery
- Improved user trust

Disadvantages:
- Stale data may remain visible.
- Recovery and reconciliation logic are required.
- Partial synchronization handling is more complex.

### Consequences

Synchronization operations must be status-aware and should use safe transaction and idempotency strategies.

## Open Trade-offs and Unresolved Decisions
| ID | Decision | Current State |
|---|---|---|
| DD-OPEN-001 | Exact authentication mechanism | To be finalized during implementation |
| DD-OPEN-002 | Session-based or token-based API authentication | To be finalized during security design |
| DD-OPEN-003 | Final Gmail scopes | Requires provider validation |
| DD-OPEN-004 | Exact Telegram integration mode | Requires provider validation |
| DD-OPEN-005 | Final AI provider | Unresolved |
| DD-OPEN-006 | AI data retention period | Requires privacy decision |
| DD-OPEN-007 | Exact database uniqueness constraints | To be finalized during implementation |
| DD-OPEN-008 | Exact retry and backoff limits | To be finalized during implementation |
| DD-OPEN-009 | Production hosting model | Unresolved |
| DD-OPEN-010 | Containerized or direct deployment | To be finalized before deployment |
| DD-OPEN-011 | Message-sending capability | Outside current approved scope |
| DD-OPEN-012 | Account deletion and data retention behavior | Requires privacy decision |

## Design Review Rules

Future changes to these decisions should document:
- The original decision
- The reason for reconsideration
- The alternatives evaluated
- The selected replacement
- The migration or implementation consequences
- The affected requirements and design artifacts

## Relationship to Later Documents

This document will provide input for:
- Database Schema Design
- Authentication and Authorization Implementation
- Provider Connector Implementation
- Synchronization Implementation
- AI Service Integration
- Error Handling Implementation
- Security Implementation
- Deployment Planning
- Testing Strategy

## Phase Status

System Design remains in draft form.

These decisions will be reviewed against the requirements, System Analysis artifacts, implementation constraints, security requirements, and operational needs.
