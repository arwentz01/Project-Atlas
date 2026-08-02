# Source Workspace Module Wiring Slice 0.79

Atlas 0.79 restores active WordPress wiring for implemented Resource-area packet and source workspace surfaces.

## What changed

- `ResourcesModule` now injects packet and source controllers/pages.
- Packet admin screens, admin-post handlers, and REST endpoints are registered from the active module.
- Source workspace admin screens, admin-post handlers, and REST endpoints are registered from the active module.
- Source impact review admin-post handlers introduced in 0.78 are now mounted.
- WordPress repository bindings were added for packet, packet snapshot, and source workspace interfaces.

## Guardrails

- This slice does not add new source business behavior.
- It mounts previously implemented services behind capability-protected WordPress hooks.
- REST permissions continue to require signed-in users with the existing Atlas capabilities.

## Deferred

- REST endpoints for explicit source comparison and impact-review mutation.
- Browser/UI smoke tests against a live WordPress install.
