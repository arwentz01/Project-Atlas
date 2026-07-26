# Project Atlas

Project Atlas is a healthcare operations and professional education platform for clinicians and healthcare organizations.

Status: discovery and product design.

Initial scope: the platform will not collect or store patient-identifiable information.

## Vision

Atlas will help clinicians find reliable operational guidance, official payer requirements, reusable patient education resources, clinical skills references, laboratory learning content, referral information, and organization-specific workflows.

Its focus is the gap between knowing what should be done and successfully getting it done within a complex healthcare system.

## Documentation

- docs/PRODUCT_PROPOSAL.md
- docs/FEATURE_CATALOG.md
- docs/ARCHITECTURE.md
- docs/ROADMAP.md
- docs/SECURITY_PRIVACY.md
- docs/DECISION_LOG.md
- docs/OPEN_QUESTIONS.md

## Product principles

1. No patient-identifiable information by default.
2. Every authoritative statement retains its source, date, version, scope, and review status.
3. Automated tools may extract and draft content, but reviewed publishing remains a human responsibility.
4. Documents should become structured, searchable requirements and workflows rather than remain a document dump.
5. HTML is the source of truth for printable resources, with PDF as an output.
6. Global, regional, organization, and personal content scopes remain explicit.
7. Evidence, official policy, organizational policy, and community experience are labeled separately.
8. Search should return concise, actionable information.
9. Important content is versioned and periodically reviewed.
10. The platform is API-first and useful without requiring an EHR integration.

## Initial release hypothesis

The first useful release should combine:

1. A source-aware healthcare operations knowledge base
2. Official payer-document ingestion and reviewed requirement extraction
3. A modern patient education and tracking-resource library
4. Multi-tenant organizations with branding, permissions, saved resources, and versioning

## Product boundary

Atlas provides educational and operational support. It does not independently diagnose, prescribe, determine medical necessity, guarantee insurance coverage, or present community reports as verified facts.
