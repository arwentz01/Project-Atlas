<?php

declare(strict_types=1);

namespace Atlas\Platform\Organizations;

use Atlas\Platform\Core\Module;

final class OrganizationsModule implements Module
{
    public function register(): void
    {
        /**
         * The Organizations vertical slice will register its repositories,
         * services, capabilities, migrations, REST routes, and admin screens here.
         */
        do_action('atlas_organizations_register', $this);
    }
}
