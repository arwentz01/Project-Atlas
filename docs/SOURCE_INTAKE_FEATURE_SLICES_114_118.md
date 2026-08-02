# Source intake feature slices 1.14-1.18

Atlas 1.14 through 1.18 shifts back from hardening into feature/function work for source intake:

- 1.14 adds an internal source intake workspace payload for a preserved document and its stored page text.
- 1.15 adds heuristic candidate suggestions from page text using requirement-language cues.
- 1.16 adds a one-step service action to create an anchored excerpt and extraction candidate from a source page.
- 1.17 exposes intake and page-candidate creation through REST.
- 1.18 renders suggested candidate statements in the Sources admin with one-click candidate creation and intake JSON links.

The intake workspace remains internal-only and is not safe for patient packet output.
