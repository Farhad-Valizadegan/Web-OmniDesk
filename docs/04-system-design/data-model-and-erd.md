# Data Model and ERD

> Status: Draft

## Purpose

This document defines the conceptual data model for OmniDesk AI.

It identifies the main entities, relationships, ownership boundaries, and persistence responsibilities without finalizing implementation-specific migrations.

## Design Objectives
- Represent provider-independent communication concepts.
- Preserve provider and external identifiers.
- Support multiple provider accounts per user.
- Prevent duplicate synchronized records.
- Protect credentials and tokens.
- Support local labels and organization.
- Record synchronization activity.
- Store user-controlled AI suggestions.
- Enforce user ownership across all protected data.

## Data Model and ERD

![OmniDesk AI Data Model and ERD](https://www.plantuml.com/plantuml/svg/hLPVRzem47_tfpZriHhx0AYgcW7hK5j012fjBv593k8riIFReMNjllik2IK92IXQ-vRSxtn_VdVdyusnNDiqZfWLDaAOnb8CqJo1CuOXjnmUL80HS1d0Q3vaB1G1GemsL84fVen5ZShk4ZJCF0cPSCrZY8J4J0PAaovZACd-3YwMxcZkNW0ti3Ie3RmoW4iG0VHX9JP2MhYwcjrTNvEuryinZBc82DvoxOTS4xuiy4j8k37FIWTUo4rOgkIOhv5R33nk2R0YHaeqJWXAaw0D-5l6E9jF7yV3zpXdMcqfpqwnIXvZUwWv5msanQdd30RJvMHHT-xulagbxH93IhnwTONl1yBsvaxX-4B5b3poUE6lqAlHggH4tmebFUBDfkO8fuDeZ6VL4qg2KFfwbn3T16fSQpHXAvRBF7n91AaqQ_Q1OWwcaqSgfRCOJoS5fmCbjrHEdYNHXT03QJLIpf3dLnmSCsZIrM_YhoQBkB5at8ejHVofoJ-CNDUv7HLvFr1X-0QxfDpCu4mlnONTHgOe2Qs9DFeY4PZrCGLCV6NPAMb9KWhedErCGYNNU-VRwBx8z9wlCFhehDHd-VEQqAi4Mct5Mj1d2Z2x-mA78AFiq2c5IW3kpydWntmw6V-g38AxatwebHH_FdqMMkw6VD6qqUUhE8cm5LhJrPzgz7pYTwFqhaizAbaxOyzTtbB_bpaxOpVTq11qJVXSXtIPalgzVugRnX3yHwyL-_JjhTTJhyszqGVrB0qhbzbfHPCc2MtjKlc0xS_KBz1-7ZqNPBqEPDflh-MrKr6inRfl_3u4kAPNGvkP8wU74xJtsVxdd9hI6qvTZgTJQen2Fxkiw6bn56kzVVhxEfq8-LXPoBNIiM5CAekWnIQqeDQD4h15jWmDS8tbZinVO75gB4WaJsn51j2cMX9a5TWGOQtpBWg-Kly7aFauzlHU2kOS1XdsWvpRFmZtLpgZ7ngbjI1HeialwGLf4ebi7jy9QhXRAMjLdFch4S3eHMagVEZiOIVp0UjbZQ5L5245lmXn1u7AB09FwV6egNBPbv8gLZGusM8HSbFrUqEVz8Rz1m00)

## Entity Responsibilities
| Entity | Responsibility |
|---|---|
| Users | Stores OmniDesk AI user identity and authentication data |
| Providers | Identifies supported external communication providers |
| Provider Accounts | Represents a user's connected provider account |
| Conversations | Represents provider-independent communication threads |
| Messages | Stores synchronized communication content |
| Labels | Stores user-defined local organization metadata |
| Conversation Labels | Associates conversations with local labels |
| Synchronizations | Records synchronization activity and status |
| AI Suggestions | Stores user-requested summaries and reply suggestions |

## Ownership Rules
- A user owns their provider accounts.
- A user owns their local labels.
- A provider account belongs to one user.
- Conversations belong to a provider account.
- Messages belong to a conversation.
- Synchronization records belong to a provider account.
- AI suggestions belong to a user and refer to a conversation.
- Users must not access entities owned by another user.

## Identifier and Duplicate Rules
- Every entity has an internal identifier.
- Provider accounts preserve the external account identifier.
- Conversations preserve the external conversation identifier.
- Messages preserve the provider message identifier.
- Duplicate prevention must consider the provider account and provider identifier.
- The exact database uniqueness constraints will be finalized during implementation.

## Credential Protection
- Access tokens must be encrypted or otherwise securely protected at rest.
- Refresh tokens must be encrypted or otherwise securely protected at rest.
- Tokens must never be returned to the frontend.
- Tokens must not be written to logs.
- Credentials must be removed or invalidated according to the approved disconnection policy.

## Communication Data

The normalized communication model may contain:
- Sender
- Recipients
- Subject or title
- Message content
- Sent timestamp
- Provider identifier
- External message identifier
- Provider-specific metadata where necessary

Provider-specific fields should not unnecessarily leak into core workspace behavior.

## Synchronization Data

Synchronization records may contain:
- Provider account reference
- Synchronization status
- Start timestamp
- Completion timestamp
- Failure category
- Safe diagnostic information
- Created timestamp

Synchronization records must not contain passwords, tokens, or unnecessary message content.

## AI Suggestion Data

AI suggestions may contain:
- User reference
- Conversation reference
- Suggestion type
- Suggestion status
- Generated or edited content
- Creation timestamp
- Update timestamp

AI suggestions remain assistive and user-controlled.

## Persistence Rules
- PostgreSQL is the durable persistence store.
- Repository boundaries should isolate persistence access.
- Database schema details are not finalized in this conceptual design.
- Redis may support caching or background processing where justified.
- Cached data must not weaken authorization or privacy controls.

## Deletion and Retention

The following decisions remain unresolved:
- Retention period for synchronized communication data.
- Deletion behavior after provider disconnection.
- Account deletion behavior.
- Retention period for AI suggestions.
- Deletion of provider credentials after disconnection.
- Treatment of locally created labels after related data is deleted.

## Security Considerations
- Every protected entity must be associated with an ownership boundary.
- Queries must enforce user ownership.
- External provider data must be treated as untrusted input.
- Sensitive fields must be protected at rest.
- Database access must be restricted to authorized application services.
- Audit and diagnostic records must avoid sensitive content.

## Unresolved Decisions
| ID | Decision | Current State |
|---|---|---|
| DATA-001 | Final database schema and migrations | To be finalized during implementation |
| DATA-002 | Exact token encryption strategy | Requires security design |
| DATA-003 | Communication data retention period | Requires privacy decision |
| DATA-004 | Deletion behavior after provider disconnection | Requires privacy decision |
| DATA-005 | Exact provider-specific metadata to preserve | To be finalized during integration design |
| DATA-006 | Exact database uniqueness constraints | To be finalized during implementation |
| DATA-007 | AI suggestion retention period | Requires privacy decision |

## PlantUML Source

The editable PlantUML source is available in [data-model-and-erd.puml](./diagrams/data-model-and-erd.puml).

## Relationship to Later Documents

This design will provide input for:
- Database Schema Design
- Laravel Migrations
- Repository Design
- Provider Synchronization Implementation
- Security Implementation
- Privacy and Data Lifecycle Design
- API Implementation
- Integration Testing

