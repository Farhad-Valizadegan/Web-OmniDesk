# Authentication and Authorization Design

> Status: Draft

## Purpose

This document defines the initial authentication and authorization design for OmniDesk AI.

Authentication verifies the identity of a user. Authorization verifies whether the authenticated user is allowed to access a resource.

## Design Objectives
- Protect user accounts and communication data.
- Require authentication before accessing protected resources.
- Ensure users can access only their own data.
- Keep credentials and provider tokens protected on the backend.
- Support secure authentication-token management.
- Provide clear authentication and authorization failures.
- Keep authentication and authorization independently testable.

## Authentication Scope

Authentication covers:
- User registration
- User login
- Credential verification
- Bearer-token issuance
- Bearer-token validation
- Bearer-token revocation during logout
- Access to the authenticated user identity

## Authorization Scope

Authorization covers:
- Resource ownership checks
- Access to user-owned conversations
- Access to provider connections
- Access to synchronization records
- Access to AI suggestions
- Access to local labels and tags
- Denial of unauthorized requests

## Authentication Sequence Diagram

![Authentication and Authorization Sequence Diagram](https://www.plantuml.com/plantuml/svg/~h407374617274756d6c0a7469746c652041757468656e7469636174696f6e20616e6420417574686f72697a6174696f6e2053657175656e6365204469616772616d0a0a6163746f7220225072696d61727920557365722220617320557365720a7061727469636970616e74202252656163742057656220436c69656e742220617320436c69656e740a7061727469636970616e7420224c61726176656c20524553542041504922206173204150490a7061727469636970616e74202241757468656e7469636174696f6e20536572766963652220617320417574680a7061727469636970616e742022417574686f72697a6174696f6e20536572766963652220617320417574686f72697a6174696f6e0a64617461626173652022506f737467726553514c222061732044420a0a55736572202d3e20436c69656e74203a207375626d697420726567697374726174696f6e206f72206c6f67696e20646174610a436c69656e74202d3e20415049203a2073656e642061757468656e7469636174696f6e20726571756573740a0a616c7420726567697374726174696f6e0a2020415049202d3e2041757468203a2076616c696461746520726567697374726174696f6e20646174610a202041757468202d3e204442203a206372656174652075736572206163636f756e740a20204442202d2d3e2041757468203a206163636f756e7420637265617465640a202041757468202d2d3e20415049203a20726567697374726174696f6e20726573756c740a2020415049202d2d3e20436c69656e74203a206163636f756e7420637265617465640a656c7365206c6f67696e0a2020415049202d3e2041757468203a207665726966792063726564656e7469616c730a202041757468202d3e204442203a2072657472696576652075736572206163636f756e740a20204442202d2d3e2041757468203a206163636f756e7420646174610a0a2020616c742063726564656e7469616c732076616c69640a2020202041757468202d2d3e20415049203a2061757468656e746963617465642073657373696f6e0a20202020415049202d2d3e20436c69656e74203a2061757468656e7469636174696f6e20737563636573730a20202020436c69656e74202d2d3e2055736572203a20646973706c61792070726f74656374656420776f726b73706163650a2020656c73652063726564656e7469616c7320696e76616c69640a2020202041757468202d2d3e20415049203a2061757468656e7469636174696f6e206661696c7572650a20202020415049202d2d3e20436c69656e74203a2061757468656e7469636174696f6e206572726f720a20202020436c69656e74202d2d3e2055736572203a20646973706c6179206572726f720a2020656e640a656e640a0a55736572202d3e20436c69656e74203a20726571756573742070726f746563746564207265736f757263650a436c69656e74202d3e20415049203a207265717565737420776974682073657373696f6e0a415049202d3e2041757468203a207665726966792073657373696f6e0a0a616c742073657373696f6e2076616c69640a202041757468202d2d3e20415049203a2061757468656e7469636174656420757365720a2020415049202d3e20417574686f72697a6174696f6e203a20636865636b207265736f75726365206f776e6572736869700a2020417574686f72697a6174696f6e202d3e204442203a207665726966792075736572206f776e6572736869700a20204442202d2d3e20417574686f72697a6174696f6e203a206f776e65727368697020726573756c740a0a2020616c7420616363657373207065726d69747465640a20202020417574686f72697a6174696f6e202d2d3e20415049203a20616363657373206772616e7465640a20202020415049202d2d3e20436c69656e74203a2072657475726e20757365722d6f776e656420646174610a20202020436c69656e74202d2d3e2055736572203a20646973706c6179207265736f757263650a2020656c7365206163636573732064656e6965640a20202020417574686f72697a6174696f6e202d2d3e20415049203a206163636573732064656e6965640a20202020415049202d2d3e20436c69656e74203a20666f7262696464656e20726573706f6e73650a20202020436c69656e74202d2d3e2055736572203a20646973706c617920616363657373206572726f720a2020656e640a656c73652073657373696f6e20696e76616c69640a202041757468202d2d3e20415049203a20756e61757468656e746963617465640a2020415049202d2d3e20436c69656e74203a2061757468656e7469636174696f6e2072657175697265640a2020436c69656e74202d2d3e2055736572203a2072657175657374207369676e2d696e0a656e640a0a6e6f7465206f76657220417574682c417574686f72697a6174696f6e0a41757468656e7469636174696f6e20636f6e6669726d73206964656e746974792e0a417574686f72697a6174696f6e20636f6e6669726d73207065726d697373696f6e20616e64207265736f75726365206f776e6572736869702e0a656e64206e6f74650a0a40656e64756d6c0d0a)

> Note: the legacy sequence diagram uses the word "session" generically. The current V1 web client authenticates API requests with Laravel Sanctum personal access bearer tokens as described below.

## Authentication Rules
- Registration data must be validated.
- Passwords must never be stored in plain text.
- Login must verify the submitted credentials.
- A successful registration or login issues a Laravel Sanctum bearer token.
- Invalid credentials must not reveal unnecessary account information.
- Logout must revoke the current bearer token.
- Invalid or expired bearer tokens must be rejected.
- Authentication errors must not expose sensitive details.

## Authorization Rules
- Every protected request must identify the authenticated user.
- Every user-owned resource must be checked for ownership.
- A user must not access another user's conversations.
- A user must not access another user's provider connections.
- A user must not access another user's synchronization records.
- A user must not access another user's AI suggestions.
- Unauthorized access must return a forbidden response.
- Missing authentication must return an authentication-required response.

## Credential and Authentication-Token Protection
- Passwords must be securely hashed.
- Credentials must be transmitted only over protected connections.
- The V1 web client stores its bearer token in `sessionStorage`, not `localStorage`.
- Sanctum bearer-token lifetime is finite and configured through `SANCTUM_TOKEN_EXPIRATION`; the documented default is 480 minutes.
- Expired bearer tokens are rejected through the normal unauthenticated response path.
- A frontend `401` response clears the stored bearer token and returns the application to the unauthenticated state.
- Sensitive authentication data must not be written to logs.
- Authentication endpoints are rate limited.
- Repeated failed authentication attempts should be monitored.

## Provider Authorization

Provider authorization is separate from OmniDesk AI user authentication.
- The user must authenticate with OmniDesk AI first.
- Provider authorization must use the provider's official authorization flow.
- Gmail OAuth state is validated during callbacks.
- Provider access and refresh tokens remain on the backend.
- Provider tokens are stored with encrypted model casts and hidden from serialization.
- Provider tokens are not returned to the frontend.
- Provider connections must belong to the authenticated OmniDesk AI user.
- Gmail disconnect attempts Google token revocation through the official Google OAuth client.
- Gmail disconnect prefers the refresh token for revocation and falls back to the access token when no refresh token is available.
- If remote Google revocation fails, local Gmail messages, conversations, and the local provider connection are still removed. The API returns an explicit warning so the remote revocation failure is not silently treated as successful.

## Authorization Enforcement Points

Authorization checks must be applied at:
- API middleware
- Controller or request handler
- Application service
- Repository or query boundary where appropriate
- Provider connection operations
- Synchronization operations
- AI assistance operations

The current V1 implementation applies authenticated-user scoping to Gmail connection and synchronization operations and ownership checks to user-owned conversation access.

## Error Handling
| Situation | Expected Result |
|---|---|
| Missing authentication | Return authentication-required response |
| Invalid credentials | Return authentication error |
| Expired bearer token | Return unauthenticated response and require sign-in |
| Missing resource | Return not-found response |
| Resource belongs to another user | Return forbidden response |
| Invalid provider authorization state | Reject the callback |
| Excessive authentication attempts | Apply rate limiting |
| Google revocation failure during disconnect | Complete local cleanup and return an explicit revocation warning |

## Security Requirements
- Authentication and authorization must be tested independently.
- Object-level authorization must be verified for every user-owned resource.
- Provider tokens must be encrypted or otherwise securely protected at rest.
- Logs must not contain passwords, tokens, or authentication secrets.
- Authentication and authorization failures must be observable without exposing sensitive data.
- Security controls must remain effective when AI services or external providers are unavailable.

## Current V1 Decisions
| ID | Decision | Current State |
|---|---|---|
| AUTH-001 | Exact authentication mechanism | Implemented: Laravel Sanctum personal access bearer tokens |
| AUTH-002 | Session-based or token-based API authentication | Implemented: bearer-token API authentication; web token stored in `sessionStorage` |
| AUTH-005 | Provider token encryption strategy | Implemented: Laravel encrypted model casts; token fields hidden from serialization |
| AUTH-007 | Authentication-token expiration | Implemented: finite Sanctum expiration configured by `SANCTUM_TOKEN_EXPIRATION`; documented default 480 minutes |
| AUTH-008 | Gmail provider revocation on disconnect | Implemented: revoke refresh token when available, otherwise access token; local cleanup proceeds with explicit warning on remote failure |

## Unresolved Decisions
| ID | Decision | Current State |
|---|---|---|
| AUTH-003 | Password recovery process | Not yet designed |
| AUTH-004 | Multi-factor authentication | Outside current MVP unless required |
| AUTH-006 | Account deletion and credential removal behavior | Requires privacy and data lifecycle decision |

## PlantUML Source

The editable PlantUML source is available in [authentication-and-authorization-sequence-diagram.puml](./diagrams/authentication-and-authorization-sequence-diagram.puml).

## Relationship to Later Documents

This design will provide input for:
- Provider Integration Design
- AI Assistance Design
- Error Handling Design
- Security Verification
- API Implementation
- Authentication Testing
- Authorization Testing
