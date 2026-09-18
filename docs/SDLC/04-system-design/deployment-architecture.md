# Deployment Architecture

> Status: Draft

## Purpose

This document defines the initial deployment architecture for OmniDesk AI.

It describes the runtime nodes, data services, external services, and communication paths required to operate the application.

## Deployment Objectives
- Support the React web client.
- Run the Laravel REST API.
- Support background synchronization and AI processing.
- Store durable application data in PostgreSQL.
- Use Redis for caching or background job processing where justified.
- Protect credentials and provider tokens.
- Keep external services outside the application boundary.
- Allow the initial deployment to remain simple and maintainable.

## Deployment Architecture Diagram

![OmniDesk AI Deployment Architecture](https://www.plantuml.com/plantuml/svg/~h407374617274756d6c0d0a7469746c65204f6d6e694465736b204149204465706c6f796d656e74204172636869746563747572650d0a0d0a6c65667420746f20726967687420646972656374696f6e0d0a736b696e706172616d206e6f64655374796c652072656374616e676c650d0a736b696e706172616d20636f6d706f6e656e745374796c652072656374616e676c650d0a0d0a6e6f6465202255736572204465766963652220617320446576696365207b0d0a2020617274696661637420225765622042726f77736572222061732042726f777365720d0a7d0d0a0d0a6e6f646520224170706c69636174696f6e20486f73742220617320486f7374207b0d0a20206e6f64652022576562205365727665722220617320576562536572766572207b0d0a202020206172746966616374202252656163742057656220436c69656e742220617320436c69656e740d0a20207d0d0a0d0a20206e6f646520224170706c69636174696f6e2052756e74696d65222061732052756e74696d65207b0d0a20202020617274696661637420224c61726176656c20524553542041504922206173204150490d0a20202020617274696661637420224261636b67726f756e6420576f726b65722220617320576f726b65720d0a20207d0d0a7d0d0a0d0a6e6f6465202244617461205365727669636573222061732044617461207b0d0a202064617461626173652022506f737467726553514c222061732044420d0a20206e6f646520225265646973222061732052656469730d0a7d0d0a0d0a636c6f7564202245787465726e616c205365727669636573222061732045787465726e616c207b0d0a2020636f6d706f6e656e742022476d61696c204150492220617320476d61696c0d0a2020636f6d706f6e656e74202254656c656772616d20415049222061732054656c656772616d0d0a2020636f6d706f6e656e74202253656c65637465642041492050726f7669646572222061732041490d0a7d0d0a0d0a42726f77736572202d2d3e20436c69656e74203a2048545450530d0a436c69656e74202d2d3e20415049203a2052455354202f204a534f4e206f7665722048545450530d0a415049202d2d3e204442203a2064757261626c6520646174610d0a415049202d2d3e205265646973203a206361636865206f72206a6f622073746174650d0a415049202d2d3e20576f726b6572203a206469737061746368206261636b67726f756e64206a6f620d0a576f726b6572202d2d3e205265646973203a2071756575652070726f63657373696e670d0a576f726b6572202d2d3e204442203a2073796e6368726f6e697a6174696f6e20616e6420617564697420646174610d0a576f726b6572202d2d3e20476d61696c203a2070726f76696465722072657175657374730d0a576f726b6572202d2d3e2054656c656772616d203a2070726f76696465722072657175657374730d0a415049202d2d3e204149203a20617070726f76656420414920726571756573740d0a576f726b6572202d2d3e204149203a206173796e6368726f6e6f757320414920726571756573740d0a0d0a6e6f7465207269676874206f662044420d0a53746f726573206170706c69636174696f6e206461746120616e642070726f7465637465640d0a636f6e6e656374696f6e20696e666f726d6174696f6e2e0d0a656e64206e6f74650d0a0d0a6e6f7465207269676874206f662045787465726e616c0d0a45787465726e616c2073657276696365732072656d61696e206f757473696465207468650d0a4f6d6e694465736b204149206465706c6f796d656e7420626f756e646172792e0d0a656e64206e6f74650d0a0d0a6e6f746520626f74746f6d206f6620486f73740d0a54686520696e697469616c206465706c6f796d656e74206d61792075736520612073696e676c650d0a6170706c69636174696f6e20686f73742077697468207365706172617465207765622c0d0a72756e74696d652c20616e6420776f726b65722070726f6365737365732e0d0a656e64206e6f74650d0a0d0a40656e64756d6c)

## Deployment Nodes
| Node | Responsibility |
|---|---|
| User Device | Runs the user's web browser |
| Web Server | Serves the React web client |
| Application Runtime | Runs the Laravel REST API |
| Background Worker | Processes synchronization and other asynchronous jobs |
| PostgreSQL | Stores durable application data |
| Redis | Supports caching or background job processing where justified |
| External Services | Provide Gmail, Telegram, and AI capabilities |

## Application Host

The initial deployment may use one application host containing:
- Web server
- React client assets
- Laravel application runtime
- Background worker process

These processes may be separated across different hosts when operational requirements justify it.

## Infrastructure Trust Assumption

The V1 server-side architecture requires the application runtime to process application-readable communication data. The approved production hosting, database/storage, snapshot, and backup operational boundary is therefore a trusted infrastructure boundary.

Authorized infrastructure or platform administrators with sufficiently privileged access may technically be capable of accessing plaintext application data where the application itself must be able to read that data.

Reducing hosting-provider/operator visibility would require a materially different security architecture, such as application-level encryption with separately controlled keys, client-side encryption, confidential computing, or equivalent controls. Those controls are not silently included in V1 and require separate risk/design approval.

This assumption is traced to SEC-001, SEC-008, SEC-015, and SEC-016.

## Data Protection Layers

The deployment distinguishes:

- transport encryption for network paths;
- infrastructure/storage encryption for disks, volumes, managed storage, and snapshots;
- database-native encryption where selected by the production platform;
- application-level/field-level encryption for specifically approved fields such as current Gmail provider tokens;
- client-side/end-to-end encryption, which is not part of the approved V1 scope.

The exact persisted-message protection model remains an unresolved SEC-015 design decision. Backup protection remains governed by SEC-016.

## Data Services

### PostgreSQL

PostgreSQL is the durable persistence service.

It stores:
- User accounts
- Provider connections
- Conversations
- Messages
- Labels
- Synchronization records
- AI suggestions
- Safe audit information

Provider credentials and tokens must be protected at rest.

### Redis

Redis may be used for:
- Background job queues
- Temporary operation state
- Caching
- Rate-limit support

Redis is optional and must be used only where justified.

Cached data must not bypass authentication or authorization checks.

## External Services

The deployment communicates with:
- Gmail API
- Telegram API
- Selected AI provider

External services remain outside the OmniDesk AI deployment boundary.

Their availability, policies, rate limits, and capabilities are external dependencies.

## Communication Paths
| Source | Destination | Communication |
|---|---|---|
| Browser | Web Server | HTTPS |
| React Client | Laravel API | REST and JSON over HTTPS |
| Laravel API | PostgreSQL | Durable data access |
| Laravel API | Redis | Cache or job state |
| Laravel API | Background Worker | Background job dispatch |
| Background Worker | PostgreSQL | Synchronization and audit data |
| Background Worker | Gmail API | Provider requests |
| Background Worker | Telegram API | Provider requests |
| Laravel API | AI Provider | Approved AI requests |
| Background Worker | AI Provider | Asynchronous AI requests |

## Security Requirements
- External client communication must use HTTPS.
- Provider tokens must remain on the backend.
- Database credentials must not be exposed to the frontend.
- AI requests must contain only approved and minimized context.
- External services must not receive unnecessary secrets.
- Internal services must use protected configuration.
- Logs must not contain passwords, tokens, or session secrets.
- Database access must be restricted.
- Redis access must be restricted.
- Background jobs must preserve user ownership boundaries.

## Reliability Requirements

The deployment should:
- Keep existing communication data available during provider failures.
- Allow background jobs to retry temporary failures.
- Isolate failures between providers.
- Keep core workspace operations available when AI is unavailable.
- Monitor worker and queue failures.
- Monitor PostgreSQL and Redis availability.
- Avoid data loss during application restarts.
- Provide safe recovery procedures.

## Scalability Direction

The initial deployment should remain simple.

If demand increases, the following components may scale independently:
- Web server
- Laravel application runtime
- Background workers
- Redis
- PostgreSQL
- Provider integration workload

Scaling decisions should be based on measured requirements.

## Backup and Recovery

The deployment should define:
- PostgreSQL backup frequency
- Backup retention period
- Recovery testing procedure
- Provider credential recovery procedure
- Queue and failed-job recovery procedure
- Data deletion and retention behavior

The exact backup strategy belongs to Deployment Planning.

## Environment Separation

The project should distinguish between:
- Local development
- Testing
- Staging or demonstration
- Production

Environment-specific credentials and configuration must not be committed to the repository.

## Observability

The deployment should monitor:
- API availability
- Application errors
- Background worker status
- Queue depth
- Synchronization failures
- Provider rate-limit responses
- AI provider failures
- PostgreSQL health
- Redis health
- Resource usage

Monitoring data must not expose sensitive communication content or credentials.

## Unresolved Decisions
| ID | Decision | Current State |
|---|---|---|
| DEP-001 | Exact hosting provider | Unresolved |
| DEP-002 | Whether web and application runtime use separate hosts | To be decided during deployment planning |
| DEP-003 | Production database hosting model | Unresolved |
| DEP-004 | Redis hosting model | Unresolved |
| DEP-005 | Backup frequency and retention | Requires deployment decision |
| DEP-006 | Monitoring and alerting tools | Requires deployment decision |
| DEP-007 | Containerized or direct deployment | To be decided before implementation deployment |
| DEP-008 | Production domain and TLS configuration | To be finalized during deployment planning |

## PlantUML Source

The editable PlantUML source is available in [deployment-architecture-diagram.puml](./diagrams/deployment-architecture-diagram.puml).

## Relationship to Later Documents

This design will provide input for:
- Deployment Planning
- Environment Configuration
- Infrastructure Setup
- Security Operations
- Backup and Recovery
- Monitoring and Observability
- Performance Testing
- Production Release
