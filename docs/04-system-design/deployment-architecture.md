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

![OmniDesk AI Deployment Architecture](https://www.plantuml.com/plantuml/svg/RPHDSzCm48Rl-XKxERTmxu5fGZgqJ8U6E4pFiho9HMp9IEk4mF3VsPLirosSl9OUl_lflOsa0_LjeyXIW_3KEhl6U8Jb0wonQ_obHKUm3AQsX8RwW4erk2SW3y4UQeBA1hwmtgbujAxJGRVWV8K5NLXGxhGxD3YxDRxjl6FTjuYIxs3nBM9WxoThS04w3YRyLW0ShDrhGx1unX9MmP-PJT1Wgp-ZohBh6ckqH0RtFbAYn4X2cH6H0iDfqE3N_9QGkRSjoaFmZutbo1EUJIRPvwWuzxhj7Tasvp3O_mW_SaLEsC3shjZ1SlEGO7w-mLRQ70_1zww2Pn-EOxp9J05CQQyrwPGJbopcyic9-AtOA7LaQCDbE0GilZvcOZN5lyNAvi-I9RgcyNq5YxkVXC7fvhNuT2eEfiR2ubEhRJCbbDvU0pjiy23JC3BZmMkiu5D3MCb0Re8_sMh8VVaWiGrTX-lh3qCxu0RkTxjDeOPNkM4NV9nA_1u-5qzVm4kJCoUN0grNp5Hzq2NFf9HgkacLu4kZJOtW0tpt9V2FGpWXkHCYO6EdoTHGldICSJK0SxaVFVO8NV1SosZTOSwaOEB5cJfuPt_bUTAifVlAKWvlXkTgtuXOgX3_KwmUASwXgUB_uyP4bb8ftGcGYpuGSw7CZD7vFiuv7aF2OJNulOnMGJvW5D7fpv3uKphiXr9_bV7EvJq2rkrzQ1FvJY5J8lbMU1myDKrW7EQIG-5oEF0zHKuIg4OrtslLoruhfJywNFvnKteYtueVsHXgntstpf9bDxFFMts1dlyd3T8_Nc3p76jPDMTBDGScUuzLprXUgP0tmLNA_vohEWm1ndaWjspoPluB)

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

