# Patient Resource Output 0.33-0.35

Builds 0.33 through 0.35 move Patient Resources from a one-shot composer into a managed output workflow.

- 0.33 fixes structured body rendering for directly-authored block lists and tightens the patient-preview print layout.
- 0.34 adds a print/export view for each variant. The page opens the browser print dialog so teams can print or save as PDF without requiring a server PDF binary.
- 0.35 adds variant lifecycle metadata: display name, active/archive state, updated timestamp, variant listing, edit, archive, and restore.

The underlying clinical body still comes from the reviewed Resource Version. Variant editing remains limited to approved organization customization fields.
