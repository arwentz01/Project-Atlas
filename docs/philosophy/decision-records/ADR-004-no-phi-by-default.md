# ADR-004: Do Not Store PHI by Default

**Status:** Accepted

## Context

Atlas is intended for clinical operations, education, coverage guidance, and resource discovery. Its initial value does not require patient-specific records, and collecting PHI would substantially increase legal, security, operational, and hosting obligations.

## Decision

Atlas will not intentionally collect or store PHI in its initial product scope. Forms, schemas, logs, search, uploads, and workflows should be designed to avoid patient-identifying fields and discourage accidental entry.

## Consequences

- Atlas can focus on reusable knowledge and workflows.
- User interfaces must clearly warn against entering patient information where free text exists.
- Logging and analytics must avoid capturing sensitive free-text content unnecessarily.
- Future PHI features require a separate architecture and compliance review rather than incremental field additions.

## Alternatives Considered

- Design for PHI immediately: rejected because it adds substantial scope without being necessary for the initial product.
- Ignore PHI risk because Atlas is not an EHR: rejected because users may still enter sensitive information unless the product actively prevents it.
