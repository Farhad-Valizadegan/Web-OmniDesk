# Project Scope Boundary Diagram

> Status: Draft

## Purpose

This Mermaid diagram visualizes the current MVP boundary of OmniDesk AI.

```mermaid
flowchart LR
subgraph InScope[Inside MVP Scope]
Unified[Unified communication workspace]
GmailMVP[Gmail integration]
TelegramMVP[Telegram integration]
Search[Search and filtering]
Organization[Local organization]
Sync[Synchronization]
AISummary[AI summaries]
AIReply[Editable reply suggestions]
Security[Security and privacy controls]
end

subgraph OutScope[Outside Current MVP Scope]
WhatsApp[WhatsApp integration]
Instagram[Instagram integration]
Slack[Slack integration]
AutoSend[Autonomous AI sending]
FinalProvider[Unselected future providers]
end

Unified --- GmailMVP
Unified --- TelegramMVP
Unified --- Search
Unified --- Organization
Unified --- Sync
Unified --- AISummary
Unified --- AIReply
Unified --- Security
```

## Notes

- Items inside the boundary represent the current product direction.
- Items outside the boundary may be considered in future releases.
- Autonomous AI sending is explicitly excluded.
- Final message-sending behavior remains unresolved.
