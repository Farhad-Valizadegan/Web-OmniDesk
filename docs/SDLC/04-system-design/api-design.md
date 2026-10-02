# API Design

> Status: Draft

## Purpose

This document defines the initial REST API design for OmniDesk.

The API provides a stable boundary between the React client and the Laravel backend.

## API Principles

- REST and JSON are used for client-server communication.
- All protected endpoints require authentication.
- Users may access only their own data.
- Provider-specific details remain behind connector boundaries.
- API responses use consistent structures.
- Sensitive credentials and tokens are never returned to the client.
- Message sending is not included in the current API scope.

## Base URL

```text
/api/v1
```

## Authentication Endpoints

| Method | Endpoint | Purpose | Authentication |
|---|---|---|---|
| POST | `/auth/register` | Create a user account | Public |
| POST | `/auth/login` | Authenticate a user | Public |
| POST | `/auth/logout` | End the current session | Required |
| GET | `/auth/me` | Return the authenticated user | Required |

## Provider Connection Endpoints

| Method | Endpoint | Purpose | Authentication |
|---|---|---|---|
| GET | `/providers` | List supported providers | Required |
| GET | `/connections` | List the user's provider connections | Required |
| POST | `/connections/{provider}/authorize` | Start provider authorization | Required |
| GET | `/connections/{provider}/callback` | Handle provider authorization callback | Provider flow |
| DELETE | `/connections/{connection}` | Disconnect a provider account | Required |
| GET | `/connections/{connection}/status` | Return connection status | Required |

Initial supported providers:

- Gmail
- Telegram

## Communication Endpoints

| Method | Endpoint | Purpose | Authentication |
|---|---|---|---|
| GET | `/conversations` | List synchronized conversations | Required |
| GET | `/conversations/{conversation}` | View a conversation and its messages | Required |
| GET | `/messages/{message}` | View a synchronized message | Required |
| GET | `/search` | Search communication data | Required |

Supported query parameters may include:

```text
provider
status
label
tag
query
page
per_page
sort
direction
```

## Label and Tag Endpoints

| Method | Endpoint | Purpose | Authentication |
|---|---|---|---|
| GET | `/labels` | List the user's local labels | Required |
| POST | `/labels` | Create a local label | Required |
| PATCH | `/labels/{label}` | Update a local label | Required |
| DELETE | `/labels/{label}` | Delete a local label | Required |
| POST | `/conversations/{conversation}/labels` | Attach a label | Required |
| DELETE | `/conversations/{conversation}/labels/{label}` | Remove a label | Required |

## Synchronization Endpoints

| Method | Endpoint | Purpose | Authentication |
|---|---|---|---|
| POST | `/connections/{connection}/sync` | Start synchronization | Required |
| GET | `/connections/{connection}/sync-status` | Return synchronization status | Required |
| GET | `/synchronizations` | List the user's synchronization records | Required |
| GET | `/synchronizations/{synchronization}` | View synchronization details | Required |

Synchronization requests should support idempotent processing.

## Response Format

Successful responses should use a consistent JSON structure:

```json
{
  "data": {},
  "meta": {}
}
```

Collection responses may include pagination metadata:

```json
{
  "data": [],
  "meta": {
    "current_page": 1,
    "per_page": 25,
    "total": 100
  }
}
```

## Error Format

Error responses should use this structure:

```json
{
  "error": {
    "code": "RESOURCE_NOT_FOUND",
    "message": "The requested resource was not found.",
    "details": {}
  }
}
```

Common error codes include:

| Code | Meaning |
|---|---|
| `UNAUTHENTICATED` | Authentication is required |
| `FORBIDDEN` | The user is not allowed to access the resource |
| `RESOURCE_NOT_FOUND` | The resource does not exist |
| `VALIDATION_FAILED` | Request data is invalid |
| `PROVIDER_UNAVAILABLE` | External provider is unavailable |
| `SYNCHRONIZATION_FAILED` | Synchronization did not complete |
| `RATE_LIMITED` | Request rate limit was exceeded |

## HTTP Status Codes

| Status | Usage |
|---|---|
| 200 | Successful read or update |
| 201 | Resource created |
| 202 | Operation accepted for background processing |
| 204 | Successful deletion with no response body |
| 400 | Invalid request |
| 401 | Authentication required |
| 403 | Access denied |
| 404 | Resource not found |
| 409 | Conflict or duplicate operation |
| 422 | Validation failure |
| 429 | Rate limit exceeded |
| 500 | Unexpected server error |

## Security Requirements

- Protected endpoints must verify the authenticated user.
- Authorization checks must be applied to every user-owned resource.
- OAuth tokens must remain server-side.
- Credentials and tokens must never appear in API responses.
- Sensitive values must not be written to logs.
- Request validation must occur at the API boundary.
- Provider callback requests must validate state and authorization data.

## API Versioning

The initial API version is:

```text
/api/v1
```

Breaking changes should use a new API version.

Backward-compatible changes may be introduced within the existing version.

## Background Operations


For accepted background operations, the API may return:

```json
{
  "data": {
    "status": "accepted",
    "operation_id": "operation-identifier"
  }
}
```

The client can use a status endpoint to obtain the latest state.

## Unresolved API Decisions

| ID | Decision | Current State |
|---|---|---|
| API-001 | Whether provider message sending will be supported | Unresolved |
| API-002 | Exact authentication mechanism | To be finalized during security design |
| API-003 | Exact pagination strategy | To be finalized during implementation |
| API-004 | Exact synchronization scheduling model | To be finalized during deployment design |
| API-006 | Exact Telegram authorization flow | Requires provider validation |

## Relationship to Later Documents

This API design will provide input for:

- Authentication and Authorization Design
- Provider Integration Design
- Error Handling Design
- Frontend Integration
- Implementation
- API Testing
- Security Testing
