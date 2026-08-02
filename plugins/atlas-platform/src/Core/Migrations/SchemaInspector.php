<?php
declare(strict_types=1);

namespace Atlas\Platform\Core\Migrations;

use RuntimeException;

final class SchemaInspector
{
    public function __construct(private object $database) {}
    public function tableExists(string $table): bool
    {
        $found = $this->database->get_var($this->database->prepare('SHOW TABLES LIKE %s', $this->database->esc_like($table)));
        return $found === $table;
    }
    public function assertTable(string $table): void
    {
        $status = $this->database->get_row($this->database->prepare('SHOW TABLE STATUS WHERE Name = %s', $table), ARRAY_A);
        if (! is_array($status)) { throw new RuntimeException("Expected table {$table} is unavailable."); }
        if (strcasecmp((string) ($status['Engine'] ?? ''), 'InnoDB') !== 0) { throw new RuntimeException("Table {$table} must use InnoDB."); }
        $expectedCollation = (string) ($this->database->collate ?? '');
        if ($expectedCollation !== '' && strcasecmp((string) ($status['Collation'] ?? ''), $expectedCollation) !== 0) { throw new RuntimeException("Table {$table} has an incompatible collation."); }
    }
    public function assertColumn(string $table, string $column, string $type, bool $nullable, ?string $default = null): void
    {
        $row = $this->database->get_row($this->database->prepare("SHOW COLUMNS FROM `{$table}` WHERE Field = %s", $column), ARRAY_A);
        if (! is_array($row)) { throw new RuntimeException("Expected column {$table}.{$column} is missing."); }
        if ($this->normalizeType((string) $row['Type']) !== $this->normalizeType($type)) { throw new RuntimeException("Column {$table}.{$column} has an incompatible type."); }
        if (((string) $row['Null'] === 'YES') !== $nullable) { throw new RuntimeException("Column {$table}.{$column} has incompatible nullability."); }
        $actualDefault = $row['Default'] === null ? null : (string) $row['Default'];
        if ($actualDefault !== $default) { throw new RuntimeException("Column {$table}.{$column} has an incompatible default."); }
    }
    public function columnExists(string $table, string $column): bool
    {
        return is_array($this->database->get_row($this->database->prepare("SHOW COLUMNS FROM `{$table}` WHERE Field = %s", $column), ARRAY_A));
    }
    /** @param list<string> $columns */
    public function indexMatches(string $table, string $name, array $columns, bool $unique): bool
    {
        $rows = $this->database->get_results($this->database->prepare("SHOW INDEX FROM `{$table}` WHERE Key_name = %s", $name), ARRAY_A);
        if (! is_array($rows) || $rows === []) { return false; }
        usort($rows, static fn(array $left, array $right): int => (int) $left['Seq_in_index'] <=> (int) $right['Seq_in_index']);
        $actualColumns = array_map(static fn(array $row): string => (string) $row['Column_name'], $rows);
        $actualUnique = (int) $rows[0]['Non_unique'] === 0;
        if ($actualColumns !== $columns || $actualUnique !== $unique) { throw new RuntimeException("Index {$table}.{$name} has an incompatible definition."); }
        return true;
    }
    /** @param list<string> $columns */
    public function assertIndex(string $table, string $name, array $columns, bool $unique): void
    {
        if (! $this->indexMatches($table, $name, $columns, $unique)) {
            throw new RuntimeException("Expected index {$table}.{$name} is missing.");
        }
    }
    public function execute(string $sql, string $operation): void
    {
        if ($this->database->query($sql) === false) {
            $error = sanitize_text_field(substr((string) ($this->database->last_error ?? ''), 0, 500));
            throw new RuntimeException("{$operation} failed: {$error}");
        }
    }
    private function normalizeType(string $type): string
    {
        $normalized = strtolower(trim(preg_replace('/\s+/', ' ', $type) ?? $type));
        return preg_replace('/\b(bigint|int|smallint|mediumint|tinyint)\([0-9]+\)/', '$1', $normalized) ?? $normalized;
    }
}
