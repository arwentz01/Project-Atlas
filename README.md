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
- Workforce profiles with employment type, expected hours, flex status, and qualifications
- Providers, teams, stations, work functions, and organization-defined qualifications
- Reusable position eligibility groups
- Open shifts with exact-position, selected-position, or eligibility-group rules
- Location, department, qualification, overlap, and cross-department eligibility checks
- Staff shift requests with plain-language eligibility results
- Provider, station, and work-function coverage assignments
- Date-specific ambulatory coverage board
- Draft, open, review, published, and archived schedule periods
- Manager shift assignment with audited eligibility overrides
- Week copying and shift cancellation
- Provider sessions with support-gap indicators
- Recurring one- to four-week rotations and shift generation
- Self-scheduling request review and approval
- Employee My Schedule workspace
- Recurring availability and date-specific exceptions
- Preferred hours, locations, departments, opening, and closing patterns
- Availability approval workflow and shift-eligibility integration
- Organization-defined paid and unpaid time-off categories
- Full-day and partial-day time-off requests with conflict visibility
- Time-off approval, denial, cancellation, and eligibility integration
- Shift giveaways and direct employee trades
- Partial-shift relief assignments
- Same-day callouts with urgent replacement offers, eligibility checks, acceptance tracking, and audited reassignment
- Recipient acceptance, manager approval, withdrawal, and expiration
- Swap-aware eligibility, overlap, availability, and qualification checks
- Responsive staff and manager navigation
- PHI-free demo fixtures

## Intentionally deferred

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

After pulling this checkpoint into an existing Atlas installation, run `php bin/migrate.php` again so the provider-session and rotation tables are created. Existing accounts, organizations, and schedules are preserved.

Run the `bin/check.php` script with the MAMP PHP binary to verify the PHP version, required extension, environment file, database connection, and migration state before browser testing. Organization administrators can also open the System Status page inside Atlas.
