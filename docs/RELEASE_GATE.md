# Atlas release gate

Atlas 0.74.0 formalizes three release gates.

1. `php tests/run-all.php` runs the standalone Atlas test suite in isolated PHP processes.
2. `php tests/release-gate.php` verifies version agreement, every required deployable file, and exact migration-manifest parity without requiring WordPress.
3. `ATLAS_TEST_WP_PATH=/absolute/path/to/wordpress php tests/run-wordpress-integration.php` activates Atlas through WordPress, runs all migrations twice, checks the module and capability inventories, tests route registration, and directly denies protected routes to unauthenticated users and subscribers.

When the generic `php` command is unavailable on a local MAMP workstation, use the bundled runtime directly, for example `/Applications/MAMP/bin/php/php8.3.30/bin/php tests/run-all.php`.

The WordPress gate must be executed against clean and upgrade databases. CI should provide MySQL 5.7 and MySQL 8.0 jobs. If one database version is unavailable, the release report must identify the missing job rather than implying it passed.

The gate intentionally uses the deployed plugin bootstrap, autoloader, migration directory, and database prefix behavior. It never requires WP-CLI in production.
