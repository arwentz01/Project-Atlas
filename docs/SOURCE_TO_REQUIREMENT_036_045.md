# Source to Requirement 0.36-0.45

Builds 0.36 through 0.45 add the first operational bridge from reviewed Resources into reusable packets and source-backed payer requirements.

- 0.36 adds packet storage and the Packets admin workspace.
- 0.37 registers source documents with publisher, URL, effective date, retrieval timestamp, checksum, and extraction status.
- 0.38 stores anchored source excerpts with page and section metadata.
- 0.39 creates extraction candidates for human review.
- 0.40 adds candidate approve/reject review actions.
- 0.41 stores structured payer requirement drafts.
- 0.42 captures payer, plan, topic, jurisdiction, requirement type, effective date, and expiration fields.
- 0.43 links payer requirement drafts back to extraction candidates.
- 0.44 exposes capability-gated Packets and Sources navigation.
- 0.45 adds the release inventory and migration gate coverage for the full slice.

The implementation remains conservative: extracted statements and payer requirements are drafts until a qualified reviewer promotes them through later editorial flows.
