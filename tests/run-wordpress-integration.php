<?php
declare(strict_types=1);

$wordpressPath = getenv('ATLAS_TEST_WP_PATH');
if (! is_string($wordpressPath) || trim($wordpressPath) === '') {
    fwrite(STDERR, "ATLAS_TEST_WP_PATH is required. Example: /workspace/wordpress\n");
    exit(2);
}

$wordpressPath = rtrim($wordpressPath, '/\\');
$bootstrap = $wordpressPath . '/wp-load.php';
if (! is_readable($bootstrap)) {
    fwrite(STDERR, sprintf("WordPress bootstrap is not readable at %s\n", $bootstrap));
    exit(2);
}

require $bootstrap;
require __DIR__ . '/wordpress-integration.php';
