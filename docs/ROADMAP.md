# Product Roadmap

This roadmap is intentionally staged. Atlas should become useful before it becomes enormous.

## Phase 0: Discovery and foundations

### Goals

- Validate the problem with working clinicians and clinic managers
- Define product boundaries and editorial governance
- Select the implementation stack
- Design the core data model and information architecture
- Identify initial source domains and content reviewers

### Deliverables

- User interviews and workflow maps
- Prioritized MVP requirements
- Content taxonomy
- Trust and publication model
- Initial UX flows and wireframes
- Technical proof of concept for document ingestion, citations, search, and print rendering
- Legal, licensing, and compliance review plan

## Phase 1: Core platform and organization workspace

### Goals

Establish the multi-tenant foundation and a trustworthy content-management workflow.

### Features

- Accounts and authentication
- Organization creation and staff invitations
- Roles and permissions
- Global, organization, and personal content scopes
- Structured content editor
- Draft, review, publish, supersede, and archive lifecycle
- Version history
- Source citations and review metadata
- Basic full-text and filtered search
- Audit events
- Organization branding profile

### Exit criteria

- A manager can create an organization and delegate staff
- An editor can publish a source-cited resource
- Organization content is isolated correctly
- Users can find global and organization resources through one search experience

## Phase 2: Patient education and printable resources

### Goals

Deliver immediate everyday value to clinics through reusable, modern materials.

### Features

- Resource templates
- Controlled customization fields
- Organization logos, contact blocks, and footers
- High-quality print layouts
- Browser and server-side PDF output
- Saved organization variants
- Basic packet builder
- Initial resource set, such as injection education and common tracking logs
- Accessibility and reading-level review

### Exit criteria

- A user can select a reviewed resource, apply organization branding, save it, and print a consistent PDF
- A global update can be identified without overwriting local customization

## Phase 3: Payer document ingestion and operational workflows

### Goals

Turn official public documents into reviewed, actionable healthcare operations knowledge.

### Features

- Source document import and storage
- Text extraction with page anchors
- Metadata classification
- Candidate requirement extraction
- Human review queue
- Structured payer requirements
- Source-version comparison
- Workflow builder
- Forms and documentation checklists
- Source-change notifications

### Exit criteria

- A reviewer can import an official document and publish cited requirements
- A clinician can find a requirement and follow a practical workflow back to the exact source
- A changed source creates a review task rather than silently changing published guidance

## Phase 4: Clinical skills and laboratory learning

### Goals

Expand Atlas into a point-of-need professional learning resource.

### Features

- Clinical skills content type
- Supply, technique, safety, troubleshooting, and escalation sections
- Organization policy overlays
- Laboratory test reference content type
- Related-test and pattern linking
- Role-specific depth
- Quick-reference print views

### Exit criteria

- Content is concise, reviewed, source-cited, and clearly separated from patient-specific decision making
- Organization policy can be shown alongside global educational content without being confused with it

## Phase 5: Referral and regional resource intelligence

### Goals

Capture local operational knowledge while being honest about uncertainty and freshness.

### Features

- Directory entities and services
- Insurer-directory and public-data imports
- Organization preferred-resource lists
- Verification dates and confidence states
- Community-reported experiences
- Moderation and corroboration
- Regional sharing
- Reverification and expiration queues

### Exit criteria

- Users can distinguish official directory claims, organization-maintained facts, and community experience
- Stale information is visibly flagged and queued for review

## Phase 6: Advanced discovery and automation

### Potential features

- Guided clinical-operational navigator
- Source-grounded answer generation
- Automated change monitoring
- Multilingual resources and translation review
- Advanced packet assembly
- Public API
- Single sign-on
- Mobile or progressive web application
- Carefully scoped EHR launch integrations

## Explicitly separate future phase

Patient-specific features, PHI storage, automated submissions, or EHR data exchange require a separate business, security, compliance, and architecture decision. They are not implied by this roadmap.

## Suggested first content pilot

A focused pilot should cover one operational domain and one patient-resource domain. For example:

- Durable medical equipment requirements for a small set of common items and payers
- A polished set of injection handouts and blood-pressure or blood-glucose logs

This combination tests the hardest content-provenance problem while also delivering something clinicians can use immediately.
