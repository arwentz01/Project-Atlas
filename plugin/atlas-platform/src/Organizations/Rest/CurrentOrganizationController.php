<?php
declare(strict_types=1);

namespace Atlas\Platform\Organizations\Rest;

use Atlas\Platform\Organizations\Services\CurrentOrganizationResolver;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

final class CurrentOrganizationController
{
    public function __construct(private CurrentOrganizationResolver $resolver) {}
    public function permission(): bool { return is_user_logged_in() && current_user_can('atlas_access'); }
    public function show(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $organization = $this->resolver->resolveForUser(get_current_user_id());
        if ($organization === null) {
            return new WP_Error('atlas_organization_context_unavailable', __('No single active organization is available for this user.', 'atlas-platform'), ['status' => 404]);
        }
        return new WP_REST_Response(['organization' => $organization->toArray()], 200);
    }
}
