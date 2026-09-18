# Requirements Traceability Matrix
> Status: Draft
## Purpose
This document establishes traceability between stakeholder needs, requirements, user stories, use cases, and acceptance criteria for OmniDesk AI.
Traceability helps verify that documented requirements are derived from stakeholder needs and can be validated through observable acceptance criteria.
## Traceability Matrix
| Stakeholder Need | Functional / Quality Requirement | User Story | Use Case | Acceptance Criteria |
|---|---|---|---|---|
| SN-001 | FR-006, FR-007, FR-010 | US-006 | UC-004, UC-006 | AC-006, AC-019 |
| SN-002 | FR-003, FR-004, FR-005 | US-003, US-004, US-005 | UC-002, UC-003, UC-009 | AC-003, AC-004, AC-005 |
| SN-003 | FR-007, FR-016, FR-024, NFR-012 | US-006, US-011 | UC-004 | AC-006, AC-010 |
| SN-004 | FR-012, FR-013 | US-008, US-009 | UC-005 | AC-008, AC-009 |
| SN-005 | FR-014, FR-015 | US-010 | UC-005 | AC-009 |
| SN-006 | FR-008, NFR-013, NFR-020 | US-007 | UC-004 | AC-007 |
| SN-007 | FR-017, SPR-013, SPR-014 | US-012 | UC-007 | AC-011 |
| SN-008 | FR-018, FR-019, SPR-014 | US-013, US-014 | UC-008 | AC-012, AC-013 |
| SN-009 | FR-020, FR-021, SPR-015 | US-018 | UC-008 | AC-016 |
| SN-010 | FR-009, FR-011, FR-023, FR-026, IR-013 | US-015 | UC-006, UC-011 | AC-020 |
| SN-011 | FR-022, NFR-009 | US-016 | UC-010 | AC-014 |
| SN-012 | FR-001, FR-002, SPR-001, SPR-002, SPR-008 | US-001, US-002, US-017 | UC-001 | AC-001, AC-002, AC-015 |
| SN-013 | FR-025, IR-015, NFR-017 | US-019 | Cross-cutting architecture concern | AC-017 |
| SN-014 | NFR-016, NFR-018 | US-020 | Cross-cutting engineering concern | AC-018 |
| SN-015 | Documentation and traceability practices | US-021 | Cross-cutting documentation concern | N/A - documentation review |
## Security-Specific Traceability

Security/privacy requirements do not require artificial user-story or use-case links when the control is primarily cross-cutting.

| Security Requirement | Risk | SEC Control | Design / Current State | Verification / Evidence State |
|---|---|---|---|---|
| SPR-021 - Email Two-Step Verification | RISK-002 | SEC-004 | Authentication and Authorization Design; email OTP selected for V1; implementation pending | OTP security tests and completion evidence pending |
| SPR-022 - Account Verification and Recovery | RISK-003 | SEC-005 | Account/password lifecycle design remains pending beyond approved security criteria | Recovery/password lifecycle tests and completion evidence pending |
| SPR-023 - Sensitive-Action Reauthentication | RISK-004 | SEC-006 | Authentication and Authorization Design; recent-authentication details pending | Sensitive-operation tests and completion evidence pending |
| SPR-024 - Persisted Sensitive Data Protection | RISK-013 | SEC-015 | Data Model and Deployment Architecture; final protection model pending | Production storage/security validation pending |
| SPR-025 - Backup Protection | RISK-014 | SEC-016 | Deployment Architecture and SEC-002 lifecycle baseline; production backup design pending | Restore/access/retention validation and deployment evidence pending |

The broader risk-to-control state remains in Project Management traceability, and SEC-001 remains the authoritative threat/risk baseline.

## Coverage Rules
1. Every Must Have stakeholder need should trace to at least one requirement.
2. Every functional requirement should trace to a user story, use case, or acceptance criterion.
3. Every security and privacy requirement should be considered in system design and testing.
4. Every acceptance criterion should validate at least one requirement or user story.
5. Unresolved product decisions must remain explicitly identified.
6. Future-provider requirements must not be treated as current MVP commitments.
## Unresolved Decisions
| ID | Decision | Current State |
|---|---|---|
| TD-001 | Whether the MVP will support sending messages | Unresolved |
| TD-002 | Exact integration mode for Telegram | Telegram API/MTProto through official TDLib behind a long-lived adapter is the selected direction; PB-023 runtime evidence remains pending before PB-024 |
| TD-003 | Final AI service provider | Unresolved |
| TD-004 | Data retention period for synchronized communication | SEC-002 provides the authoritative classification/lifecycle baseline; exact operational retention durations remain unresolved |
## Traceability Status
The current matrix provides initial traceability for the Requirements Engineering phase.
It should be updated when requirements change and reviewed before requirements approval.
## Relationship to Later Documents
This matrix will provide input for:
- System Analysis
- System Design
- Implementation Planning
- Test Planning
- Test Case Design
- Change Impact Analysis
- Requirements Validation

