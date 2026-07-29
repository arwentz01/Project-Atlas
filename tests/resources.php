<?php
declare(strict_types=1);

define('ATLAS_PLATFORM_DIR', dirname(__DIR__) . '/plugin/atlas-platform/');
require ATLAS_PLATFORM_DIR . 'src/Autoloader.php';
Atlas\Platform\Autoloader::register();

use Atlas\Platform\Resources\Domain\Citation;
use Atlas\Platform\Resources\Domain\PublishedResource;
use Atlas\Platform\Resources\Domain\Resource;
use Atlas\Platform\Resources\Domain\ResourcePolicy;
use Atlas\Platform\Resources\Domain\ResourceVersion;
use Atlas\Platform\Resources\Repositories\ResourceRepository;
use Atlas\Platform\Resources\Services\ResourceReader;
use Atlas\Platform\Resources\Search\ResourceSearchRepository;
use Atlas\Platform\Resources\Search\ResourceSearchService;
use Atlas\Platform\Resources\Search\SearchCriteria;
use Atlas\Platform\Resources\Search\SearchPage;
use Atlas\Platform\Resources\Search\SearchResult;

final class MemoryResources implements ResourceRepository
{
    /** @param array<string, PublishedResource> $resources */ public function __construct(private array $resources) {}
    public function findPublishedForContext(string $resourceId, ?string $organizationId): ?PublishedResource
    {
        $resource = $this->resources[$resourceId] ?? null;
        if ($resource === null || $resource->version->reviewStatus !== 'published') { return null; }
        if (in_array($resource->resource->scope, ResourcePolicy::GLOBAL_SCOPES, true)) { return $resource; }
        return $resource->resource->scope === 'organization' && $organizationId !== null && $resource->resource->organizationId === $organizationId ? $resource : null;
    }
}
final class MemorySearch implements ResourceSearchRepository
{
    public ?string $receivedOrganization = null;
    public function searchPublished(SearchCriteria $criteria, ?string $organizationId): SearchPage { $this->receivedOrganization=$organizationId; $items=$criteria->query==='missing'?[]:[new SearchResult('550e8400-e29b-41d4-a716-446655440000','Coverage example','Actionable summary','payer_summary','platform','published','2026-01-01','2027-01-01','Example agency','Example source')]; return new SearchPage($items,$criteria->page,$criteria->perPage,false); }
}
function resource_expect(bool $condition, string $message): void { if (! $condition) { throw new RuntimeException($message); } echo "PASS: {$message}\n"; }
function published(string $id, string $scope, ?string $organizationId, string $status = 'published'): PublishedResource
{
    $resource = new Resource($id, $scope, $organizationId, 'payer_summary', 'coverage-example', '2026-01-01 00:00:00', '2026-01-02 00:00:00');
    $version = new ResourceVersion('6ba7b814-9dad-41d1-80b4-00c04fd430c8', $id, 1, 'Coverage example', 'Illustrative summary', ['blocks' => [['type' => 'paragraph', 'text' => 'Structured example']]], $status, '2026-01-01', '2027-01-01', 'Initial version', 10, '2026-01-01 00:00:00');
    $citation = new Citation('6ba7b815-9dad-41d1-80b4-00c04fd430c8', 'Example agency', 'Example source', 'https://example.test/source', 'DOC-1', '2026-01-01', '12', 'Coverage');
    return new PublishedResource($resource, $version, [$citation]);
}

$globalId = '550e8400-e29b-41d4-a716-446655440000'; $orgId = '6ba7b810-9dad-41d1-80b4-00c04fd430c8'; $otherOrg = '6ba7b811-9dad-41d1-80b4-00c04fd430c8'; $orgResourceId = '6ba7b812-9dad-41d1-80b4-00c04fd430c8'; $draftId = '6ba7b813-9dad-41d1-80b4-00c04fd430c8';
$reader = new ResourceReader(new MemoryResources([$globalId => published($globalId, 'platform', null), $orgResourceId => published($orgResourceId, 'organization', $orgId), $draftId => published($draftId, 'platform', null, 'draft')]));
resource_expect($reader->findPublished($globalId, null)?->resource->id === $globalId, 'published platform resources are available without organization context');
resource_expect($reader->findPublished($orgResourceId, $orgId)?->resource->id === $orgResourceId, 'organization resources are available in their owning context');
resource_expect($reader->findPublished($orgResourceId, $otherOrg) === null, 'organization A resources are unavailable to organization B');
resource_expect($reader->findPublished($orgResourceId, null) === null, 'organization resources are unavailable without tenant context');
resource_expect($reader->findPublished($draftId, null) === null, 'draft versions are not exposed by the published reader');
$payload = $reader->findPublished($globalId, null)?->toArray();
resource_expect(($payload['review_status'] ?? '') === 'published' && count($payload['citations'] ?? []) === 1, 'resource payload retains review state and source citation');

$policy = new ResourcePolicy();
resource_expect($policy->validScope('organization') && ! $policy->validScope('tenant'), 'resource scopes use the canonical policy');
resource_expect($policy->validType('patient_education') && ! $policy->validType('post'), 'resource types use the canonical policy');
resource_expect($policy->validReviewStatus('review_due') && ! $policy->validReviewStatus('live'), 'review lifecycle states use the canonical policy');
resource_expect($policy->scopeKey('organization', $orgId) === 'organization:' . $orgId && $policy->scopeKey('organization', null) === null, 'organization scope keys require explicit ownership');
$searchRepository=new MemorySearch(); $search=new ResourceSearchService($searchRepository,$policy);
$criteria=SearchCriteria::normalize('  coverage   example  ','payer_summary',1,20); $page=$search->search($criteria,$orgId);
resource_expect($criteria->query==='coverage example' && $searchRepository->receivedOrganization===$orgId,'search normalizes input and passes server-resolved tenant context');
resource_expect(count($page->results)===1 && ($page->toArray()['items'][0]['source_publisher']??'')==='Example agency','search answer cards retain source authority metadata');
resource_expect($search->search(SearchCriteria::normalize('missing',null),$orgId)->results===[],'search returns an intentional empty result set');
try { SearchCriteria::normalize(str_repeat('x',101),null); throw new RuntimeException('long query accepted'); } catch (InvalidArgumentException) { resource_expect(true,'search rejects oversized queries'); }
try { $search->search(SearchCriteria::normalize('', 'post'),$orgId); throw new RuntimeException('invalid type accepted'); } catch (InvalidArgumentException) { resource_expect(true,'search rejects unsupported resource type filters'); }

echo "All resource foundation tests passed.\n";
