# Atlas Permission Matrix

Status: Initial security and product baseline

Atlas uses two permission layers:

1. Platform capabilities assigned through WordPress roles and Atlas platform roles
2. Object and tenant authorization based on organization membership, ownership, scope, and workflow state

A WordPress capability alone is never sufficient to access another organization's data.

## Proposed roles

### Platform Administrator

Manages platform configuration, organization lifecycle, global content, security, and moderation. This role is not intended for ordinary organization administrators.

### Platform Clinical Editor

Creates and edits platform-scoped clinical and educational content but cannot publish without reviewer authority unless separately granted.

### Platform Clinical Reviewer

Reviews, approves, rejects, and publishes platform-scoped clinical content.

### Organization Administrator

Manages one organization's membership, branding, private resources, workflows, packets, and sharing preferences.

### Organization Editor

Creates and edits organization-scoped resources and workflows.

### Organization Reviewer

Reviews and approves organization-scoped content.

### Clinician

Searches and uses published resources, creates personal drafts or packets where enabled, and submits community reports.

### Learner

Uses published educational and reference content but has limited contribution privileges.

### Guest

Accesses only explicitly public material.

## Capability matrix

| Action | Platform Admin | Platform Editor | Platform Reviewer | Org Admin | Org Editor | Org Reviewer | Clinician | Learner | Guest |
|---|---:|---:|---:|---:|---:|---:|---:|---:|---:|
| Manage platform settings | Yes | No | No | No | No | No | No | No | No |
| Create organization | Yes | No | No | Request | No | No | No | No | No |
| Suspend organization | Yes | No | No | No | No | No | No | No | No |
| Manage own organization branding | Yes | No | No | Yes | No | No | No | No | No |
| Invite organization users | Yes | No | No | Yes | No | No | No | No | No |
| Assign organization roles | Yes | No | No | Yes, constrained | No | No | No | No | No |
| View organization-private content | Yes, audited | No | No | Own org | Own org | Own org | Own org published | Own org published | No |
| Create platform resource | Yes | Yes | Yes | No | No | No | No | No | No |
| Publish platform resource | Yes | No | Yes | No | No | No | No | No | No |
| Create organization resource | Yes | No | No | Own org | Own org | Own org | Personal draft | No | No |
| Publish organization resource | Yes | No | No | Own org | No | Own org | No | No | No |
| Create personal packet | Yes | Yes | Yes | Yes | Yes | Yes | Yes | Optional | No |
| Save organization packet | Yes | No | No | Own org | Own org | Own org | Submit only | No | No |
| Upload source document | Yes | Yes | Yes | Own org | Own org | Own org | No | No | No |
| Approve extracted requirement | Yes | No | Yes | Own org if authorized | No | Own org | No | No | No |
| Submit community report | Yes | Yes | Yes | Yes | Yes | Yes | Yes | Optional | No |
| Moderate public community report | Yes | No | Optional | No | No | No | No | No | No |
| Export organization data | Yes, audited | No | No | Own org | No | No | No | No | No |
| View audit history | Yes | No | No | Own org limited | No | No | Own actions | Own actions | No |

## Initial WordPress capabilities

Suggested capabilities for the plugin foundation:

```text
atlas_access
atlas_manage_platform
atlas_manage_organizations
atlas_manage_own_organization
atlas_manage_members
atlas_manage_branding
atlas_create_resources
atlas_edit_resources
atlas_review_resources
atlas_publish_resources
atlas_upload_sources
atlas_review_extractions
atlas_manage_workflows
atlas_create_packets
atlas_manage_directory
atlas_submit_community_reports
atlas_moderate_community_reports
atlas_view_audit_log
atlas_export_organization_data
```

Capabilities should be granular. Roles are convenient bundles, not hard-coded authorization rules.

## Required authorization checks

Every protected action must verify:

1. The user is authenticated
2. The user has the required capability
3. The user belongs to the relevant organization when the object is organization-scoped
4. The object belongs to the expected organization
5. The object's status permits the requested transition
6. The request includes valid CSRF protection or REST authentication
7. Sensitive actions generate an audit event

## Tenant isolation rule

Queries for organization-scoped data must include the organization identifier in the data-access layer. Filtering after retrieving a broad dataset is not acceptable.

## Privileged support access

Platform administrators may require support access to organization data. Any such access must:

- Be limited to authorized administrators
- Be auditable
- Display a clear support-access context
- Avoid impersonation where possible
- Never bypass content provenance or version history

## Open decisions

- Whether organization administrators may create additional custom roles
- Whether learners may save personal collections
- Whether organization reviewers may also publish by default
- Whether public anonymous access is part of the first release
- Whether support access requires an entered reason
