# Project Context Diagram

> Status: Draft

## Purpose

This Mermaid diagram presents OmniDesk AI in its high-level project environment.

```mermaid
flowchart TD
User[Primary User]
Owner[Project Owner and Developer]
Providers[Communication Providers]
AI[AI Service]
Reviewers[Technical Reviewers and Recruiters]

OmniDesk[OmniDesk AI]

User -->|uses| OmniDesk
Owner -->|defines and maintains| OmniDesk
Providers -->|provide communication data| OmniDesk
OmniDesk -->|requests assistance| AI
AI -->|returns summaries or suggestions| OmniDesk
Reviewers -->|evaluate engineering quality| OmniDesk
```

## Notes

- The diagram describes the project environment, not the internal architecture.
- Provider-specific implementation details are intentionally excluded.
- AI assistance remains under user control.
