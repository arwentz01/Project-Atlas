<?php
declare(strict_types=1);
namespace Atlas\Platform\Resources\Packets;

use Atlas\Platform\Organizations\Services\CurrentOrganizationResolver;
use InvalidArgumentException;

final class PacketBuilderAdminPage
{
    private string $hook = '';

    public function __construct(private PacketService $packets, private CurrentOrganizationResolver $organizations) {}

    public function register(): void
    {
        $this->hook = (string) add_submenu_page('atlas', __('Packets', 'atlas-platform'), __('Packets', 'atlas-platform'), 'atlas_create_packets', 'atlas-packets', [$this, 'render']);
    }

    public function enqueue(string $hook): void
    {
        if ($hook === $this->hook) { wp_enqueue_style('atlas-preview', ATLAS_PLATFORM_URL . 'assets/css/atlas-preview.css', [], ATLAS_PLATFORM_VERSION); }
    }

    public function create(): void
    {
        $this->guard('atlas_create_packet');
        $org = $this->organizations->resolveForUser(get_current_user_id());
        $items = [];
        foreach (preg_split('/\R/', sanitize_textarea_field(wp_unslash((string) ($_POST['items'] ?? '')))) ?: [] as $line) {
            $line = trim($line);
            if ($line !== '') { $items[] = ['type' => 'resource', 'id' => $line, 'title' => $line]; }
        }
        $this->attempt(fn() => $this->packets->create($org?->id ?? '', get_current_user_id(), ['name' => sanitize_text_field(wp_unslash((string) ($_POST['name'] ?? ''))), 'description' => sanitize_textarea_field(wp_unslash((string) ($_POST['description'] ?? ''))), 'items' => $items]));
    }

    public function addItem(): void
    {
        $this->guard('atlas_add_packet_item');
        $packet = sanitize_text_field(wp_unslash((string) ($_POST['packet_id'] ?? '')));
        $this->attempt(fn() => $this->packets->addItem($packet, get_current_user_id(), ['type' => sanitize_key(wp_unslash((string) ($_POST['item_type'] ?? 'resource'))), 'id' => sanitize_text_field(wp_unslash((string) ($_POST['item_id'] ?? ''))), 'title' => sanitize_text_field(wp_unslash((string) ($_POST['title'] ?? ''))), 'notes' => sanitize_textarea_field(wp_unslash((string) ($_POST['notes'] ?? '')))]), $packet);
    }

    public function removeItem(): void
    {
        $this->guard('atlas_remove_packet_item');
        $packet = sanitize_text_field(wp_unslash((string) ($_POST['packet_id'] ?? '')));
        $this->attempt(fn() => $this->packets->removeItem($packet, sanitize_text_field(wp_unslash((string) ($_POST['packet_item_id'] ?? ''))), get_current_user_id()), $packet);
    }

    public function status(): void
    {
        $this->guard('atlas_update_packet_status');
        $packet = sanitize_text_field(wp_unslash((string) ($_POST['packet_id'] ?? '')));
        $this->attempt(fn() => $this->packets->setStatus($packet, get_current_user_id(), sanitize_key(wp_unslash((string) ($_POST['status'] ?? '')))), $packet);
    }

    public function snapshot(): void
    {
        $this->guard('atlas_snapshot_packet');
        $org = $this->organizations->resolveForUser(get_current_user_id());
        $packet = sanitize_text_field(wp_unslash((string) ($_POST['packet_id'] ?? '')));
        $this->attempt(function () use ($packet, $org): string { $this->packets->createSnapshot($packet, get_current_user_id(), $org?->id); return $packet; }, $packet);
    }

    public function render(): void
    {
        if (! current_user_can('atlas_create_packets')) { wp_die(esc_html__('Not allowed.', 'atlas-platform'), '', ['response' => 403]); }
        $org = $this->organizations->resolveForUser(get_current_user_id());
        $packetId = sanitize_text_field(wp_unslash((string) ($_GET['packet_id'] ?? '')));
        $snapshotId = sanitize_text_field(wp_unslash((string) ($_GET['snapshot_id'] ?? '')));
        $selected = $packetId === '' ? null : $this->packets->preview($packetId, get_current_user_id(), $org?->id);
        $readiness = $packetId === '' ? null : $this->packets->readiness($packetId, get_current_user_id(), $org?->id);
        $snapshots = $packetId === '' ? [] : $this->packets->snapshotHistory($packetId, get_current_user_id(), $org?->id);
        $snapshot = $packetId === '' || $snapshotId === '' ? null : $this->packets->snapshotDetail($packetId, $snapshotId, get_current_user_id(), $org?->id);
        if (is_array($snapshot) && isset($snapshot['snapshot']) && is_array($snapshot['snapshot'])) { $selected = $snapshot['snapshot']; }
        $packets = $this->packets->listForUser(get_current_user_id(), $org?->id);
        $patientResources = $this->packets->patientResourceOptions($org?->id, 50);
        $error = sanitize_text_field(wp_unslash((string) ($_GET['atlas_error'] ?? '')));
        $isPrint = sanitize_key(wp_unslash((string) ($_GET['atlas_print'] ?? ''))) === '1';
        require ATLAS_PLATFORM_DIR . 'templates/resources/packets.php';
    }

    private function guard(string $nonce): void
    {
        if (! current_user_can('atlas_create_packets')) { wp_die(esc_html__('Not allowed.', 'atlas-platform'), '', ['response' => 403]); }
        check_admin_referer($nonce);
    }

    private function attempt(callable $action, string $packet = ''): never
    {
        try { $result = $action(); $this->redirect(is_string($result) ? $result : $packet); } catch (InvalidArgumentException $e) { $this->redirect($packet, $e->getMessage()); }
    }

    private function redirect(string $packet = '', string $error = ''): never
    {
        $args = ['page' => 'atlas-packets'];
        if ($packet !== '') { $args['packet_id'] = $packet; }
        if ($error !== '') { $args['atlas_error'] = $error; }
        wp_safe_redirect(add_query_arg($args, admin_url('admin.php')));
        exit;
    }
}
