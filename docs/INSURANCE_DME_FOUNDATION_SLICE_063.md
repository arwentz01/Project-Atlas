# Insurance and DME Foundation Slice 0.63

This slice starts the insurance/DME layer without collecting patient information.

## Added

- Non-PHI insurance profile registry:
  - payer
  - plan name
  - line of business
  - jurisdiction
  - portal/contact metadata
  - effective date/status
- DME category catalog:
  - slug
  - label
  - description
  - status
- Requirement matching by payer, plan, DME category/topic, and jurisdiction.

## REST routes

- `GET /wp-json/atlas/v1/insurance-profiles`
- `POST /wp-json/atlas/v1/insurance-profiles`
- `GET /wp-json/atlas/v1/dme-categories`
- `POST /wp-json/atlas/v1/dme-categories`
- `GET /wp-json/atlas/v1/dme-requirement-matches`

## Admin surface

The Sources workspace now includes an Insurance and DME matching section.

## Migration impact

Migration `0018` creates:

- `atlas_insurance_profiles`
- `atlas_dme_categories`

No PHI is stored by this slice.
