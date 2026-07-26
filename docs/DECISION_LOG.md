# Decision Log

This log records the reasoning behind early product choices. Decisions may change, but changes should be explicit.

## ADR-001: Use Project Atlas as the working codename

**Status:** Accepted  
**Date:** 2026-07-26

Atlas communicates navigation through complex healthcare systems without prematurely locking the final commercial brand.

## ADR-002: Do not collect patient-identifiable information in the initial product

**Status:** Accepted  
**Date:** 2026-07-26

The initial value proposition concerns reusable knowledge, workflows, professional learning, and patient education templates. Patient data is unnecessary for these capabilities and would add substantial privacy, security, contractual, and operational burden.

## ADR-003: Make HTML the canonical format for printable resources

**Status:** Accepted  
**Date:** 2026-07-26

HTML supports accessibility, responsive display, translation, organization branding, controlled customization, and print layouts. PDF remains an important output, but not the editable source of truth.

## ADR-004: Support organization branding

**Status:** Accepted  
**Date:** 2026-07-26

Organizations may configure a logo, contact block, and approved footer. Branding should be applied at render time so reviewed core content can remain standardized.

## ADR-005: Build multi-tenant organizations from the beginning

**Status:** Accepted  
**Date:** 2026-07-26

Clinic managers need to create organizations, invite staff, delegate roles, and maintain reusable organization resources. Retrofitting tenant isolation later would be risky and expensive.

## ADR-006: Permit organization forks of global resources

**Status:** Accepted  
**Date:** 2026-07-26

Organizations should be able to customize a global resource while retaining its relationship to the source. Atlas should notify them of material source updates without overwriting local changes.

## ADR-007: Treat official policy, reviewed interpretation, organization policy, and community experience as different content classes

**Status:** Accepted  
**Date:** 2026-07-26

Operational experience is valuable, but anecdote must not appear equivalent to official policy. Different classes require clear labels, provenance, review states, and freshness rules.

## ADR-008: Require human review before publishing extracted requirements

**Status:** Accepted  
**Date:** 2026-07-26

Automated extraction can accelerate work, but payer and clinical documents contain ambiguity, exceptions, and context. Automated output may create drafts and comparisons, but may not silently become authoritative guidance.

## ADR-009: Start with a modular monolith

**Status:** Proposed  
**Date:** 2026-07-26

A modular monolith balances clean domain boundaries with a manageable initial deployment. Services can be separated later when justified by scale, reliability, or team ownership.

## ADR-010: Differentiate through operationalizing care

**Status:** Accepted  
**Date:** 2026-07-26

Atlas is not primarily an evidence-review product or an EHR. It focuses on turning reliable sources into practical workflows, reusable resources, local knowledge, and organization-specific execution.
