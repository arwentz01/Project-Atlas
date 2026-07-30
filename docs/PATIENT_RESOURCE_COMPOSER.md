# Patient Resource composer

Atlas 0.22.0 adds **Atlas → Patient Resources** for users with `atlas_manage_branding`. The catalog contains only published `patient_education` Resource Versions visible to the server-resolved current organization. The submitted Resource/Version pair is rechecked against that scoped catalog and the access repository before a variant is created.

The composer permits only the centrally defined bounded organization fields: clinic phone, clinic address, and approved footer. It cannot change reviewed clinical blocks. The resulting preview renders the immutable published content, organization branding, approved customization, source Version identifier, and a print-friendly layout.

Variant persistence is idempotent and now records the variant and append-only audit event in one transaction. An identical retry returns the existing variant; conflicting idempotency reuse fails safely.
