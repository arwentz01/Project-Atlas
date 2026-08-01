# Atlas Platform Plugin

This directory contains the installable WordPress plugin for Project Atlas.

## Current foundation (0.74.0)

- WordPress plugin bootstrap
- Namespace autoloader
- Dependency-injection container and dependency-aware module lifecycle
- Forward-only, locked migration ledger and filesystem discovery
- Central capability registry and structured operational logging
- Tools → Atlas Diagnostics administration page
- Activation and deactivation lifecycle
- Installed-version tracking
- Public REST health endpoint
- Organization and membership persistence boundary with read-only current-context API
- Source-aware Resource, immutable Resource Version, Source, and Citation read boundary
- Tenant-aware published resource search with bounded filters and answer-card metadata
- Source-aware resource detail and browser print presentation
- Idempotent editorial review/publication lifecycle with append-only audit events
- Organization branding and controlled, idempotent patient-resource variants
- Versioned, source-linked, tenant-aware operational workflows
- Protected release-readiness gate for manifest, migrations, modules, capabilities, PHP, and WordPress
- Read-only Atlas product preview with working demonstration search
- Conservative uninstall behavior that preserves data
- Deterministic WordPress administration menu registration with Atlas Home as
  the canonical parent destination
- Capability-aware application navigation and organization context across all
  Atlas administration screens
- Expiring organization invitations with email-bound acceptance and revocation
- Governed organization member roles and safe member removal
- Validated organization branding settings with an administration preview
- Structured resource discovery metadata captured during authoring
- Immutable resource history, revision creation, and archival controls
- Reviewer assignments, due dates, and append-only editorial notes
- Metadata-aware discovery across structured resource classifications
- Personal saved resources and reusable saved searches
- Patient packet builder with ordered Resource references
- Source document registry, anchored excerpts, extraction candidates, and reviewed payer requirement drafts
- Packet item/status management, source freshness/status tracking, filtered payer requirement discovery, and requirement review transitions
- REST-backed source workspace service for audited payer requirement creation, discovery, and review
- REST-backed packet creation, preview, item validation, audit events, and print-safe packet rendering
- Packet content resolver for printable Resource bodies, structured Requirement text, and visible provenance
- Source-anchored Requirement provenance for internal evidence review, including source document, page/section, excerpt, extraction statement, and source dates
- Patient packet guardrails that separate patient-facing materials from internal payer requirements
- Internal documentation checklists and requirement evidence detail for staff documentation workflows
- Patient packet resource picker API and catalog-backed patient-facing resource validation
- Immutable patient packet print snapshots
- Persistent internal documentation checklist state with audit events
- Packet readiness gates, snapshot history/detail reprint, and wp-admin patient resource picker
- Requirement review lanes, freshness alerts, checklist status controls, evidence audit state, and internal checklist export
- Explicit patient-facing/internal-only metadata normalization during resource authoring
- Non-PHI insurance profile registry, DME category catalog, and payer/DME requirement matching
- DME-specific payer requirement details for category slug, prior authorization, frequency limits, replacement intervals, required forms, and coverage criteria
- DME requirement matching now returns readiness summaries, missing-field lists, and ready-match counts
- Internal DME coverage summaries combine payer requirement details, readiness, checklist items, and source evidence without becoming patient packet content
- Internal prior authorization workups summarize next actions, blocking gaps, staff tasks, and required forms without external submission or PHI
- Filtered internal coverage-summary exports provide operational counts for ready requirements and prior-authorization prep
- Sources admin exposes the internal coverage-summary export JSON from the insurance and DME matching panel
- Prior Authorization Prep Workspace summarizes required workups, ready/blocked counts, task previews, and export access in the Sources admin
- DME Coverage Operations Dashboard summarizes readiness, prior authorization buckets, and action rows for published DME requirements
- Coverage Requirement Detail Workspace composes requirement text, readiness, prior authorization workup, checklist state, source evidence, and internal-only export access from a single requirement selection
- Immutable payer requirement revisions preserve append-only snapshots when requirements are created and when review status changes

## Install manually

1. Copy `plugins/atlas-platform` into `wp-content/plugins/atlas-platform`.
2. Activate **Atlas Platform** in WordPress.
3. Visit `/wp-json/atlas/v1/health` and confirm that the response reports `status: ok`.
4. Select **Atlas** in WordPress administration to explore the product preview.

## Development rules

- UI code calls REST endpoints or application services.
- Services own business logic.
- Repositories will be the only layer that directly accesses Atlas database tables.
- Every REST route enforces authorization server-side unless it is intentionally public, such as the health endpoint.
- Atlas data is preserved on ordinary plugin deactivation and uninstall.

## Runtime requirements

Atlas runs as a conventional plugin with committed PHP assets. Production does not require Composer, Node.js, a CLI, a worker, or a build process. The supported minimums are PHP 8.1 and WordPress 6.5; MySQL-compatible SQL is limited to syntax supported by MySQL 5.7 and 8.0.

## Core development

See [`../../docs/CORE_ARCHITECTURE.md`](../../docs/CORE_ARCHITECTURE.md) for container, module, migration, capability, logging, diagnostics, and verification guidance.

For a disposable WordPress installation, see [`../../docs/WORDPRESS_INTEGRATION_TESTS.md`](../../docs/WORDPRESS_INTEGRATION_TESTS.md).

## Current vertical slice

The Organizations foundation provides scoped repository contracts, current-context resolution, tenant authorization, MySQL-compatible tables, `GET /wp-json/atlas/v1/organizations/current`, and idempotent organization onboarding. Invitations, switching, and deletion remain intentionally deferred.

The Resources foundation provides immutable versions, sources, citations, tenant-aware reads and search, idempotent draft authoring, and editorial review/publication transitions. A complete administration UI and seed content remain future builds.

`GET /wp-json/atlas/v1/resources` provides bounded search over published global and current-organization resources with optional type, page, and page-size parameters.

