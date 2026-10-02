# SDLC Roadmap

> Status: Draft

## Purpose

This document describes the Software Development Life Cycle structure used by OmniDesk.

Project Definition is maintained separately under `docs/Project-Definition/`. It defines the project's problem, goals, scope, stakeholders, assumptions, risks, and success criteria. It is an input to the SDLC, not an SDLC phase in this repository's documentation structure.

The numbered SDLC folders organize engineering activities and artifacts. They do **not** define a strict sequential Waterfall execution model.

## Roadmap

The authoritative editable diagram source is [sdlc-roadmap.puml](./diagrams/sdlc-roadmap.puml).

## SDLC Activities

| Activity | Main Responsibility |
|---|---|
| Project Planning | Organize development execution using the approved project definition, delivery model, backlog, milestones, Sprint process, risks, constraints, and initial technical direction |
| Requirements Engineering | Elicit, document, validate, and manage requirements |
| System Analysis | Analyze behavior, domain concepts, workflows, and business rules |
| System Design | Define architecture, modules, APIs, data, security, and deployment structure |
| Implementation | Build the backend, frontend, integrations, and supporting components |
| Testing | Verify functionality, security, reliability, performance, and quality |
| Deployment | Prepare and operate the application in its target environment |
| Maintenance | Correct defects, support released versions, improve the system, and evolve capabilities in a controlled way |

## Project Definition Relationship

Project Definition remains outside the SDLC at `docs/Project-Definition/`.

Project Planning consumes those definition artifacts and organizes execution. It does not redefine project scope, goals, stakeholders, or product identity.

## Agile and Incremental Execution

Milestones define meaningful delivery outcomes, while Agile Sprints organize iterative execution toward those outcomes.

- The Product Backlog contains prioritized work.
- Sprint Planning selects work for a specific Sprint Goal.
- A Sprint may involve Requirements, Analysis, Design, Implementation, Testing, and Documentation as required.
- Every Sprint is not required to execute every SDLC activity mechanically.
- Each Sprint should produce a meaningful, reviewable Increment.
- Review, feedback, learning, and changing priorities may refine future work.
- A Sprint does not automatically create a release.

The SDLC therefore provides the project's engineering and lifecycle structure, while Agile practices organize how work is executed and adapted over time.

## Current Project Structure

The SDLC documentation folders are:

- `01-project-planning`
- `02-requirements-engineering`
- `03-system-analysis`
- `04-system-design`
- `05-implementation`
- `06-testing`
- `07-deployment`
- `08-maintenance`

Project Definition remains separately maintained under `docs/Project-Definition/`.

## PlantUML Source

The editable source is available in [sdlc-roadmap.puml](./diagrams/sdlc-roadmap.puml).
