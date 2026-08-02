# Requirement change proposal queue slice 0.88

Atlas 0.88 adds a dedicated internal queue for payer requirement change proposals.

The queue lets staff review proposals by status without opening each requirement detail workspace first.

Added:

- `GET /atlas/v1/requirement-change-proposals`;
- service summary with proposal counts and status criteria;
- dashboard inclusion for draft proposal review;
- status filters for draft, applied, and rejected buckets.
