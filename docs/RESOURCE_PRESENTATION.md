# Resource Detail and Print Presentation

Atlas 0.7.0 adds the capability-protected `admin.php?page=atlas-resource&id={uuid}` presentation route. It uses the same `ResourceReader` and server-resolved organization context as REST, so missing, draft, and cross-organization resources remain indistinguishable.

Structured bodies are rendered from explicit paragraph, heading, list, and callout blocks. Unknown blocks are ignored; text and attributes are escaped during construction. Templates receive prepared view data and do not query storage. Provenance remains visible beside content, including authority, source title, source URL, section, scope, review status, version, effective date, review due date, and change summary.

The committed stylesheet provides desktop, narrow-width, and black-and-white browser print layouts. Printing hides WordPress chrome and non-content actions. PDF remains a browser output rather than canonical storage.

## Manual verification

1. Open a platform resource and an authorized organization resource directly.
2. Confirm a cross-organization identifier and draft identifier return the same not-found page.
3. Test malicious block, source, title, and URL content for contextual escaping.
4. Review desktop, narrow, 200% zoom, keyboard, and screen-reader output.
5. Print in color and monochrome and confirm provenance remains visible.
6. Confirm a resource without citations displays the unverified warning.
