<?php
declare(strict_types=1);
namespace Atlas\Platform\Resources\Rest;
use Atlas\Platform\Core\Logging\Logger;
use Atlas\Platform\Organizations\Services\CurrentOrganizationResolver;
use Atlas\Platform\Resources\Packets\PacketService;
use InvalidArgumentException;
use Throwable;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

final class PacketController
{
    public function __construct(private PacketService $packets, private CurrentOrganizationResolver $orgs, private Logger $logger) {}
    public function permission(): bool { return is_user_logged_in() && current_user_can('atlas_access'); }
    public function createPermission(): bool { return is_user_logged_in() && current_user_can('atlas_create_packets'); }

    public function show(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        try {
            $org = $this->orgs->resolveForUser(get_current_user_id());
            $packet = $this->packets->preview((string) $request->get_param('id'), get_current_user_id(), $org?->id);
            return $packet === null ? new WP_Error('atlas_packet_not_found', __('The packet was not found or is not accessible.', 'atlas-platform'), ['status' => 404]) : new WP_REST_Response($packet, 200);
        } catch (Throwable $e) { return $this->error($e, 'atlas_packet_unavailable'); }
    }

    public function create(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        try {
            $org = $this->orgs->resolveForUser(get_current_user_id());
            $id = $this->packets->create($org?->id ?? '', get_current_user_id(), (array) $request->get_json_params());
            return new WP_REST_Response(['id' => $id], 201);
        } catch (Throwable $e) { return $this->error($e, 'atlas_packet_create_failed'); }
    }

    private function error(Throwable $e, string $code): WP_Error
    {
        if (! $e instanceof InvalidArgumentException) { $this->logger->log('error', $code, 'Packet request failed.', [], 'resources', $e); }
        return new WP_Error($code, $e instanceof InvalidArgumentException ? $e->getMessage() : __('The packet request failed safely.', 'atlas-platform'), ['status' => $e instanceof InvalidArgumentException ? 400 : 500]);
    }
}
