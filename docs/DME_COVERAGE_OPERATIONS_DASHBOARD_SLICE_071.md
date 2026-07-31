# DME Coverage Operations Dashboard slice 0.71.0

This larger release band adds an internal operations dashboard for published DME coverage requirements.

## Added

- Service-level `dmeCoverageOperationsDashboard()`.
- Sources admin panel for **DME coverage operations dashboard**.
- Dashboard summary cards for:
  - total published coverage summaries
  - ready requirements
  - blocked requirements
  - prior authorization required
  - prior authorization conditional
  - prior authorization not required
  - prior authorization unknown
- Action rows with:
  - payer
  - topic
  - readiness state
  - prior authorization status
  - next action
  - evidence links

## Boundary

The dashboard is internal, non-PHI, and staff-facing. It does not create patient records, patient packets, or payer submissions.
