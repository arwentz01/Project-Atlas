# Atlas Product Preview

Atlas 0.3.0 includes a deliberately bounded product preview at **WordPress Admin → Atlas**. Its purpose is to validate the application shell, navigation language, search prominence, information hierarchy, responsive behavior, and trust labels before Atlas commits to deeper product screens.

## What is real

- The page is registered through the Atlas module lifecycle.
- Access requires the canonical `atlas_access` capability.
- Search is functional and sanitized.
- The UI consumes `PreviewService`, which depends on `PreviewResourceRepository`.
- Output is escaped for its HTML, attribute, and URL context.
- Styles are committed deployable CSS with no production build step.
- The route is included in the canonical route inventory.

## What is illustrative

The preview repository is intentionally in-memory. Its four records demonstrate coverage, patient education, clinical reference, and organization-policy presentation. They are not clinical guidance, payer criteria, or production content. The screen labels both the product and every record as preview content.

Future navigation is rendered as noninteractive text rather than links. Atlas does not register blank or unfinished destinations and does not imply that those areas are authorized or implemented.

## Manual review

1. Activate Atlas Platform and sign in as an administrator.
2. Select **Atlas** in the WordPress navigation.
3. Search for `coverage`, `injection`, and `tracking`; then search for a missing term.
4. Confirm the search URL can be opened directly and the query remains escaped.
5. Confirm a user without `atlas_access` cannot open `admin.php?page=atlas`.
6. Inspect the screen at desktop, tablet, and narrow mobile widths.
7. Navigate by keyboard and confirm focus remains visible.
8. Confirm future navigation labels are not links.
9. Review the page in a screen reader and at 200% zoom.
10. Confirm no preview copy could be mistaken for actual clinical or payer guidance.

## Next build

The preview should be reviewed before it becomes a broader application shell. After visual approval, the next build should add live WordPress integration coverage for activation, capabilities, direct admin access, asset loading, diagnostics, health, and migration actions. Real organization or resource persistence should follow that integration gate.
