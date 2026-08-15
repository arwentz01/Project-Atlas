<?php

declare(strict_types=1);

final class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function field(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8') . '">';
    }

    public static function verify(): void
    {
        $provided = (string)($_POST['csrf_token'] ?? '');
        if ($provided === '' || !hash_equals(self::token(), $provided)) {
            http_response_code(419);
            exit('Your session expired. Please go back, refresh the page, and try again.');
        }
    }
}

