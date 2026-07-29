# Patient Resources and Branding

Atlas 0.9.0 adds organization branding profiles and controlled saved variants for published patient-education versions. Branding stores an organization display name, WordPress media attachment ID, contact block, approved footer, and validated color token. The print view applies branding without obscuring provenance or review metadata.

`POST /wp-json/atlas/v1/patient-resources/{version}/variants` requires `atlas_manage_branding`, one server-resolved organization, and an idempotency key. Only `clinic_phone`, `clinic_address`, and `approved_footer` are accepted, with bounded lengths; unknown fields are discarded. The repository verifies that the version is published patient education accessible to that organization before saving. A unique idempotency key prevents duplicate variants.

No patient identifier, patient-specific instruction, result, order, or free-form clinical note field exists. Variants are reusable organization assets, not patient records.

## Manual verification

1. Apply a branding profile to a published patient resource and print in color and monochrome.
2. Confirm provenance, scope, review status, and sources remain visible.
3. Retry variant creation and race identical keys; confirm one stored row.
4. Reuse a key for a different organization/version and confirm conflict.
5. Confirm Organization A cannot customize or read Organization B variants.
6. Confirm drafts and non-patient resource types cannot create variants.
7. Confirm unauthorized, invalid nonce, malformed input, oversized fields, and direct URL behavior.
8. Run migration `0005` twice and from partial table/index states on MySQL 5.7 and 8.0.
