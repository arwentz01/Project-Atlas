# Architecture Proposal

## Architectural goals

Project Atlas should be:

- multi-tenant and organization-aware
- source-traceable and versioned
- safe without collecting patient information
- modular enough to add future integrations
- accessible on desktop, tablet, mobile, and print
- capable of turning documents into reviewed structured knowledge
- searchable across global and local organizational content

## Recommended starting architecture

A modular monolith is the preferred starting point. It keeps development and deployment manageable while preserving clear domain boundaries that can later be separated if scale or team ownership demands it.

### Client

- Responsive web application
- Server-rendered or hybrid rendering for fast content access and search indexing
- Accessible component system
- Print-specific styles for patient resources and quick-reference sheets
- Progressive web application capabilities may be added later

### Application layer

Suggested modules:

1. Identity and access
2. Organizations and membership
3. Content and publishing
4. Source documents and ingestion
5. Search and discovery
6. Workflows
7. Patient-resource rendering
8. Referrals and community resources
9. Community contributions and moderation
10. Notifications
11. Audit and analytics

### Data layer

- Relational database as the system of record
- Object storage for original documents, images, and generated files
- Search index for full-text and faceted search
- Background job system for ingestion, indexing, rendering, and notifications
- Cache for frequently accessed public and organization content

## Core domain concepts

### Tenant and access

- User
- Organization
- Membership
- Role
- Permission
- BrandingProfile

### Knowledge

- ContentItem
- ContentVersion
- ContentType
- Topic
- Tag
- Audience
- Jurisdiction
- Payer
- Plan
- Specialty
- ReviewRecord
- Citation

### Sources and ingestion

- SourceOrganization
- SourceDocument
- SourceDocumentVersion
- SourceSection
- IngestionRun
- ExtractionCandidate
- Requirement
- RequirementVersion
- ChangeEvent

### Workflows and resources

- Workflow
- WorkflowVersion
- WorkflowStep
- ConditionalRule
- ResourceTemplate
- ResourceVariant
- Packet
- OrganizationFork

### Directories and community knowledge

- DirectoryEntity
- ServiceOffering
- InsuranceParticipationClaim
- VerificationEvent
- CommunityReport
- ModerationDecision
- Corroboration

### Governance

- AuditEvent
- Notification
- CorrectionRequest
- PublicationState
- DataRetentionRule

## Content scope model

Every content item should carry an explicit scope:

- Global: maintained by Atlas editors
- Organization: visible within one organization
- Regional: shared within a defined geographic community
- Public: externally discoverable, when appropriate
- Personal: private draft or saved customization

A user may fork a global item into an organization scope. The fork retains the original relationship so Atlas can notify the organization when the source changes.

## Source ingestion pipeline

1. Acquire an approved public source or user-authorized document.
2. Store the original file and immutable metadata.
3. Extract text while preserving page and section anchors.
4. Classify source, payer, plan, jurisdiction, topic, and effective dates.
5. Generate candidate requirements, forms, exceptions, and definitions.
6. Present candidates to a qualified reviewer.
7. Publish approved structured records with citations.
8. Index the original and structured content.
9. Re-check the source according to its monitoring schedule.
10. Compare new versions and create review tasks for material changes.

Automated extraction must never silently overwrite reviewed content.

## Search architecture

Search should combine:

- full-text relevance
- metadata filters
- terminology synonyms
- organization-specific content priority
- source authority
- review status
- freshness
- user role and permissions

Answer summaries should be generated only from accessible, published content and should return citations to the exact underlying source sections.

## Patient-resource rendering

HTML should be the canonical representation. Each resource consists of:

- reviewed content blocks
- structured editable fields
- layout template
- organization branding profile
- language and reading-level metadata
- print rules

PDF generation can occur in the browser for simple use or through a server-side rendering service for consistent downloadable output. The underlying resource remains HTML and structured data.

## Identity and authorization

Use role-based access control with resource-level checks. Likely initial roles:

- Platform administrator
- Clinical editor
- Clinical reviewer
- Organization owner
- Organization manager
- Organization contributor
- Organization member
- Individual user

Authorization must be enforced on the server for every scoped object. Organization identifiers alone are not sufficient security boundaries.

## Auditability

Record significant actions, including:

- authentication and administrative access
- membership and role changes
- source imports
- extraction approvals and rejections
- content publication and supersession
- organization forks and customizations
- branding changes
- moderation decisions
- exports where appropriate

Audit records should be append-only from the application perspective.

## API strategy

Use a versioned internal API with explicit domain boundaries. The web application should consume the same business services that future integrations use. Do not expose a broad public API until authorization, rate limiting, content licensing, and support commitments are defined.

## Deployment approach

A cloud deployment should include:

- isolated production, staging, and development environments
- managed relational database
- encrypted object storage
- managed search service or well-supported search engine
- centralized logs and monitoring
- secret management
- automated backups and restore testing
- infrastructure as code
- CI checks for tests, dependencies, and security scanning

## Technology selection criteria

The final stack should favor team familiarity, maintainability, mature libraries, strong type safety, accessible web tooling, and dependable document rendering. Architecture decisions should not be locked until the implementation phase, but likely options include a TypeScript web stack with PostgreSQL and a mature search engine.

## Future regulated integration boundary

Any future feature that receives patient context, stores PHI, connects to an EHR, or performs patient-specific decision support should be treated as a separate architectural and compliance phase. It must not be introduced as a small extension to the PHI-free platform without a formal review.
