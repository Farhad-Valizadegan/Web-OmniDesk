# Version 1 Release Notes

> Status: Structure Only
>
> This directory is prepared for Version 1 release documentation. It does not declare Version 1 released and does not change Version 1 scope.

## Purpose

Release notes describe what was actually delivered in a specific release.

They should be based on completed and verified work, not on Future Scope or tentative Roadmap items.

## Recommended Release Files

A Version 1 release may use files such as:

- `v1.0.0.md` — first stable Version 1 release
- `v1.0.1.md` — patch release
- `v1.1.0.md` — backward-compatible feature release within the Version 1 product line, if applicable

Do not create a release note for functionality that has not been completed and verified.

## Release Note Template

```md
# OmniDesk AI vX.Y.Z

Release date: YYYY-MM-DD
Status: Stable | Beta | Alpha | Release Candidate

## Summary

Brief description of the release and its purpose.

## Added

- New user-visible capabilities.

## Changed

- Behavior changes, UX changes, architectural changes visible to maintainers or users.

## Fixed

- Bugs and regressions corrected in this release.

## Security

- Security-relevant improvements or fixes that are appropriate to disclose.

## Provider Integrations

### Gmail

- Relevant Gmail integration changes.

### Telegram

- Relevant Telegram integration changes.

## AI Assistance

- Summarization, reply-assistance, or related AI changes included in the release.

## Testing and Quality

- Test coverage or important verification completed for the release.
- CI/build/deployment validation status where useful.

## Known Limitations

- Important known limitations that remain in the released version.

## Upgrade / Deployment Notes

- Required environment variables.
- Database migrations.
- Configuration changes.
- Operational or deployment actions.

## Scope Note

This release contains only completed Version 1 work. Future Scope and Roadmap items are not implied to be included unless explicitly listed above.
```

## Release Documentation Rule

The documentation hierarchy is:

- **Future Scope** — what may exist later.
- **Product Roadmap** — tentative order of future product evolution.
- **Backlog** — concrete work accepted for planning.
- **Sprint documentation** — work selected for active implementation.
- **Release notes** — work actually completed, verified, and released.

Release notes are therefore historical and factual. They must not be used as a place for speculative functionality.

## Version 1 Baseline Protection

Creating this directory does not modify the current Version 1 project scope or existing sprint definitions. Version 1 remains governed by the existing project-definition, requirements, and sprint documentation until an explicit scope decision changes them.
