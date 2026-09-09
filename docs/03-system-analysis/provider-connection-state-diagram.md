# Provider Connection State Diagram

> Status: Draft

## Purpose

This state diagram describes the lifecycle of a Gmail or Telegram provider connection.

## Diagram

![Provider Connection State Diagram](https://www.plantuml.com/plantuml/svg/bLHDJyCm3BtdL_YCj3-m0q8QS4RQ4N7mKjD5jCv8qh8DyTzniduatJXmwKVy_Fpiv_R11tIXQniLJ6W8NfpjJKKEDfQPT32MOHim43mQh1ssIhtUlS5gTIy7Nbz0L2cLlwNmIC0rhA7pGfbAUT0JiyemMOeGh06xiBVEd359y9tMH9N_8qCWpsYQ6udlSkoLsXJIjYVMUsVPd2_g_FG-L4j2LGbR2hnA6YLUfyMJITyoRr2OOMv9T1JSIQwV7VauxmKy5nO9EqU0-eFjbo3gw4yvWQVZmRY4rd8Z3WOR3vHErHYySj7Hu5qcP44yhq5c_83sKCrHSJ-lyv-yAzENsnZRGjRKW8qade8g-MybpFjkkCV6L9ZQysdvvoQ_5HJJFemV36fjEmvWl3m6qvFw6RDBCoF3qjw9HQfYfDXH34JN3VLI2Ba2jguLOMUHLL0FKnXvfqsZe_6ftKJgg4N38gwNEEwQJDzeH6IODsFkhFYYH1nRQ2pN4ag3YaGFn9NyHNu1)

## Main States

- `Disconnected`: No active provider connection exists.

- `Connecting`: Authorization is in progress.

- `Connected`: The provider account is active.

- `Synchronizing`: Provider data is being retrieved and normalized.

- `SyncFailed`: Synchronization failed, but existing data remains available.

- `Expired`: Provider authorization is no longer valid.

- `Disconnecting`: The connection is being invalidated.

## State Rules

- Authorization failure returns the connection to `Disconnected`.

- Synchronization failure must not corrupt existing data.

- A failed synchronization may be retried.

- Expired connections require reauthorization.

- Disconnecting a provider invalidates its connection information.

## PlantUML Source

The editable PlantUML source is available in [provider-connection-state-diagram.puml](./diagrams/provider-connection-state-diagram.puml).

