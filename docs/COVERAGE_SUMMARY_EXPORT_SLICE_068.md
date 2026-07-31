# Coverage summary export slice 0.68.0

This slice adds filtered internal exports for DME coverage summaries.

## Added

- `GET /wp-json/atlas/v1/coverage-summaries/export`
- Export filters:
  - payer
  - topic
  - DME category slug
  - jurisdiction
  - prior authorization status
- Export summary counts:
  - total summaries
  - ready summaries
  - prior-authorization-ready summaries

## Boundary

The export is internal-only and not patient-packet-safe. It summarizes staff-facing payer/DME requirement workups and does not submit to payers.
