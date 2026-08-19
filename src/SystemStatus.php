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
        'shift_history','notification_preferences','membership_permissions','membership_scopes',
        'email_verification_tokens','password_reset_tokens','login_attempts','user_sessions','verified_emails','application_errors',
        'organization_settings','department_schedule_defaults','employee_import_batches','employee_import_rows',
        'employment_records','secondary_work_assignments','employee_manager_notes','employee_onboarding_items','employee_profile_snapshots',
        'employee_documents',
        'organization_security_settings','local_accounts',
        'time_off_policies','time_off_balances','request_blackouts','callout_escalations','attendance_events',
        'coverage_demand_forecasts','command_center_items','access_delegations',
        'notification_delivery_settings','notification_templates','notification_deliveries',
        'position_credential_requirements','credential_renewals','credential_documents',
        'labor_periods','time_entry_exceptions','break_compliance_rules',
        'fairness_settings','recommendation_outcomes','schedule_acknowledgments',
        'saved_views','navigation_activity','data_import_jobs','data_import_rows','background_jobs','support_requests',
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
