# Component Diagram

> Status: Draft

## Purpose

This diagram shows the main software components of OmniDesk AI and their dependencies.

## Diagram

![OmniDesk AI Component Diagram](https://www.plantuml.com/plantuml/svg/TLJ1Rjim3BthAuZS-mj3tQOO30nO6dVeMRKvHuWj6P9IB1lsxwFeIBRax114V7ny9lLeFpelhR-CW_3A3mZVHwtswCvGrV1iniber1xsIlPMZa8C-DE3Ds1LV_BGAOkjLqOBTrPwaaI1DXOr_afwWI1rFw0GaspFiaVO7Imw8iXGkOC_0fOYs1rHjXxUyGEU1qN83gIx7SNVbKWrJODgMGCQj9-gHRSLUsdUe3hKB4B_URQw-1CT2fMPIwcS_6xisT7JCUCbD2StLzsUhD7gzrOwv8e-QgYSK-I4BkIh-XPbezVQ8zd1oa_ceZjfhvlHluvI3MIXrkI0iIp7M4vxmo5eZGKpmfj63zPygWujL9sSFDfRcxSeQ_E0DWo5EWnJTFV3aSGH9-CKFVBAAW4BKCwa9HmlEdfKb6J9UxKyqzt2cDbMbUOLfI7DHJHh9xtya2uCQvplBJQltvYqVrhbZjWfnp2VnCQbkA4SXJMzPr0aHQ3WDGIt7ZjOcSJybrzaZ9P3LGinlqVmyF25tmRwpUUmxJ5OLZeYlB69crPIX3B6adC9AUvtUNdcPEbQdfzlAJoLmtbhKvXMCo5RlPHAPYLa5azJ_4SWpo_xBMR3KblX5fD3w-xuUcCPPzO-n0lZ1EtAfe4biRx7YCQfOYmUKNVq3Vy7)

## Component Responsibilities

- The React client provides the user interface.

- The REST API exposes application operations.

- Application services implement authentication, workspace, synchronization, and AI workflows.

- Connectors isolate provider-specific integration logic.

- Repositories isolate persistence access.

- PostgreSQL stores durable application data.

- Redis supports caching or background processing where justified.

- External APIs remain outside the OmniDesk AI boundary.

## Design Rules

- Components should have clear responsibilities.

- Provider-specific logic must remain behind connector interfaces.

- Core business logic should remain independently testable.

- External failures should be isolated where possible.

- AI access must occur through the AI assistance boundary.

## PlantUML Source

The editable PlantUML source is available in [component-diagram.puml](./diagrams/component-diagram.puml).

