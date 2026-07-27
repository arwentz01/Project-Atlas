# Atlas Domain Model

Status: Initial design baseline

## Ownership scopes

Every Atlas object has an explicit scope:

- `platform`: maintained by Atlas and available across the platform
- `organization`: private to one organization unless intentionally shared
- `personal`: visible to its owner until submitted or promoted
- `regional`: shared within a defined geographic or professional community
- `public`: available to all permitted Atlas users

Scope is separate from publication status.

## Core entities

### User

A WordPress user with Atlas-specific memberships, roles, preferences, and audit history.

### Organization

A clinic, practice, agency, educational program, or other participating group.

Key relationships:

- Has many memberships
- Has one branding profile
- Owns resources, workflows, packets, and private notes
- May contribute selected material to regional or public collections

### Organization Membership

Connects a user to an organization with one or more organization-level roles.

Important fields:

- Organization ID
- User ID
- Status
- Roles
- Invited by
- Joined at
- Last active at

### Resource

The common content identity for material users can find, review, reuse, print, or include in workflows.

Examples:

- Patient handout
- Clinical skill guide
- Lab reference
- Payer summary
- Community resource
- Form
- Quick-reference card

A resource has many versions and may have one specialized subtype.

### Resource Version

An immutable snapshot of a resource's content and metadata.

Includes:

- Version number
- Structured body
- Author
- Review status
- Effective date
- Review due date
- Change summary
- Source relationships

### Source Document

An official or contributed document used to support Atlas content.

Examples include CMS manuals, payer medical policies, state Medicaid guidance, manufacturer instructions, and organization policies.

### Source Document Version

An immutable version of a source document, including file identity, source URL, publisher, effective date, retrieved date, checksum, and extraction status.

### Citation

Connects a resource version, workflow step, or extracted claim to a precise source document version and optional page, section, or quoted passage.

### Workflow

A structured operational pathway that converts requirements into actionable steps.

Examples:

- Obtain a Medicare-covered hospital bed
- Prepare a prior authorization submission
- Assemble a diabetes teaching packet

### Workflow Version

An immutable approved or draft snapshot of a workflow.

### Workflow Step

An ordered instruction within a workflow version. A step may reference requirements, forms, resources, cautions, or decision branches.

### Requirement

A structured condition extracted or authored from policy.

Examples:

- Documentation requirement
- Eligibility criterion
- Frequency limit
- Prior authorization condition
- Required form

Requirements should retain exact provenance and must not be treated as approved until reviewed.

### Patient Packet

A saved, reusable collection of approved printable resources, ordering rules, and organization branding settings. It must not contain patient-identifying information.

### Branding Profile

Organization-controlled presentation settings:

- Logo
- Display name
- Contact block
- Footer
- Print preferences
- Optional approved color tokens

### Community Report

A time-stamped operational observation, such as a provider reportedly accepting a payer or a referral process requiring a recent lab.

Community reports are experiences, not verified policy. They include geography, reporter context, confidence, verification count, and expiration or recheck date.

### Directory Entry

A provider, vendor, agency, program, or community service that may be used in referrals or operational workflows.

### Review Assignment

Connects content to a reviewer and records requested, started, completed, rejected, or returned states.

### Audit Event

An append-only record of meaningful security, content, membership, sharing, and publishing actions.

### Notification

A user-facing event such as a review assignment, source change, expiring resource, invitation, or workflow impact alert.

## Resource specialization

Atlas uses a shared Resource identity with specialized domain data where needed.

```text
Resource
├── Patient Education Resource
├── Clinical Skill Guide
├── Lab Reference
├── Payer Policy Summary
├── Community Resource Guide
├── Form or Log
└── Quick Reference
```

This supports unified search and permissions while allowing subtype-specific fields.

## Primary relationships

```text
User ──< Organization Membership >── Organization
Organization ──< Resource
Resource ──< Resource Version
Resource Version ──< Citation >── Source Document Version
Source Document ──< Source Document Version
Workflow ──< Workflow Version ──< Workflow Step
Workflow Step ──< Requirement
Patient Packet ──< Packet Item >── Resource Version
Organization ──1 Branding Profile
Directory Entry ──< Community Report
Resource Version ──< Review Assignment
User/Organization/Resource ──< Audit Event
```

## Publication lifecycle

```text
Draft
→ In Review
→ Approved
→ Published
→ Review Due
→ Superseded or Archived
```

Rejected and returned-for-changes are review outcomes rather than publication states.

## First implementation slice

The first vertical slice should include:

1. Organization
2. Organization Membership
3. Branding Profile
4. Audit Event
5. Atlas roles and capabilities
6. REST endpoints and administration screens for organization management

Resources and workflows should build on this tenant foundation rather than precede it.
