# Persistent Resource library

Atlas 0.18.0 adds **Atlas → Resources**, backed by the production `ResourceSearchService` and repository rather than preview data. The library searches only published platform/public Resources plus published Resources belonging to the server-resolved current organization. Search input is limited to 100 characters, type values use the central Resource policy, page size is fixed at 20, and pagination is bounded.

Every result displays scope, review state, source authority when available, and a direct authorized Resource detail destination. The empty state distinguishes an empty published library from an application error. No unfinished authoring destination is displayed.
