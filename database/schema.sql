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
