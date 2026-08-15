<?php

declare(strict_types=1);

$data = require __DIR__ . '/src/demo-data.php';

$scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
$basePath = rtrim(dirname($scriptName), '/.');
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
if ($basePath !== '' && $basePath !== '/' && str_starts_with($requestPath, $basePath)) {
    $requestPath = substr($requestPath, strlen($basePath)) ?: '/';
}
$route = trim($requestPath, '/');
$route = $route === '' || $route === 'index.php' ? 'dashboard' : $route;
$allowedRoutes = ['dashboard', 'schedule', 'coverage', 'team', 'organization'];
if (!in_array($route, $allowedRoutes, true)) {
    http_response_code(404);
    $route = 'not-found';
}

function url_for(string $path = ''): string
{
    global $basePath;
    return ($basePath === '/' ? '' : $basePath) . '/' . ltrim($path, '/');
}

function esc(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function icon(string $name): string
{
    $icons = [
        'grid' => '<path d="M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h6v6h-6z"/>',
        'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/>',
        'coverage' => '<circle cx="9" cy="8" r="4"/><path d="M2.5 20c.7-4 2.9-6 6.5-6s5.8 2 6.5 6M17 8v6M14 11h6"/>',
        'team' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',
        'org' => '<path d="M3 21h18M5 21V9l7-5 7 5v12M9 21v-6h6v6"/>',
        'bell' => '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/>',
        'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'arrow' => '<path d="m9 18 6-6-6-6"/>',
        'chevron' => '<path d="m9 18 6-6-6-6"/>',
        'spark' => '<path d="m12 3 1.4 4.1L17.5 8.5l-4.1 1.4L12 14l-1.4-4.1-4.1-1.4 4.1-1.4zM18 15l.8 2.2L21 18l-2.2.8L18 21l-.8-2.2L15 18l2.2-.8z"/>',
        'building' => '<rect x="4" y="3" width="16" height="18" rx="2"/><path d="M8 7h2M14 7h2M8 11h2M14 11h2M8 15h2M14 15h2"/>',
        'layers' => '<path d="m12 2 9 5-9 5-9-5zM3 12l9 5 9-5M3 17l9 5 9-5"/>',
        'briefcase' => '<rect x="3" y="7" width="18" height="13" rx="2"/><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M3 12h18"/>',
    ];
    return '<svg class="icon" viewBox="0 0 24 24" aria-hidden="true">' . ($icons[$name] ?? $icons['grid']) . '</svg>';
}

$titles = [
    'dashboard' => ['Good morning, Andrew', 'Here’s how Harbor Point is staffed today.'],
    'schedule' => ['Schedule', 'Build, review, and publish your ambulatory team schedule.'],
    'coverage' => ['Daily coverage', 'See every provider, station, and work function at a glance.'],
    'team' => ['People', 'Manage staff, positions, departments, and reporting relationships.'],
    'organization' => ['Organization builder', 'Shape Atlas around the way your practice actually works.'],
    'not-found' => ['Page not found', 'The page you requested is not part of this preview.'],
];
[$pageTitle, $pageSubtitle] = $titles[$route];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#17243f">
    <title><?= esc($pageTitle) ?> · Atlas</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= esc(url_for('assets/app.css')) ?>">
</head>
<body>
<div class="app-shell">
    <aside class="sidebar" id="sidebar">
        <div class="brand"><span class="brand-mark">A</span><span>Atlas</span></div>
        <button class="org-switcher" type="button" data-toast="Organization switching will be wired in a later build.">
            <span class="org-monogram">HP</span>
            <span><strong><?= esc($data['organization']['name']) ?></strong><small><?= esc($data['organization']['location']) ?></small></span>
            <span class="switch-dots">⌄</span>
        </button>
        <nav class="primary-nav" aria-label="Primary navigation">
            <?php foreach ([
                'dashboard' => ['grid', 'Overview'],
                'schedule' => ['calendar', 'Schedule'],
                'coverage' => ['coverage', 'Coverage'],
                'team' => ['team', 'People'],
                'organization' => ['org', 'Organization'],
            ] as $path => [$navIcon, $label]): ?>
                <a href="<?= esc(url_for($path === 'dashboard' ? '' : $path)) ?>" class="nav-link <?= $route === $path ? 'active' : '' ?>"><?= icon($navIcon) ?><span><?= esc($label) ?></span><?php if ($path === 'coverage'): ?><b class="nav-badge">2</b><?php endif; ?></a>
            <?php endforeach; ?>
        </nav>
        <div class="sidebar-spacer"></div>
        <div class="preview-card"><span class="preview-icon"><?= icon('spark') ?></span><strong>Visual preview</strong><p>Explore the foundation before workflow logic is connected.</p></div>
        <div class="user-card"><span class="avatar">AW</span><span><strong>Andrew Wentz</strong><small>Organization owner</small></span><button aria-label="Account menu">•••</button></div>
    </aside>

    <main class="main-area">
        <header class="topbar">
            <button class="menu-button" id="menuButton" aria-label="Open navigation">☰</button>
            <div class="global-search"><?= icon('search') ?><input type="search" placeholder="Search people, shifts, departments…" aria-label="Search"></div>
            <div class="top-actions"><button class="icon-button" aria-label="Notifications" data-toast="You have 4 schedule notifications."><?= icon('bell') ?><i></i></button><button class="help-button">?</button></div>
        </header>

        <div class="page-wrap">
            <header class="page-header">
                <div><p class="eyebrow"><?= $route === 'dashboard' ? 'Saturday, August 15' : 'Atlas Staffing' ?></p><h1><?= esc($pageTitle) ?></h1><p><?= esc($pageSubtitle) ?></p></div>
                <?php if ($route === 'dashboard'): ?><a class="button secondary" href="<?= esc(url_for('schedule')) ?>"><?= icon('calendar') ?> View full schedule</a><?php endif; ?>
                <?php if ($route === 'schedule'): ?><button class="button primary" data-toast="Shift creation is part of the next functional pass."><?= icon('plus') ?> Add shift</button><?php endif; ?>
                <?php if ($route === 'team'): ?><button class="button primary" data-toast="Invitations will be connected after the visual system is approved."><?= icon('plus') ?> Invite staff</button><?php endif; ?>
                <?php if ($route === 'organization'): ?><button class="button primary" data-toast="Your visual organization structure is up to date.">Save structure</button><?php endif; ?>
            </header>

            <?php if ($route === 'dashboard'): ?>
                <section class="summary-grid">
                    <?php foreach ($data['summary'] as $item): ?><article class="metric-card <?= esc($item['tone']) ?>"><span class="metric-pip"></span><div><strong><?= esc($item['value']) ?></strong><span><?= esc($item['label']) ?></span><small><?= esc($item['detail']) ?></small></div></article><?php endforeach; ?>
                </section>
                <section class="dashboard-grid">
                    <article class="panel span-2">
                        <div class="panel-header"><div><h2>Today’s coverage</h2><p>Saturday, August 15 · Clinton Primary Care</p></div><a href="<?= esc(url_for('coverage')) ?>">Open coverage board <?= icon('arrow') ?></a></div>
                        <div class="coverage-list compact">
                            <?php foreach (array_slice($data['coverage'], 0, 5) as $row): ?><div class="coverage-row"><span class="status-dot <?= esc($row['state']) ?>"></span><span class="coverage-time"><?= esc($row['time']) ?></span><span class="coverage-person"><strong><?= esc($row['person']) ?></strong><small><?= esc($row['role']) ?></small></span><span class="coverage-assignment"><?= esc($row['assignment']) ?></span><span class="state-pill <?= esc($row['state']) ?>"><?= $row['state'] === 'gap' ? 'Needs coverage' : ucfirst(esc($row['state'])) ?></span></div><?php endforeach; ?>
                        </div>
                    </article>
                    <aside class="panel attention-panel"><div class="panel-header"><div><h2>Needs attention</h2><p>Resolve before the day begins</p></div></div><div class="attention-item urgent"><span>MA</span><div><strong>Dr. Chen needs support</strong><p>Medical Assistant · 8:00–12:30</p></div><button aria-label="Review">›</button></div><div class="attention-item"><span>FD</span><div><strong>Check-out desk uncovered</strong><p>PSR · 12:30–5:00</p></div><button aria-label="Review">›</button></div><div class="attention-item"><span>4</span><div><strong>Pending schedule requests</strong><p>2 time off · 2 shift pickups</p></div><button aria-label="Review">›</button></div></aside>
                    <article class="panel span-2"><div class="panel-header"><div><h2>Upcoming schedule</h2><p>August 17–21</p></div><a href="<?= esc(url_for('schedule')) ?>">Manage schedule <?= icon('arrow') ?></a></div><div class="mini-week"><?php foreach ($data['week'] as $day): ?><div class="mini-day"><div class="mini-date"><span><?= esc($day['day']) ?></span><strong><?= esc($day['date']) ?></strong></div><?php foreach ($day['items'] as $item): ?><div class="mini-shift <?= esc($item['tone']) ?>"><strong><?= esc($item['name']) ?></strong><span><?= esc($item['detail']) ?></span></div><?php endforeach; ?></div><?php endforeach; ?></div></article>
                    <aside class="panel"><div class="panel-header"><div><h2>Departments</h2><p>35 active team members</p></div></div><div class="department-list"><?php foreach ($data['departments'] as $dept): ?><div class="department-row"><i style="--dept-color:<?= esc($dept['color']) ?>"></i><span><strong><?= esc($dept['name']) ?></strong><small><?= esc($dept['supervisor']) ?> · <?= esc((string)$dept['count']) ?> people</small></span></div><?php endforeach; ?></div></aside>
                </section>
            <?php elseif ($route === 'schedule'): ?>
                <section class="toolbar-card"><div class="segmented"><button class="active">Week</button><button>Day</button><button>Month</button></div><div class="date-nav"><button>‹</button><strong>August 17–21, 2026</strong><button>›</button></div><div class="filters"><button>Clinton Primary Care⌄</button><button>All departments⌄</button></div></section>
                <section class="panel schedule-panel"><div class="schedule-meta"><span><i class="legend blue"></i> Clinical Support</span><span><i class="legend purple"></i> Front Office</span><span><i class="legend green"></i> Flex</span><span><i class="legend coral"></i> Open shift</span><div class="schedule-stat"><strong>164</strong> scheduled hours</div></div><div class="week-board"><div class="week-label"><span>Team member</span></div><?php foreach ($data['week'] as $day): ?><div class="week-heading"><span><?= esc($day['day']) ?></span><strong><?= esc($day['date']) ?></strong></div><?php endforeach; ?><?php foreach (array_slice($data['people'], 0, 4) as $index => $person): ?><div class="staff-label"><span class="avatar small" style="--avatar-color:<?= esc($person['color']) ?>"><?= esc($person['initials']) ?></span><span><strong><?= esc($person['name']) ?></strong><small><?= esc($person['position']) ?></small></span></div><?php foreach ($data['week'] as $dayIndex => $day): ?><div class="schedule-cell"><?php if (($index + $dayIndex) % 4 !== 3): ?><div class="shift-block <?= ['purple','blue','green','amber'][$index] ?>"><strong><?= $index === 2 ? 'Flex coverage' : '8:00 – 4:30' ?></strong><span><?= esc($index === 0 ? 'Front desk' : ($index === 1 ? 'Provider support' : ($index === 2 ? 'Clinical Support' : 'Referrals'))) ?></span></div><?php else: ?><button class="empty-cell" data-toast="This empty shift can be opened for self-scheduling.">+ Open</button><?php endif; ?></div><?php endforeach; endforeach; ?></div></section>
            <?php elseif ($route === 'coverage'): ?>
                <section class="coverage-hero"><div><span class="live-dot"></span><strong>Saturday coverage is live</strong><p>25 of 27 assignments covered · Last updated just now</p></div><div class="coverage-progress"><span style="width:92%"></span></div><button class="button secondary" data-toast="The shareable coverage view will remain PHI-free.">Share board</button></section>
                <section class="panel"><div class="panel-header"><div><h2>Clinton Primary Care</h2><p>All departments · August 15</p></div><div class="filters"><button>Group by department⌄</button></div></div><div class="coverage-list full"><?php foreach ($data['coverage'] as $row): ?><div class="coverage-row"><span class="status-dot <?= esc($row['state']) ?>"></span><span class="coverage-time"><?= esc($row['time']) ?></span><span class="coverage-person"><strong><?= esc($row['person']) ?></strong><small><?= esc($row['role']) ?></small></span><span class="coverage-assignment"><strong><?= esc($row['assignment']) ?></strong><small><?= esc($row['department']) ?></small></span><span class="state-pill <?= esc($row['state']) ?>"><?= $row['state'] === 'gap' ? 'Needs coverage' : ucfirst(esc($row['state'])) ?></span><button class="row-menu">•••</button></div><?php endforeach; ?></div></section>
            <?php elseif ($route === 'team'): ?>
                <section class="people-summary"><div><span><?= icon('team') ?></span><strong>35</strong><small>Active people</small></div><div><span><?= icon('layers') ?></span><strong>4</strong><small>Departments</small></div><div><span><?= icon('briefcase') ?></span><strong>9</strong><small>Custom positions</small></div><div><span><?= icon('coverage') ?></span><strong>4</strong><small>Supervisor groups</small></div></section>
                <section class="panel"><div class="table-toolbar"><div class="search-field"><?= icon('search') ?><input placeholder="Search people"></div><div class="filters"><button>All departments⌄</button><button>All locations⌄</button></div></div><div class="people-table"><div class="table-row table-head"><span>Team member</span><span>Position</span><span>Department</span><span>Supervisor</span><span>Status</span><span></span></div><?php foreach ($data['people'] as $person): ?><div class="table-row"><span class="person-cell"><i class="avatar small" style="--avatar-color:<?= esc($person['color']) ?>"><?= esc($person['initials']) ?></i><span><strong><?= esc($person['name']) ?></strong><small><?= esc($person['location']) ?></small></span></span><span><?= esc($person['position']) ?></span><span><?= esc($person['department']) ?></span><span><?= esc($person['supervisor']) ?></span><span><b class="state-pill <?= strtolower(esc($person['status'])) ?>"><?= esc($person['status']) ?></b></span><button class="row-menu">•••</button></div><?php endforeach; ?></div></section>
            <?php elseif ($route === 'organization'): ?>
                <section class="builder-layout"><aside class="builder-steps"><p>ORGANIZATION SETUP</p><?php foreach ([['building','Organization details','Complete'],['org','Locations','2 locations'],['layers','Departments','4 departments'],['briefcase','Positions','9 positions'],['coverage','Supervisor groups','4 groups'],['team','Invite your team','35 people']] as $i => $step): ?><button class="builder-step <?= $i === 2 ? 'active' : '' ?>"><span><?= icon($step[0]) ?></span><span><strong><?= esc($step[1]) ?></strong><small><?= esc($step[2]) ?></small></span><b>›</b></button><?php endforeach; ?></aside><section class="panel builder-main"><div class="builder-title"><span class="builder-icon"><?= icon('layers') ?></span><div><h2>Departments</h2><p>Organize people, schedules, communication, and supervisor routing around the way your practice works.</p></div><button class="button primary small-button" data-toast="Department creation will be connected in the next functional pass."><?= icon('plus') ?> Add department</button></div><div class="department-cards"><?php foreach ($data['departments'] as $dept): ?><article class="department-card"><i style="--dept-color:<?= esc($dept['color']) ?>"></i><div class="dept-title"><span class="dept-symbol" style="--dept-color:<?= esc($dept['color']) ?>"><?= strtoupper(substr($dept['name'],0,2)) ?></span><span><strong><?= esc($dept['name']) ?></strong><small><?= esc((string)$dept['count']) ?> people · 2 custom positions</small></span><button class="row-menu">•••</button></div><div class="dept-rule"><span><?= icon('coverage') ?></span><span><small>DEFAULT SUPERVISOR GROUP</small><strong><?= esc($dept['name']) ?> Supervisors</strong><em><?= esc($dept['supervisor']) ?></em></span></div><div class="auto-note"><span>✓</span> Staff added to this department automatically join its supervisor group.</div></article><?php endforeach; ?></div><div class="custom-note"><span><?= icon('spark') ?></span><div><strong>Your organization, your language</strong><p>Department names, position titles, teams, stations, and work functions are completely customizable for each organization.</p></div></div></section></section>
            <?php else: ?><section class="empty-state"><span>404</span><h2>That page wandered off.</h2><p>Return to the Atlas overview to continue exploring.</p><a class="button primary" href="<?= esc(url_for()) ?>">Back to overview</a></section><?php endif; ?>
        </div>
    </main>
</div>
<div class="toast" id="toast" role="status"></div>
<script src="<?= esc(url_for('assets/app.js')) ?>"></script>
</body>
</html>

