# Stakeholder Ecosystem Diagram

> Status: Draft

## Purpose

This Mermaid diagram shows the main stakeholders, external providers, and supporting services surrounding OmniDesk AI.

```mermaid
flowchart TD
Owner[Project Owner and Developer]
User[Primary User]
Reviewers[Technical Reviewers and Recruiters]
Gmail[Gmail Provider]
Telegram[Telegram Provider]
AI[AI Service]
Future[Future Providers]

OmniDesk[OmniDesk AI]

Owner --> OmniDesk
User --> OmniDesk
Reviewers -. evaluate .-> OmniDesk
Gmail <--> OmniDesk
Telegram <--> OmniDesk
OmniDesk <--> AI
Future -. future integration .-> OmniDesk
```

## Notes

- The Primary User is the main product stakeholder.
- Gmail and Telegram are the initial providers.
- AI is an assisting external service.
- Future providers are outside the current MVP commitment.
