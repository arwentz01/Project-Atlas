# Atlas Staffing

Atlas Staffing is a visual-first ambulatory workforce scheduling platform for primary care, specialty practices, outpatient offices, and ambulatory surgery centers.

This checkpoint establishes the product interface and information architecture before workflow logic is connected.

## Included in this visual checkpoint

- Multi-tenant organization shell and organization switcher
- Ambulatory staffing dashboard
- Weekly schedule builder
- Daily provider, station, and work-function coverage board
- People directory with organization-defined positions
- Departments and supervisor-group routing
- Responsive staff and manager navigation
- PHI-free demo fixtures

## Intentionally deferred

- Authentication and invitations
- Database persistence
- Self-scheduling rules and approvals
- Rotations
- Messaging
- Time clock and attendance
- Payroll and labor-cost modules
- External integrations

## Local setup

1. Copy `.env.example` to `.env` and add local database credentials.
2. Point MAMP at the repository directory or place it under the MAMP document root.
3. Open the project URL. Apache rewrite rules route application pages through `index.php`.

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

The current data is deliberately fixture-backed so the visual language can be approved before database behavior is implemented.
