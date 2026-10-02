# Product Roadmap

> Status: Directional
>
> This roadmap describes a possible evolution path for OmniDesk. It is not a release promise, fixed schedule, or scope expansion for Version 1.

## Roadmap Principles

The roadmap preserves the current Version 1 baseline and separates committed work from future exploration.

Version numbers below indicate product direction only. Features may move between versions, be split into smaller releases, be delayed, or be removed after technical and product evaluation.

## Version 1 — Unified Communication Foundation

Primary direction:

- Gmail integration
- Telegram integration
- Unified inbox / communication workspace
- Consistent conversation and message presentation
- Provider attribution
- Search and filtering within the Version 1 data model
- Synchronization and duplicate prevention
- Core reliability, security, testing, and deployment preparation

Version 1 is the baseline product. Existing scope and sprint documentation remain authoritative for what is actually committed.

## Version 2 — Multi-Account and Basic Cross-Source Discovery

Potential direction:

- Multiple accounts per supported provider
- Account-aware inbox filtering
- Account-aware synchronization state
- Basic cross-provider search
- Federated search adapters for supported providers
- Unified result presentation with provider and account attribution
- Search filters for source, account, sender, date, and conversation


## Version 3 — Channel Indexing and Advanced Search

Potential direction:

- Telegram channel indexing where API access permits
- Full-text search over indexed channel data
- Topic discovery and trend analysis
- Entity and keyword extraction
- Cross-channel search and comparison tools

This version would depend on the quality and maturity of indexing, retrieval, provenance, and data-governance capabilities developed earlier.

## Version 4+ — Provider Ecosystem Expansion

Potential direction:

- Microsoft Outlook / Microsoft 365 integration
- Additional officially supported communication providers
- LinkedIn integration only where official APIs, permissions, and platform policies support the intended use case
- Provider SDK or adapter conventions to reduce integration cost
- More mature operational controls for a broader provider ecosystem

Provider expansion remains conditional. API availability, terms of service, authentication requirements, rate limits, review processes, and maintenance cost may prevent or reshape an integration.

## What the Roadmap Does Not Mean

The roadmap does not mean:

- Every listed capability will be built.
- Every capability will appear in the version currently shown.
- A version has a committed release date.
- Future capabilities belong in current sprints.
- A roadmap item is ready for implementation.

## Relationship to Future Scope and Backlog

### Future Scope

Future Scope records possibilities.

Question answered:

> What might the product support later?

An item may remain in Future Scope indefinitely and may never be selected for development.

### Roadmap

The Roadmap describes tentative sequencing and product direction.

Question answered:

> If we choose to build these capabilities, what broad progression makes architectural and product sense?

Roadmap placement is not a commitment.

### Backlog

The Backlog contains concrete work that has been analyzed and accepted for planning.

Question answered:

> What exactly must be implemented, tested, documented, or operated?

A backlog item should be actionable and normally include scope, dependencies, acceptance criteria, and priority.

## Promotion Model

A capability should move through the following states deliberately:

1. **Future Scope** — concept is recorded.
2. **Roadmap Candidate** — concept has a tentative product position.
3. **Analysis** — feasibility, APIs, security, architecture, UX, cost, and dependencies are evaluated.
4. **Backlog** — concrete work items and acceptance criteria are created.
5. **Sprint Planning** — selected backlog items enter an active sprint.
6. **Implementation and Verification** — code, tests, documentation, and operational requirements are completed.
7. **Release** — verified work is included in a version and documented in release notes.

## Roadmap Governance

Version 1 must not absorb future-roadmap features unless the Version 1 scope is deliberately revised through an explicit scope decision.

Current sprint files should represent active delivery work, not speculative future product ideas.

Future roadmap updates should therefore remain independent from the existing Version 1 sprint sequence until a later version is formally planned.
