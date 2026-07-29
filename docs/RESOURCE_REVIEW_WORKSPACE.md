# Resource review workspace

Atlas 0.20.0 adds **Atlas → Review Queue**. Its repository returns a bounded set of draft, in-review, approved, and review-due Resource Versions scoped to the current organization, with platform content included only for platform managers. The queue never broad-loads another organization's versions and never filters tenant ownership after retrieval.

Every transition is an authenticated, nonce-protected POST through the existing idempotent editorial service. Reviewers may move versions through review states; publishing additionally requires `atlas_publish_resources`. The mutation records its operation and append-only audit event atomically. Failures are logged and the UI displays a safe retry message without SQL or exception details.
