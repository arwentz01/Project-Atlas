<?php

declare(strict_types=1);

final class Config
{
    private static array $values = [];

    public static function load(string $root): void
    {
        $file = $root . '/.env';
        if (is_file($file)) {
            foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                    continue;
                }
                [$key, $value] = array_map('trim', explode('=', $line, 2));
                self::$values[$key] = trim($value, "\"'");
            }
        }
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        $environment = getenv($key);
        return $environment !== false ? $environment : (self::$values[$key] ?? $default);
    }
}

