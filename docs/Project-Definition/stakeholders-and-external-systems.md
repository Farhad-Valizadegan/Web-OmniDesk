# Stakeholders and External Systems
> Status: Draft
## Stakeholders
| Stakeholder | Role | Main Interest |
|---|---|---|
| Primary User | Uses OmniDesk to manage communication | Efficient and consistent communication management |
| Project Owner and Developer | Defines, designs, implements, tests, and maintains the system | Successful product development and portfolio demonstration |
| External Service Providers | Provide APIs and authentication services | Correct API usage and compliance with platform policies |
| Technical Reviewers | Evaluate the project technically | Evidence of professional engineering practices |
| Recruiters and Employers | Assess the developer's capabilities | Clear evidence of practical Software Engineering skills |
## Primary Stakeholder
The primary stakeholder is the individual user who manages communication across multiple supported platforms.
The system should prioritize this user's security, privacy, usability, and control over external actions.
## Secondary Stakeholders
Secondary stakeholders include:
- The project owner and developer
- External communication-platform providers
- Technical reviewers
- Recruiters and potential employers
## External Systems
| External System | Relationship with OmniDesk |
|---|---|
| Gmail and Google APIs | Provide email access, message data, and Gmail-related operations |
| Telegram API | Provides access to supported Telegram communication capabilities |
| Identity and OAuth Services | Support authentication and authorization for external accounts |
| Email or Notification Services | May support system notifications in future versions |
## External-System Constraints
OmniDesk depends on the capabilities, availability, authentication requirements, quotas, rate limits, and policies of external systems.
The system must not depend on undocumented scraping or browser automation for its core integrations.
External systems remain authoritative for their original communication data. OmniDesk maintains its own local representation for unified viewing, search, organization, and synchronization.
## Stakeholder Priorities
The following priorities guide the project:
1. User privacy and security
2. Reliable access to supported communication data
3. Clear user control over external actions
4. Maintainable and extensible integration boundaries
5. Practical portfolio value


