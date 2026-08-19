# Atlas production runbook

## Release sequence

1. Deploy to staging and copy `.env.production.example` to `.env` outside source control.
2. Configure HTTPS, `APP_URL`, secure sessions, database credentials, backups, email, and monitoring.
3. Run `php bin/deploy-check.php`, `php bin/migrate.php`, `php bin/check.php`, `php bin/audit.php`, `php bin/test.php`, and `php bin/security-audit.php`.
4. Exercise registration, invitation, scheduling, publication, clocking, document upload, exports, and tenant switching in staging.
5. Create and validate a backup before production migration.
6. Deploy application files, run the migration once, verify `/index.php?route=health`, then start the queue worker through cron or a process supervisor.

## Monitoring

Monitor the health endpoint, PHP/Apache errors, application errors, failed notification deliveries, disk usage, backup age, queue depth, and database availability. Never include passwords, reset tokens, invitation tokens, employee documents, or message bodies in monitoring events.

## Rollback

Application code may be rolled back to the previous release. Database rollback must use a tested backup or an explicit forward-fix migration. Do not delete new columns or tables while a previous release could still reference them.
