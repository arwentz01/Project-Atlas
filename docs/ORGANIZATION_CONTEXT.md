# Organization administration and context

Atlas 0.17.0 adds **Atlas → Organizations**. Users see only organizations for which the membership repository returns an active membership. When several are available, an authenticated nonce-protected POST stores the explicit selection in WordPress user metadata. The resolver verifies the stored identifier against current membership and organization status on every request; stale or forged selections are ignored.

Users with `atlas_manage_organizations` can create an organization through the existing idempotent onboarding service. The creator membership and organization are written atomically, the new context is selected, and audit events record both operations. The browser never supplies organization context to Resource or Workflow repositories as trusted authority.
