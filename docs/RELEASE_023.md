# Atlas 0.23.0

Atlas 0.23.0 is a release-hardening build for WordPress administration
navigation.

## Change

The Atlas application shell now registers its top-level menu before feature
modules register their submenus. This preserves `admin.php?page=atlas` as the
top-level destination and prevents WordPress from promoting Organizations to
the parent destination or producing invalid `/wp-admin/{slug}` links.

## Verification

The WordPress integration gate asserts that:

- exactly one Atlas top-level menu exists;
- its slug is `atlas`;
- Atlas Home is the first submenu;
- Organizations, Resources, and Workflows remain children of Atlas.

Deploying this build does not add a database migration. Existing installations
at schema `0009` remain current.
