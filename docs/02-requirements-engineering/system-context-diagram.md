# System Context Diagram

> Status: Draft

## Purpose

This Mermaid diagram defines the system boundary and the main external interactions identified during Requirements Engineering.

```mermaid
flowchart LR
User[Primary User]
Gmail[Gmail Provider]
Telegram[Telegram Provider]
AI[AI Service]
Sync[Background Sync Trigger]

OmniDesk[OmniDesk AI]

User -->|authenticate, browse, search, organize| OmniDesk
Gmail -->|messages and connection status| OmniDesk
Telegram -->|messages and connection status| OmniDesk
Sync -->|starts synchronization| OmniDesk
OmniDesk -->|requested summary or reply context| AI
AI -->|summary or editable suggestion| OmniDesk
OmniDesk -->|reviewed information and status| User
```

## Context Rules

- Gmail and Telegram are the initial external providers.
- AI is an assisting external service, not an autonomous system actor.
- The Background Sync Trigger represents scheduled or system-triggered synchronization.
- The system must not send content autonomously through AI.
