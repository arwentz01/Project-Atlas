# Project Atlas Product Roadmap

Atlas is a multi-tenant workforce operations platform for ambulatory practices, outpatient departments, and similar healthcare organizations. This document is the canonical product plan and should be updated as builds are completed and verified.

## Status definitions

- **Built:** Implemented in the repository.
- **Runtime verification required:** Implemented but awaiting the MAMP migration, audit, or browser workflow test.
- **Planned:** Not implemented.
- **Production gate:** Required before Atlas can be treated as production-ready.

## Current position

Atlas is approximately **0.9 in feature breadth** and **0.75 in production readiness**. Scheduling, Master Schedule, workforce workflows, mobile tools, labor, credentials, reporting, search, portability, security controls, automated checks, and deployment tooling exist. Full legacy authorization enforcement, external delivery providers, browser/load testing, independent security review, and verified hosting operations remain the largest gates.

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
- [x] Editable timezone and organization settings
- [x] Operating hours and department scheduling defaults
- [x] Holiday and closure rules
- [x] Organization branding settings
- [x] Employee CSV import foundation with validation preview
- [x] Archive confirmation workflow

**Acceptance:** every setting is organization-scoped; archived resources retain history; imports reject invalid or duplicate rows without partial writes.

## Phase 3: Workforce directory

- [x] Unified employee workspace
- [x] Primary workforce assignment
- [x] Employment type and expected hours
- [x] Availability, time off, credentials, callouts, and work history
- [x] Employment status and effective dates
- [x] Secondary locations, departments, and positions
- [x] Restricted manager notes
- [x] Onboarding checklist
- [x] Profile snapshots and offboarding foundation
- [x] Search and filters
- [x] Secure employee document storage

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
- [x] Undoable schedule changes
- [x] Daily, weekly, monthly, and print-ready views
- [x] Draft and published state visibility
- [x] Bulk shift selection and editing foundation
- [x] Overnight-shift validation

**Acceptance:** all moves are auditable; invalid assignments are blocked unless an authorized override is recorded; date views agree; large weeks remain usable.

## Phase 5: Master Schedule

Named and effective-dated baselines, bulk weekday entry, editing, versioning, employee totals, coverage validation, holidays, special hours, draft generation, conflict resolution, duplicate protection, and employee publication are built. Version restoration is supported through versioned baseline duplication. Visual comparison, cost preview, and automatic alternate-master substitution remain refinement work.

## Phase 6: Employee requests

Availability, preferences, time off, partial-day requests, trades, giveaways, partial coverage, recipient response, manager approval, withdrawal, and eligibility rechecking are built. Policy definitions, balances, blackout periods, and configurable approval levels are now built in the Operations Console. Calendar refinement and automatic multi-level routing remain planned.

## Phase 7: Callouts and urgent coverage

Callout reporting, open replacement coverage, offers, responses, manager selection, escalation waves, response deadlines, late arrivals, early departures, no-shows, and 90-day attendance analytics are built. Outbound delivery remains planned.

## Phase 8: Coverage intelligence

Recurring requirements, provider/station/function coverage, priorities, daily gaps, Master Schedule validation, interval forecasts, skill-mix notes, float-pool counts, break coverage, and demand-versus-assigned gap indicators are built. A richer visual heatmap remains planned.

## Phase 9: Scheduling command center

The unified queue now supports direct resolution, urgency scoring, ownership, deadlines, 24-hour snoozing, and readiness-oriented coverage indicators.

## Phase 10: Roles, permissions, and scopes

Owner, administrator, scheduler, supervisor, and member roles exist with granular permission and scope storage. Server-side capability and resource-scope enforcement now protects the advanced operations actions, including temporary delegated access with expiration. Extending the policy check across every legacy handler, payroll masking, and a formal permission-matrix test suite remain production gates.

## Phase 11: Notifications and communication

In-app notifications, account-wide messages, read state, preferences, organization templates, quiet hours, digest cadence, delivery queueing, failure tracking, and publication notifications are built. Sending queued email and web-push deliveries still requires a production delivery worker and provider.

## Phase 12: Credentials and compliance

Credential types, verification, expiration, reminders, risk indicators, eligibility integration, secure source uploads, renewal requests, requirements by position, and employee compliance forecasting are built.

## Phase 13: Time clock and labor

Clocking, breaks, review, pay profiles, overtime thresholds, previews, CSV export, export history, break-compliance scanning, exception resolution, and enforced period locking are built. Automated missed-punch generation and payroll-provider integrations remain planned.

## Phase 14: Fairness and recommendations

Explainable eligibility and workload ranking now includes adjustable weights for hours, openings, closings, weekends, declined offers, and estimated cost. Recommendation outcomes and manager overrides can be recorded and compared. Atlas recommendations remain advisory and do not infer protected traits.

## Phase 15: Mobile employee experience

Today, upcoming schedule, clocking, requests, trades, callouts, messages, alerts, profile access, PWA installation, cached offline shell access, push preferences, calendar export, shift acknowledgment, dark mode, keyboard focus, reduced motion, and mobile safe-area support are built. Actual web-push delivery requires a production push provider.

## Phase 16: Reports and audit

Operational, fairness, labor, credential, payroll, administrator, and shift-change reporting now includes monthly staffing, open-shift, callout, and approved-time-off trends with reusable saved report filters.

## Phase 17: Search and navigation

Grouped, collapsible, scrollable navigation now includes organization-scoped global search, slash-key focus, Escape clearing, recent-page history, favorites, breadcrumbs, and reusable report, schedule, and search views.

## Phase 18: Data import and export

Payroll, workforce, structure, and coverage CSV foundations now include validation previews, all-or-nothing commits, database duplicate protection, recoverable rollback when records are unused, employee calendar export, and organization JSON portability.

## Phase 19: Security and privacy

Prepared SQL, tenant ownership checks, CSRF protection, password hashing, audit logs, CSP and browser security headers, protected private storage, production security checks, backup validation, environment hardening, and security/privacy policies are built. Independent authorization review, dependency scanning, vulnerability scanning, and penetration testing remain production gates. Atlas must not collect patient information or unnecessary employee health information.

## Phase 20: Quality assurance

The runtime audit now has an automated test runner and continuous-integration syntax, manifest, and security checks covering core authentication primitives, tenant ownership, repository queries, saved views, mobile assets, and private storage. Full browser automation, accessibility scanning, load testing, and expanded workflow fixtures remain release gates.

## Phase 21: Production readiness

Production configuration templates, secure-cookie controls, backup and restore tools, queue processing, deployment checks, migration commands, JSON health checks, environment readiness UI, privacy/security/runbook documentation, and support workflows are built. The hosting environment must still provide HTTPS certificates, transactional email and push adapters, monitoring credentials, scheduled backups, queue supervision, staging, and an independently verified production deployment.

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

1. Run the full migration, audit, automated test, and security suite under MAMP PHP.
2. Extend the Phase 10 permission policy across every legacy action handler.
3. Connect transactional email and web-push transport adapters.
4. Add browser, accessibility, tenant-isolation, scheduling, and performance test suites.
5. Complete independent security review and a verified staging-to-production release rehearsal.
