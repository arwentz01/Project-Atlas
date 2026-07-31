# Patient Packet and Checklist Workflow Slice 0.61

This slice loops through the next five planned slices as one cohesive workflow increment.

## 1. Patient packet resource picker

- `PacketService::patientResourceOptions()` exposes published patient education Resources for the current organization context.
- REST route: `GET /wp-json/atlas/v1/packets/options`.

## 2. Packet print snapshots

- Adds immutable packet snapshot storage.
- REST route: `POST /wp-json/atlas/v1/packets/{id}/snapshots`.
- Admin action: `atlas_snapshot_packet`.
- Snapshot payload stores generated timestamp, packet metadata, resolved printable items, and provenance. It must not contain PHI.

## 3. Requirement checklist workflow

- Adds durable checklist state for internal documentation work.
- Checklist statuses: `needed`, `collected`, `waived`, `not_applicable`.
- REST route: `POST /wp-json/atlas/v1/payer-requirements/{id}/checklist`.
- Checklist updates record audit events.

## 4. Evidence detail polish

- Requirement evidence now returns generated checklist items with saved state and source evidence in one response.

## 5. Patient resource classification guardrails

- Patient packets validate Resource items through the patient education catalog.
- Resources outside the published patient-facing catalog are rejected.
- Payer requirements remain blocked from patient packets.

## Migration impact

Migration `0017` creates:

- `atlas_packet_snapshots`
- `atlas_requirement_checklist_state`
