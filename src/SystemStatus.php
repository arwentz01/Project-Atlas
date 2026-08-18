<?php

declare(strict_types=1);

final class SystemStatus
{
    private const REQUIRED_TABLES = [
        'users','organizations','memberships','locations','departments','positions',
        'supervisor_groups','staff_assignments','invitations','audit_logs',
        'workforce_profiles','providers','work_functions','stations','qualifications',
        'eligibility_groups','schedule_periods','shifts','shift_requests',
        'coverage_assignments','provider_sessions','rotations','rotation_generated_shifts',
        'workforce_preferences','availability_entries',
        'request_types','time_off_requests',
        'shift_change_requests','shift_relief_assignments',
        'callouts','callout_offers',
        'message_threads','message_thread_members','messages','notifications',
        'coverage_requirements',
        'shift_templates',
        'credential_types','member_credentials',
        'time_entries',
        'pay_profiles','payroll_exports',
        'master_schedules','master_schedule_entries','master_schedule_generations','master_schedule_generation_items',
        'master_schedule_versions','schedule_exceptions','master_generation_resolutions','master_generation_publications',
    ];

    public static function missingTables(PDO $db): array
    {
        $found = $db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
        return array_values(array_diff(self::REQUIRED_TABLES, $found));
    }

    public static function diagnostics(PDO $db): array
    {
        return [
            'php' => PHP_VERSION,
            'pdo_mysql' => extension_loaded('pdo_mysql'),
            'database' => (string)$db->query('SELECT DATABASE()')->fetchColumn(),
            'missing_tables' => self::missingTables($db),
            'session' => session_status() === PHP_SESSION_ACTIVE,
            'environment' => Config::get('APP_ENV', 'production'),
        ];
    }
}
