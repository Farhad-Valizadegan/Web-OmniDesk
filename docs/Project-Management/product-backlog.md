# OmniDesk AI Product Backlog

> Status: Draft

## Purpose

This document contains the prioritized product backlog for OmniDesk AI.

Backlog items represent capabilities, improvements, technical work, and verification work. They will later be selected for sprint planning.

## Priorities

- Must: Required for the initial product scope
- Should: Important but not blocking the initial product
- Could: Valuable future improvement
- Won't Yet: Explicitly outside the current scope

## Backlog Items
| ID | Backlog Item | Priority | Milestone | Status |
|---|---|---|---|---|
| PB-001 | Prepare the local development environment | Must | M0 | Completed |
| PB-002 | Establish Laravel backend foundation | Must | M0 | Completed |
| PB-003 | Establish React and TypeScript frontend foundation | Must | M0 | Completed |
| PB-004 | Configure PostgreSQL and database access | Must | M0 | Completed |
| PB-005 | Establish testing, formatting, and CI checks | Must | M0 | Completed |
| PB-006 | Implement user registration | Must | M1 | Completed |
| PB-007 | Implement login and logout | Must | M1 | Completed |
| PB-008 | Implement authenticated sessions | Must | M1 | Completed |
| PB-009 | Implement authorization and ownership checks | Must | M1 | Completed |
| PB-010 | Implement the initial workspace layout | Must | M1 | Completed |
| PB-011 | Implement the conversation list | Must | M3 | Completed |
| PB-012 | Implement conversation details and message display | Must | M3 | Completed |
| PB-013 | Implement search and filtering | Should | M3 | Completed |
| PB-014 | Implement local labels and tags | Should | M3 | Planned |
| PB-015 | Implement Gmail authorization | Must | M2 | Completed |
| PB-016 | Implement secure provider token storage | Must | M2 | Completed |
| PB-017 | Implement the Gmail connector | Must | M2 | Completed |
| PB-018 | Normalize Gmail communication data | Must | M2 | Completed |
| PB-019 | Implement initial synchronization | Must | M2 | Completed |
| PB-020 | Implement incremental synchronization | Must | M2 | Completed |
| PB-021 | Prevent duplicate synchronized records | Must | M2 | Completed |
| PB-022 | Record synchronization status and failures | Must | M2 | Completed |
| PB-023 | Validate the Telegram integration mode | Must | M4 | Planned |
| PB-024 | Implement the Telegram connector | Must | M4 | Planned |
| PB-025 | Implement Telegram synchronization | Must | M4 | Planned |
| PB-026 | Isolate failures between providers | Must | M4 | Planned |
| PB-027 | Implement conversation summaries | Should | M5 | Planned |
| PB-028 | Implement editable reply suggestions | Should | M5 | Planned |
| PB-029 | Minimize context before AI processing | Must | M5 | Planned |
| PB-030 | Implement the AI provider abstraction | Must | M5 | Planned |
| PB-031 | Implement the AI suggestion lifecycle | Should | M5 | Planned |
| PB-032 | Prevent autonomous message sending | Must | M5 | Planned |
| PB-033 | Implement consistent API error responses | Must | M6 | Planned |
| PB-034 | Implement retry handling for temporary failures | Should | M6 | Planned |
| PB-035 | Verify security and ownership boundaries | Must | M6 | Planned |
| PB-036 | Add integration and end-to-end tests | Must | M6 | Planned |
| PB-037 | Prepare deployment and recovery documentation | Must | M6 | Planned |
| PB-038 | Prepare release notes and release candidate | Must | M6 | Planned |
| PB-039 | Complete functional workspace sidebar navigation and active-state behavior | Should | TBD | Refined |
| PB-040 | Implement a Starred workspace view | Should | TBD | Refined |
| PB-041 | Implement a Labels workspace view with dynamic/custom label support | Should | TBD | Refined |
| PB-042 | Define and implement the Settings workspace view or remove the placeholder until its scope is approved | Could | TBD | Refined |

## Backlog Item Expectations

Each selected backlog item should have:

- A clear description
- A related milestone
- A priority
- Acceptance criteria
- Known dependencies
- An implementation task breakdown
- Relevant tests
- A definition of done

## Scope Exclusions

The following items are outside the current approved scope:

- Autonomous message sending
- Unsupported provider integrations
- Unvalidated Telegram capabilities
- Unapproved AI features
- Production infrastructure decisions before deployment planning

## Backlog Management

The backlog will be refined before sprint planning.

Items may be:

- Added
- Removed
- Reprioritized
- Split into smaller items
- Combined when appropriate
- Deferred until more information is available

A backlog item is not committed to a sprint until it has been reviewed and selected.

Newly discovered work from a completed sprint is added to the Product Backlog rather than silently expanding or reopening that sprint.

## Relationship to Milestones and Sprints

Milestones describe meaningful project outcomes.

Backlog items describe individual capabilities or work items.

Sprints select a limited group of backlog items and produce a reviewable increment.

One milestone may contain many backlog items and several sprints.

The workspace-navigation items PB-039 through PB-042 have been refined but are not assigned to a sprint or milestone yet. They must now be prioritized against the existing M4 Telegram work before Sprint Planning.

## Unresolved Decisions
| ID | Decision | Current State |
|---|---|---|
| BACKLOG-001 | Final backlog prioritization | To be reviewed |
| BACKLOG-002 | Acceptance criteria for each item | To be defined during refinement |
| BACKLOG-003 | Final sprint assignments | To be defined during sprint planning |
| BACKLOG-004 | Telegram scope | Requires provider validation |
| BACKLOG-005 | Final AI provider | Unresolved |
| BACKLOG-006 | Priority and milestone assignment for PB-039 through PB-042 | Requires prioritization before Sprint Planning |
