# Source Comparison Selector UI Slice 0.82

Atlas 0.82 lets staff choose which superseding source document to compare from the Sources admin.

## What changed

- The Sources admin includes a comparison selector for documents that supersede another source.
- The selected `comparison_source_id` drives the comparison panel.
- Invalid comparison identifiers are ignored instead of breaking the admin render path.

