<?php
declare(strict_types=1);
define('ATLAS_PLATFORM_DIR', dirname(__DIR__) . '/plugin/atlas-platform/');
require ATLAS_PLATFORM_DIR . 'src/Autoloader.php';
Atlas\Platform\Autoloader::register();
if (! function_exists('wp_json_encode')) { function wp_json_encode($value): string { return json_encode($value, JSON_THROW_ON_ERROR); } }

use Atlas\Platform\Resources\Authoring\ResourceDraftRepository;
use Atlas\Platform\Resources\Authoring\ResourceDraftService;
use Atlas\Platform\Resources\Authoring\ResourceDraftValidator;
use Atlas\Platform\Resources\Authoring\ResourceMetadataRepository;

function author_expect(bool $condition, string $message): void { if (! $condition) { throw new RuntimeException($message); } echo "PASS: {$message}\n"; }

$repository = new class implements ResourceDraftRepository {
    public array $seen = [];
    public function create(string $key, string $fingerprint, array $draft, int $userId): array {
        if (isset($this->seen[$key])) {
            if ($this->seen[$key]['fingerprint'] !== $fingerprint) { throw new RuntimeException('different request'); }
            $result = $this->seen[$key]['result']; $result['replayed'] = true; return $result;
        }
        $result = ['resource_id' => 'r1', 'resource_version_id' => 'v1', 'status' => 'draft', 'replayed' => false];
        $this->seen[$key] = compact('fingerprint', 'result');
        return $result;
    }
};
$metadata = new class implements ResourceMetadataRepository {
    public array $saved = [];
    public function save(string $resourceId, array $metadata): void { $this->saved[$resourceId] = $metadata; }
};
$service = new ResourceDraftService($repository, new ResourceDraftValidator(), $metadata);
$input = ['scope' => 'organization', 'slug' => 'hand-hygiene', 'resource_type' => 'patient_education', 'title' => 'Hand Hygiene', 'summary' => 'Safe practice', 'body' => [['type' => 'paragraph', 'text' => 'Wash hands.']], 'source' => ['publisher' => 'CDC', 'title' => 'Guideline', 'url' => 'https://example.test/source'], 'citation' => ['section' => '1']];
$first = $service->create('draft-1', $input, 'org-a', 9, false);
$retry = $service->create('draft-1', $input, 'org-a', 9, false);
author_expect(! $first['replayed'] && $retry['replayed'], 'resource draft retries return the original immutable version');
author_expect(($metadata->saved['r1']['patient_facing'] ?? false) === true && ($metadata->saved['r1']['internal_only'] ?? true) === false, 'patient education resources default to patient-facing metadata');
try { $service->create('draft-2', array_merge($input, ['scope' => 'platform']), 'org-a', 9, false); throw new RuntimeException('Expected authorization validation.'); } catch (InvalidArgumentException) { author_expect(true, 'platform drafts require platform management authorization'); }
try { $bad = $input; $bad['body'] = [['type' => 'script', 'text' => 'unsafe']]; $service->create('draft-3', $bad, 'org-a', 9, false); throw new RuntimeException('Expected body validation.'); } catch (InvalidArgumentException) { author_expect(true, 'unsupported structured content blocks are rejected'); }
try { $service->create('draft-4', array_merge($input, ['patient_facing' => true, 'internal_only' => true]), 'org-a', 9, false); throw new RuntimeException('Expected metadata conflict.'); } catch (InvalidArgumentException) { author_expect(true, 'patient-facing resources cannot also be internal-only'); }
echo "All resource authoring tests passed.\n";
