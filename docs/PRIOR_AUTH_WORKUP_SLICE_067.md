# Prior authorization workup slice 0.67.0

This slice adds an internal prior authorization workup to DME coverage summaries.

## Added

Coverage summaries now include `prior_authorization_workup` with:

- prior authorization status
- whether authorization is required
- whether the requirement is ready for submission prep
- next action
- blocking gaps
- staff tasks
- required forms
- explicit `external_submission: false`
- explicit `phi_allowed: false`

## Boundary

This is not payer submission automation yet. It is a staff-facing preparation aid built from internal, non-PHI payer requirement data.
