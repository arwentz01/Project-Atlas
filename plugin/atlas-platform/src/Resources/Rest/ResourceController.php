<?php
declare(strict_types=1);

namespace Atlas\Platform\Resources\Rest;

use Atlas\Platform\Organizations\Services\CurrentOrganizationResolver;
use Atlas\Platform\Resources\Services\ResourceReader;
use Atlas\Platform\Resources\Domain\ResourcePolicy;
use Atlas\Platform\Resources\Search\ResourceSearchService;
use Atlas\Platform\Resources\Search\SearchCriteria;
use InvalidArgumentException;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

final class ResourceController
{
    public function __construct(private ResourceReader $reader, private CurrentOrganizationResolver $organizations, private ResourcePolicy $policy, private ResourceSearchService $search) {}
    public function permission(): bool { return is_user_logged_in() && current_user_can('atlas_access'); }
    public function show(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $id = strtolower(sanitize_text_field((string) $request->get_param('id')));
        if (! $this->policy->validIdentifier($id)) { return new WP_Error('atlas_resource_invalid_id', __('The resource identifier is invalid.', 'atlas-platform'), ['status' => 400]); }
        $organization = $this->organizations->resolveForUser(get_current_user_id());
        $resource = $this->reader->findPublished($id, $organization?->id);
        if ($resource === null) { return new WP_Error('atlas_resource_not_found', __('The resource was not found or is not accessible.', 'atlas-platform'), ['status' => 404]); }
        return new WP_REST_Response(['resource' => $resource->toArray()], 200);
    }
    public function index(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        try {
            $criteria=SearchCriteria::normalize(sanitize_text_field((string)$request->get_param('q')),sanitize_key((string)$request->get_param('type')) ?: null,max(1,(int)$request->get_param('page') ?: 1),max(1,(int)$request->get_param('per_page') ?: 20));
            $organization=$this->organizations->resolveForUser(get_current_user_id());
            return new WP_REST_Response($this->search->search($criteria,$organization?->id)->toArray(),200);
        } catch (InvalidArgumentException $exception) {
            return new WP_Error('atlas_resource_search_invalid',__($exception->getMessage(),'atlas-platform'),['status'=>400]);
        }
    }
}
