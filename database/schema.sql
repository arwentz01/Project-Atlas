SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS organizations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(160) NOT NULL,
    slug VARCHAR(180) NOT NULL UNIQUE,
    organization_type ENUM('primary_care','specialty','multispecialty','asc','outpatient','other') NOT NULL DEFAULT 'other',
    timezone VARCHAR(80) NOT NULL DEFAULT 'America/New_York',
    created_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_org_creator FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS memberships (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    role ENUM('owner','admin','scheduler','supervisor','member') NOT NULL DEFAULT 'member',
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_membership (organization_id, user_id),
    CONSTRAINT fk_membership_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_membership_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS locations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(140) NOT NULL,
    timezone VARCHAR(80) NULL,
    is_primary TINYINT(1) NOT NULL DEFAULT 0,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_location_name (organization_id, name),
    CONSTRAINT fk_location_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS departments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    location_id BIGINT UNSIGNED NULL,
    default_supervisor_group_id BIGINT UNSIGNED NULL,
    name VARCHAR(140) NOT NULL,
    color CHAR(7) NOT NULL DEFAULT '#2563eb',
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_department_name (organization_id, name),
    CONSTRAINT fk_department_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_department_location FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS positions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    department_id BIGINT UNSIGNED NULL,
    name VARCHAR(140) NOT NULL,
    category ENUM('clinical_support','provider','front_office','procedural','diagnostic','administrative','revenue_cycle','operations','leadership','other') NOT NULL DEFAULT 'other',
    color CHAR(7) NOT NULL DEFAULT '#7756d9',
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_position_name (organization_id, name),
    CONSTRAINT fk_position_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_position_department FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS supervisor_groups (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    department_id BIGINT UNSIGNED NULL,
    name VARCHAR(140) NOT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_supervisor_group_name (organization_id, name),
    CONSTRAINT fk_supervisor_group_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_supervisor_group_department FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS supervisor_group_members (
    supervisor_group_id BIGINT UNSIGNED NOT NULL,
    membership_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (supervisor_group_id, membership_id),
    CONSTRAINT fk_sgm_group FOREIGN KEY (supervisor_group_id) REFERENCES supervisor_groups(id) ON DELETE CASCADE,
    CONSTRAINT fk_sgm_membership FOREIGN KEY (membership_id) REFERENCES memberships(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS staff_assignments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    membership_id BIGINT UNSIGNED NOT NULL,
    location_id BIGINT UNSIGNED NULL,
    department_id BIGINT UNSIGNED NULL,
    position_id BIGINT UNSIGNED NULL,
    supervisor_group_id BIGINT UNSIGNED NULL,
    is_primary TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_primary_assignment (membership_id, is_primary),
    CONSTRAINT fk_assignment_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_assignment_membership FOREIGN KEY (membership_id) REFERENCES memberships(id) ON DELETE CASCADE,
    CONSTRAINT fk_assignment_location FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE SET NULL,
    CONSTRAINT fk_assignment_department FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
    CONSTRAINT fk_assignment_position FOREIGN KEY (position_id) REFERENCES positions(id) ON DELETE SET NULL,
    CONSTRAINT fk_assignment_supervisor FOREIGN KEY (supervisor_group_id) REFERENCES supervisor_groups(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS invitations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    email VARCHAR(190) NOT NULL,
    role ENUM('admin','scheduler','supervisor','member') NOT NULL DEFAULT 'member',
    location_id BIGINT UNSIGNED NULL,
    department_id BIGINT UNSIGNED NULL,
    position_id BIGINT UNSIGNED NULL,
    token_hash CHAR(64) NOT NULL UNIQUE,
    invited_by BIGINT UNSIGNED NOT NULL,
    accepted_by BIGINT UNSIGNED NULL,
    accepted_at TIMESTAMP NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_invite_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_invite_location FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE SET NULL,
    CONSTRAINT fk_invite_department FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
    CONSTRAINT fk_invite_position FOREIGN KEY (position_id) REFERENCES positions(id) ON DELETE SET NULL,
    CONSTRAINT fk_invite_sender FOREIGN KEY (invited_by) REFERENCES users(id),
    CONSTRAINT fk_invite_acceptor FOREIGN KEY (accepted_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NULL,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(80) NOT NULL,
    entity_id BIGINT UNSIGNED NULL,
    metadata_json JSON NULL,
    ip_address VARCHAR(45) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_audit_org_created (organization_id, created_at),
    CONSTRAINT fk_audit_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS workforce_profiles (
    membership_id BIGINT UNSIGNED PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    employment_type ENUM('full_time','part_time','prn','contract') NOT NULL DEFAULT 'full_time',
    expected_weekly_hours DECIMAL(5,2) NULL,
    flex_eligible TINYINT(1) NOT NULL DEFAULT 0,
    opening_eligible TINYINT(1) NOT NULL DEFAULT 0,
    closing_eligible TINYINT(1) NOT NULL DEFAULT 0,
    availability_json JSON NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_workforce_membership FOREIGN KEY (membership_id) REFERENCES memberships(id) ON DELETE CASCADE,
    CONSTRAINT fk_workforce_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS teams (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    department_id BIGINT UNSIGNED NULL,
    name VARCHAR(140) NOT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE KEY uq_team_name (organization_id,name),
    CONSTRAINT fk_team_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_team_department FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS team_members (
    team_id BIGINT UNSIGNED NOT NULL,
    membership_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (team_id,membership_id),
    CONSTRAINT fk_tm_team FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
    CONSTRAINT fk_tm_member FOREIGN KEY (membership_id) REFERENCES memberships(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS providers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    location_id BIGINT UNSIGNED NULL,
    department_id BIGINT UNSIGNED NULL,
    name VARCHAR(140) NOT NULL,
    specialty VARCHAR(140) NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE KEY uq_provider_name (organization_id,name),
    CONSTRAINT fk_provider_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_provider_location FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE SET NULL,
    CONSTRAINT fk_provider_department FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS work_functions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    department_id BIGINT UNSIGNED NULL,
    name VARCHAR(140) NOT NULL,
    color CHAR(7) NOT NULL DEFAULT '#0d9a7c',
    active TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE KEY uq_function_name (organization_id,name),
    CONSTRAINT fk_function_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_function_department FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS stations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    location_id BIGINT UNSIGNED NULL,
    department_id BIGINT UNSIGNED NULL,
    name VARCHAR(140) NOT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE KEY uq_station_name (organization_id,name),
    CONSTRAINT fk_station_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_station_location FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE SET NULL,
    CONSTRAINT fk_station_department FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS qualifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(140) NOT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE KEY uq_qualification_name (organization_id,name),
    CONSTRAINT fk_qualification_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS staff_qualifications (
    membership_id BIGINT UNSIGNED NOT NULL,
    qualification_id BIGINT UNSIGNED NOT NULL,
    expires_on DATE NULL,
    verified_at TIMESTAMP NULL,
    PRIMARY KEY (membership_id,qualification_id),
    CONSTRAINT fk_sq_member FOREIGN KEY (membership_id) REFERENCES memberships(id) ON DELETE CASCADE,
    CONSTRAINT fk_sq_qualification FOREIGN KEY (qualification_id) REFERENCES qualifications(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS eligibility_groups (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(140) NOT NULL,
    description VARCHAR(255) NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE KEY uq_eligibility_group_name (organization_id,name),
    CONSTRAINT fk_eg_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS eligibility_group_positions (
    eligibility_group_id BIGINT UNSIGNED NOT NULL,
    position_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (eligibility_group_id,position_id),
    CONSTRAINT fk_egp_group FOREIGN KEY (eligibility_group_id) REFERENCES eligibility_groups(id) ON DELETE CASCADE,
    CONSTRAINT fk_egp_position FOREIGN KEY (position_id) REFERENCES positions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS schedule_periods (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(140) NOT NULL,
    starts_on DATE NOT NULL,
    ends_on DATE NOT NULL,
    status ENUM('draft','open','review','published','archived') NOT NULL DEFAULT 'draft',
    created_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_period_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_period_creator FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS shifts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    schedule_period_id BIGINT UNSIGNED NULL,
    location_id BIGINT UNSIGNED NOT NULL,
    department_id BIGINT UNSIGNED NOT NULL,
    assigned_membership_id BIGINT UNSIGNED NULL,
    shift_date DATE NOT NULL,
    starts_at TIME NOT NULL,
    ends_at TIME NOT NULL,
    status ENUM('draft','assigned','open','filled','cancelled') NOT NULL DEFAULT 'draft',
    eligibility_mode ENUM('exact','selected','group') NOT NULL DEFAULT 'exact',
    exact_position_id BIGINT UNSIGNED NULL,
    eligibility_group_id BIGINT UNSIGNED NULL,
    cross_department_mode ENUM('prohibited','approval','allowed') NOT NULL DEFAULT 'prohibited',
    notes VARCHAR(500) NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_shift_org_date (organization_id,shift_date),
    CONSTRAINT fk_shift_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_shift_period FOREIGN KEY (schedule_period_id) REFERENCES schedule_periods(id) ON DELETE SET NULL,
    CONSTRAINT fk_shift_location FOREIGN KEY (location_id) REFERENCES locations(id),
    CONSTRAINT fk_shift_department FOREIGN KEY (department_id) REFERENCES departments(id),
    CONSTRAINT fk_shift_assignee FOREIGN KEY (assigned_membership_id) REFERENCES memberships(id) ON DELETE SET NULL,
    CONSTRAINT fk_shift_position FOREIGN KEY (exact_position_id) REFERENCES positions(id) ON DELETE SET NULL,
    CONSTRAINT fk_shift_group FOREIGN KEY (eligibility_group_id) REFERENCES eligibility_groups(id) ON DELETE SET NULL,
    CONSTRAINT fk_shift_creator FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS shift_eligible_positions (
    shift_id BIGINT UNSIGNED NOT NULL,
    position_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (shift_id,position_id),
    CONSTRAINT fk_sep_shift FOREIGN KEY (shift_id) REFERENCES shifts(id) ON DELETE CASCADE,
    CONSTRAINT fk_sep_position FOREIGN KEY (position_id) REFERENCES positions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS shift_required_qualifications (
    shift_id BIGINT UNSIGNED NOT NULL,
    qualification_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (shift_id,qualification_id),
    CONSTRAINT fk_srq_shift FOREIGN KEY (shift_id) REFERENCES shifts(id) ON DELETE CASCADE,
    CONSTRAINT fk_srq_qualification FOREIGN KEY (qualification_id) REFERENCES qualifications(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS shift_requests (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    shift_id BIGINT UNSIGNED NOT NULL,
    membership_id BIGINT UNSIGNED NOT NULL,
    status ENUM('pending','approved','denied','withdrawn') NOT NULL DEFAULT 'pending',
    eligibility_result ENUM('eligible','approval','ineligible') NOT NULL,
    eligibility_reasons JSON NULL,
    reviewed_by BIGINT UNSIGNED NULL,
    reviewed_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_shift_request (shift_id,membership_id),
    CONSTRAINT fk_request_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_request_shift FOREIGN KEY (shift_id) REFERENCES shifts(id) ON DELETE CASCADE,
    CONSTRAINT fk_request_member FOREIGN KEY (membership_id) REFERENCES memberships(id) ON DELETE CASCADE,
    CONSTRAINT fk_request_reviewer FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS coverage_assignments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    shift_id BIGINT UNSIGNED NOT NULL,
    membership_id BIGINT UNSIGNED NOT NULL,
    provider_id BIGINT UNSIGNED NULL,
    station_id BIGINT UNSIGNED NULL,
    work_function_id BIGINT UNSIGNED NULL,
    coverage_type ENUM('primary','backup','flex','shared') NOT NULL DEFAULT 'primary',
    starts_at TIME NULL,
    ends_at TIME NULL,
    notes VARCHAR(500) NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_coverage_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_coverage_shift FOREIGN KEY (shift_id) REFERENCES shifts(id) ON DELETE CASCADE,
    CONSTRAINT fk_coverage_member FOREIGN KEY (membership_id) REFERENCES memberships(id) ON DELETE CASCADE,
    CONSTRAINT fk_coverage_provider FOREIGN KEY (provider_id) REFERENCES providers(id) ON DELETE SET NULL,
    CONSTRAINT fk_coverage_station FOREIGN KEY (station_id) REFERENCES stations(id) ON DELETE SET NULL,
    CONSTRAINT fk_coverage_function FOREIGN KEY (work_function_id) REFERENCES work_functions(id) ON DELETE SET NULL,
    CONSTRAINT fk_coverage_creator FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS provider_sessions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    provider_id BIGINT UNSIGNED NOT NULL,
    location_id BIGINT UNSIGNED NOT NULL,
    department_id BIGINT UNSIGNED NOT NULL,
    session_date DATE NOT NULL,
    starts_at TIME NOT NULL,
    ends_at TIME NOT NULL,
    support_count SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    status ENUM('scheduled','cancelled') NOT NULL DEFAULT 'scheduled',
    notes VARCHAR(500) NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_provider_session_org_date (organization_id,session_date),
    CONSTRAINT fk_ps_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_ps_provider FOREIGN KEY (provider_id) REFERENCES providers(id) ON DELETE CASCADE,
    CONSTRAINT fk_ps_location FOREIGN KEY (location_id) REFERENCES locations(id),
    CONSTRAINT fk_ps_department FOREIGN KEY (department_id) REFERENCES departments(id),
    CONSTRAINT fk_ps_creator FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rotations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(140) NOT NULL,
    membership_id BIGINT UNSIGNED NULL,
    location_id BIGINT UNSIGNED NOT NULL,
    department_id BIGINT UNSIGNED NOT NULL,
    position_id BIGINT UNSIGNED NOT NULL,
    weekdays VARCHAR(20) NOT NULL,
    starts_at TIME NOT NULL,
    ends_at TIME NOT NULL,
    effective_from DATE NOT NULL,
    effective_to DATE NULL,
    week_interval SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_rotation_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_rotation_member FOREIGN KEY (membership_id) REFERENCES memberships(id) ON DELETE SET NULL,
    CONSTRAINT fk_rotation_location FOREIGN KEY (location_id) REFERENCES locations(id),
    CONSTRAINT fk_rotation_department FOREIGN KEY (department_id) REFERENCES departments(id),
    CONSTRAINT fk_rotation_position FOREIGN KEY (position_id) REFERENCES positions(id),
    CONSTRAINT fk_rotation_creator FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rotation_generated_shifts (
    rotation_id BIGINT UNSIGNED NOT NULL,
    shift_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (rotation_id,shift_id),
    UNIQUE KEY uq_rotation_shift (shift_id),
    CONSTRAINT fk_rgs_rotation FOREIGN KEY (rotation_id) REFERENCES rotations(id) ON DELETE CASCADE,
    CONSTRAINT fk_rgs_shift FOREIGN KEY (shift_id) REFERENCES shifts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS workforce_preferences (
    membership_id BIGINT UNSIGNED PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    preferred_start TIME NULL,
    preferred_end TIME NULL,
    maximum_weekly_hours DECIMAL(5,2) NULL,
    preferred_location_id BIGINT UNSIGNED NULL,
    preferred_department_id BIGINT UNSIGNED NULL,
    opening_preference ENUM('prefer','available','avoid') NOT NULL DEFAULT 'available',
    closing_preference ENUM('prefer','available','avoid') NOT NULL DEFAULT 'available',
    notes VARCHAR(500) NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_pref_member FOREIGN KEY (membership_id) REFERENCES memberships(id) ON DELETE CASCADE,
    CONSTRAINT fk_pref_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_pref_location FOREIGN KEY (preferred_location_id) REFERENCES locations(id) ON DELETE SET NULL,
    CONSTRAINT fk_pref_department FOREIGN KEY (preferred_department_id) REFERENCES departments(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS availability_entries (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    membership_id BIGINT UNSIGNED NOT NULL,
    entry_type ENUM('recurring','date_exception') NOT NULL,
    weekday TINYINT UNSIGNED NULL,
    applies_on DATE NULL,
    availability ENUM('available','preferred','unavailable') NOT NULL,
    starts_at TIME NULL,
    ends_at TIME NULL,
    status ENUM('pending','approved','denied') NOT NULL DEFAULT 'approved',
    reviewed_by BIGINT UNSIGNED NULL,
    reviewed_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_availability_member (membership_id,status),
    KEY idx_availability_date (organization_id,applies_on),
    CONSTRAINT fk_availability_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_availability_member FOREIGN KEY (membership_id) REFERENCES memberships(id) ON DELETE CASCADE,
    CONSTRAINT fk_availability_reviewer FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS request_types (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(100) NOT NULL,
    paid TINYINT(1) NOT NULL DEFAULT 0,
    active TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE KEY uq_request_type (organization_id,name),
    CONSTRAINT fk_request_type_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS time_off_requests (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    membership_id BIGINT UNSIGNED NOT NULL,
    request_type_id BIGINT UNSIGNED NULL,
    starts_on DATE NOT NULL,
    ends_on DATE NOT NULL,
    starts_at TIME NULL,
    ends_at TIME NULL,
    employee_note VARCHAR(500) NULL,
    manager_note VARCHAR(500) NULL,
    status ENUM('pending','approved','denied','cancelled') NOT NULL DEFAULT 'pending',
    reviewed_by BIGINT UNSIGNED NULL,
    reviewed_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_time_off_org_status (organization_id,status,starts_on),
    CONSTRAINT fk_time_off_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_time_off_member FOREIGN KEY (membership_id) REFERENCES memberships(id) ON DELETE CASCADE,
    CONSTRAINT fk_time_off_type FOREIGN KEY (request_type_id) REFERENCES request_types(id) ON DELETE SET NULL,
    CONSTRAINT fk_time_off_reviewer FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS shift_change_requests (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    requester_membership_id BIGINT UNSIGNED NOT NULL,
    offered_shift_id BIGINT UNSIGNED NOT NULL,
    recipient_membership_id BIGINT UNSIGNED NULL,
    requested_shift_id BIGINT UNSIGNED NULL,
    request_type ENUM('giveaway','trade','partial') NOT NULL,
    partial_starts_at TIME NULL,
    partial_ends_at TIME NULL,
    employee_note VARCHAR(500) NULL,
    manager_note VARCHAR(500) NULL,
    status ENUM('pending_recipient','pending_manager','approved','denied','withdrawn','expired') NOT NULL DEFAULT 'pending_recipient',
    eligibility_result ENUM('eligible','approval','ineligible') NULL,
    eligibility_reasons JSON NULL,
    reviewed_by BIGINT UNSIGNED NULL,
    reviewed_at TIMESTAMP NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_change_org_status (organization_id,status),
    CONSTRAINT fk_change_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_change_requester FOREIGN KEY (requester_membership_id) REFERENCES memberships(id) ON DELETE CASCADE,
    CONSTRAINT fk_change_offered_shift FOREIGN KEY (offered_shift_id) REFERENCES shifts(id) ON DELETE CASCADE,
    CONSTRAINT fk_change_recipient FOREIGN KEY (recipient_membership_id) REFERENCES memberships(id) ON DELETE SET NULL,
    CONSTRAINT fk_change_requested_shift FOREIGN KEY (requested_shift_id) REFERENCES shifts(id) ON DELETE SET NULL,
    CONSTRAINT fk_change_reviewer FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS shift_relief_assignments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    shift_id BIGINT UNSIGNED NOT NULL,
    membership_id BIGINT UNSIGNED NOT NULL,
    starts_at TIME NOT NULL,
    ends_at TIME NOT NULL,
    source_request_id BIGINT UNSIGNED NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_relief_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_relief_shift FOREIGN KEY (shift_id) REFERENCES shifts(id) ON DELETE CASCADE,
    CONSTRAINT fk_relief_member FOREIGN KEY (membership_id) REFERENCES memberships(id) ON DELETE CASCADE,
    CONSTRAINT fk_relief_request FOREIGN KEY (source_request_id) REFERENCES shift_change_requests(id) ON DELETE SET NULL,
    CONSTRAINT fk_relief_creator FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS callouts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    shift_id BIGINT UNSIGNED NOT NULL,
    membership_id BIGINT UNSIGNED NOT NULL,
    reason_category ENUM('illness','family_emergency','transportation','weather','other') NOT NULL DEFAULT 'illness',
    employee_note VARCHAR(500) NULL,
    manager_note VARCHAR(500) NULL,
    status ENUM('reported','replacement_open','covered','closed','cancelled') NOT NULL DEFAULT 'reported',
    replacement_membership_id BIGINT UNSIGNED NULL,
    reported_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    acknowledged_by BIGINT UNSIGNED NULL,
    acknowledged_at TIMESTAMP NULL,
    resolved_at TIMESTAMP NULL,
    UNIQUE KEY uq_callout_shift (shift_id),
    KEY idx_callout_org_status (organization_id,status,reported_at),
    CONSTRAINT fk_callout_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_callout_shift FOREIGN KEY (shift_id) REFERENCES shifts(id) ON DELETE CASCADE,
    CONSTRAINT fk_callout_member FOREIGN KEY (membership_id) REFERENCES memberships(id) ON DELETE CASCADE,
    CONSTRAINT fk_callout_replacement FOREIGN KEY (replacement_membership_id) REFERENCES memberships(id) ON DELETE SET NULL,
    CONSTRAINT fk_callout_ack FOREIGN KEY (acknowledged_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS callout_offers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    callout_id BIGINT UNSIGNED NOT NULL,
    membership_id BIGINT UNSIGNED NOT NULL,
    eligibility_result ENUM('eligible','approval','ineligible') NOT NULL,
    eligibility_reasons JSON NULL,
    status ENUM('offered','accepted','declined','expired','selected') NOT NULL DEFAULT 'offered',
    responded_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_callout_offer (callout_id,membership_id),
    CONSTRAINT fk_callout_offer_callout FOREIGN KEY (callout_id) REFERENCES callouts(id) ON DELETE CASCADE,
    CONSTRAINT fk_callout_offer_member FOREIGN KEY (membership_id) REFERENCES memberships(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS message_threads (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    subject VARCHAR(180) NOT NULL,
    thread_type ENUM('direct','group','announcement','shift') NOT NULL DEFAULT 'direct',
    shift_id BIGINT UNSIGNED NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_thread_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_thread_shift FOREIGN KEY (shift_id) REFERENCES shifts(id) ON DELETE SET NULL,
    CONSTRAINT fk_thread_creator FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS message_thread_members (
    thread_id BIGINT UNSIGNED NOT NULL,
    membership_id BIGINT UNSIGNED NOT NULL,
    last_read_at TIMESTAMP NULL,
    muted TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (thread_id,membership_id),
    CONSTRAINT fk_thread_member_thread FOREIGN KEY (thread_id) REFERENCES message_threads(id) ON DELETE CASCADE,
    CONSTRAINT fk_thread_member_membership FOREIGN KEY (membership_id) REFERENCES memberships(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS messages (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    thread_id BIGINT UNSIGNED NOT NULL,
    sender_membership_id BIGINT UNSIGNED NOT NULL,
    body TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    edited_at TIMESTAMP NULL,
    CONSTRAINT fk_message_thread FOREIGN KEY (thread_id) REFERENCES message_threads(id) ON DELETE CASCADE,
    CONSTRAINT fk_message_sender FOREIGN KEY (sender_membership_id) REFERENCES memberships(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    membership_id BIGINT UNSIGNED NOT NULL,
    notification_type VARCHAR(80) NOT NULL,
    title VARCHAR(180) NOT NULL,
    body VARCHAR(500) NULL,
    action_route VARCHAR(120) NULL,
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_notification_member (membership_id,read_at,created_at),
    CONSTRAINT fk_notification_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_notification_member FOREIGN KEY (membership_id) REFERENCES memberships(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS coverage_requirements (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    location_id BIGINT UNSIGNED NOT NULL,
    department_id BIGINT UNSIGNED NOT NULL,
    provider_id BIGINT UNSIGNED NULL,
    station_id BIGINT UNSIGNED NULL,
    work_function_id BIGINT UNSIGNED NULL,
    weekday TINYINT UNSIGNED NOT NULL,
    starts_at TIME NOT NULL,
    ends_at TIME NOT NULL,
    required_count SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    priority ENUM('standard','important','critical') NOT NULL DEFAULT 'standard',
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_requirement_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_requirement_location FOREIGN KEY (location_id) REFERENCES locations(id),
    CONSTRAINT fk_requirement_department FOREIGN KEY (department_id) REFERENCES departments(id),
    CONSTRAINT fk_requirement_provider FOREIGN KEY (provider_id) REFERENCES providers(id) ON DELETE SET NULL,
    CONSTRAINT fk_requirement_station FOREIGN KEY (station_id) REFERENCES stations(id) ON DELETE SET NULL,
    CONSTRAINT fk_requirement_function FOREIGN KEY (work_function_id) REFERENCES work_functions(id) ON DELETE SET NULL,
    CONSTRAINT fk_requirement_creator FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS shift_templates (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(140) NOT NULL,
    location_id BIGINT UNSIGNED NOT NULL,
    department_id BIGINT UNSIGNED NOT NULL,
    position_id BIGINT UNSIGNED NOT NULL,
    starts_at TIME NOT NULL,
    ends_at TIME NOT NULL,
    cross_department_mode ENUM('prohibited','approval','allowed') NOT NULL DEFAULT 'prohibited',
    notes VARCHAR(500) NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_shift_template_name (organization_id,name),
    CONSTRAINT fk_template_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_template_location FOREIGN KEY (location_id) REFERENCES locations(id),
    CONSTRAINT fk_template_department FOREIGN KEY (department_id) REFERENCES departments(id),
    CONSTRAINT fk_template_position FOREIGN KEY (position_id) REFERENCES positions(id),
    CONSTRAINT fk_template_creator FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS credential_types (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(140) NOT NULL,
    issuing_authority VARCHAR(180) NULL,
    renewal_days SMALLINT UNSIGNED NULL,
    warning_days SMALLINT UNSIGNED NOT NULL DEFAULT 60,
    active TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE KEY uq_credential_type (organization_id,name),
    CONSTRAINT fk_credential_type_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS member_credentials (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    membership_id BIGINT UNSIGNED NOT NULL,
    credential_type_id BIGINT UNSIGNED NOT NULL,
    credential_number VARCHAR(120) NULL,
    issued_on DATE NULL,
    expires_on DATE NULL,
    status ENUM('pending','verified','rejected','expired') NOT NULL DEFAULT 'pending',
    verified_by BIGINT UNSIGNED NULL,
    verified_at TIMESTAMP NULL,
    notes VARCHAR(500) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_member_credential (membership_id,credential_type_id,credential_number),
    KEY idx_credentials_expiry (organization_id,status,expires_on),
    CONSTRAINT fk_member_credential_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_member_credential_member FOREIGN KEY (membership_id) REFERENCES memberships(id) ON DELETE CASCADE,
    CONSTRAINT fk_member_credential_type FOREIGN KEY (credential_type_id) REFERENCES credential_types(id) ON DELETE CASCADE,
    CONSTRAINT fk_member_credential_verifier FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS time_entries (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    membership_id BIGINT UNSIGNED NOT NULL,
    shift_id BIGINT UNSIGNED NULL,
    clocked_in_at DATETIME NOT NULL,
    clocked_out_at DATETIME NULL,
    break_minutes SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    status ENUM('open','submitted','approved','rejected') NOT NULL DEFAULT 'open',
    employee_note VARCHAR(500) NULL,
    manager_note VARCHAR(500) NULL,
    reviewed_by BIGINT UNSIGNED NULL,
    reviewed_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_time_entry_member (membership_id,clocked_in_at),
    CONSTRAINT fk_time_entry_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_time_entry_member FOREIGN KEY (membership_id) REFERENCES memberships(id) ON DELETE CASCADE,
    CONSTRAINT fk_time_entry_shift FOREIGN KEY (shift_id) REFERENCES shifts(id) ON DELETE SET NULL,
    CONSTRAINT fk_time_entry_reviewer FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pay_profiles (
    membership_id BIGINT UNSIGNED PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    pay_type ENUM('hourly','salary') NOT NULL DEFAULT 'hourly',
    hourly_rate DECIMAL(10,2) NULL,
    annual_salary DECIMAL(12,2) NULL,
    overtime_multiplier DECIMAL(4,2) NOT NULL DEFAULT 1.50,
    overtime_weekly_hours DECIMAL(5,2) NOT NULL DEFAULT 40.00,
    updated_by BIGINT UNSIGNED NOT NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_pay_profile_member FOREIGN KEY (membership_id) REFERENCES memberships(id) ON DELETE CASCADE,
    CONSTRAINT fk_pay_profile_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_pay_profile_updater FOREIGN KEY (updated_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS payroll_exports (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    period_start DATE NOT NULL,
    period_end DATE NOT NULL,
    row_count INT UNSIGNED NOT NULL,
    total_hours DECIMAL(10,2) NOT NULL,
    total_gross DECIMAL(12,2) NULL,
    exported_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_payroll_export_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_payroll_export_user FOREIGN KEY (exported_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS master_schedules (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(140) NOT NULL,
    effective_from DATE NOT NULL,
    effective_to DATE NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_master_schedule_org (organization_id,active,effective_from),
    CONSTRAINT fk_master_schedule_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_master_schedule_creator FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS master_schedule_entries (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    master_schedule_id BIGINT UNSIGNED NOT NULL,
    organization_id BIGINT UNSIGNED NOT NULL,
    membership_id BIGINT UNSIGNED NULL,
    weekday TINYINT UNSIGNED NOT NULL,
    location_id BIGINT UNSIGNED NOT NULL,
    department_id BIGINT UNSIGNED NOT NULL,
    position_id BIGINT UNSIGNED NOT NULL,
    starts_at TIME NOT NULL,
    ends_at TIME NOT NULL,
    notes VARCHAR(500) NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_master_entry_pattern (master_schedule_id,weekday,starts_at),
    CONSTRAINT fk_master_entry_master FOREIGN KEY (master_schedule_id) REFERENCES master_schedules(id) ON DELETE CASCADE,
    CONSTRAINT fk_master_entry_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_master_entry_member FOREIGN KEY (membership_id) REFERENCES memberships(id) ON DELETE SET NULL,
    CONSTRAINT fk_master_entry_location FOREIGN KEY (location_id) REFERENCES locations(id),
    CONSTRAINT fk_master_entry_department FOREIGN KEY (department_id) REFERENCES departments(id),
    CONSTRAINT fk_master_entry_position FOREIGN KEY (position_id) REFERENCES positions(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS master_schedule_generations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    master_schedule_id BIGINT UNSIGNED NOT NULL,
    organization_id BIGINT UNSIGNED NOT NULL,
    week_starts_on DATE NOT NULL,
    shift_count INT UNSIGNED NOT NULL DEFAULT 0,
    conflict_count INT UNSIGNED NOT NULL DEFAULT 0,
    generated_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_master_generation_week (master_schedule_id,week_starts_on),
    CONSTRAINT fk_master_generation_master FOREIGN KEY (master_schedule_id) REFERENCES master_schedules(id) ON DELETE CASCADE,
    CONSTRAINT fk_master_generation_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_master_generation_user FOREIGN KEY (generated_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS master_schedule_generation_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    generation_id BIGINT UNSIGNED NOT NULL,
    master_entry_id BIGINT UNSIGNED NOT NULL,
    shift_id BIGINT UNSIGNED NULL,
    target_date DATE NOT NULL,
    result ENUM('created','conflict','skipped') NOT NULL,
    conflict_reasons JSON NULL,
    CONSTRAINT fk_master_item_generation FOREIGN KEY (generation_id) REFERENCES master_schedule_generations(id) ON DELETE CASCADE,
    CONSTRAINT fk_master_item_entry FOREIGN KEY (master_entry_id) REFERENCES master_schedule_entries(id),
    CONSTRAINT fk_master_item_shift FOREIGN KEY (shift_id) REFERENCES shifts(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS master_schedule_versions (
    master_schedule_id BIGINT UNSIGNED PRIMARY KEY,
    source_master_schedule_id BIGINT UNSIGNED NULL,
    version_number INT UNSIGNED NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_master_version_schedule FOREIGN KEY (master_schedule_id) REFERENCES master_schedules(id) ON DELETE CASCADE,
    CONSTRAINT fk_master_version_source FOREIGN KEY (source_master_schedule_id) REFERENCES master_schedules(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS schedule_exceptions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    exception_date DATE NOT NULL,
    name VARCHAR(140) NOT NULL,
    exception_type ENUM('closed','special_hours','alternate_master') NOT NULL DEFAULT 'closed',
    starts_at TIME NULL,
    ends_at TIME NULL,
    alternate_master_schedule_id BIGINT UNSIGNED NULL,
    notes VARCHAR(500) NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_schedule_exception (organization_id,exception_date),
    CONSTRAINT fk_schedule_exception_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_schedule_exception_master FOREIGN KEY (alternate_master_schedule_id) REFERENCES master_schedules(id) ON DELETE SET NULL,
    CONSTRAINT fk_schedule_exception_user FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS master_generation_resolutions (
    generation_item_id BIGINT UNSIGNED PRIMARY KEY,
    resolution ENUM('reassigned','override','left_open','dismissed') NOT NULL,
    membership_id BIGINT UNSIGNED NULL,
    notes VARCHAR(500) NULL,
    resolved_by BIGINT UNSIGNED NOT NULL,
    resolved_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_master_resolution_item FOREIGN KEY (generation_item_id) REFERENCES master_schedule_generation_items(id) ON DELETE CASCADE,
    CONSTRAINT fk_master_resolution_member FOREIGN KEY (membership_id) REFERENCES memberships(id) ON DELETE SET NULL,
    CONSTRAINT fk_master_resolution_user FOREIGN KEY (resolved_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS master_generation_publications (
    generation_id BIGINT UNSIGNED PRIMARY KEY,
    status ENUM('draft','published') NOT NULL DEFAULT 'draft',
    published_by BIGINT UNSIGNED NULL,
    published_at TIMESTAMP NULL,
    CONSTRAINT fk_master_publication_generation FOREIGN KEY (generation_id) REFERENCES master_schedule_generations(id) ON DELETE CASCADE,
    CONSTRAINT fk_master_publication_user FOREIGN KEY (published_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS shift_history (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    shift_id BIGINT UNSIGNED NOT NULL,
    action VARCHAR(80) NOT NULL,
    before_json JSON NULL,
    after_json JSON NULL,
    reason VARCHAR(500) NULL,
    changed_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_shift_history (organization_id,shift_id,created_at),
    CONSTRAINT fk_shift_history_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_shift_history_shift FOREIGN KEY (shift_id) REFERENCES shifts(id) ON DELETE CASCADE,
    CONSTRAINT fk_shift_history_user FOREIGN KEY (changed_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notification_preferences (
    membership_id BIGINT UNSIGNED PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    schedule_published TINYINT(1) NOT NULL DEFAULT 1,
    schedule_changed TINYINT(1) NOT NULL DEFAULT 1,
    coverage_offers TINYINT(1) NOT NULL DEFAULT 1,
    request_decisions TINYINT(1) NOT NULL DEFAULT 1,
    credential_reminders TINYINT(1) NOT NULL DEFAULT 1,
    in_app_enabled TINYINT(1) NOT NULL DEFAULT 1,
    email_enabled TINYINT(1) NOT NULL DEFAULT 0,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_notification_pref_member FOREIGN KEY (membership_id) REFERENCES memberships(id) ON DELETE CASCADE,
    CONSTRAINT fk_notification_pref_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS membership_permissions (
    membership_id BIGINT UNSIGNED PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    can_schedule TINYINT(1) NOT NULL DEFAULT 0,
    can_approve TINYINT(1) NOT NULL DEFAULT 0,
    can_manage_payroll TINYINT(1) NOT NULL DEFAULT 0,
    can_manage_credentials TINYINT(1) NOT NULL DEFAULT 0,
    read_only TINYINT(1) NOT NULL DEFAULT 0,
    updated_by BIGINT UNSIGNED NOT NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_membership_permission_member FOREIGN KEY (membership_id) REFERENCES memberships(id) ON DELETE CASCADE,
    CONSTRAINT fk_membership_permission_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_membership_permission_user FOREIGN KEY (updated_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS membership_scopes (
    membership_id BIGINT UNSIGNED NOT NULL,
    scope_type ENUM('location','department') NOT NULL,
    resource_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (membership_id,scope_type,resource_id),
    CONSTRAINT fk_membership_scope_member FOREIGN KEY (membership_id) REFERENCES memberships(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS time_off_policies (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    request_type_id BIGINT UNSIGNED NULL,
    name VARCHAR(140) NOT NULL,
    annual_hours DECIMAL(8,2) NOT NULL DEFAULT 0,
    minimum_notice_days INT UNSIGNED NOT NULL DEFAULT 0,
    approval_levels TINYINT UNSIGNED NOT NULL DEFAULT 1,
    active TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE KEY uq_time_off_policy (organization_id,name),
    CONSTRAINT fk_time_off_policy_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_time_off_policy_type FOREIGN KEY (request_type_id) REFERENCES request_types(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS time_off_balances (
    organization_id BIGINT UNSIGNED NOT NULL,
    membership_id BIGINT UNSIGNED NOT NULL,
    policy_id BIGINT UNSIGNED NOT NULL,
    balance_hours DECIMAL(8,2) NOT NULL DEFAULT 0,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (membership_id,policy_id),
    CONSTRAINT fk_time_off_balance_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_time_off_balance_member FOREIGN KEY (membership_id) REFERENCES memberships(id) ON DELETE CASCADE,
    CONSTRAINT fk_time_off_balance_policy FOREIGN KEY (policy_id) REFERENCES time_off_policies(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS request_blackouts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    starts_on DATE NOT NULL,
    ends_on DATE NOT NULL,
    name VARCHAR(140) NOT NULL,
    reason VARCHAR(500) NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_request_blackout_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_request_blackout_user FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS callout_escalations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    callout_id BIGINT UNSIGNED NOT NULL,
    wave_number INT UNSIGNED NOT NULL,
    response_due_at DATETIME NOT NULL,
    audience VARCHAR(120) NOT NULL DEFAULT 'eligible_staff',
    status ENUM('open','closed','expired') NOT NULL DEFAULT 'open',
    created_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_callout_wave (callout_id,wave_number),
    CONSTRAINT fk_callout_escalation_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_callout_escalation_callout FOREIGN KEY (callout_id) REFERENCES callouts(id) ON DELETE CASCADE,
    CONSTRAINT fk_callout_escalation_user FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS attendance_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    membership_id BIGINT UNSIGNED NOT NULL,
    shift_id BIGINT UNSIGNED NULL,
    event_type ENUM('callout','late_arrival','early_departure','no_show') NOT NULL,
    occurred_at DATETIME NOT NULL,
    minutes_affected INT UNSIGNED NOT NULL DEFAULT 0,
    notes VARCHAR(500) NULL,
    recorded_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_attendance_event_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_attendance_event_member FOREIGN KEY (membership_id) REFERENCES memberships(id) ON DELETE CASCADE,
    CONSTRAINT fk_attendance_event_shift FOREIGN KEY (shift_id) REFERENCES shifts(id) ON DELETE SET NULL,
    CONSTRAINT fk_attendance_event_user FOREIGN KEY (recorded_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS coverage_demand_forecasts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    location_id BIGINT UNSIGNED NOT NULL,
    department_id BIGINT UNSIGNED NOT NULL,
    forecast_date DATE NOT NULL,
    starts_at TIME NOT NULL,
    ends_at TIME NOT NULL,
    required_count INT UNSIGNED NOT NULL,
    required_skill_mix VARCHAR(500) NULL,
    float_pool_count INT UNSIGNED NOT NULL DEFAULT 0,
    break_coverage_count INT UNSIGNED NOT NULL DEFAULT 0,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_coverage_forecast_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_coverage_forecast_location FOREIGN KEY (location_id) REFERENCES locations(id),
    CONSTRAINT fk_coverage_forecast_department FOREIGN KEY (department_id) REFERENCES departments(id),
    CONSTRAINT fk_coverage_forecast_user FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS command_center_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    item_type VARCHAR(60) NOT NULL,
    title VARCHAR(180) NOT NULL,
    urgency_score INT UNSIGNED NOT NULL DEFAULT 50,
    owner_membership_id BIGINT UNSIGNED NULL,
    due_at DATETIME NULL,
    snoozed_until DATETIME NULL,
    status ENUM('open','resolved','dismissed') NOT NULL DEFAULT 'open',
    resolution_notes VARCHAR(500) NULL,
    resolved_by BIGINT UNSIGNED NULL,
    resolved_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_command_queue (organization_id,status,urgency_score,due_at),
    CONSTRAINT fk_command_item_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_command_item_owner FOREIGN KEY (owner_membership_id) REFERENCES memberships(id) ON DELETE SET NULL,
    CONSTRAINT fk_command_item_resolver FOREIGN KEY (resolved_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS access_delegations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    membership_id BIGINT UNSIGNED NOT NULL,
    capability ENUM('schedule','approve','payroll','credentials') NOT NULL,
    starts_at DATETIME NOT NULL,
    expires_at DATETIME NOT NULL,
    granted_by BIGINT UNSIGNED NOT NULL,
    revoked_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_access_delegation_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_access_delegation_member FOREIGN KEY (membership_id) REFERENCES memberships(id) ON DELETE CASCADE,
    CONSTRAINT fk_access_delegation_user FOREIGN KEY (granted_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notification_delivery_settings (
    membership_id BIGINT UNSIGNED PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    quiet_starts_at TIME NULL,
    quiet_ends_at TIME NULL,
    digest_frequency ENUM('immediate','daily','weekly') NOT NULL DEFAULT 'immediate',
    push_enabled TINYINT(1) NOT NULL DEFAULT 0,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_delivery_setting_member FOREIGN KEY (membership_id) REFERENCES memberships(id) ON DELETE CASCADE,
    CONSTRAINT fk_delivery_setting_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notification_templates (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    event_key VARCHAR(80) NOT NULL,
    channel ENUM('in_app','email','push') NOT NULL DEFAULT 'in_app',
    subject_template VARCHAR(180) NOT NULL,
    body_template TEXT NOT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE KEY uq_notification_template (organization_id,event_key,channel),
    CONSTRAINT fk_notification_template_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notification_deliveries (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    membership_id BIGINT UNSIGNED NOT NULL,
    notification_id BIGINT UNSIGNED NULL,
    channel ENUM('in_app','email','push') NOT NULL,
    status ENUM('queued','sent','failed','suppressed') NOT NULL DEFAULT 'queued',
    failure_message VARCHAR(500) NULL,
    attempts INT UNSIGNED NOT NULL DEFAULT 0,
    next_attempt_at DATETIME NULL,
    sent_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_delivery_queue (status,next_attempt_at),
    CONSTRAINT fk_notification_delivery_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_notification_delivery_member FOREIGN KEY (membership_id) REFERENCES memberships(id) ON DELETE CASCADE,
    CONSTRAINT fk_notification_delivery_notification FOREIGN KEY (notification_id) REFERENCES notifications(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS position_credential_requirements (
    organization_id BIGINT UNSIGNED NOT NULL,
    position_id BIGINT UNSIGNED NOT NULL,
    credential_type_id BIGINT UNSIGNED NOT NULL,
    required TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (position_id,credential_type_id),
    CONSTRAINT fk_position_credential_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_position_credential_position FOREIGN KEY (position_id) REFERENCES positions(id) ON DELETE CASCADE,
    CONSTRAINT fk_position_credential_type FOREIGN KEY (credential_type_id) REFERENCES credential_types(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS credential_renewals (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    member_credential_id BIGINT UNSIGNED NOT NULL,
    requested_on DATE NOT NULL,
    due_on DATE NULL,
    status ENUM('requested','submitted','verified','rejected') NOT NULL DEFAULT 'requested',
    notes VARCHAR(500) NULL,
    requested_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_credential_renewal_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_credential_renewal_credential FOREIGN KEY (member_credential_id) REFERENCES member_credentials(id) ON DELETE CASCADE,
    CONSTRAINT fk_credential_renewal_user FOREIGN KEY (requested_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS credential_documents (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    member_credential_id BIGINT UNSIGNED NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    storage_name VARCHAR(255) NOT NULL UNIQUE,
    mime_type VARCHAR(120) NOT NULL,
    file_size BIGINT UNSIGNED NOT NULL,
    uploaded_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_credential_document_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_credential_document_credential FOREIGN KEY (member_credential_id) REFERENCES member_credentials(id) ON DELETE CASCADE,
    CONSTRAINT fk_credential_document_user FOREIGN KEY (uploaded_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS labor_periods (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    starts_on DATE NOT NULL,
    ends_on DATE NOT NULL,
    status ENUM('open','locked') NOT NULL DEFAULT 'open',
    locked_by BIGINT UNSIGNED NULL,
    locked_at DATETIME NULL,
    UNIQUE KEY uq_labor_period (organization_id,starts_on,ends_on),
    CONSTRAINT fk_labor_period_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_labor_period_user FOREIGN KEY (locked_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS time_entry_exceptions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    membership_id BIGINT UNSIGNED NOT NULL,
    time_entry_id BIGINT UNSIGNED NULL,
    exception_type ENUM('missed_in','missed_out','late','early','long_break','short_break','unscheduled') NOT NULL,
    exception_date DATE NOT NULL,
    status ENUM('open','resolved','dismissed') NOT NULL DEFAULT 'open',
    notes VARCHAR(500) NULL,
    resolved_by BIGINT UNSIGNED NULL,
    resolved_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_time_exception_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_time_exception_member FOREIGN KEY (membership_id) REFERENCES memberships(id) ON DELETE CASCADE,
    CONSTRAINT fk_time_exception_entry FOREIGN KEY (time_entry_id) REFERENCES time_entries(id) ON DELETE CASCADE,
    CONSTRAINT fk_time_exception_resolver FOREIGN KEY (resolved_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS break_compliance_rules (
    organization_id BIGINT UNSIGNED PRIMARY KEY,
    shift_minutes_threshold INT UNSIGNED NOT NULL DEFAULT 360,
    required_break_minutes INT UNSIGNED NOT NULL DEFAULT 30,
    updated_by BIGINT UNSIGNED NOT NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_break_rule_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_break_rule_user FOREIGN KEY (updated_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS fairness_settings (
    organization_id BIGINT UNSIGNED PRIMARY KEY,
    hours_weight DECIMAL(6,2) NOT NULL DEFAULT 1,
    opening_weight DECIMAL(6,2) NOT NULL DEFAULT 2,
    closing_weight DECIMAL(6,2) NOT NULL DEFAULT 2,
    weekend_weight DECIMAL(6,2) NOT NULL DEFAULT 2,
    declined_offer_weight DECIMAL(6,2) NOT NULL DEFAULT 0,
    cost_weight DECIMAL(6,2) NOT NULL DEFAULT 0,
    updated_by BIGINT UNSIGNED NOT NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_fairness_setting_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_fairness_setting_user FOREIGN KEY (updated_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS recommendation_outcomes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    shift_id BIGINT UNSIGNED NOT NULL,
    recommended_membership_id BIGINT UNSIGNED NULL,
    selected_membership_id BIGINT UNSIGNED NULL,
    recommendation_score DECIMAL(10,2) NULL,
    outcome ENUM('accepted','declined','overridden','not_used') NOT NULL,
    manager_reason VARCHAR(500) NULL,
    recorded_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_recommendation_outcome_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_recommendation_outcome_shift FOREIGN KEY (shift_id) REFERENCES shifts(id) ON DELETE CASCADE,
    CONSTRAINT fk_recommendation_recommended FOREIGN KEY (recommended_membership_id) REFERENCES memberships(id) ON DELETE SET NULL,
    CONSTRAINT fk_recommendation_selected FOREIGN KEY (selected_membership_id) REFERENCES memberships(id) ON DELETE SET NULL,
    CONSTRAINT fk_recommendation_user FOREIGN KEY (recorded_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS schedule_acknowledgments (
    organization_id BIGINT UNSIGNED NOT NULL,
    membership_id BIGINT UNSIGNED NOT NULL,
    shift_id BIGINT UNSIGNED NOT NULL,
    acknowledged_at DATETIME NOT NULL,
    PRIMARY KEY (membership_id,shift_id),
    CONSTRAINT fk_schedule_ack_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_schedule_ack_member FOREIGN KEY (membership_id) REFERENCES memberships(id) ON DELETE CASCADE,
    CONSTRAINT fk_schedule_ack_shift FOREIGN KEY (shift_id) REFERENCES shifts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS saved_views (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    membership_id BIGINT UNSIGNED NOT NULL,
    view_type ENUM('report','schedule','search') NOT NULL,
    name VARCHAR(140) NOT NULL,
    route VARCHAR(120) NOT NULL,
    parameters_json JSON NOT NULL,
    favorite TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_saved_view_name (membership_id,view_type,name),
    CONSTRAINT fk_saved_view_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_saved_view_member FOREIGN KEY (membership_id) REFERENCES memberships(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS navigation_activity (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    membership_id BIGINT UNSIGNED NOT NULL,
    route VARCHAR(120) NOT NULL,
    title VARCHAR(180) NOT NULL,
    visited_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_navigation_recent (membership_id,visited_at),
    CONSTRAINT fk_navigation_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_navigation_member FOREIGN KEY (membership_id) REFERENCES memberships(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS data_import_jobs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    import_type ENUM('locations','departments','positions','coverage_requirements') NOT NULL,
    status ENUM('preview','committed','rejected','rolled_back') NOT NULL DEFAULT 'preview',
    source_name VARCHAR(255) NULL,
    row_count INT UNSIGNED NOT NULL DEFAULT 0,
    valid_count INT UNSIGNED NOT NULL DEFAULT 0,
    invalid_count INT UNSIGNED NOT NULL DEFAULT 0,
    created_by BIGINT UNSIGNED NOT NULL,
    committed_at DATETIME NULL,
    rolled_back_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_data_import_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_data_import_user FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS data_import_rows (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    import_job_id BIGINT UNSIGNED NOT NULL,
    row_number INT UNSIGNED NOT NULL,
    payload_json JSON NOT NULL,
    status ENUM('valid','invalid','committed','rolled_back') NOT NULL,
    error_message VARCHAR(500) NULL,
    created_resource_id BIGINT UNSIGNED NULL,
    CONSTRAINT fk_data_import_row_job FOREIGN KEY (import_job_id) REFERENCES data_import_jobs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS background_jobs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NULL,
    job_type VARCHAR(100) NOT NULL,
    payload_json JSON NOT NULL,
    status ENUM('queued','running','completed','failed') NOT NULL DEFAULT 'queued',
    attempts INT UNSIGNED NOT NULL DEFAULT 0,
    available_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    locked_at DATETIME NULL,
    completed_at DATETIME NULL,
    failure_message VARCHAR(500) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_background_queue (status,available_at),
    CONSTRAINT fk_background_job_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS support_requests (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    membership_id BIGINT UNSIGNED NOT NULL,
    category ENUM('question','defect','access','data','security') NOT NULL DEFAULT 'question',
    subject VARCHAR(180) NOT NULL,
    description TEXT NOT NULL,
    status ENUM('open','in_progress','resolved','closed') NOT NULL DEFAULT 'open',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_support_request_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_support_request_member FOREIGN KEY (membership_id) REFERENCES memberships(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS email_verification_tokens (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_email_verification_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS password_reset_tokens (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_password_reset_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS login_attempts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(190) NOT NULL,
    ip_address VARCHAR(45) NULL,
    successful TINYINT(1) NOT NULL DEFAULT 0,
    attempted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_login_attempt (email,ip_address,attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_sessions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    session_hash CHAR(64) NOT NULL UNIQUE,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(500) NULL,
    last_seen_at DATETIME NOT NULL,
    revoked_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_user_session_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS verified_emails (
    user_id BIGINT UNSIGNED PRIMARY KEY,
    verified_at DATETIME NOT NULL,
    CONSTRAINT fk_verified_email_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS application_errors (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    route VARCHAR(190) NULL,
    exception_class VARCHAR(190) NOT NULL,
    message VARCHAR(1000) NOT NULL,
    request_id VARCHAR(80) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_application_error_created (created_at),
    CONSTRAINT fk_application_error_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS organization_settings (
    organization_id BIGINT UNSIGNED PRIMARY KEY,
    display_name VARCHAR(160) NULL,
    timezone VARCHAR(80) NOT NULL DEFAULT 'America/New_York',
    week_starts_on TINYINT UNSIGNED NOT NULL DEFAULT 1,
    operating_hours_json JSON NULL,
    primary_color CHAR(7) NOT NULL DEFAULT '#2867d8',
    secondary_color CHAR(7) NOT NULL DEFAULT '#7756d9',
    logo_text VARCHAR(40) NULL,
    updated_by BIGINT UNSIGNED NOT NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_org_settings_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_org_settings_user FOREIGN KEY (updated_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS department_schedule_defaults (
    department_id BIGINT UNSIGNED PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    default_starts_at TIME NULL,
    default_ends_at TIME NULL,
    minimum_staff SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    allow_cross_department TINYINT(1) NOT NULL DEFAULT 0,
    updated_by BIGINT UNSIGNED NOT NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_department_default_department FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE,
    CONSTRAINT fk_department_default_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_department_default_user FOREIGN KEY (updated_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS employee_import_batches (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    status ENUM('validated','rejected','imported') NOT NULL,
    row_count INT UNSIGNED NOT NULL DEFAULT 0,
    valid_count INT UNSIGNED NOT NULL DEFAULT 0,
    invalid_count INT UNSIGNED NOT NULL DEFAULT 0,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_employee_import_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_employee_import_user FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS employee_import_rows (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    batch_id BIGINT UNSIGNED NOT NULL,
    row_number INT UNSIGNED NOT NULL,
    email VARCHAR(190) NULL,
    role VARCHAR(40) NULL,
    location_name VARCHAR(140) NULL,
    department_name VARCHAR(140) NULL,
    position_name VARCHAR(140) NULL,
    status ENUM('valid','invalid','imported') NOT NULL,
    error_message VARCHAR(500) NULL,
    invitation_id BIGINT UNSIGNED NULL,
    CONSTRAINT fk_employee_import_row_batch FOREIGN KEY (batch_id) REFERENCES employee_import_batches(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS employment_records (
    membership_id BIGINT UNSIGNED PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    starts_on DATE NULL,
    ends_on DATE NULL,
    employment_status ENUM('preboarding','active','leave','ended') NOT NULL DEFAULT 'active',
    separation_reason VARCHAR(500) NULL,
    updated_by BIGINT UNSIGNED NOT NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_employment_record_member FOREIGN KEY (membership_id) REFERENCES memberships(id) ON DELETE CASCADE,
    CONSTRAINT fk_employment_record_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_employment_record_user FOREIGN KEY (updated_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS secondary_work_assignments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    membership_id BIGINT UNSIGNED NOT NULL,
    location_id BIGINT UNSIGNED NULL,
    department_id BIGINT UNSIGNED NULL,
    position_id BIGINT UNSIGNED NULL,
    effective_from DATE NULL,
    effective_to DATE NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_secondary_assignment_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_secondary_assignment_member FOREIGN KEY (membership_id) REFERENCES memberships(id) ON DELETE CASCADE,
    CONSTRAINT fk_secondary_assignment_location FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE SET NULL,
    CONSTRAINT fk_secondary_assignment_department FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
    CONSTRAINT fk_secondary_assignment_position FOREIGN KEY (position_id) REFERENCES positions(id) ON DELETE SET NULL,
    CONSTRAINT fk_secondary_assignment_user FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS employee_manager_notes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    membership_id BIGINT UNSIGNED NOT NULL,
    note_text VARCHAR(2000) NOT NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_manager_note_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_manager_note_member FOREIGN KEY (membership_id) REFERENCES memberships(id) ON DELETE CASCADE,
    CONSTRAINT fk_manager_note_user FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS employee_onboarding_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    membership_id BIGINT UNSIGNED NOT NULL,
    item_name VARCHAR(180) NOT NULL,
    due_on DATE NULL,
    completed_at DATETIME NULL,
    completed_by BIGINT UNSIGNED NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_onboarding_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_onboarding_member FOREIGN KEY (membership_id) REFERENCES memberships(id) ON DELETE CASCADE,
    CONSTRAINT fk_onboarding_completed_user FOREIGN KEY (completed_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_onboarding_created_user FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS employee_profile_snapshots (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    membership_id BIGINT UNSIGNED NOT NULL,
    snapshot_type ENUM('manual','assignment_change','offboarding') NOT NULL DEFAULT 'manual',
    profile_json JSON NOT NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_profile_snapshot_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_profile_snapshot_member FOREIGN KEY (membership_id) REFERENCES memberships(id) ON DELETE CASCADE,
    CONSTRAINT fk_profile_snapshot_user FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS employee_documents (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    membership_id BIGINT UNSIGNED NOT NULL,
    document_name VARCHAR(190) NOT NULL,
    storage_name VARCHAR(190) NOT NULL UNIQUE,
    mime_type VARCHAR(100) NOT NULL,
    file_size BIGINT UNSIGNED NOT NULL,
    uploaded_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_employee_document_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_employee_document_member FOREIGN KEY (membership_id) REFERENCES memberships(id) ON DELETE CASCADE,
    CONSTRAINT fk_employee_document_user FOREIGN KEY (uploaded_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS organization_security_settings (
    organization_id BIGINT UNSIGNED PRIMARY KEY,
    allow_local_accounts TINYINT(1) NOT NULL DEFAULT 0,
    updated_by BIGINT UNSIGNED NOT NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_org_security_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_org_security_user FOREIGN KEY (updated_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS local_accounts (
    user_id BIGINT UNSIGNED PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    username VARCHAR(80) NOT NULL UNIQUE,
    must_change_password TINYINT(1) NOT NULL DEFAULT 1,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_local_account_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_local_account_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_local_account_creator FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
