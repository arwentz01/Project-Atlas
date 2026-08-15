<?php

declare(strict_types=1);

return [
    'organization' => [
        'name' => 'Harbor Point Medical Group',
        'short_name' => 'HPMG',
        'location' => 'Clinton Primary Care',
    ],
    'summary' => [
        ['value' => '27', 'label' => 'Working today', 'detail' => 'Across 4 departments', 'tone' => 'blue'],
        ['value' => '2', 'label' => 'Coverage gaps', 'detail' => 'Need attention', 'tone' => 'coral'],
        ['value' => '5', 'label' => 'Open shifts', 'detail' => '3 staff eligible', 'tone' => 'violet'],
        ['value' => '4', 'label' => 'Requests', 'detail' => 'Awaiting review', 'tone' => 'amber'],
    ],
    'departments' => [
        ['name' => 'Front Office', 'color' => '#8b5cf6', 'count' => 8, 'supervisor' => 'Monica Reed'],
        ['name' => 'Clinical Support', 'color' => '#2563eb', 'count' => 11, 'supervisor' => 'Angela Morris'],
        ['name' => 'Referrals & Authorizations', 'color' => '#0f9f85', 'count' => 5, 'supervisor' => 'David Kim'],
        ['name' => 'Practice Operations', 'color' => '#e68a2e', 'count' => 3, 'supervisor' => 'Lauren Brooks'],
    ],
    'people' => [
        ['name' => 'Jane Carter', 'initials' => 'JC', 'position' => 'Patient Services Representative', 'department' => 'Front Office', 'supervisor' => 'Monica Reed', 'location' => 'Clinton Primary Care', 'status' => 'Working', 'color' => '#7257d8'],
        ['name' => 'Angel Wade', 'initials' => 'AW', 'position' => 'Senior Medical Assistant', 'department' => 'Clinical Support', 'supervisor' => 'Angela Morris', 'location' => 'Clinton Primary Care', 'status' => 'Working', 'color' => '#2367cf'],
        ['name' => 'Jordan Lee', 'initials' => 'JL', 'position' => 'Clinical Flex Coordinator', 'department' => 'Clinical Support', 'supervisor' => 'Angela Morris', 'location' => 'Clinton Primary Care', 'status' => 'Flex', 'color' => '#0f9f85'],
        ['name' => 'Carlos Bennett', 'initials' => 'CB', 'position' => 'Referral Coordinator', 'department' => 'Referrals & Authorizations', 'supervisor' => 'David Kim', 'location' => 'Central Services', 'status' => 'Remote', 'color' => '#d97706'],
        ['name' => 'Maya Patel', 'initials' => 'MP', 'position' => 'Licensed Practical Nurse', 'department' => 'Clinical Support', 'supervisor' => 'Angela Morris', 'location' => 'Clinton Primary Care', 'status' => 'Working', 'color' => '#db4f68'],
    ],
    'coverage' => [
        ['time' => '7:30 – 4:00', 'person' => 'Jane Carter', 'role' => 'Patient Services Representative', 'assignment' => 'Check-in · Desk A', 'department' => 'Front Office', 'state' => 'covered'],
        ['time' => '8:00 – 4:30', 'person' => 'Angel Wade', 'role' => 'Senior Medical Assistant', 'assignment' => 'Dr. Rivera · Primary support', 'department' => 'Clinical Support', 'state' => 'covered'],
        ['time' => '8:00 – 4:30', 'person' => 'Jordan Lee', 'role' => 'Clinical Flex Coordinator', 'assignment' => 'Family Medicine · Flex coverage', 'department' => 'Clinical Support', 'state' => 'flex'],
        ['time' => '8:00 – 12:30', 'person' => 'Unassigned', 'role' => 'Medical Assistant', 'assignment' => 'Dr. Chen · Primary support', 'department' => 'Clinical Support', 'state' => 'gap'],
        ['time' => '9:00 – 5:30', 'person' => 'Carlos Bennett', 'role' => 'Referral Coordinator', 'assignment' => 'Referral work queue', 'department' => 'Referrals & Authorizations', 'state' => 'remote'],
        ['time' => '12:30 – 5:00', 'person' => 'Unassigned', 'role' => 'Patient Services Representative', 'assignment' => 'Check-out · Desk B', 'department' => 'Front Office', 'state' => 'gap'],
    ],
    'week' => [
        ['day' => 'Mon', 'date' => '17', 'items' => [['name' => 'Angel', 'detail' => 'Rivera', 'tone' => 'blue'], ['name' => 'Jane', 'detail' => 'Check-in', 'tone' => 'purple'], ['name' => 'Jordan', 'detail' => 'Flex', 'tone' => 'green']]],
        ['day' => 'Tue', 'date' => '18', 'items' => [['name' => 'Angel', 'detail' => 'Rivera', 'tone' => 'blue'], ['name' => 'Jane', 'detail' => 'Check-in', 'tone' => 'purple'], ['name' => 'Open shift', 'detail' => 'MA · 8–12:30', 'tone' => 'coral']]],
        ['day' => 'Wed', 'date' => '19', 'items' => [['name' => 'Maya', 'detail' => 'Nurse visits', 'tone' => 'rose'], ['name' => 'Angel', 'detail' => 'Chen', 'tone' => 'blue'], ['name' => 'Carlos', 'detail' => 'Referrals', 'tone' => 'amber']]],
        ['day' => 'Thu', 'date' => '20', 'items' => [['name' => 'Jordan', 'detail' => 'Flex', 'tone' => 'green'], ['name' => 'Jane', 'detail' => 'Check-out', 'tone' => 'purple'], ['name' => 'Open shift', 'detail' => 'PSR · 12:30–5', 'tone' => 'coral']]],
        ['day' => 'Fri', 'date' => '21', 'items' => [['name' => 'Angel', 'detail' => 'Rivera', 'tone' => 'blue'], ['name' => 'Maya', 'detail' => 'Triage', 'tone' => 'rose'], ['name' => 'Carlos', 'detail' => 'Authorizations', 'tone' => 'amber']]],
    ],
];

