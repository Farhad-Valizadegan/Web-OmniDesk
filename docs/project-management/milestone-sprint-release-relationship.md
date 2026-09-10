\# Milestone, Sprint, and Release Relationship



> Status: Draft



\## Purpose



This document explains how product backlog items, sprints, milestones, and releases relate to one another in OmniDesk AI.



\## Relationship Model



\- The product backlog contains planned work items.

\- Sprint planning selects a limited set of backlog items.

\- Sprints produce reviewable implementation increments.

\- Multiple sprints may contribute to one milestone.

\- One sprint may contribute to more than one milestone.

\- Milestones represent meaningful project outcomes.

\- Releases represent published increments.

\- A sprint does not automatically create a release.

\- A milestone does not automatically equal one version.

\- A release is created only after release criteria are satisfied.



\## Current Allocation



| Milestone | Contributing Sprints | Outcome |

|---|---|---|

| M0 | Sprint 00 and Sprint 01 | Engineering foundation |

| M1 | Sprint 02 and Sprint 03 | Authentication and workspace foundation |

| M2 | Sprint 04 and Sprint 05 | Gmail connection and synchronization |

| M3 | Sprint 03 and Sprint 05 | Unified communication workspace |

| M4 | Sprint 06 | Telegram connection and synchronization |

| M5 | Sprint 07 | User-controlled AI assistance |

| M6 | Sprint 08 | Production hardening and release readiness |



\## Release Rule



A release may include work from one or more milestones.



A version tag is created only after:



\- The intended milestone outcomes are complete.

\- Acceptance criteria are satisfied.

\- Required tests pass.

\- Security checks pass.

\- Release-blocking defects are resolved.

\- Release notes are prepared.



The project uses Semantic Versioning:



```text

MAJOR.MINOR.PATCH

