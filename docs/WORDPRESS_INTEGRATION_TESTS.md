# WordPress Integration Gate

Atlas includes a database-backed integration suite for a disposable WordPress installation. The suite uses the deployed plugin, WordPress bootstrap, Atlas autoloader, production migration discovery, active database prefix, REST server, roles, and capabilities. It is not a substitute for browser accessibility review or the complete MySQL-version matrix.

## Prerequisites

- A disposable WordPress 6.5 or newer installation.
- PHP 8.1 or newer.
- A reachable MySQL 5.7 or MySQL 8.0 database.
- No production, personal, or patient data.
- Atlas source available at `plugin/atlas-platform`.

Do not store database or administrator credentials in this repository. WordPress may use its normal `wp-config.php`, or secrets may be supplied through environment-specific configuration outside Git.

## Install the development plugin link

```bash
ATLAS_TEST_WP_PATH=/workspace/wordpress php tests/install-atlas-plugin.php
```

The installer refuses to replace an existing plugin directory or a symlink that points elsewhere.

## Run

```bash
ATLAS_TEST_WP_PATH=/workspace/wordpress php tests/run-wordpress-integration.php
```

Exit code `2` means the required WordPress environment is unavailable. Exit code `1` means setup or an assertion failed. Exit code `0` means every executed assertion passed.

## Covered behavior

- Atlas is installed and active through WordPress.
- Core modules boot through the production application.
- Expected administrator capabilities are assigned.
- Activation leaves no pending migrations.
- Running the complete migration runner twice makes no further changes.
- Migration inventory has no malformed files, duplicates, or gaps.
- The health route registers and returns its stable public fields.
- The health response excludes absolute WordPress paths and SQL details.
- Atlas navigation registers once with `atlas_access`.
- Navigation and canonical route inventory use the same capability.
- A disposable subscriber lacks Atlas, diagnostics, and migration capabilities.

The suite creates and removes a disposable subscriber. Run it only against a resettable test installation.

## Still manual or separately automated

- Invalid diagnostics and migration nonces, because the current admin-post callbacks terminate requests after redirect or `wp_die()`.
- Browser rendering, CSS loading, responsive layouts, keyboard navigation, zoom, and screen-reader behavior.
- Clean, previous-release, partial-DDL, and already-completed fixtures on both supported MySQL versions.
- Web-server and Bluehost-specific behavior.

These gaps must not be reported as passing until their respective environments are executed.
