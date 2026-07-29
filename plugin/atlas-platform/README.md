# Atlas Platform Plugin

This directory contains the installable WordPress plugin for Project Atlas.

## Current foundation (0.22.0)

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

## Install manually

1. Copy `plugin/atlas-platform` into `wp-content/plugins/atlas-platform`.
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
