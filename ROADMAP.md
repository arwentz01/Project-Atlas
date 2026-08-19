# Project Atlas Product Roadmap

Atlas is a multi-tenant workforce operations platform for ambulatory practices, outpatient departments, and similar healthcare organizations. This document is the canonical product plan and should be updated as builds are completed and verified.

## Status definitions

- **Built:** Implemented in the repository.
- **Runtime verification required:** Implemented but awaiting the MAMP migration, audit, or browser workflow test.
- **Planned:** Not implemented.
- **Production gate:** Required before Atlas can be treated as production-ready.

## Current position

Atlas is approximately **0.7 in feature breadth** and **0.5 in production readiness**. Scheduling, Master Schedule, workforce workflows, mobile tools, labor, credentials, reporting, and manager operations exist. Authorization enforcement, automated tests, outbound delivery, security hardening, and production operations remain the largest gates.

## Phase 1: Application foundation

- [x] Custom PHP routing
- [x] Registration and login
- [x] Password hashing and eight-character minimum
- [x] Secure sessions and CSRF validation
- [x] Multi-tenant organizations and switching
- [x] MAMP/MySQL configuration
- [x] Database migrations, runtime audit, and system status
- [x] Password reset workflow
- [x] Email verification workflow
- [x] Login throttling and failed-attempt recording
- [x] User session management and revocation
- [x] Application error recording
- [ ] Production email delivery
- [ ] External error monitoring

**Acceptance:** recovery and verification tokens expire and are single-use; login abuse is throttled; users can revoke sessions; authenticated runtime audit passes.

## Phase 2: Organization builder

- [x] Locations, departments, positions, and supervisor groups
- [x] Automatic supervisor routing
- [x] Invitations and membership lifecycle
- [x] Resource editing and archiving
- [ ] Editable timezone and organization settings
- [ ] Operating hours and department scheduling defaults
- [x] Holiday and closure rules
- [ ] Organization branding settings
- [ ] Employee CSV import foundation with validation preview
- [ ] Archive confirmation workflow

**Acceptance:** every setting is organization-scoped; archived resources retain history; imports reject invalid or duplicate rows without partial writes.

## Phase 3: Workforce directory

- [x] Unified employee workspace
- [x] Primary workforce assignment
- [x] Employment type and expected hours
- [x] Availability, time off, credentials, callouts, and work history
- [ ] Employment status and effective dates
- [ ] Secondary locations, departments, and positions
- [ ] Restricted manager notes
- [ ] Onboarding checklist
- [ ] Profile snapshots and offboarding foundation
- [x] Search and filters
- [ ] Secure employee document storage

**Acceptance:** employee information is tenant-isolated; manager notes are restricted; historical schedules remain after offboarding; assignments respect effective dates.

## Phase 4: Scheduling foundation

- [x] Open, assigned, filled, and cancelled shifts
- [x] Exact, selected-position, and eligibility-group rules
- [x] Qualification, availability, overlap, location, and department checks
- [x] Seven-day employee schedule board
- [x] Location and department filters
- [x] Direct and drag-and-drop editing
- [x] Ranked replacement recommendations
- [x] Before-and-after shift history
- [ ] Undoable schedule changes
- [ ] Daily, weekly, monthly, and print views
- [x] Draft and published state visibility
- [ ] Bulk shift selection and editing foundation
- [ ] Overnight-shift validation

**Acceptance:** all moves are auditable; invalid assignments are blocked unless an authorized override is recorded; date views agree; large weeks remain usable.

## Phase 5: Master Schedule

Named and effective-dated baselines, bulk weekday entry, editing, versioning, employee totals, coverage validation, holidays, special hours, draft generation, conflict resolution, duplicate protection, and employee publication are built. Remaining refinement includes visual version comparison, restoration, cost previews, and complete automatic alternate-master substitution.

## Phase 6: Employee requests

Availability, preferences, time off, partial-day requests, trades, giveaways, partial coverage, recipient response, manager approval, withdrawal, and eligibility rechecking are built. Policy automation, balances, blackout rules, request calendars, and multi-level approval remain planned.

