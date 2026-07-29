# Source-aware resource authoring

Atlas 0.13.0 adds capability-protected `POST /wp-json/atlas/v1/resources/drafts`. It accepts a bounded structured body, source metadata, a citation, and a required `Idempotency-Key`. Organization drafts are always scoped from the authenticated user's active organization; the request cannot select another tenant. Platform drafts additionally require `atlas_manage_atlas`.

Every draft begins at version 1 with `draft` review status. Resource identity, immutable version, source, citation, and operation result are written atomically. Identical retries return the original result and conflicting key reuse is rejected. This endpoint stores clinical content, so operators remain responsible for ensuring submitted text is appropriate and contains no prohibited patient data.
