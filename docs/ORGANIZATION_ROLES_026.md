# Organization roles 0.26.0

Organization membership roles are constrained to:

- organization administrator
- editor
- reviewer
- publisher
- member

Role changes and removals require `atlas_manage_members` and a nonce. A manager
cannot remove their own membership through the administration action, and the
repository will not remove the final active member.
