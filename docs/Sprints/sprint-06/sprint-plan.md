# Sprint 06 Plan - Unified Inbox

> Status: Planned
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
