# Requirement proposal workflow UI slices 0.89-0.93

Atlas 0.89 through 0.93 turns requirement change proposals into a usable staff workflow:

- 0.89: proposal queue admin UI with draft, applied, and rejected buckets;
- 0.90: before/after diff rows for proposed requirement field changes;
- 0.91: admin apply/reject POST actions with nonce and capability checks;
- 0.92: source-review closeout is blocked while draft proposals remain;
- 0.93: requirement detail includes a proposal lifecycle timeline.

The workflow remains internal-only and non-PHI. Drafts must be applied or rejected before source impact review can be cleared.
