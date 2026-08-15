# Atlas Staffing

Atlas Staffing is a visual-first ambulatory workforce scheduling platform for primary care, specialty practices, outpatient offices, and ambulatory surgery centers.

This checkpoint connects Atlas's multi-tenant organization foundation while preserving the approved visual system.

## Included in this visual checkpoint

- Account registration, login, and secure sessions
- Multi-tenant organization creation and switching
- Ambulatory staffing dashboard
- Weekly schedule builder
- Daily provider, station, and work-function coverage board
- Database-backed people directory and secure invitations
- Custom locations, departments, and positions
- Supervisor groups with automatic department routing
- Responsive staff and manager navigation
- PHI-free demo fixtures

## Intentionally deferred

- Self-scheduling rules and approvals
- Rotations
- Messaging
- Time clock and attendance
- Payroll and labor-cost modules
- External integrations

## Local setup

1. Copy `.env.example` to `.env` and add local database credentials.
2. Import `database/schema.sql` into the `atlas` database using phpMyAdmin, or run `php bin/migrate.php` with the MAMP PHP binary.
3. Point MAMP at the repository directory or place it under the MAMP document root.
4. Open the project URL and create the first Atlas account and organization.

For a quick PHP-only preview:

```bash
php -S localhost:8080 index.php
```

Then visit `http://localhost:8080`.

## Routes

- `/` overview
- `/schedule`
- `/coverage`
- `/team`
- `/organization`

Organization membership is checked server-side for every tenant-scoped action. Organization owners and administrators can change structure and issue invitations.
