# ADR-002: Use Explicit Organizations Instead of WordPress Multisite

**Status:** Accepted

## Context

Atlas users may belong to healthcare organizations that own branding, memberships, resources, workflows, and local knowledge. These organizations are tenants inside one product, not independent websites.

## Decision

Atlas will model organizations and memberships explicitly in custom tables and enforce tenant scope in application authorization. WordPress Multisite will not represent Atlas organizations.

## Consequences

- Organization behavior can match the product instead of website-network semantics.
- Every tenant-owned action must validate organization membership and scope.
- Cross-organization sharing can be designed intentionally.
- A user can belong to multiple organizations without maintaining separate WordPress sites.

## Alternatives Considered

- WordPress Multisite: rejected because it models separate sites and complicates shared resources, search, and user experience.
- Separate installation per organization: rejected because it prevents a coherent shared platform.
