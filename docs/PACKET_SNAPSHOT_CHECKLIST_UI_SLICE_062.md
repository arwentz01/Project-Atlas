# Packet Snapshot and Checklist UI Slice 0.62

This slice surfaces the durable packet/checklist foundations added in 0.61.

## Patient packets

- Packet admin now has a patient education resource picker.
- Packet readiness gates prevent marking a packet ready when items are missing, unresolved, non-printable, or internal-only.
- Packet admin can save print snapshots.
- Packet admin shows snapshot history.
- Historical snapshots can be opened as exact reprint views.

## Requirement/checklist workspace

- Sources admin now shows requirement review lanes.
- Freshness alerts surface stale sources and expiring/expired payer requirements.
- Checklist rows have staff workflow status controls: `needed`, `collected`, `waived`, `not_applicable`.
- Evidence detail shows checklist audit state and fuller source metadata.
- REST route `GET /wp-json/atlas/v1/documentation-checklists/export` provides an internal-only checklist export payload.

## Resource metadata

- Resource authoring normalizes explicit `patient_facing` and `internal_only` metadata flags.
- Patient education defaults to patient-facing.
- Payer summaries and clinical skill resources default to internal-only.
