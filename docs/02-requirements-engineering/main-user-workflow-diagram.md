# Main User Workflow Diagram

> Status: Draft

## Purpose

This Mermaid diagram shows the main user workflow for the OmniDesk AI MVP.

```mermaid
flowchart TD
Start([Start]) --> Auth[Authenticate user]
Auth --> Connect[Connect Gmail or Telegram]
Connect --> Sync[Synchronize available communication]
Sync --> Workspace[Open unified workspace]
Workspace --> Browse[Browse conversations]
Browse --> Search[Search or filter communication]
Search --> Decision{Need AI assistance?}

Decision -->|No| Organize[Organize or continue browsing]
Decision -->|Yes| Select[Select a conversation]
Select --> Summary[Request summary]
Select --> Reply[Request reply suggestion]
Summary --> Review[Review AI output]
Reply --> Review
Review --> Edit[Edit or discard suggestion]
Edit --> Organize
Organize --> End([Continue using workspace])
```

## Workflow Rules

- AI assistance is initiated by the user.
- AI output must be reviewed.
- AI failure must not block browsing, searching, or organizing.
- Message sending is not part of this workflow unless separately approved.
