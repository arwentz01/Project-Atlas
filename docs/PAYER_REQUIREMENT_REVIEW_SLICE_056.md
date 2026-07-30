# Payer Requirement Review Slice 0.56

Atlas 0.56 completes the first REST-backed source-to-requirement review slice.

## Completed User Workflow

A permitted reviewer can register a source document, create a structured payer requirement, filter requirements by payer/topic/status, and move a requirement through review states from the Atlas Sources workspace. Future clients can use the same behavior through REST endpoints instead of duplicating admin-form logic.

## Architecture

The slice follows the documented boundary:

`UI -> REST/admin adapter -> SourceWorkspaceService -> SourceWorkspaceRepository -> custom tables`

Business rules now live in `SourceWorkspaceService`, including required fields, allowed statuses, date and URL validation, obvious PHI-marker rejection, and audit recording. The WordPress admin page and REST controller both use that service.

## REST Endpoints

- `GET /wp-json/atlas/v1/sources/dashboard`
- `POST /wp-json/atlas/v1/sources/documents`
- `GET /wp-json/atlas/v1/payer-requirements`
- `POST /wp-json/atlas/v1/payer-requirements`
- `POST /wp-json/atlas/v1/payer-requirements/{id}/review`

## Security

Read access requires `atlas_access`. Source document creation requires `atlas_upload_sources`. Requirement creation and review require `atlas_review_extractions`. Organization context is resolved server-side; clients do not choose tenant scope.

## Limitations

The current implementation stores source metadata and anchored excerpts, but it does not perform OCR, background extraction, external source monitoring, or source-version comparison. Those remain behind future document-processing interfaces.
