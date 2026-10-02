# Sprint 05 Plan - Gmail Synchronization

> Status: Complete

## Sprint Goal

Synchronize Gmail data into the provider-neutral OmniDesk inbox using safe full and incremental synchronization.

## Related Milestone

M2 - Gmail Integration

## Scope

- Run an initial Gmail thread and message synchronization.
- Store Gmail history cursors per provider connection.
- Use Gmail History API for incremental synchronization.
- Upsert conversations and messages without duplicates.
- Apply current Gmail labels and read state.
- Remove locally stored messages deleted from Gmail.
- Refresh expired access tokens with encrypted refresh tokens.
- Fall back to full synchronization when a history cursor expires.
- Support manual synchronization through the authenticated API.
- Support scheduled synchronization through the Laravel scheduler.
- Keep provider credentials and provider-specific logic behind the Gmail connector boundary.
- Preserve existing authentication and unified inbox behavior.

## API Scope

| Method | Endpoint | Purpose |
|---|---|---|
| POST | `/api/integrations/gmail/sync` | Start authenticated Gmail synchronization |

## Console Scope

| Command | Schedule | Purpose |
|---|---|---|
| `php artisan gmail:sync` | Every five minutes | Synchronize all connected Gmail accounts |

## Data Requirements

- Preserve Gmail thread IDs as `provider_conversation_id`.
- Preserve Gmail message IDs as `provider_message_id`.
- Store the latest Gmail mailbox cursor in `provider_connections.history_id`.
- Encrypt access and refresh tokens at rest.
- Do not return tokens in API responses.
- Prevent duplicate conversations and messages through provider-scoped upserts.
- Do not advance the cursor until successful processing completes.

## Synchronization Behavior

### Initial synchronization

When no cursor exists:

1. Read the Gmail profile history ID.
2. List and fetch Gmail threads in full format.
3. Normalize messages into the provider-neutral inbox schema.
4. Store the cursor after successful processing.

### Incremental synchronization

When a cursor exists:

1. Request Gmail history after the stored cursor.
2. Collect added and label-changed message IDs.
3. Fetch affected messages and their complete threads.
4. Upsert affected conversations and messages.
5. Remove messages reported as deleted.
6. Store the newest returned history ID only after successful processing.

### Recovery

If Gmail returns HTTP 404 for an expired or invalid history cursor:

1. Clear the stale cursor.
2. Run a full synchronization.
3. Store a new cursor after successful completion.

Temporary provider failures must preserve existing data and must not expose provider details to clients or logs.

## Testing Requirements

- Initial synchronization stores conversations, messages, and a history cursor.
- Incremental synchronization reads history after the stored cursor.
- Repeated synchronization does not create duplicates.
- Label changes update read state and labels.
- Deleted Gmail messages are removed locally.
- History pagination is processed.
- Expired history cursors trigger full synchronization.
- Expired access tokens are refreshed.
- Failed synchronization does not advance the cursor.
- Unauthenticated API access is rejected.
- Manual API failures are safe.
- Scheduled command handles connected and disconnected accounts.
- Frontend manual synchronization refreshes the inbox.
- Backend and frontend verification commands pass.

## Acceptance Criteria

- A connected Gmail account can be synchronized initially.
- Subsequent runs use the stored history cursor.
- A new Gmail message appears in the unified inbox after the next sync.
- Re-running sync without changes processes zero new messages.
- Gmail labels and read state are reflected locally.
- Deleted Gmail messages do not remain locally.
- Expired cursors recover through full synchronization.
- Scheduled synchronization runs every five minutes on the local Windows scheduler worker.
- Existing authentication, ownership, and inbox tests remain passing.
- No secrets are committed.

## Out of Scope

- Gmail push notifications or Pub/Sub webhooks.
- Telegram synchronization.
- Sending or modifying Gmail messages.
- Production deployment.

## Completion Evidence

- Backend full suite: 78 tests passed, 306 assertions.
- Targeted Gmail synchronization, lazy-routing, and command tests passed.
- Frontend tests passed.
- Frontend lint passed.
- Frontend build passed.
- Gmail initial synchronization completed successfully.
- Gmail incremental synchronization was verified with cursor advancement.
- Repeated synchronization prevented duplicate processing.
- Rate-limit recovery preserved the synchronization token and resumed successfully.
- Final Gmail state: `page_token = null`, `snapshot_history_id = null`, `completed = true`.
- `git diff --check` passed.
- Working tree was clean before merge.
- Pull Request #6 was merged into `main`.
- Merge commit: `5438688`.
