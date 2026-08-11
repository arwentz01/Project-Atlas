<?php

declare(strict_types=1);

final class Database
{
    private static ?PDO $connection = null;

    public static function loadEnv(string $path): void
    {
        if (!is_file($path)) return;
        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) continue;
            [$key,$value] = array_map('trim', explode('=', $line, 2));
            $value = trim($value, "\"'");
            if ($key !== '' && getenv($key) === false) { putenv("{$key}={$value}"); $_ENV[$key]=$value; }
        }
    }

    public static function connect(): PDO
    {
        if (self::$connection instanceof PDO) return self::$connection;
        $host=self::env('DB_HOST','127.0.0.1');
        $port=self::env('DB_PORT','3306');
        $name=self::env('DB_DATABASE','atlas');
        $user=self::env('DB_USERNAME','andrew');
        $pass=self::env('DB_PASSWORD','password');
        $charset=self::env('DB_CHARSET','utf8mb4');
        self::$connection = new PDO("mysql:host={$host};port={$port};dbname={$name};charset={$charset}",$user,$pass,[
            PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES=>false,
        ]);
        return self::$connection;
    }

    private static function env(string $key,string $default): string
    {
        $value=$_ENV[$key]??getenv($key);
        return ($value!==false && $value!==null && $value!=='') ? (string)$value : $default;
    }
}
