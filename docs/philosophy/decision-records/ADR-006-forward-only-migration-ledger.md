# ADR-006: Use a Forward-Only Migration Ledger

**Status:** Accepted

## Context

WordPress activation may be repeated, shared-hosting requests may overlap or time out, and MySQL DDL is not reliably transactional. A single numeric option cannot describe partially applied or independently completed migrations.

## Decision

Atlas uses discoverable migration objects with stable unrestricted numeric identifiers, an authoritative dedicated completion ledger, additive schema changes, and an owner-token option lock with bounded expiry. Migrations inspect state and resume forward; Atlas does not automatically roll schema backward. The historical database-version option remains a derived compatibility indicator.

## Consequences

- Clean installs, upgrades, and retries use the same runner and discovery path.
- Partial DDL must be explicitly inspected and safely resumed by each migration.
- Inventory defects block execution and appear in privileged diagnostics.
- Web execution is bounded and can continue in a later authenticated request.
- MySQL-version validation remains a release responsibility.

## Alternatives Considered

- One schema-version option: rejected because it cannot safely represent partial work.
- Transactional rollback: rejected because supported MySQL DDL cannot guarantee it.
- A third-party migration framework: rejected to keep the deployable shared-hosting plugin self-contained.
