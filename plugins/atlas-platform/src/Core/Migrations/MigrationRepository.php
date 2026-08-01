<?php
declare(strict_types=1);

namespace Atlas\Platform\Core\Migrations;

use RuntimeException;

final class MigrationRepository implements MigrationStore
{
    private string $table;
    public function __construct(private object $database) { $this->table = $database->prefix . 'atlas_migrations'; }
    public function table(): string { return $this->table; }

    public function ensureLedger(): void
    {
        $charset = $this->database->get_charset_collate();
        $sql = "CREATE TABLE IF NOT EXISTS `{$this->table}` (migration_id varchar(191) NOT NULL, migration_class varchar(255) NOT NULL, description text NOT NULL, completed_at datetime NOT NULL, elapsed_ms bigint unsigned NOT NULL DEFAULT 0, PRIMARY KEY (migration_id)) {$charset}";
        $result = $this->database->query($sql);
        if ($result === false || ! $this->tableExists()) { throw new RuntimeException('Atlas migration ledger could not be created: ' . $this->safeDatabaseError()); }
    }

    public function tableExists(): bool
    {
        $found = $this->database->get_var($this->database->prepare('SHOW TABLES LIKE %s', $this->database->esc_like($this->table)));
        return $found === $this->table;
    }
    /** @return list<string> */
    public function completedIds(): array
    {
        if (! $this->tableExists()) { return []; }
        $ids = $this->database->get_col("SELECT migration_id FROM `{$this->table}` ORDER BY migration_id ASC");
        return is_array($ids) ? array_map('strval', $ids) : [];
    }
    public function record(Migration $migration, int $elapsedMs): void
    {
        $result = $this->database->insert($this->table, ['migration_id' => $migration->id(), 'migration_class' => get_class($migration), 'description' => $migration->description(), 'completed_at' => gmdate('Y-m-d H:i:s'), 'elapsed_ms' => $elapsedMs], ['%s', '%s', '%s', '%s', '%d']);
        if ($result === false) { throw new RuntimeException('Migration completion could not be recorded: ' . $this->safeDatabaseError()); }
    }
    private function safeDatabaseError(): string { return sanitize_text_field(substr((string) $this->database->last_error, 0, 500)); }
}
