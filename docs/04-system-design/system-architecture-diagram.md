# System Architecture Diagram

> Status: Draft

## Purpose

This diagram presents the initial technical architecture of OmniDesk AI.

It shows the client, application, data, and external service layers.

## Diagram

![OmniDesk AI System Architecture](https://www.plantuml.com/plantuml/svg/VLJ1Rjim3BthAuXSYsBt7eQwJJ5aoDeiJj6pQhEE45doA3cRD-pVHqcn7MNTJg4U-PuOFbgtpaloVQk5LruZFBL6BT4Te5X1EJYFBHHKxPN7olU4GcXyy-0ja6hs7cf5d53M27TGffCaMwXisrc3nfT-OCLG84sZcIihRmaM6rAjf06U7T82f8k149si3h91MDnhnMnOoo7aVmcO9M6nHLQ1AzWD7POLgSx32xv2uaIr58hVPuf5rsbLoT3eFsJNtFeHDMmVoXqKcrLKuj-ygkZzdeDHIfeQ0cH9_On8OZ6IqvwDUbDOmukbW-Ec4BxOkjSOooSmvvI3gVPapKdvd15I5tsje71EiPSc5ozM2S_BDsIFgaQ2UsiCHbFkR6zgDYLDS8HTDiMbzF9yV3MVNwNZpCOwtn2MNzUHlxpBibkibOk966MQ3pyyaf4QIgIZgj3zvSodLYezMH9FUS4EDJPXySQQ4RWOAAELPnV2PgVBJYCwdK9VOHFX-lhZQOdW1dg73hvF7ftmKCBNSJukp0VuN3uz2X6Wc0ehC1vcWqSa6ZZLZXQ9m8eGpszUbNU1DCYWaxgT3PimvannAXzl6jFP-Kn2p75C9GTk-4lsfF28OQfjRyQl81XzIPay-Jyhws0oWacowtX7arc4ttha-Spvyx5niUlR-9xm6lF_vAtZLyVrJSEay3A9MpGrlt1_0000)

## Architecture Layers

- Client Layer: React and TypeScript web client.

- Application Layer: Laravel REST API and core application modules.

- Data Layer: PostgreSQL and Redis.

- External Services: Gmail API, Telegram API, and the selected AI service.

## Design Rules

- Provider-specific logic remains behind the connector boundary.

- The core workspace uses provider-independent communication concepts.

- AI requests are initiated through the AI assistance module.

- Only approved context is sent to the AI service.

- Secrets and provider credentials remain protected on the backend.

- Redis is used only where caching or background processing is justified.

## PlantUML Source

The editable PlantUML source is available in [system-architecture-diagram.puml](./diagrams/system-architecture-diagram.puml).

