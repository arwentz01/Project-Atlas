<?php
declare(strict_types=1);

namespace Atlas\Platform\Core\Logging;

use Throwable;

final class WordPressLogger implements Logger
{
    private const OPTION = 'atlas_platform_operational_errors';
    private const LEVELS = ['debug', 'info', 'warning', 'error', 'critical'];
    private const SENSITIVE = ['password', 'pass', 'token', 'secret', 'nonce', 'cookie', 'authorization', 'api_key', 'request_body'];

    public function log(string $level, string $event, string $message, array $context = [], string $module = 'core', ?Throwable $exception = null): void
    {
        $level = in_array($level, self::LEVELS, true) ? $level : 'error';
        $entry = [
            'level' => $level,
            'event' => sanitize_key($event),
            'message' => sanitize_text_field($message),
            'context' => $this->sanitizeContext($context),
            'module' => sanitize_key($module),
            'timestamp' => gmdate('c'),
        ];
        if ($exception !== null) {
            $entry['exception'] = ['class' => get_class($exception), 'message' => sanitize_text_field($exception->getMessage()), 'code' => (string) $exception->getCode()];
        }
        if (in_array($level, ['error', 'critical'], true)) {
            $entries = $this->recentErrors(49);
            array_unshift($entries, $entry);
            update_option(self::OPTION, $entries, false);
        }
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[Atlas] ' . wp_json_encode($entry)); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
        }
    }

    public function recentErrors(int $limit = 20): array
    {
        $entries = get_option(self::OPTION, []);
        return is_array($entries) ? array_slice(array_values($entries), 0, max(0, min(50, $limit))) : [];
    }

    /** @param array<string, mixed> $context @return array<string, mixed> */
    private function sanitizeContext(array $context): array
    {
        $safe = [];
        foreach ($context as $key => $value) {
            $normalized = strtolower((string) $key);
            if (in_array($normalized, self::SENSITIVE, true)) {
                $safe[$key] = '[redacted]';
            } elseif (is_scalar($value) || $value === null) {
                $safe[$key] = is_string($value) ? sanitize_text_field($value) : $value;
            } else {
                $safe[$key] = '[complex value omitted]';
            }
        }
        return $safe;
    }
}
