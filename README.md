# Project Atlas

Project Atlas is a front-end healthcare operations workspace delivered as a WordPress plugin. WordPress provides infrastructure; Atlas owns the routine user experience.

## Development direction

Atlas is being developed visual-first. Each product area is first represented as a complete front-end workflow backed by isolated fixture data. Production persistence and services are added only after the experience is approved.

## Local install

1. Copy this repository into `wp-content/plugins/project-atlas`.
2. Activate **Project Atlas** in WordPress.
3. Sign in and visit `/atlas`.

Activation registers Atlas front-end routes and flushes rewrite rules once.

## Demo data

All seeded content is currently code-only fixture data in `src/Support/Fixtures.php`. It does not create posts, users, options, or database rows. Disable it globally with:

```php
add_filter('atlas_enable_demo_data', '__return_false');
```

This makes cleanup deterministic: replace the fixture provider with production repositories or disable the filter without deleting application content from WordPress.

## Hosting constraints

Production code targets shared hosting with PHP 8.1+ and WordPress/MySQL. No Node, npm, background worker, Redis, Docker, or server build step is required.

## Visual build log

- **Build 001 / 0.1.0:** application shell, authentication return flow, responsive navigation, dashboard, accessible base components, fixture boundary.
