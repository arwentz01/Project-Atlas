# Project Atlas

Project Atlas is a standalone PHP 8 + MySQL healthcare operations application. WordPress has been removed from the runtime. Atlas owns its routing, authentication, application shell, and future data model directly.

## Why this reset happened

The initial visual prototype was built as a WordPress plugin, but Atlas did not use WordPress CMS features. Repeated routing friction showed that the CMS runtime was adding complexity without delivering enough value. The repository history preserves the prototype, but `main` now contains the standalone application.

## Reused proven patterns

- Session/authentication approach adapted from CTSMD Connect.
- PDO/env-loading pattern adapted from CTSMD Connect.
- Front-controller `.htaccess` pattern adapted from Koravik.
- Atlas visual language and product concepts retained from the WordPress prototype.

No CTSMD theatre-specific or Koravik event/health product code is copied into Atlas.

## Local setup (MAMP)

1. Put the repository at `/Applications/MAMP/htdocs/Atlas` (or another MAMP web-root folder).
2. Copy `.env.example` to `.env` and adjust the database values if needed.
3. Create a MySQL database named `atlas`.
4. Run `database/schema.sql` against that database.
5. Create an administrator from Terminal:

```bash
cd /Applications/MAMP/htdocs/Atlas
/Applications/MAMP/bin/php/php8.3.30/bin/php bin/create-user.php andrew@example.test 'Choose-an-8+-character-password' 'Andrew'
```

6. Open `http://localhost/Atlas/` and sign in.

Atlas uses an 8-character minimum password length.

## Current standalone experience

- `/` — Home 2.0 with continue-work, guidance review, recent work, and high-value actions
- `/resources` and `/resources/home-oxygen`
- `/insurance` plus payer-rule detail routes
- `/playbooks` plus guided Playbook detail routes
- `/knowledge-base` plus local-standard detail routes
- `/patient-resources` plus printable patient handout previews
- `/patient-resources/packet-builder`
- `/search` — unified search across the Atlas fixture catalog
- `/workspace`
- `/profile`
- `/login`

The router automatically strips the installation folder from the request path, so the same code works when Atlas is installed under `/Atlas` locally or at a domain root in production.

## Visual builds 011–015

### Build 011 — Insurance Workspace
Payer-rule library and detail experience with current-source context, effective/review dates, documentation requirements, common misses, and connected Atlas guidance.

### Build 012 — Playbooks + Connected Workflow
Guided Playbook library/detail experience. `Arrange Home Oxygen` demonstrates the core Atlas chain from need → payer requirement → documentation → supplier handoff → patient education.

### Build 013 — Knowledge Base + Unified Search
Local policy/SOP/standard library with visible ownership and review status, plus one search surface spanning Insurance, Playbooks, Knowledge Base, Patient Resources, and core Resources.

### Build 014 — Patient Resources + Packet Builder
Plain-language handout previews, staff tools, print presentation, Packet Builder, and an explicit PHI-free boundary.

### Build 015 — My Workspace + Profile + Home 2.0
Personal working-set concept, recent/saved guidance, Playbook continuation, organization-context concept, profile surface, and a home dashboard centered on operational work rather than generic module counts.

## Fixture boundary

Current product content is intentionally defined in `src/Fixtures.php`. It is visual/demo content and does not create clinical records or fake persistence. `src/AtlasViews.php` owns the visual module renderers. This keeps the product pass easy to remove or replace when production repositories/services are introduced.

## Security foundation

- PHP sessions with HttpOnly and SameSite=Lax cookies
- session ID regeneration on login/logout
- `password_hash()` / `password_verify()`
- prepared PDO statements with emulated prepares disabled
- CSRF token verification for state-changing POST requests
- authenticated-by-default routes
- 8-character minimum password length
- no PHI fields in the current visual/product foundation

## Next development phase

The visual product surface is broad enough to begin the functional pass selectively. Production persistence, organization authorization, audit, content governance, saved-item state, Playbook progress, and authoring should be implemented only where the approved experience requires them.
