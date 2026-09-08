# Use Case Diagram

> Status: Draft

## Purpose

This document presents the UML-style Use Case Diagram for OmniDesk AI.

The diagram is authored in PlantUML and displayed in this Markdown file as an SVG image.

## Diagram

![OmniDesk AI Use Case Diagram](https://www.plantuml.com/plantuml/svg/RLJRRjim37tNLn1vJu7ragOlGzCMAFAqeka-W9DfMuWikReapORz-oZPJfMiJnR9SqYHXzQz3-X2xBJGL0S85fngsW2LSYI3iaRuhJ8zEkoWHxd5XZRXe0bI64sZgG3u5YkxLwQ16hKd8L06ws3subI7xW0_FRaPeC-7AVZSeTBmuknELMCukwRu6sbgKlOJoEITKAirRCZjbAGSNgsdm0DVkd4scWes1oFXZVjhnYJ98SIn4vZzw8nw8hzb_Wp-283eIQBdoACZ30J8QJ26banGCZkavDnXwEhnsmd56iEfXsxEW9TV0O-TdcAlIko3itl-HgDgHHNihTjwreL6x7M9tH0wsUOxrqe7SY1jrp6LRyxAZfIR4mfFf7NMgDy4_JZlBrcBalLAxv5yOA3PaVCP1pvsIVGHVrlYdycGIzEZIWNybRUfrmUci3I-gB8iMK_Aot5IzC7T63oVwftuAqJQBPZFlsSv2kEoDAvAuxeqRahZjZIMfN4dnA3hbBoq5k8evLJkpB4GlB1JZUDnAVAAJX1XBCze-1zjdUEhzRmPXrUbSGTUH1xUU-JVrOkOxkTefsW_R6gVD4HzaKeOOct0awdyaSchR8tjRFJwS24u1Ada_uKNudCn1dLhxZPoJK17q78nJPLW4L1dPOBQ4TIQTpLVf8ky7PnKIEjSx9E0YdNcXwB2W4NbUpxnO_GF)

## Diagram Notes

- Primary User is the main human actor.
- Gmail Provider and Telegram Provider are external communication systems.
- AI Service is an external system used for summaries and editable reply suggestions.
- Background Sync Trigger represents scheduled or system-triggered synchronization.
- AI service unavailability and provider synchronization failure are alternative flows.
- AI-generated content requires user review and approval.
- AI must never send content autonomously.
- Message sending remains outside the current MVP commitment.

## Source

The editable PlantUML source is available in [use-case-diagram.puml](./diagrams/use-case-diagram.puml).
