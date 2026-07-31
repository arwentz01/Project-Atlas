<?php
declare(strict_types=1);
namespace Atlas\Platform\Resources\Packets;
use Atlas\Platform\Core\Audit\AuditRecorder;
use InvalidArgumentException;

final class PacketService
{
    public function __construct(private PacketRepository $packets, private AuditRecorder $audit, private PacketItemResolver $resolver) {}

    public function create(string $organizationId, int $userId, array $input): string
    {
        $this->user($userId);
        $name = $this->text($input, 'name', 255, true);
        $description = $this->noPhi($this->text($input, 'description', 2000, false));
        $items = [];
        foreach ($input['items'] ?? [] as $item) {
            if (is_array($item)) { $items[] = $this->normalizeItem($item); }
        }
        $id = $this->packets->create($organizationId, $userId, $name, $items, $description);
        $this->audit->record('packet.created', 'resources', $userId, $organizationId === '' ? null : $organizationId, 'packet', $id, ['item_count' => count($items)]);
        return $id;
    }

    public function addItem(string $packetId, int $userId, array $item): bool
    {
        $this->user($userId);
        $packetId = $this->id($packetId);
        $ok = $this->packets->addItem($packetId, $userId, $this->normalizeItem($item));
        if ($ok) { $this->audit->record('packet.item_added', 'resources', $userId, null, 'packet', $packetId, []); }
        return $ok;
    }

    public function removeItem(string $packetId, string $itemId, int $userId): bool
    {
        $this->user($userId);
        $packetId = $this->id($packetId);
        $itemId = $this->id($itemId);
        $ok = $this->packets->removeItem($packetId, $itemId, $userId);
        if ($ok) { $this->audit->record('packet.item_removed', 'resources', $userId, null, 'packet', $packetId, ['item_id' => $itemId]); }
        return $ok;
    }

    public function setStatus(string $packetId, int $userId, string $status): bool
    {
        $this->user($userId);
        $packetId = $this->id($packetId);
        $status = sanitize_key($status);
        if (! in_array($status, ['draft', 'ready', 'archived'], true)) { throw new InvalidArgumentException('Packet status is invalid.'); }
        $ok = $this->packets->setStatus($packetId, $userId, $status);
        if ($ok) { $this->audit->record('packet.status_updated', 'resources', $userId, null, 'packet', $packetId, ['status' => $status]); }
        return $ok;
    }

    /** @return array{packet:array<string,mixed>,items:list<array<string,mixed>>,print_title:string}|null */
    public function preview(string $packetId, int $userId, ?string $organizationId): ?array
    {
        $this->user($userId);
        $packetId = $this->id($packetId);
        $view = $this->packets->findForUser($packetId, $userId, $organizationId);
        if ($view === null) { return null; }
        $items = [];
        foreach ($view['items'] as $item) {
            $normalized = [
                'id' => (string) ($item['id'] ?? ''),
                'type' => (string) ($item['item_type'] ?? 'resource'),
                'item_id' => (string) ($item['item_id'] ?? ''),
                'title' => (string) (($item['title'] ?? '') !== '' ? $item['title'] : $item['item_id'] ?? ''),
                'notes' => (string) ($item['notes'] ?? ''),
                'display_order' => (int) ($item['display_order'] ?? 0),
            ];
            $items[] = $this->resolver->resolve($normalized, $organizationId);
        }
        return ['packet' => $view['packet'], 'items' => $items, 'print_title' => (string) ($view['packet']['name'] ?? __('Patient packet', 'atlas-platform'))];
    }

    /** @return list<array<string,mixed>> */
    public function listForUser(int $userId, ?string $organizationId, int $limit = 25): array
    {
        $this->user($userId);
        return $this->packets->listForUser($userId, $organizationId, $limit);
    }

    private function normalizeItem(array $item): array
    {
        return [
            'type' => $this->type($item['type'] ?? $item['item_type'] ?? 'resource'),
            'id' => $this->text($item, 'id', 36, true),
            'title' => $this->text($item, 'title', 255, false),
            'notes' => $this->noPhi($this->text($item, 'notes', 1000, false)),
        ];
    }

    private function type(mixed $value): string
    {
        $type = sanitize_key((string) $value);
        return in_array($type, ['resource', 'requirement', 'instruction'], true) ? $type : 'resource';
    }

    private function user(int $userId): void { if ($userId <= 0) { throw new InvalidArgumentException('A signed-in Atlas user is required.'); } }
    private function id(string $id): string { $id = strtolower(trim($id)); if (preg_match('/^[a-f0-9-]{36}$/', $id) !== 1) { throw new InvalidArgumentException('Identifier is invalid.'); } return $id; }
    private function text(array $input, string $key, int $max, bool $required): string { $value = substr(trim((string) ($input[$key] ?? '')), 0, $max); if ($required && $value === '') { throw new InvalidArgumentException("{$key} is required."); } return $value; }
    private function noPhi(string $value): string { if (preg_match('/\b(?:MRN|DOB|SSN)\b|(?:\d{3}-\d{2}-\d{4})/i', $value) === 1) { throw new InvalidArgumentException('Do not enter patient-identifying information in Atlas packets.'); } return $value; }
}
