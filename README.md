# Project Atlas

Project Atlas is a front-end healthcare operations workspace delivered as a WordPress plugin. WordPress provides infrastructure; Atlas owns the routine user experience.

## Development direction

Atlas is being developed visual-first. Each product area is first represented as a complete front-end workflow backed by isolated fixture/demo data. Production persistence and services are added only after the experience is approved.

The current 1.0.1 visual milestone includes the first ten cumulative product builds plus a routing/diagnostics fix. It is intentionally not the final persistence, authorization, tenant, or authoring architecture.

## Local install

1. Place this repository in `wp-content/plugins/project-atlas`.
2. Activate **Project Atlas** in WordPress.
3. In WordPress admin, open **Atlas** to confirm route diagnostics.
4. Sign in and visit `/atlas`.
5. If **Settings → Permalinks** is set to **Plain**, choose a non-Plain structure and save once.

Atlas 1.0.1 includes a direct request-path fallback in addition to normal WordPress rewrite rules, and a wp-admin diagnostics screen showing the registered routes and permalink status.

## Current visual routes

- `/atlas`
- `/resources`
- `/resources/home-oxygen-qualification`
- `/insurance`
- `/insurance/medicare-dme-oxygen`
- `/playbooks`
- `/playbooks/arrange-home-oxygen`
- `/knowledgebase`
- `/knowledgebase/discharge-documentation-standard`
- `/patient-resources`
- `/patient-resources/home-oxygen-what-to-expect`
- `/patient-resources/packet-builder`
- `/profile`
- `/search`
- `/workspace`

## Demo data and cleanup

The visual builds do not create demo posts, custom tables, users, patient records, or clinical records. Core fixture content lives in `src/Support/Fixtures.php`; later visual modules contain only code-defined demo display content and are all gated by the same fixture switch.

Disable demo content globally with:

```php
add_filter('atlas_enable_demo_data', '__return_false');
```

No database cleanup is required to remove the current demo content because it is not persisted.

## PHI boundary

Atlas remains PHI-free by default. The Patient Resources and Packet Builder visual workflows explicitly avoid patient names, dates of birth, MRNs, account numbers, patient-specific diagnoses, notes, orders, or results.

## Hosting constraints

Production is intended for Bluehost-compatible shared hosting using PHP, WordPress APIs, MySQL-compatible SQL, HTML, CSS, and browser JavaScript. No Node, npm, Python, Redis, Docker, background worker, persistent process, or server build step is required.

## Visual build log

- **Build 001 / 0.1.0:** Atlas application shell, authenticated front-end entry, responsive navigation, home dashboard, accessible base components, fixture boundary.
- **Build 002 / 0.2.0:** Resource Library search/filter experience, trust metadata, resource detail, related guidance, print presentation.
- **Build 003 / 0.3.0:** Insurance workspace, payer requirement directory, source/effective-date context, practical requirement detail and documentation checklist.
- **Build 004 / 0.4.0:** Playbooks library and guided-use detail, ordered steps, warnings, required documentation, cross-links to payer and resource guidance.
- **Build 005 / 0.5.0:** Knowledge Base library/detail, ownership and review context, local-policy distinction, cross-module navigation polish.
- **Build 006 / 0.6.0:** Patient Resources library and printable patient handout preview with explicit no-PHI boundary and staff-facing cross-links.
- **Build 007 / 0.7.0:** Patient education Packet Builder with selectable materials, page-count preview, print flow, empty state, and no-PHI guardrails.
- **Build 008 / 0.8.0:** Profile and organization-context experience, workspace switch concept, role/context hierarchy, and documented future server-side authorization boundary.
- **Build 009 / 0.9.0:** Unified Atlas search across Resources, Insurance, Playbooks, Knowledge Base, and Patient Resources.
- **Build 010 / 1.0.0:** Personal Workspace for saved guidance, recent work concepts, packet continuation, and cross-module shortcuts.
- **1.0.1:** Direct-path route fallback plus wp-admin route/permalink diagnostics.

## Modular visual extensions

Beginning with Build 006, visual modules register routes and renderers through `src/FrontEnd/ModuleRegistry.php`. This keeps broad product experimentation from continuously expanding the core application controller. The module system is still a visual-development mechanism, not a substitute for the eventual service/repository architecture.

## Important boundary

These ten builds are a visual product foundation. Business services, repositories, production schema, tenant enforcement, audit records, saved-item persistence, packet persistence, organization membership, invitations, authoring, and production search should be implemented only after the front-end information architecture and workflow direction are reviewed and accepted.
