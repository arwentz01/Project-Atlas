# Atlas Platform Plugin

This directory contains the installable WordPress plugin for Project Atlas.

## Current foundation

- WordPress plugin bootstrap
- Namespace autoloader
- Application/module container
- Activation and deactivation lifecycle
- Installed-version tracking
- Public REST health endpoint
- Organizations module extension seam
- Conservative uninstall behavior that preserves data

## Install manually

1. Copy `plugin/atlas-platform` into `wp-content/plugins/atlas-platform`.
2. Activate **Atlas Platform** in WordPress.
3. Visit `/wp-json/atlas/v1/health` and confirm that the response reports `status: ok`.

## Development rules

- UI code calls REST endpoints or application services.
- Services own business logic.
- Repositories will be the only layer that directly accesses Atlas database tables.
- Every REST route enforces authorization server-side unless it is intentionally public, such as the health endpoint.
- Atlas data is preserved on ordinary plugin deactivation and uninstall.

## Next vertical slice

The next implementation milestone is Organizations: migrations, repositories, capabilities, services, audit events, REST routes, and a minimal management interface.
