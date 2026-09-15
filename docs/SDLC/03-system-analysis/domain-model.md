# Domain Model

> Status: Draft

## Purpose

This conceptual domain model identifies the main business concepts and relationships within OmniDesk AI.

It describes the communication domain without defining database tables or implementation classes.

## Diagram

![OmniDesk AI Domain Model](https://www.plantuml.com/plantuml/svg/XPF1Ri8m38RlUGghHoGGN7kY6XUaeKrYUu3GcfBH95NiWc3Qkoyab5Bej9lZ_l_dlt4x9vQE6rq9LbmXl6cZ5aXxo9Qmi5eg0ojRO2K4xPMffPCQyaeIPSnERHh6PMxDMfqHfcAd2WIDlBC52H5ay4deu5i0DBvO5hv0Jwt4JojuT_RWVIxBSziO3bgFD9YpiYOuwglaurHZx-cQ9JVqp0gGrXKGHchiT2_M7D2Hl33QWRT6q5Ep-V8pVEKDdFbf1yMdZ3l62ebaYS6kOzqBkxhh4HhEECvXNtUKLxd1AZ2gIpMGTNqo-SvPeyvTNFAz28uN40h7M5o7wBh2UBfXikMwAKkaZd4x1XB7kxq1xvA65IQp1CPZIAQJoIXvMbeAzcX8FBQJKJJDEeUN-YzXGDhZznQKXZ3-KoJHwryieu0FddOXGKwAcC1kXyB7Eq-XmAqo-0YDccUrTQNqIy02DgT_qZ-kB8MTfA4alQMauF2Wy3ZmbdywbDbQfqdCqHJ-9_u5)

## Domain Concepts

- `User` owns provider connections and local labels.

- `ProviderAccount` represents a connected Gmail or Telegram account.

- `Provider` identifies the external communication provider.

- `Conversation` groups related messages.

- `Message` represents synchronized communication content.

- `Label` represents local organization metadata.

- `Synchronization` records synchronization activity and status.

- `AISuggestion` represents a user-requested summary or reply suggestion.

## Analysis Notes

- This is a conceptual model, not a database schema.

- Provider-specific data is mapped to provider-independent concepts.

- AI suggestions remain under user control.

- Message sending is not represented as an approved capability.

- Security and privacy constraints apply to every concept.

## PlantUML Source

The editable PlantUML source is available in [domain-model.puml](./diagrams/domain-model.puml).

