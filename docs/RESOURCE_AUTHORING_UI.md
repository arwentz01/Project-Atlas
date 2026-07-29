# Resource authoring administration

Atlas 0.19.0 adds **Atlas → Create Resource** for users with `atlas_create_resources`. The nonce-protected POST normalizes browser input, resolves organization context server-side, converts blank-line-separated text to bounded paragraph blocks, and delegates to the existing idempotent Resource draft service. Platform scope is shown and accepted only for users with `atlas_manage_atlas`.

The form requires source publisher and title and supports URL, document identifier, effective date, page, and section citation metadata. It explicitly warns against patient-identifying information. Failures are logged without exposing SQL or internal exceptions to the administrator.

This build also corrects every copied call to the WordPress UUID API to use the supported `wp_generate_uuid4()` function. The affected audit, organization onboarding, Resource authoring, Workflow authoring, and organization form occurrences were searched and corrected systemically.
