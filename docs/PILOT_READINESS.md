# Pilot Readiness Gate

Atlas 0.11.0 adds `GET /wp-json/atlas/v1/diagnostics/readiness`, protected by `atlas_view_diagnostics`. It evaluates the deployed release manifest, migration inventory, pending migrations, module boot state, administrator capabilities, PHP minimum, and WordPress minimum. It returns HTTP 200 only when every check passes, otherwise HTTP 503 with the exact failed check names.

This is an application readiness signal, not proof of hosting, backup, security, accessibility, MySQL-version, clinical-content, or legal readiness. Those require their own executed evidence. The endpoint exposes no absolute paths, SQL, secrets, users, or content.

Before a pilot, run the WordPress integration suite, migrations twice, tenant fixtures, direct route authorization, browser accessibility/print checks, backup and restore, and the MySQL 5.7/8.0 matrix where infrastructure permits.
