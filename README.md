# Atlas Staffing

Atlas Staffing is a visual-first ambulatory workforce scheduling platform for primary care, specialty practices, outpatient offices, and ambulatory surgery centers.

The Master Schedule module keeps normal recurring weekly assignments separate from live callouts, approved time off, trades, and one-time changes. Administrators can maintain named, effective-dated baselines and generate duplicate-protected live weeks. Conflicting assignments become open shifts for review while the baseline remains unchanged.

Master Schedule also supports bulk weekday entry, assignment editing and archiving, versioned baseline copies, employee weekly-hour summaries, recurring coverage validation, holiday and special-hours rules, draft generation review, conflict resolution, week comparison, and employee publication. Master-generated assignments remain hidden from employee schedules until their generated week is published.

Atlas now includes a seven-day employee schedule board with drag-and-drop reassignment, scoped location and department filters, ranked replacement suggestions, a scheduling command center, unified employee workspaces, detailed shift change history, notification delivery preferences, operational roles and resource scopes, and a mobile-first employee home for shifts, clocking, requests, trades, callouts, messages, and alerts.

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
- Direct and group workforce messaging with unread state and an in-app notification inbox
- Deduplicated automatic alerts for callouts, pending approvals, submitted timesheets, and expiring credentials
- Recurring provider, station, and work-function coverage requirements with priority-aware daily gap detection
- Reusable shift templates that generate open or eligibility-checked assigned shifts alongside recurring rotations
- Explainable fairness metrics for hours, openings, closings, weekends, target variance, and eligibility-aware candidate guidance
- Date-range operational reports, workforce-hour exports, request/callout summaries, coverage indicators, and administrator audit history
- Credential catalog, employee credential records, verification state, expiration warnings, and compliance visibility
- Employee clock-in/out, optional shift linkage, break capture, timesheet submission, and manager approval
- Hourly and salary pay profiles, weekly overtime rules, approved-time payroll previews, gross estimates, and audited CSV exports
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

### Runtime audit

After migrating and creating the first organization, run the repository query audit with the same PHP binary used by MAMP:

```bash
/Applications/MAMP/bin/php/php8.3.30/bin/php bin/audit.php
```

The audit executes every major read path inside a rolled-back transaction and reports the exact failing repository method before browser testing.
