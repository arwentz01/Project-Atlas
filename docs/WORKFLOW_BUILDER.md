# Workflow builder and runner

Atlas 0.21.0 adds **Atlas → Workflows**. The catalog repository returns at most 50 published platform/public and current-organization Workflows. Each entry opens a server-rendered, tenant-authorized runner with ordered instructions, explicit requirements, warnings, version, scope, and optional source Resource Version.

Users with `atlas_manage_workflows` can create an idempotent draft with 1–50 ordered steps. The no-JavaScript form accepts one line per step using `Title | Instruction | Requirement | Optional warning`; the service applies the same central validator used by REST. Organization context is server-resolved, and platform scope additionally requires platform authority.

Workflow publication remains a governed persistence operation and is intentionally not implied by draft creation. Only versions already marked published and selected as the Workflow's current version appear in the runner.
