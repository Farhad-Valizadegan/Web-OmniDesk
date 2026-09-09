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

![AI Assistance Design Sequence Diagram](https://www.plantuml.com/plantuml/svg/~h407374617274756d6c0a7469746c6520414920417373697374616e63652044657369676e2053657175656e6365204469616772616d0a0a6163746f7220225072696d61727920557365722220617320557365720a7061727469636970616e74202252656163742057656220436c69656e742220617320436c69656e740a7061727469636970616e7420224c61726176656c20524553542041504922206173204150490a7061727469636970616e742022414920417373697374616e6365204d6f64756c652220617320417373697374616e63650a7061727469636970616e742022417574686f72697a6174696f6e20536572766963652220617320417574686f72697a6174696f6e0a7061727469636970616e742022436f6e74657874204d696e696d697a657222206173204d696e696d697a65720a64617461626173652022436f6d6d756e69636174696f6e2053746f7265222061732053746f72650a7061727469636970616e74202253656c65637465642041492050726f76696465722220617320414950726f76696465720a0a55736572202d3e20436c69656e74203a20726571756573742073756d6d617279206f72207265706c792073756767657374696f6e0a436c69656e74202d3e20415049203a207375626d697420414920617373697374616e636520726571756573740a415049202d3e20417574686f72697a6174696f6e203a207665726966792075736572206f776e6572736869700a417574686f72697a6174696f6e202d3e2053746f7265203a20636865636b20636f6e766572736174696f6e206f776e6572736869700a53746f7265202d2d3e20417574686f72697a6174696f6e203a206f776e65727368697020726573756c740a0a616c7420616363657373207065726d69747465640a2020417574686f72697a6174696f6e202d2d3e20415049203a20616363657373206772616e7465640a2020415049202d3e20417373697374616e6365203a2063726561746520414920617373697374616e636520726571756573740a2020417373697374616e6365202d3e2053746f7265203a2072657472696576652073656c656374656420636f6e766572736174696f6e0a202053746f7265202d2d3e20417373697374616e6365203a20636f6e766572736174696f6e20646174610a2020417373697374616e6365202d3e204d696e696d697a6572203a206d696e696d697a6520726571756972656420636f6e746578740a20204d696e696d697a6572202d2d3e20417373697374616e6365203a20617070726f766564207265717565737420636f6e746578740a2020417373697374616e6365202d3e20414950726f7669646572203a207375626d6974206d696e696d697a656420636f6e746578740a0a2020616c7420414920726571756573742073756363656564730a20202020414950726f7669646572202d2d3e20417373697374616e6365203a2073756d6d617279206f72207265706c792073756767657374696f6e0a20202020417373697374616e6365202d2d3e20415049203a2067656e65726174656420726573756c740a20202020415049202d2d3e20436c69656e74203a20646973706c617920726573756c7420666f72207265766965770a20202020436c69656e74202d2d3e2055736572203a2073686f77206564697461626c6520414920726573756c740a2020202055736572202d3e20436c69656e74203a20656469742c206163636570742c206f72206469736361726420726573756c740a20202020436c69656e74202d3e20415049203a20736176652075736572206465636973696f6e0a20202020415049202d2d3e20436c69656e74203a206465636973696f6e207265636f726465640a2020656c73652041492072657175657374206661696c730a20202020414950726f7669646572202d2d3e20417373697374616e6365203a206661696c75726520726573706f6e73650a20202020417373697374616e6365202d2d3e20415049203a20414920756e617661696c61626c65207374617475730a20202020415049202d2d3e20436c69656e74203a20646973706c6179206661696c7572650a20202020436c69656e74202d2d3e2055736572203a2073686f77206661696c75726520776974686f757420626c6f636b696e6720636f72652066656174757265730a2020656e640a656c7365206163636573732064656e6965640a2020417574686f72697a6174696f6e202d2d3e20415049203a206163636573732064656e6965640a2020415049202d2d3e20436c69656e74203a20666f7262696464656e20726573706f6e73650a2020436c69656e74202d2d3e2055736572203a20646973706c617920616363657373206572726f720a656e640a0a6e6f7465206f76657220417373697374616e63652c414950726f76696465720a4149206f75747075742069732061737369737469766520616e6420756e747275737465642e0a4149206d757374206e657665722073656e6420636f6e74656e74206175746f6e6f6d6f75736c792e0a656e64206e6f74650a0a6e6f7465206f76657220557365722c4150490a416e792065787465726e616c2073656e64696e6720726571756972657320612073657061726174652070726f64756374206465636973696f6e0a616e64206578706c69636974207573657220617070726f76616c2e0a656e64206e6f74650a0a40656e64756d6c0d0a)

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
