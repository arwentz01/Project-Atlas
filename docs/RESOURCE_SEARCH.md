# Tenant-Aware Resource Search

Atlas 0.6.0 adds bounded read-only search at `GET /wp-json/atlas/v1/resources`. Authentication and `atlas_access` are required.

Parameters are `q` (normalized, at most 100 characters), `type` (a canonical Resource type), `page` (1–100), and `per_page` (1–50). Results include answer-card fields, review state, dates, scope, and the first ordered source authority. The response reports `has_more` without an expensive unbounded count query.

The repository applies publication and tenant scope inside SQL. Platform and public results are accessible to authenticated Atlas users; organization results require the exact server-resolved organization. Organization results rank before global results, followed by update time and stable identifier. Search uses MySQL 5.7-compatible `LIKE`, bounded limits, and prepared values. A later measured migration may introduce full-text indexing without changing the service contract.

Specific resource-detail routes register before the collection route. Unsupported types, excessive queries, and excessive pagination return the stable `atlas_resource_search_invalid` error. Empty searches and no-match searches return valid empty collections, not errors.

## Manual verification

1. Seed published platform, public, Organization A, Organization B, draft, and archived fixtures.
2. Verify A receives global plus A, never B; verify B receives global plus B, never A.
3. Verify drafts and archived versions never appear.
4. Exercise query, type, page, and page-size validation directly through REST.
5. Verify organization results rank before global results and pagination remains stable.
6. Verify unauthenticated and capability-denied requests fail.
7. Inspect query plans on MySQL 5.7 and 8.0 with representative bounded data before increasing page limits.
