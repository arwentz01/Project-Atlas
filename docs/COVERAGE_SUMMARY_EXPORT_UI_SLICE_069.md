# Coverage summary export UI slice 0.69.0

This slice makes the internal coverage-summary export discoverable from the Sources admin screen.

## Added

- A Sources admin button: **Open coverage summary export JSON**
- The link preserves the current payer and topic filters.
- The export remains internal-only and not patient-packet-safe.

## Boundary

This is a staff-facing discoverability improvement. It does not add patient packet content and does not submit anything to payers.
