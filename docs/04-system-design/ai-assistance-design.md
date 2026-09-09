# AI Assistance Design

> Status: Draft

## Purpose

This document defines the initial design for AI-assisted summaries and editable reply suggestions in OmniDesk AI.

AI assistance supports the user without replacing the user's judgment or control.

## Design Objectives
- Allow users to request summaries and reply suggestions.
- Verify conversation ownership before AI processing.
- Send only the minimum required context to the AI provider.
- Treat AI output as untrusted assistive content.
- Keep generated content reviewable and editable.
- Ensure AI failure does not block core communication features.
- Prevent autonomous external message sending.

## AI Assistance Boundary

The AI Assistance Module is responsible for:
- Receiving user-initiated AI requests.
- Verifying access to the selected conversation.
- Retrieving the required conversation context.
- Removing unnecessary data before external processing.
- Sending approved context to the selected AI provider.
- Receiving and validating the AI result.
- Returning the result to the user for review.
- Storing the user's decision about the result.

The AI provider remains outside the OmniDesk AI system boundary.

## AI Assistance Design Sequence Diagram

![AI Assistance Design Sequence Diagram](https://www.plantuml.com/plantuml/svg/~1ZLNBRjim4BphAnOv9_c078eOQGy16Y18MlHCIsjx4OfK-R3ZV7s752rHSPFs94gStTaTpbAtFYWNOg-R844phUvfvRtWgscPlhANhQ5d_XqvlulQEjKtZMg3TNJnwAHNxaW_FRiBKZulcW4PfPL1cK0NJmmi_U8rtMbX4p9iN2w1tvLJUzRqzEtv1wqUxpCGpoLgMT-3xQBc4JbzVHSGmyuwULD1REh4xQKj4VNECkZEci2lWHx4I2zlfRdfhUbKK6lbEI7xFXffItgeCYRFgsNIPzRS1kwIne_ExgKhULVtfzUcIGBIrPUY4DsGIzhxG3xsMMh8xdZGHtpORh6HQYzWX44lnFYuxYKa7ZLhLH8r2PAG2rrkQCzEDaUAYTyU33k_aw5PeX2M-mAytN7xGgqrYFFZxXmrWgx-mZ9XK8wFEi19Ef1gM_QU1dOe6merz2xmQcgjGE52Ku2bdRbH5ETO1VwWVwgnLKUEWnFUC_dJETNT8QpgQa5MQv2CSSOm-GRelgnpEU96bkGrHCsuCn8r3B08q2Sxp55BhjbBimzEd3CNmfBk46Ys5uJbpcE7wYHdbNpcmnnRbJETsfPnx2ha-lEnPsGwkThgdVX1gsF1q2PJx8KF6NuoEIBoaA2KdJqGTu9P76-kAldP72NSPRRFW2TIWwrLRb7HsHpXGXedekDM_DJYMU5b5xbQwxhiIzQUQu4tIlI_rKsew98x_62DvuybHUfer1xut3tsG_IVwrgIVwRcYVyW6ByOQArj-o9c2-VWwmPZXTr4mwPhSejb83isyZ-3E-FULudZNak7_Rhvyp9FpPHyx9nrJQgcCHOpZo5nbM2NrTqAnT3IWAx4buj1SBxAT50okEZXp-k4wh4amocJH-PnQb25Yi4QszlezV4wSLAYh8bJZPVfdxKoHyAWiJDAvoH9mpBo8CSd_1hI7OMnxY9-a9F1KZdyEcZyDiBelN7sbQufRx74J_iF)

## Supported AI Features
| Feature | Description | User Control |
|---|---|---|
| Conversation Summary | Produces a summary of a selected conversation | User requests and reviews result |
| Reply Suggestion | Produces an editable response suggestion | User edits, accepts, or discards result |

Autonomous message sending is not a supported feature.

## AI Request Flow

1. The user selects a conversation.

2. The user requests a summary or reply suggestion.

3. The API verifies authentication.

4. The Authorization Service verifies conversation ownership.

5. The AI Assistance Module retrieves the selected conversation.

6. The Context Minimizer removes unnecessary information.

7. The minimized context is sent to the selected AI provider.

8. The result is returned to the AI Assistance Module.

9. The result is displayed to the user.

10. The user edits, accepts, or discards the result.

## Context Minimization

Only the minimum data required for the requested feature should be sent to the AI provider.

The system should avoid sending:
- Provider credentials
- Access tokens
- Session identifiers
- Unrelated conversations
- Unnecessary personal information
- Internal database identifiers
- Security and diagnostic information
- Unnecessary provider metadata

The exact minimization rules depend on the selected AI feature and provider.

## AI Output Handling

AI output must be treated as untrusted content.

The system should:
- Display the result as an assistant suggestion.
- Allow the user to edit the result.
- Allow the user to discard the result.
- Avoid presenting generated content as authoritative.
- Preserve user control over any subsequent action.
- Prevent direct autonomous transmission to external providers.

## Authorization and Ownership

Before processing an AI request:
- The user must have an authenticated session.
- The selected conversation must belong to the user.
- The user must be authorized to access the conversation.
- The AI request must be associated with the authenticated user.
- The generated suggestion must not be accessible to another user.

## Failure Handling

If the AI provider is unavailable or returns an error:
- The failure must be classified.
- The failure must be reported to the user.
- Sensitive provider details must not be exposed.
- Core communication features must remain available.
- The selected conversation must remain accessible.
- The system may allow the user to retry the request.

## Security Requirements
- AI requests must be initiated by the user.
- AI requests must pass authentication and authorization checks.
- Context must be minimized before external processing.
- Provider credentials and tokens must never be sent to the AI provider.
- AI requests and responses must use protected communication.
- Sensitive AI request data must not be written to logs.
- AI-generated content must not trigger external actions automatically.
- Access to stored suggestions must be protected by user ownership.

## Privacy Requirements
- The system should send only necessary communication context.
- The selected AI provider's data-handling policy must be evaluated.
- Retention of AI requests and results must be defined.
- Users should be informed when communication data is processed by an AI provider.
- AI-related data should be deleted according to the approved retention policy.
- Provider-specific privacy requirements must be considered before implementation.

## Suggestion Lifecycle
| State | Meaning |
|---|---|
| Requested | The user has requested AI assistance |
| Processing | The AI request is being processed |
| Generated | A result has been returned |
| Edited | The user has modified the result |
| Accepted | The user accepted the result for their workflow |
| Discarded | The user discarded the result |
| Failed | The AI request could not be completed |

Acceptance does not authorize autonomous external sending.

## Message Sending Boundary

Message sending remains outside the current approved AI capability.

If message sending is approved later:
- It must require explicit user approval.
- The final content must remain visible to the user.
- The user must be able to edit the content before sending.
- The system must record the approval decision.
- AI must not send messages autonomously.
- Additional provider, security, and testing requirements must be defined.

## Observability

The system may record safe diagnostic information, including:
- Request type
- Request status
- Processing duration
- Failure category
- User-visible operation identifier

Logs must not contain:
- Passwords
- Provider tokens
- Session secrets
- Unnecessary conversation content
- Full AI prompts
- Sensitive AI responses

## Unresolved Decisions
| ID | Decision | Current State |
|---|---|---|
| AI-001 | Final AI service provider | Unresolved |
| AI-002 | AI request and response retention period | Requires privacy decision |
| AI-003 | Exact context minimization rules | To be finalized during implementation |
| AI-004 | Maximum conversation size for AI processing | To be finalized during implementation |
| AI-005 | AI request rate limits | To be finalized during implementation |
| AI-006 | Whether accepted suggestions may be copied to an external send flow | Depends on message-sending decision |
| AI-007 | User disclosure of external AI processing | Requires privacy review |

## PlantUML Source

The editable PlantUML source is available in [ai-assistance-design-sequence-diagram.puml](./diagrams/ai-assistance-design-sequence-diagram.puml).

## Relationship to Later Documents

This design will provide input for:
- API Implementation
- AI Service Integration
- Security Implementation
- Privacy Design
- Error Handling Design
- User Interface Design
- Integration Testing
- Security Testing

