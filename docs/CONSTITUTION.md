# Atlas Constitution

Status: Foundational

This document defines the non-negotiable principles of Project Atlas. Product, design, engineering, and content decisions should be tested against these principles.

## 1. No patient data by default

Atlas is not a patient record system. The platform must not request, require, or encourage the entry of protected health information. Features that could create a reasonable risk of collecting patient-identifying data require an explicit architecture and compliance review before implementation.

## 2. Traceability over authority

Every clinical, operational, payer, or educational statement should identify its source, version, publication date when known, and review status. Atlas must help users understand where information came from rather than asking them to trust an unexplained answer.

## 3. Evidence, policy, and experience are distinct

Atlas must clearly distinguish:

- Evidence-based clinical guidance
- Government or payer policy
- Organization-specific policy
- Community-reported operational experience
- Unverified drafts or contributions

These categories must never be visually or semantically blended.

## 4. Content is versioned, not silently overwritten

Published guidance, handouts, workflows, and source documents must retain version history. Material changes create a new version and preserve the prior record.

## 5. Review status is visible

Users must be able to see whether content is draft, under review, approved, published, due for review, superseded, or archived.

## 6. Organizations own their organization-specific data

An organization controls its branding, memberships, custom resources, internal workflows, and private operational notes. Atlas may not expose organization-private information to other tenants without explicit authorization.

## 7. Shared knowledge requires consent and context

Organization or individual contributions may be promoted to regional or public knowledge only through an intentional sharing action. Shared content must retain provenance, review status, timestamps, and appropriate attribution.

## 8. Least privilege is the default

Users receive only the permissions needed for their role. Access to organization administration, publishing, source ingestion, and platform-wide functions must be explicitly granted.

## 9. Atlas is modular

Each product area should have a clear boundary, public interface, and ownership model. Modules must not depend on undocumented behavior in unrelated modules.

## 10. Business logic is independent of presentation

Templates and user interfaces display outcomes. They do not own permission rules, publishing rules, source validation, workflow decisions, or persistence logic.

## 11. Every major capability exposes an API

Atlas features should be accessible through documented application services and API endpoints so future interfaces, integrations, and mobile applications do not require duplicating business logic.

## 12. HTML is the canonical printable format

Patient handouts, logs, quick-reference cards, and packets should be authored and rendered from structured content and print-safe HTML. PDF is an output format, not the source of truth.

## 13. Accessibility is a product requirement

Interfaces and printable resources should target WCAG 2.2 AA. Accessibility must be considered during design, not added after implementation.

## 14. Safety outranks convenience

Atlas must not present generated or community-derived material as approved clinical guidance without human review. Automation may assist extraction, classification, drafting, and change detection, but accountable users approve clinical and operational content.

## 15. Portability is preserved

Atlas may use WordPress infrastructure, but its domain logic, data model, and APIs should remain portable. WordPress is the initial application host, not an excuse for tightly coupled or undocumented code.

## 16. Shared hosting is a deployment constraint, not an architectural identity

The initial platform may run on shared hosting. Heavy document processing, OCR, embeddings, crawling, and long-running jobs must be isolated behind interfaces so they can move to external workers or managed services later.

## 17. Secure defaults

Atlas should use secure coding practices, input validation, output escaping, CSRF protection, capability checks, tenant checks, audit logging, and conservative file handling by default.

## 18. Clinical usefulness over feature volume

A feature belongs in Atlas only if it makes reliable information easier to find, understand, operationalize, teach, or maintain. Complexity without clinical or operational value is rejected.

## Decision test

Before approving a major decision, ask:

1. Does it risk collecting patient data?
2. Can users identify the source and review status?
3. Does it preserve tenant isolation and least privilege?
4. Is the business rule testable outside the interface?
5. Can the capability migrate beyond WordPress?
6. Does it make care delivery or clinical learning meaningfully easier?
