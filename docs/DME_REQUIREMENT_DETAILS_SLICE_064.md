# DME requirement details slice 0.64.0

This slice turns payer requirements into more useful DME operations data without mixing them into patient packets.

## Added

- `dme_category_slug` for exact DME category matching.
- `prior_authorization_status` with `required`, `not_required`, `conditional`, and `unknown`.
- `frequency_limit` and `replacement_interval`.
- `required_forms_json` for internal form lists.
- `coverage_criteria_text` for non-PHI payer coverage criteria.

## Boundaries

- These fields are internal payer/DME requirements.
- They are not patient packet materials.
- The service layer rejects obvious patient-identifying markers before saving requirement details.

## Validation

- Source workspace tests cover DME detail normalization and matching.
- Release gate requires migration `0019_dme_requirement_details.php`.
