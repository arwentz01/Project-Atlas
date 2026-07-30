# Atlas administration shell 0.24.0

Atlas 0.24.0 makes the capability-derived Atlas navigation available on every
Atlas administration destination instead of limiting it to the Home preview.

The application navigation:

- uses canonical `admin.php?page=...` destinations;
- marks the current destination with `aria-current`;
- displays only destinations authorized for the current user;
- displays the server-resolved organization context;
- loads the Atlas application stylesheet on every Atlas administration page;
- remains horizontally scrollable on narrow screens.

Destination handlers continue to enforce authorization independently of
navigation visibility.
