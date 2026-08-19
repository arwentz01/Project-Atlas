# Backup and restore procedure

Set `BACKUP_DIR` to an absolute directory outside the web root, then run `php bin/backup.php` from cron. Backups are created with restrictive permissions and pruned according to `BACKUP_RETENTION_DAYS`.

Validate each backup with `php bin/restore-check.php /path/to/backup.sql`. At least quarterly, restore the backup into an isolated staging database, run migrations, then run the check, audit, automated test, and security scripts. Record the restore date, operator, backup name, duration, and result outside Atlas.

Never test a restore over the production database.
