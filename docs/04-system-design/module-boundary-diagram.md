# Module Boundary Diagram

> Status: Draft

## Purpose

This diagram defines the main logical module boundaries of OmniDesk AI and their dependencies.

## Diagram

![OmniDesk AI Module Boundary Diagram](https://www.plantuml.com/plantuml/svg/VLN1Rjim3BthAuZS-mCz3CqQO0Ymh4MzRcTMPcmYjkH9ShPqsB-FIXnRaOtUMZsAx_7noNdm0LtesqO53Wt1KsjuG_u0wotiRDdBqMVRcnBT2JQCbSDMgORs0O85nrKTe6H7Eh0roX_OT2Wbe6tRMKCc5E4aBM81cgeXfJhK1wm8LjjIO0wdWMO5w64y-wjWwW6hTH_gY6YCFAivQ1s_Bs6ldXpiq0XZAmShzIzHy6ZRjZT3qrJ6BP1fUJMyPohXftK7BxqeetoqvaZE3qqJwfkgWjW3f6j2KyEGgDAFyL61nChfszWgc4dCe4nbX6SEwaWq6wpBs3GQ47zXmkG1-iwuLQ6XSa3loRCXDtpNH7u9dHyUucbpxixD7BiNzhGaaiSa7kE8-bpvU52EDvCPUMhZztEelBN8prMNeTseYSPiMf0ebuMllMHw0qPHA4o3FtnPUw5TFFdnWgj04-XDWnuPRVgSSB_ggaWvJB38zHOrEShMnCfKv2xdyEGkWdxKUFLjhBQyr3riQoGpQaVV2dfr-nJTgSlbPubv8YnFd4IH5-8O-Rbren56AZbw_6vTrJu1IupE_k_iq3Abktrm2vBm9D6KeEPHT9Vyjz1tPxw5pDaWon7NKMfnO99jOxkL35XnBuEFDY-DnziLhjyJ2rRyrx6imm_CIqlFTGMzUxWXBy1BXipoTRF1w8vb3CDwVe8zVE_lRI6NDNUPpke6vAIcwQ4A23_mtAtqWKyglo7y0)

## Module Responsibilities

- Identity manages authentication, authorization, and users.

- Communication manages the unified workspace and local organization.

- Synchronization validates, normalizes, and deduplicates provider data.

- Provider Integration isolates Gmail and Telegram logic.

- AI Assistance manages minimized context and user-reviewable results.

- Shared Domain contains provider-independent business concepts.

- Persistence provides durable storage for application data.

## Design Rules

- Provider-specific logic remains behind the provider integration boundary.

- Core modules use shared domain concepts.

- AI must verify user ownership before processing conversation data.

- AI output must remain reviewable and editable.

- Persistence details remain behind module boundaries.

- Core business logic should remain independently testable.

## PlantUML Source

The editable PlantUML source is available in [module-boundary-diagram.puml](./diagrams/module-boundary-diagram.puml).

