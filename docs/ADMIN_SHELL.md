# Atlas administration shell

Atlas 0.16.0 introduces one capability-derived application navigation service. The shell displays only registered, implemented destinations the current user can access. It does not render disabled links for speculative modules, and destination authorization remains server-side.

The home screen displays authenticated user and resolved organization context. Missing organization context is explicit rather than inferred from browser state. Future modules add their implemented destinations through the `atlas_admin_navigation` filter and must retain the canonical route inventory entry and destination capability check.
