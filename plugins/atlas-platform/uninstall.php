<?php

declare(strict_types=1);

if (! defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

/**
 * Atlas intentionally preserves its data during uninstall.
 * A future explicit purge workflow must require a privileged confirmation.
 */
