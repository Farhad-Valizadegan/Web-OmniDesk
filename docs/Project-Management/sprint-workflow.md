# Sprint Workflow

> Status: Draft

## Purpose

This document describes the standard workflow used to plan, execute, verify, and complete an OmniDesk Sprint.

A Sprint is a fixed/defined timeboxed execution iteration, not an SDLC phase. Depending on the Sprint Goal, it may involve Requirements, Analysis, Design, Implementation, Testing, and Documentation activities as needed.

## Workflow

![OmniDesk Sprint Workflow](./diagrams/sprint-workflow.svg)

The editable workflow source is [sprint-workflow.puml](./diagrams/sprint-workflow.puml).

## Sprint Activities

1. Review the relevant milestone outcome.
2. Refine the Product Backlog as needed.
3. Select backlog items for the Sprint.
4. Define the Sprint Goal.
5. Confirm or define acceptance criteria.
6. Confirm the fixed/defined Sprint timebox.
7. Break selected items into engineering tasks.
8. Start the Sprint and perform the lifecycle work required by the Sprint Goal, such as requirements refinement, analysis, design, implementation, testing, or documentation.
9. Run relevant automated and manual verification and applicable quality and security checks during the Sprint.
10. End the Sprint when the defined timebox ends.
11. Review the Sprint Increment and evaluate the Sprint Goal against the available Done work.
12. Record completed and incomplete work and any resulting decisions or discoveries.

A Sprint is not required to mechanically execute every SDLC activity. The necessary engineering activities are selected according to the Sprint Goal and the work being delivered.

## Sprint Closure and Work Completion Rule

A Sprint closes when its defined timebox ends and the Sprint review/closure activities are performed. Sprint Goal achievement is evaluated at closure; it does not determine whether the timebox ends.

A selected work item counts as **Done** only when its acceptance criteria and applicable Definition of Done are satisfied. Applicable verification, quality and security checks, and required documentation remain part of that work-completion decision. Done work remains Done.

Incomplete work does not keep the Sprint open. It returns to the Product Backlog for reassessment and is not automatically carried into the next Sprint.

## Review and Adaptation

Sprint review, feedback, technical discoveries, and changing priorities may refine or reprioritize future Product Backlog items. Future Sprint selection occurs through Sprint Planning.

## Release Relationship

A Sprint produces a meaningful, reviewable Increment.

A Sprint does not automatically create a release. A release requires a separate assessment of release criteria and the intended product state.

## PlantUML Source

The editable source is available in [sprint-workflow.puml](./diagrams/sprint-workflow.puml).
