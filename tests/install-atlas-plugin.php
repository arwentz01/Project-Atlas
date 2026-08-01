<?php
declare(strict_types=1);

$wordpressPath = getenv('ATLAS_TEST_WP_PATH');
if (! is_string($wordpressPath) || trim($wordpressPath) === '') {
    fwrite(STDERR, "ATLAS_TEST_WP_PATH is required.\n");
    exit(2);
}

$wordpressPath = rtrim($wordpressPath, '/\\');
$source = realpath(dirname(__DIR__) . '/plugins/atlas-platform');
$plugins = $wordpressPath . '/wp-content/plugins';
$target = $plugins . '/atlas-platform';
if ($source === false || ! is_dir($plugins)) {
    fwrite(STDERR, "Atlas source or the WordPress plugins directory is unavailable.\n");
    exit(2);
}
if (file_exists($target) || is_link($target)) {
    $resolved = realpath($target);
    if ($resolved === $source) {
        echo "Atlas development link is already installed.\n";
        exit(0);
    }
    fwrite(STDERR, "The Atlas plugin target already exists and points elsewhere; refusing to replace it.\n");
    exit(1);
}
if (! symlink($source, $target)) {
    fwrite(STDERR, "Unable to create the Atlas development plugin link.\n");
    exit(1);
}
echo "Atlas development plugin link installed at {$target}.\n";
