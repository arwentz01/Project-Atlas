# Atlas security policy

Atlas stores workforce scheduling and employment operations data. It must not be used for patient information or unnecessary employee health information.

- Report suspected vulnerabilities through the organization support workflow using the Security category.
- Store secrets only in environment configuration, never in Git.
- Require HTTPS and secure session cookies in production.
- Restrict private uploads to PDF, JPG, and PNG, validate MIME type, randomize storage names, and keep storage outside public routing where possible.
- Review owner, administrator, scheduler, supervisor, delegated, and scoped access before every release.
- Retain audit logs and investigate repeated authentication failures, authorization denials, export spikes, and upload failures.
- Rotate database, email, monitoring, and deployment credentials after suspected exposure.

Production release requires an independent vulnerability scan and penetration test. Repository checks do not replace those reviews.
