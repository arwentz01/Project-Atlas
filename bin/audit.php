#!/usr/bin/env php
<?php

declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

$db = Database::connection();
$failures = [];
$passes = 0;

function auditCheck(string $label, callable $check): void
{
    global $failures, $passes;
    try {
        $check();
        $passes++;
        fwrite(STDOUT, "[PASS] {$label}\n");
    } catch (Throwable $exception) {
        $failures[] = $label . ': ' . $exception->getMessage();
        fwrite(STDERR, "[FAIL] {$label}: {$exception->getMessage()}\n");
    }
}

auditCheck('Required schema', function () use ($db): void {
    $missing = SystemStatus::missingTables($db);
    if ($missing) throw new RuntimeException('Missing: ' . implode(', ', $missing));
});

$organizationId = (int)$db->query('SELECT id FROM organizations ORDER BY id LIMIT 1')->fetchColumn();
$membershipId = $organizationId ? (int)$db->query('SELECT id FROM memberships WHERE organization_id=' . $organizationId . ' AND status="active" ORDER BY id LIMIT 1')->fetchColumn() : 0;

if (!$organizationId || !$membershipId) {
    fwrite(STDERR, "[FAIL] Audit requires one organization with an active member.\n");
    exit(1);
}

$atlas = new AtlasRepository($db);
$scheduling = new SchedulingRepository($db);
$today = date('Y-m-d');
$monthStart = date('Y-m-01');
$monthEnd = date('Y-m-t');

$readChecks = [
    'Organization overview' => fn() => $atlas->overview($organizationId),
    'Scheduling catalog' => fn() => $scheduling->catalog($organizationId),
    'Shift list' => fn() => $scheduling->shifts($organizationId),
    'Daily coverage' => fn() => $scheduling->coverage($organizationId, $today),
    'Schedule periods' => fn() => $scheduling->periods($organizationId),
    'Shift requests' => fn() => $scheduling->requests($organizationId),
    'Provider sessions' => fn() => $scheduling->providerSessions($organizationId),
    'Rotations' => fn() => $scheduling->rotations($organizationId),
    'Member schedule' => fn() => $scheduling->memberSchedule($organizationId, $membershipId),
    'Availability' => fn() => $scheduling->availability($organizationId, $membershipId),
    'Pending availability' => fn() => $scheduling->pendingAvailability($organizationId),
    'Request types' => fn() => $scheduling->requestTypes($organizationId),
    'Time off' => fn() => $scheduling->timeOff($organizationId),
    'Shift changes' => fn() => $scheduling->shiftChanges($organizationId),
    'Callouts' => fn() => $scheduling->callouts($organizationId),
    'Callout offers' => fn() => $scheduling->calloutOffers($organizationId),
    'Message threads' => fn() => $scheduling->threads($organizationId, $membershipId),
    'Notifications' => fn() => $scheduling->notifications($organizationId, $membershipId),
    'Coverage requirements' => fn() => $scheduling->coverageRequirements($organizationId, $today),
    'Shift templates' => fn() => $scheduling->shiftTemplates($organizationId),
    'Fairness metrics' => fn() => $scheduling->fairnessMetrics($organizationId, $monthStart, $monthEnd),
    'Operational report' => fn() => $scheduling->reportSummary($organizationId, $monthStart, $monthEnd),
    'Audit trail' => fn() => $scheduling->auditTrail($organizationId, 10),
    'Credential types' => fn() => $scheduling->credentialTypes($organizationId),
    'Credentials' => fn() => $scheduling->credentials($organizationId),
    'Time entries' => fn() => $scheduling->timeEntries($organizationId),
    'Payroll preview' => fn() => $scheduling->payrollPreview($organizationId, $monthStart, $monthEnd),
    'Master schedules' => fn() => $scheduling->masterSchedules($organizationId),
    'Master schedule entries' => fn() => $scheduling->masterScheduleEntries($organizationId),
    'Master schedule generations' => fn() => $scheduling->masterScheduleGenerations($organizationId),
    'Master generation items' => fn() => $scheduling->masterGenerationItems($organizationId, null),
    'Schedule exceptions' => fn() => $scheduling->scheduleExceptions($organizationId),
    'Master employee patterns' => fn() => $scheduling->masterEmployeePatterns($organizationId, 0),
    'Master coverage validation' => fn() => $scheduling->masterCoverageGaps($organizationId, 0),
];

$db->beginTransaction();
foreach ($readChecks as $label => $check) auditCheck($label, $check);
$db->rollBack();

fwrite(STDOUT, "\n{$passes} runtime checks passed.\n");
if ($failures) {
    fwrite(STDERR, count($failures) . " checks failed. Fix these before browser testing.\n");
    exit(1);
}

fwrite(STDOUT, "Atlas repository queries are ready for browser workflow testing.\n");
