# Business Rules Catalog

> Status: Draft

## Purpose

This document defines the main business rules identified during System Analysis for OmniDesk AI.

## Business Rule Catalog
| ID | Business Rule | Source |
|---|---|---|
| BR-001 | A user must be authenticated before accessing protected communication data. | SPR-001, FR-002 |
| BR-002 | A user may access only their own accounts, connections, and communication data. | SPR-002, SPR-008 |
| BR-003 | Gmail and Telegram are the initial supported communication providers. | IR-001, IR-002 |
| BR-004 | Provider-specific data must be mapped to the internal communication model before entering the unified workspace. | IR-008, FR-025 |
| BR-005 | Each synchronized record must preserve its original provider identity. | IR-009, NFR-020 |
| BR-006 | Repeated synchronization must not create duplicate local records. | IR-010, FR-010 |
| BR-007 | Synchronization failure must not corrupt previously synchronized communication data. | NFR-010, IR-014 |
| BR-008 | Failure in one provider integration should not disable unrelated provider integrations. | IR-014 |
| BR-009 | AI assistance must be initiated by the user. | SPR-014, FR-017, FR-018 |
| BR-010 | AI-generated summaries and reply suggestions must be presented as assistive content, not authoritative communication. | SPR-013, SPR-014 |
| BR-011 | AI-generated content must remain reviewable and editable by the user. | FR-019 |
| BR-012 | AI must never send content autonomously. | FR-021, SPR-015 |
| BR-013 | If message sending is implemented, explicit user approval is required before sending. | FR-020 |
| BR-014 | Core communication features must remain available when AI services are unavailable. | FR-022, NFR-009 |
| BR-015 | External provider data must be treated as untrusted input. | SPR-009, SPR-010 |
| BR-016 | Logs must not contain credentials, tokens, or unnecessary message content. | SPR-012, NFR-019 |
| BR-017 | Only the minimum communication data required by supported features should be stored. | SPR-016 |
| BR-018 | Disconnecting a provider must invalidate its connection where applicable. | SPR-007, IR-017 |
| BR-019 | Future providers are outside the current MVP commitment. | Project Scope, IR-015 |
| BR-020 | Unresolved product decisions must not be treated as finalized behavior. | Requirements Traceability Matrix |

## Important Unresolved Rule

Message sending remains an unresolved product decision.

If message sending is later approved, it must require explicit user approval and must never be performed autonomously by AI.

## Relationship to Later Documents

These business rules will provide input for:

- System Design

- Security Design

- Integration Design

- Data Model Design

- API Design

- Test Design

- Requirements Traceability Matrix

