# Atlas release gate

Atlas 0.15.0 formalizes two release gates.

1. `php tests/release-gate.php` verifies version agreement, every required deployable file, and exact migration-manifest parity without requiring WordPress.
2. `ATLAS_TEST_WP_PATH=/absolute/path/to/wordpress php tests/run-wordpress-integration.php` activates Atlas through WordPress, runs all migrations twice, checks the module and capability inventories, tests route registration, and directly denies protected routes to unauthenticated users and subscribers.

The WordPress gate must be executed against clean and upgrade databases. CI should provide MySQL 5.7 and MySQL 8.0 jobs. If one database version is unavailable, the release report must identify the missing job rather than implying it passed.

The gate intentionally uses the deployed plugin bootstrap, autoloader, migration directory, and database prefix behavior. It never requires WP-CLI in production.
