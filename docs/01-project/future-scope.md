# Future Scope

> Status: Exploratory
>
> This document intentionally describes capabilities that are **outside OmniDesk AI Version 1**. It does not modify the approved Version 1 scope, existing sprint commitments, or current acceptance criteria.

## Purpose

Version 1 remains focused on the currently defined foundation: supported provider integration, a unified communication workspace, conversation-level workflows, and assistive AI that remains under user control.

The items below are future product possibilities. They may be evaluated incrementally after Version 1 based on technical feasibility, provider API availability, security, product value, maintenance cost, and development capacity.

Nothing in this document is a delivery commitment.

## Potential Future Capabilities

### Multiple Accounts per Provider

Allow a user to connect more than one account for the same provider, for example multiple Gmail accounts or multiple Telegram identities where provider capabilities permit it.

Possible concerns include:

- Account identity and switching
- Per-account authorization and token lifecycle
- Duplicate prevention across accounts
- Account-aware filtering and search
- Clear provider/account attribution in the unified inbox
- Isolation of synchronization state for each connection

This is not part of Version 1.

### Cross-Provider / Federated Search

Allow one query to run across multiple connected communication sources at the same time.

Potential behavior could include:

- Searching Gmail and Telegram in one operation
- Provider-specific query adapters behind one search interface
- Merging, ranking, and deduplicating results
- Filtering by provider, account, date range, sender, channel, or conversation
- Preserving source attribution for every result

This is broader than Version 1 search, which remains scoped to the current product model and implemented provider data.

### AI Retrieval Across Sources

Introduce a retrieval layer that identifies and collects relevant content from one or more connected sources before sending selected context to an AI model.

A future retrieval pipeline could include:

1. Accepting a user question or analysis request.
2. Searching indexed or provider-backed communication data.
3. Selecting the most relevant conversations, messages, or excerpts.
4. Preserving source references and provider attribution.
5. Building a bounded context package.
6. Sending only the selected context to the AI layer for summarization, comparison, extraction, or analysis.

This capability would require careful work around privacy, authorization, context size, ranking quality, provenance, and hallucination control.

It is not part of Version 1.

### Telegram Channel Indexing and Content Analysis

A future Telegram capability may support deeper analysis of channels or large message collections where the Telegram API and account permissions permit access.

Potential capabilities include:

- Channel content indexing
- Full-text search over indexed channel content
- Topic discovery
- Keyword and entity extraction
- Time-based topic trends
- Topic clustering
- Channel-level summaries
- Comparative analysis across selected channels

This is distinct from basic Telegram communication integration and is not part of Version 1.

### Thematic Reports

Generate reports around user-selected themes rather than only individual conversations.

Examples may include:

- Summarizing discussion around a selected topic across multiple conversations
- Comparing how a topic appears across different providers or channels
- Producing a time-bounded thematic digest
- Extracting recurring concerns, decisions, entities, or action items
- Linking report findings back to source conversations or messages

Thematic reporting would depend on future search, indexing, retrieval, and provenance capabilities.

It is not part of Version 1.

### Additional Providers

Additional communication or professional platforms may be evaluated after Version 1.

Potential examples include:

- Microsoft Outlook / Microsoft 365
- LinkedIn, only where official API capabilities and permissions make the intended integration feasible
- Other providers with stable, documented, officially supported APIs

A provider should not be considered committed merely because it appears in this document. Integration depends on API access, authentication requirements, rate limits, terms of service, data models, review requirements, and long-term maintainability.

Undocumented scraping or browser automation should not become the default integration strategy.

## Evaluation Principles

Future capabilities should be considered only when they preserve the core engineering constraints of OmniDesk AI:

- Official and maintainable integrations
- Explicit user authorization
- Secure server-side handling of credentials and tokens
- Provider isolation behind defined integration boundaries
- Source attribution and traceability
- Duplicate prevention
- Graceful handling of provider failures and rate limits
- User control over AI-assisted actions
- Incremental delivery rather than uncontrolled scope expansion

## Relationship to Version 1

This document does **not** extend Version 1.

Version 1 scope remains governed by the existing project-definition and sprint documentation. Future capabilities listed here must not be silently pulled into active sprints or treated as Version 1 acceptance criteria.

A future capability enters implementation only after explicit prioritization, technical analysis, and backlog decomposition.

## Future Scope vs Roadmap vs Backlog

These three artifacts serve different purposes:

### Future Scope — What Might Exist

Future Scope records product possibilities that are intentionally outside the current committed version.

It answers:

> What capabilities might OmniDesk AI explore later?

Items here are exploratory and may never be implemented.

### Product Roadmap — In What Sequence We May Explore It

The roadmap groups product evolution into tentative version directions.

It answers:

> If the product continues, in what broad order might major capabilities be introduced?

A roadmap is directional, not a fixed delivery contract. Version contents may move, split, merge, or be removed after evaluation.

### Backlog — What Work Must Actually Be Done

The backlog contains actionable implementation work that has been accepted for planning.

It answers:

> What specific engineering, product, testing, documentation, or operational tasks must be completed?

A future-scope idea should not appear as implementation work until it has been analyzed and decomposed into backlog items with clear acceptance criteria.

## Scope Governance Rule

The intended flow is:

`Future Scope -> Roadmap Candidate -> Analysis -> Prioritized Backlog -> Sprint -> Release`

Skipping these stages risks scope creep and weakens the Version 1 baseline.
