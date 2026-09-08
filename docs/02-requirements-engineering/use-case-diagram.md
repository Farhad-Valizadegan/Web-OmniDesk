# Use Case Diagram

> Status: Draft

## Purpose

This Mermaid diagram provides a visual overview of the main actors and use cases of OmniDesk AI.

```mermaid
flowchart TD
User[Primary User]
Gmail[Gmail Provider]
Telegram[Telegram Provider]
AI[AI Service]

subgraph OmniDeskAI[OmniDesk AI]
UC1[UC-001 Create and authenticate account]
UC2[UC-002 Connect Gmail account]
UC3[UC-003 Connect Telegram account]
UC4[UC-004 Browse unified workspace]
UC5[UC-005 Search and filter communication]
UC6[UC-006 Synchronize provider communication]
UC7[UC-007 Request conversation summary]
UC8[UC-008 Generate editable reply suggestion]
UC9[UC-009 Disconnect external account]
UC10[UC-010 Handle unavailable AI service]
UC11[UC-011 Handle provider synchronization failure]
Rule[AI never sends content autonomously]
end

User --> UC1
User --> UC2
User --> UC3
User --> UC4
User --> UC5
User --> UC7
User --> UC8
User --> UC9
User --> UC10

Gmail --> UC2
Gmail --> UC6
Telegram --> UC3
Telegram --> UC6
AI --> UC7
AI --> UC8

UC6 -.-> UC11
UC7 -.-> UC10
UC8 -.-> Rule
```

## Diagram Notes

- The Primary User initiates the main workspace and AI interactions.
- Gmail and Telegram are the initial external providers.
- The AI Service supports summaries and editable reply suggestions.
- AI-generated content remains under user control.
- Autonomous AI message sending is explicitly prohibited.
- Provider synchronization failures must not corrupt existing data.


