# Editorial Lifecycle

Atlas 0.8.0 adds explicit resource-version transitions: draft → in review → approved → published, with deliberate return-to-draft, review-due, supersede, and archive paths. Archived versions are terminal. Published version content remains immutable; transitions change review state and publication updates the Resource current-version pointer.

`POST /wp-json/atlas/v1/resource-versions/{uuid}/transitions` requires REST authentication plus `atlas_review_resources`; publication additionally requires `atlas_publish_resources`. Platform/public mutations require `atlas_manage_atlas`, while organization mutations require the server-resolved organization context. The request requires an `Idempotency-Key` header or `idempotency_key` parameter of 8–128 safe characters.

The repository locks the version row, revalidates the transition, updates state, appends an operational audit event, records the successful result under a database-unique operation key, and commits as one DML transaction. Retries return the recorded result with `replayed: true`. A duplicate-key race rolls back and reads the winning result. Audit context contains only status names, not bodies or clinical content.

Migration `0004` creates the operation-result and append-only audit tables with separately verified uniqueness and lookup indexes. These operational audit events remain separate from Core developer logs.

## Manual verification

1. Exercise every allowed and disallowed transition directly.
2. Retry the same successful key and confirm one state change and one audit record.
3. Race two requests with the same key and confirm both return one result.
4. Confirm a different operation using the same key returns an idempotency conflict and no new effect.
5. Confirm Organization A cannot mutate B, even with review capability.
6. Confirm organization reviewers cannot mutate platform resources and reviewers cannot publish without publish capability.
7. Confirm failed transitions create neither operation completion nor audit events.
8. Run migration `0004` twice and resume from each partial table/index state on MySQL 5.7 and 8.0.
