# AI Assistance Sequence Diagram

> Status: Draft

## Purpose

This sequence diagram shows how OmniDesk AI handles user-requested summaries and editable reply suggestions.

## Diagram

![AI Assistance Sequence Diagram](https://www.plantuml.com/plantuml/svg/VLFBRjmm3BphAuWzP_C1EHHPD9Sz50smw0TmPQvBH0zNf9nilxwKx1HEqkPacHhES3pqlIWMhJ4uPGq4XoCSHDYgoHESw5UbTdXW70j6vz1hBh1xB1onNE67KDa1IZ-uoQZOyuH9OVSz9duWUJBAZZXTHIc-nRnNApDxwkYb_06zGk2nv9c7LVjmT0Cgdb48TbzpZ3MnH-MSu6JJBeJzv5oR4_PVrcdW3agpA0fIOtTaxWfDuMg5SRGBOt4hkBKrbjwbXMac40habGRmESrK19U63jjlTRRtqCRTi2vc6wWGAW5Ea_apKdEypkTMJ4CV3IcK1eYSEFBlT-hjHUb5BQoW6mfpv3tH80vQTRyL_imzlCBU-3aSzoCbAjZC5v8Qr92ldWpPl_GT32nJmEiAWKjNc9cU3VqX31fORm1jpCcU1hLkZ-Ml00MXhQCBSlYNdLQlbe2rJJa9_TS3r8IpWV5ikszhg5K-TV5A_CpwCrU5SyZ-YTDetzog5qlFRiLPECwbR55QZAMJtApxRvhME5bledRNWfGbCjjnh9fJZhbAkDwwRoI28tLC4scssVY1NgPWluL2RUCjwuBXjkb2atNktexsL_y1)

## Interaction Rules

- AI assistance must be initiated by the user.

- Only the minimum required conversation context should be sent to the AI service.

- AI output must be shown to the user for review.

- The user may edit, accept, or discard the generated result.

- AI failure must not block core communication features.

- AI must never send content autonomously.

## PlantUML Source

The editable PlantUML source is available in [ai-assistance-sequence-diagram.puml](./diagrams/ai-assistance-sequence-diagram.puml).

