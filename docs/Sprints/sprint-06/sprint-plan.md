# Sprint 06 Plan - Unified Inbox

> Status: In Progress
> Primary Milestone: M3 - Unified Inbox

## Sprint Goal

Complete the unified inbox experience on top of the provider-neutral workspace and synchronized Gmail data.

## Scope

- Complete conversation organization and inbox workflows.
- Add and verify conversation status and label filtering.
- Display conversation status consistently.
- Complete inbox selection, loading, error, and interaction states.
- Present synchronized Gmail conversations and messages in the unified workspace.
- Preserve ownership boundaries and provider-neutral API behavior.
- Keep Telegram integration, AI assistance, and production deployment outside this sprint.

## Related Milestone

M3 - Unified Inbox

Sprint 06 is the only primary sprint assigned to M3. Sprint 03 delivered the workspace foundation under M1; Sprint 05 delivered Gmail synchronization under M2.

## Current Progress

Implemented and verified so far:

- Unified workspace presentation for synchronized Gmail conversations and messages.
- Search and read/unread filtering.
- Conversation labels are displayed when present.
- Conversation selection, message loading, empty states, error states, and pagination behavior.
- Lazy Gmail pagination when additional provider pages are available.
- Provider-neutral conversation/message API usage and ownership boundaries.
- Existing backend and frontend quality checks remain passing in CI.

Remaining before Sprint 06 can be considered complete:

- Add and verify label filtering in the unified inbox UI.
- Display the conversation `status` field consistently in the UI.
- Add/adjust frontend tests covering those remaining behaviors.
- Record final Sprint completion evidence after all Completion Criteria are satisfied.

## Completion Criteria

- Unified inbox workflows are complete and verified.
- Status and label filters work end to end.
- Conversation status is visible.
- Frontend interaction, loading, and error tests pass.
- Existing backend and frontend verification remains passing.
- No provider credentials are exposed.
- No secrets are committed.
- Documentation and acceptance evidence are recorded.

## Dependencies

- M0 and M1 are complete.
- M2 Gmail integration and synchronization are complete.
- Provider-neutral conversation and message APIs are available.

## Out of Scope

- Telegram authorization or synchronization.
- AI-generated replies or summaries.
- Autonomous message sending.
- Production deployment and release hardening.

## Verification

```powershell
Push-Location backend
php artisan test
Pop-Location
npm.cmd --prefix frontend run test
npm.cmd --prefix frontend run lint
npm.cmd --prefix frontend run build
git diff --check
```
