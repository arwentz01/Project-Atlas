# Candidate to requirement fast path slices 1.19-1.23

Atlas 1.19 through 1.23 closes the source intake loop from reviewed extraction candidate to structured payer requirement:

- 1.19 adds a candidate workspace payload with source document/page context and suggested requirement fields.
- 1.20 adds draft payer requirement creation from approved extraction candidates.
- 1.21 adds heuristic field inference for requirement type, prior authorization status, forms, topic, payer, and source linkage.
- 1.22 adds admin actions and JSON links for candidate workspaces and draft requirement creation.
- 1.23 exposes REST routes and tests for candidate workspace and candidate-to-requirement draft creation.

The fast path creates draft requirements only. Staff still review and publish requirements through the existing requirement lifecycle.
