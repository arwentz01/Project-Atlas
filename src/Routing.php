<?php

declare(strict_types=1);

final class Routing
{
    private static string $basePath = '';

    public static function resolve(): string
    {
        $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
        self::$basePath = rtrim(dirname($script), '/.');
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        if (self::$basePath !== '' && self::$basePath !== '/' && str_starts_with($path, self::$basePath)) {
            $path = substr($path, strlen(self::$basePath)) ?: '/';
        }
        return trim((string)($_GET['route'] ?? ''), '/') ?: (trim($path, '/') ?: 'dashboard');
    }

    public static function url(string $path = ''): string
    {
        $base = self::$basePath === '/' ? '' : self::$basePath;
        $path = ltrim($path, '/');
        if ($path === '' || $path === 'dashboard') return $base . '/index.php';
        if (str_starts_with($path, 'assets/')) return $base . '/' . $path;
        $parts = parse_url($path);
        $route = trim((string)($parts['path'] ?? ''), '/');
        $url = $base . '/index.php?route=' . rawurlencode($route);
        if (!empty($parts['query'])) $url .= '&' . $parts['query'];
        return $url;
    }
}
