# Organization membership 0.25.0

Atlas organization administrators can invite a user by email, assign initial
roles, view pending invitations, and revoke an invitation. Invitations expire
after seven days and store only a SHA-256 hash of the bearer token.

Acceptance requires an authenticated WordPress user whose normalized account
email matches the invited email. The invitation is single-use and activates or
restores the corresponding organization membership.
