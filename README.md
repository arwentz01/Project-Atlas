# Project Atlas

Project Atlas is a front-end healthcare operations workspace delivered as a WordPress plugin. WordPress provides infrastructure; Atlas owns the routine user experience.

## Development direction

Atlas is being developed visual-first. Each product area is first represented as a complete front-end workflow backed by isolated fixture data. Production persistence and services are added only after the experience is approved.

## Local install

1. Copy this repository into `wp-content/plugins/project-atlas`.
2. Activate **Project Atlas** in WordPress.
3. Sign in and visit `/atlas`.

Activation registers Atlas front-end routes and flushes rewrite rules once.

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

## Demo data and cleanup

All seeded content is code-only fixture data in `src/Support/Fixtures.php`. It does **not** create WordPress posts, users, options, custom tables, or other database rows.

Disable demo content globally with:

```php
add_filter('atlas_enable_demo_data', '__return_false');
```

The production path is intentional: approved visual workflows can later receive repository/service-backed data without deleting fixture records from WordPress because no fixture records were written there in the first place.

## Hosting constraints

Production code targets shared hosting with PHP 8.1+ and WordPress/MySQL. No Node, npm, background worker, Redis, Docker, or server build step is required.

## Visual build log

- **Build 001 / 0.1.0:** Atlas application shell, authenticated front-end entry, responsive navigation, home dashboard, accessible base components, fixture boundary.
- **Build 002 / 0.2.0:** Resource Library search/filter experience, trust metadata, resource detail, related guidance, print presentation.
- **Build 003 / 0.3.0:** Insurance workspace, payer requirement directory, source/effective-date context, practical requirement detail and documentation checklist.
- **Build 004 / 0.4.0:** Playbooks library and guided-use detail, ordered steps, warnings, required documentation, cross-links to payer and resource guidance.
- **Build 005 / 0.5.0:** Knowledge Base library/detail, ownership and review context, local-policy distinction, cross-module navigation polish.

## Important boundary

These five builds are a **visual product foundation**, not the final persistence architecture. Business services, repositories, schema, tenant enforcement, audit records, and production authoring workflows should be added only after the front-end experience is reviewed and accepted.
