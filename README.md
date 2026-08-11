# Project Atlas

Project Atlas is a standalone PHP 8 + MySQL healthcare operations application. WordPress has been removed from the runtime. Atlas owns its routing, authentication, application shell, and future data model directly.

## Why this reset happened

The initial visual prototype was built as a WordPress plugin, but Atlas did not use WordPress CMS features. Repeated routing friction showed that the CMS runtime was adding complexity without delivering enough value. The repository history preserves the prototype, but `main` now contains the standalone application foundation.

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
/Applications/MAMP/bin/php/php8.3.30/bin/php bin/create-user.php andrew@example.test 'Choose-a-12+-character-password' 'Andrew'
```

6. Open `http://localhost/Atlas/` and sign in.

## Current standalone routes

- `/` — Atlas Home
- `/login`
- `/resources`
- `/resources/home-oxygen`
- `/insurance`
- `/playbooks`
- `/knowledge-base`
- `/patient-resources`
- `/search`
- `/workspace`

The router automatically strips the installation folder from the request path, so the same code works when Atlas is installed under `/Atlas` locally or at a domain root in production.

## Security foundation

- PHP sessions with HttpOnly and SameSite=Lax cookies
- session ID regeneration on login/logout
- `password_hash()` / `password_verify()`
- prepared PDO statements with emulated prepares disabled
- CSRF token verification for state-changing POST requests
- authenticated-by-default routes
- no PHI fields in the current visual/product foundation

## Development direction

Atlas remains visual-first. The next step is to restore the approved Insurance, Playbooks, Knowledge Base, Patient Resources, Search, and Workspace experiences on top of this standalone foundation, then add production repositories/services only where the approved experience requires them.
