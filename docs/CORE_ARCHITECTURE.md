# Atlas Core Architecture

Atlas Core is the production backbone of the WordPress plugin. It follows `UI → REST controller → service → repository → database`; only repositories and migrations may use `$wpdb` or issue SQL. Runtime paths are derived from the main plugin file and never from the current working directory.

## Container

`Plugin::container()` exposes the Atlas container. `atlas()` consistently returns the application. Bind interfaces in module `register()` methods:

```php
$container->singleton(Clock::class, WordPressClock::class);
$container->bind(Formatter::class, static fn (Container $c): Formatter => new Formatter());
```

Constructor autowiring is limited to named class/interface types. Scalar or ambiguous arguments require explicit factories. Circular and missing dependencies raise clear exceptions. Domain services should receive dependencies through constructors rather than calling `atlas()` as a service locator.

## Modules

A module implements `Core\Modules\Module`: stable `slug()`, `version()`, dependency slugs, `register(Container)`, `boot()`, and `health()`. Registration binds services without hooks; boot registers WordPress hooks and routes. The registry rejects duplicate slugs, detects missing/circular dependencies, boots topologically, and records failures. Modules communicate through injected services or interfaces, never another module's tables.

## Forward-only migrations

Migration files live in `plugin/atlas-platform/migrations` and use `<numeric-id>_<snake_case_name>.php`, for example `0077_add_index.php` or `1001_future_change.php`. Numeric identifiers have no fixed width or maximum, must match `Migration::id()`, and are naturally ordered. Each file returns one `Migration` object.

Every migration must inspect the existing schema before each mutation, verify that an existing object has the expected definition, use one DDL mutation per statement, be repeatable after interruption, and fail on unknown SQL errors. Never assume transactional DDL or implement automatic rollback. Long data work must use persisted, idempotent, bounded batches. The runner records completion only after `up()` succeeds, processes at most ten migrations per web request, and retains the legacy `atlas_platform_db_version` option as a convenient latest-completed value; the dedicated ledger is authoritative.

Discovery reports malformed files, duplicate IDs, and sequence gaps, including relative paths. The option-based lock uses an owner token, creation/expiry timestamps, atomic acquisition, owner-checked release, and a nonce/capability-protected stale-lock action. Migration errors must include operational identifiers and bounded diagnostics, never record data, secrets, PHI, absolute paths, or full payloads.

## Capabilities

All capability definitions live in `CapabilityRegistry`. Add new capabilities there with a human-readable label. Synchronization is additive: administrators receive expected platform capabilities, while Atlas never silently removes capabilities from any role. Navigation and destination authorization must use the same registered capability.

## Logging

Depend on `Logging\Logger`, not the WordPress implementation. Use a stable event name, safe message, bounded non-sensitive context, and module slug. Never log PHI, credentials, tokens, nonces, cookies, uploaded contents, full requests, or sensitive personal information. Errors are retained in a bounded WordPress option for privileged diagnostics; `WP_DEBUG` also sends structured JSON to the normal PHP error log. Operational logging is not a user audit trail.

## Diagnostics and routes

Administrators with `atlas_view_diagnostics` can use **Tools → Atlas Diagnostics**. Migration and stale-lock mutations use authenticated POST requests, `atlas_run_migrations`, and a nonce. The public `GET /wp-json/atlas/v1/health` response is intentionally bounded. `Core\Routes\RouteInventory` is the canonical inventory for REST, admin, and admin-post routes.

## Adding a module

1. Define service interfaces and repositories at the module boundary.
2. Implement the `Module` contract and declare stable dependency slugs.
3. Bind interfaces during `register()` and register hooks/routes during `boot()`.
4. Add the module to `Plugin::boot()` and the canonical route inventory.
5. Add behavioral tests for authorization, direct route access, tenant scope, retries, and failures.
6. Add deployable files to `release-manifest.json`.

## Manual verification

1. Activate on a clean WordPress 6.5+ installation and visit Tools → Atlas Diagnostics.
2. Run pending migrations, then run them again and confirm no migration is repeated.
3. Repeat from the previously released database and a deliberately partial migration state.
4. Confirm unauthorized users cannot open diagnostics or POST either mutation action; confirm invalid nonces fail.
5. Request `/wp-json/atlas/v1/health` directly and validate its JSON and lack of internal paths/errors.
6. Exercise clean, upgrade, partial, and completed states on MySQL 5.7 and MySQL 8.0.
7. Verify organization-scoped endpoints against cross-organization access when that vertical slice is implemented.

The repository's focused PHP tests do not replace live WordPress or database-version integration testing. `tests/migration-runner.php` uses the same discovery and runner classes with replaceable store and lock contracts to verify deterministic execution, second-run idempotency, partial-state resume, failure retry, failure recording rules, and lock contention without requiring `$wpdb`.
