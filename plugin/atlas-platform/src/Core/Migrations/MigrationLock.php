<?php
declare(strict_types=1);

namespace Atlas\Platform\Core\Migrations;

final class MigrationLock implements Lock
{
    private const OPTION = 'atlas_platform_migration_lock';
    public function __construct(private int $ttl = 300) {}
    public function acquire(): ?string
    {
        $token = bin2hex(random_bytes(16));
        $value = ['owner' => $token, 'created' => time(), 'expires' => time() + $this->ttl];
        return add_option(self::OPTION, $value, '', false) ? $token : null;
    }
    public function release(string $token): bool
    {
        $lock = $this->status();
        return isset($lock['owner']) && hash_equals((string) $lock['owner'], $token) && delete_option(self::OPTION);
    }
    /** @return array<string, mixed> */
    public function status(): array
    {
        $lock = get_option(self::OPTION, []);
        if (! is_array($lock) || ! isset($lock['owner'], $lock['created'], $lock['expires'])) { return ['state' => 'unlocked']; }
        return ['state' => (int) $lock['expires'] <= time() ? 'stale' : 'active', 'owner' => (string) $lock['owner'], 'created' => (int) $lock['created'], 'expires' => (int) $lock['expires']];
    }
    public function clearStale(): bool
    {
        return ($this->status()['state'] ?? '') === 'stale' && delete_option(self::OPTION);
    }
}
