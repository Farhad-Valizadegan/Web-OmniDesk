# Backlog Refinement

> Status: Draft

## Purpose

This document describes how OmniDesk AI backlog items are prepared before sprint planning.

## Workflow

![OmniDesk AI Backlog Refinement Workflow](./diagrams/backlog-refinement.svg)

## Refinement Activities

Each backlog item should be reviewed to determine:

- What capability or work is required.
- Which milestone it supports.
- Its priority.
- Its acceptance criteria.
- Its dependencies.
- Its implementation tasks.
- Its testing requirements.
- Whether it is ready for sprint selection.

## Readiness Rule

A backlog item is ready for sprint planning when:

- Its purpose is clear.
- Its scope is understandable.
- Acceptance criteria are defined.
- Dependencies are identified.
- Priority is known.
- The item can be divided into implementation tasks.
- Relevant testing is understood.

## Deferral Rule

An item may be deferred or returned for further refinement when:

- Its scope is unclear.
- Acceptance criteria are missing.
- A dependency is unresolved.
- Required provider capabilities are not validated.
- Its priority is lower than the current sprint capacity.
- It is outside the approved project scope.

## Handling Newly Discovered Work

Work discovered after a sprint is completed is not silently added back into that closed sprint.

It should be:

1. Captured in the Product Backlog.
2. Classified as a defect, technical debt, UX/functional debt, or new capability.
3. Refined with acceptance criteria, dependencies, priority, and testing expectations.
4. Compared with existing prioritized work.
5. Selected only during a later Sprint Planning event when it is ready and valuable enough.

### Current Discovered Workspace Work

The Sprint 06 review identified non-functional or incomplete sidebar navigation entries that require backlog treatment:

- PB-039: complete functional workspace sidebar navigation and active-state behavior.
- PB-040: implement a Starred workspace view.
- PB-041: implement a Labels workspace view with dynamic/custom label support.
- PB-042: define and implement the Settings workspace view, or remove the placeholder until its scope is approved.

These items are currently **Needs Refinement**. They are not committed to the next sprint and are not assigned to a milestone yet. They must be prioritized against the existing M4 Telegram backlog before Sprint Planning.

## Relationship to Sprints

Backlog refinement prepares items for sprint planning.

Sprint planning selects a limited set of ready backlog items.

A backlog item is not committed to a sprint until it has been reviewed and selected.

## PlantUML Source

The editable source is available in [backlog-refinement.puml](./diagrams/backlog-refinement.puml).
