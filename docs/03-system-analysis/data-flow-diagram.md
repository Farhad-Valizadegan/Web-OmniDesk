# Data Flow Diagram

> Status: Draft

## Purpose

This diagram shows how data enters, moves through, and leaves OmniDesk AI.

It focuses on system responsibilities and data movement without defining database tables or implementation details.

## Diagram

![OmniDesk AI Data Flow Diagram](https://www.plantuml.com/plantuml/svg/bLLDZziW4BtpAr3Ecp_GGxLferQHskvAsLNF4pnnK32u37QQ_leEu0-S-jAJO_mUyzxCWpnJ01-wneYWWq7vqbYzHxhAtK7k8O3yOjnDxZNK7XeX39w334vwNL-2hBH75RIpWgxQji0GgLpJEeis7CETzui0iBL18K05v-NcrUi6_5s-4_gD14e_n8IJcwyDQ2DVlUjrDK3IKebvGuDHqH8shfP8zd54tsk52R8xb1y_WRhMtdMsaiUxLVADVTN3Pd51B7RgmeLzQGNHiWGcVNRMvWx8xs2XneO1kGw3ItBSxUATrNzczW_d6p33IcADArXItwq-Qup5cgQpO_cVpb-fXS7MzBQeYU3L9PNwvXGO-U9hi6MzZ7Xeruv8SohisB7qAaJ5QJW1CQPmVUIHPjYyI0jegRb45-ibOQrFkwxIWItKYSfF4YA6Hcwt7rEZvGUfF5PnE60eSK0f7clWPwwGO15Vw8oqsOgsvzZz5ESIL1QPqToWqB6E7DQ8cMR7m7P8PJmDXVzeKqpH_HzIIkSZWHvw5N9w_r5VSc8_UYoSa9W_v_wKas667JzNQyAMv3YR5M7OysZAeKrnPV39knjFFWxDvNoYcB-lIl88gsAMj27-B6S-1sTj0clm-Aj33lK0MIt22BwbrcOnaqeV71rrqRRcpQap2xlv22NXgQwahabt8D-7rF7CAALkWAsg8KwD2ca6jaTFMG-_1FmTIYOVO9vo6yF4wB7W13mi1HL2a0yPdFWEM5TKMktOsDPZh_6M6DcnUEX_iizzGIA-6UbfE3DFQH1ZavxHLlplynS0)

## Main Data Flows

- Users provide authentication and workspace actions.

- Gmail and Telegram provide external communication data.

- Synchronization normalizes provider data before storage.

- The workspace reads normalized communication data.

- Search queries the local communication store.

- AI receives only approved conversation context.

- AI returns summaries or editable suggestions.

- Synchronization events are recorded without sensitive content.

## Analysis Notes

- The Communication Store represents provider-independent local data.

- The Connection Store contains protected connection information.

- The Synchronization and Audit Log must not expose credentials, tokens, or unnecessary message content.

- AI-generated content remains under user control.

- This diagram is conceptual and does not define database schema.

## PlantUML Source

The editable PlantUML source is available in [data-flow-diagram.puml](./diagrams/data-flow-diagram.puml).