## Phase 7: Callouts and urgent coverage

Callout reporting, open replacement coverage, offers, responses, and manager selection are built. Escalation waves, deadlines, outbound delivery, late arrival, early departure, and attendance analytics remain planned.

## Phase 8: Coverage intelligence

Recurring requirements, provider/station/function coverage, priorities, daily gaps, and Master Schedule validation are built. Heatmaps, interval analysis, skill mix, float pools, break coverage, and forecasting remain planned.

## Phase 9: Scheduling command center

The initial unified queue is built. Direct resolution, urgency scoring, ownership, deadlines, snoozing, and readiness scoring remain planned.

## Phase 10: Roles, permissions, and scopes

Owner, administrator, scheduler, supervisor, and member roles exist with granular permission and scope storage. Complete server-side enforcement, temporary delegation, expiration, payroll masking, and a formal permission-matrix test suite are production gates.

## Phase 11: Notifications and communication

In-app notifications, account-wide messages, read state, and preferences are built. Email delivery, templates, quiet hours, digests, failure tracking, and publication notifications remain planned.

## Phase 12: Credentials and compliance

Credential types, verification, expiration, reminders, risk indicators, and eligibility integration are built. Secure uploads, renewals, requirements by position, and compliance forecasting remain planned.

## Phase 13: Time clock and labor

Clocking, breaks, review, pay profiles, overtime thresholds, previews, CSV export, and export history are built. Exceptions, missed punches, period locking, break compliance, and payroll integrations remain planned.

## Phase 14: Fairness and recommendations

Explainable eligibility and workload ranking exists. Adjustable weights, cost, undesirable-shift distribution, declined offers, bias monitoring, and recommendation-outcome comparison remain planned. Atlas recommendations must remain advisory.

## Phase 15: Mobile employee experience

Today, upcoming schedule, clocking, requests, trades, callouts, messages, alerts, and profile access are built. PWA installation, offline access, push notifications, calendar export, acknowledgment, dark mode, and accessibility remain planned.

## Phase 16: Reports and audit

Operational, fairness, labor, credential, payroll, administrator, and shift-change reporting exists. Expanded trend reports and saved filters remain planned.

## Phase 17: Search and navigation

Grouped, collapsible, scrollable navigation is built. Functional global search, keyboard navigation, recent pages, favorites, breadcrumbs, and saved views remain planned.

## Phase 18: Data import and export

Payroll and workforce CSV foundations exist. Additional structured imports, calendar export, portability, previews, duplicate detection, and recoverable rollback remain planned.

## Phase 19: Security and privacy

Prepared SQL, tenant ownership checks, CSRF protection, password hashing, and audit logs exist. A full authorization audit, security headers, rate-limit review, upload hardening, backup/restore testing, dependency review, and penetration testing are production gates. Atlas must not collect patient information or unnecessary employee health information.

## Phase 20: Quality assurance

The runtime audit exists. Automated authentication, tenant-isolation, permission, eligibility, Master Schedule, time-zone, payroll, mobile, accessibility, and performance tests remain planned.

## Phase 21: Production readiness

Production configuration, HTTPS, secure cookies, transactional email, backups, monitoring, queue processing, staging, deployment migrations, health checks, policies, documentation, and support workflows remain planned.

## Release milestones

| Release | Focus |
|---|---|
| 0.1 | Accounts, organizations, and structure |
| 0.2 | Workforce profiles and invitations |
| 0.3 | Scheduling and eligibility |
| 0.4 | Requests and callouts |
| 0.5 | Master Schedule and publishing |
| 0.6 | Weekly board and command center |
| 0.7 | Roles, notifications, and mobile |
| 0.8 | Reports, labor, and compliance |
| 0.9 | Security, tests, and performance |
| 1.0 | Production-ready workforce operations |

## Immediate build order

1. Complete and runtime-test Phases 1 through 4.
2. Enforce the Phase 10 permission matrix server-side.
3. Connect transactional email and event-driven notifications.
4. Build automated tenant-isolation and scheduling tests.
5. Complete the production security and operations gates.
