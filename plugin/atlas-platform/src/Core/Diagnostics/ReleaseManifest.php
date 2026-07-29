<?php
declare(strict_types=1);

namespace Atlas\Platform\Core\Diagnostics;

final class ReleaseManifest
{
    /** @return array{valid: bool, version: string, missing: list<string>, error: string} */
    public function verify(): array
    {
        $path = ATLAS_PLATFORM_DIR . 'release-manifest.json';
        if (! is_readable($path)) { return ['valid' => false, 'version' => '', 'missing' => ['release-manifest.json'], 'error' => 'Release manifest is missing.']; }
        $contents = file_get_contents($path);
        $manifest = is_string($contents) ? json_decode($contents, true) : null;
        if (! is_array($manifest) || ! isset($manifest['version'], $manifest['required']) || ! is_array($manifest['required'])) { return ['valid' => false, 'version' => '', 'missing' => [], 'error' => 'Release manifest is malformed.']; }
        $missing = [];
        foreach ($manifest['required'] as $relative) { if (! is_string($relative) || ! is_file(ATLAS_PLATFORM_DIR . $relative)) { $missing[] = is_string($relative) ? $relative : '[invalid entry]'; } }
        return ['valid' => $missing === [] && $manifest['version'] === ATLAS_PLATFORM_VERSION, 'version' => (string) $manifest['version'], 'missing' => $missing, 'error' => $manifest['version'] === ATLAS_PLATFORM_VERSION ? '' : 'Manifest and plugin versions differ.'];
    }
}
