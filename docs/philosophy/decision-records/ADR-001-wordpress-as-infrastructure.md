# ADR-001: Use WordPress as Infrastructure

**Status:** Accepted

## Context

Atlas requires authentication, media handling, editorial revisions, administration, and a deployable foundation compatible with shared hosting. Building those capabilities from scratch would delay the product substantially.

## Decision

Atlas will use WordPress for infrastructure while implementing its domain model, business logic, permissions, APIs, and application interface in a first-party Atlas plugin.

## Consequences

- Atlas can ship useful functionality earlier.
- WordPress updates and conventions become an operational dependency.
- Business logic must remain outside themes and presentation templates.
- The interface should conceal irrelevant WordPress concepts from routine users.

## Alternatives Considered

- Fully custom CMS: rejected for initial cost and scope.
- Third-party plugin assembly: rejected because core behavior would be fragmented and subscription-dependent.
