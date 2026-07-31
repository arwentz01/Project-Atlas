# DME coverage summary slice 0.66.0

This slice adds an internal coverage-summary payload for payer/DME requirements.

## Added

- `GET /wp-json/atlas/v1/payer-requirements/{id}/coverage-summary`
- Coverage summaries include:
  - payer, plan, topic, DME category, jurisdiction
  - prior authorization status
  - frequency and replacement rules
  - required forms
  - coverage criteria
  - requirement text
  - readiness summary
  - checklist items
  - source evidence

## Guardrail

Coverage summaries are explicitly `internal_only` and `patient_packet_safe: false`.
They are staff-facing payer/DME support artifacts, not educational patient packet content.
