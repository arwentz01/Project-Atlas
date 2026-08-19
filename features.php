<?php
declare(strict_types=1);

$sections = [
    [
        'kicker' => 'Foundation',
        'title' => 'Accounts & organizations',
        'icon' => '◎',
        'tone' => 'blue',
        'summary' => 'Secure access, tenant isolation, organization structure, and the operational foundation everything else runs on.',
        'features' => [
            'Registration, login, logout, secure sessions, and CSRF protection',
            'Email-or-username sign-in with organization-controlled local accounts',
            'Administrator-created users who do not require an email address',
            'Temporary-password enforcement for manually created local users',
            'Password reset, email verification, login throttling, and session revocation',
            'Multi-tenant organizations with organization switching',
            'Locations, departments, positions, supervisor groups, and automatic routing',
            'Timezone, branding, operating hours, scheduling defaults, holidays, and closure rules',
            'Invitations, membership lifecycle, resource archiving, and CSV employee import validation',
            'One-click full demo organization with isolated role-based test accounts and operational fixtures',
            'Database migrations, runtime audit, application error recording, and System Status',
        ],
    ],
    [
        'kicker' => 'People',
        'title' => 'Workforce directory',
        'icon' => '◉',
        'tone' => 'violet',
        'summary' => 'One employee workspace for assignments, employment details, credentials, history, onboarding, and secure records.',
        'features' => [
            'Unified employee profiles with search and filters',
            'Primary and secondary locations, departments, and positions',
            'Employment type, expected hours, flex status, status, and effective dates',
            'Qualifications, availability, preferences, time off, credentials, callouts, and work history',
            'Restricted manager notes, onboarding checklists, profile snapshots, and offboarding foundation',
            'Historical schedule preservation after offboarding',
            'Protected employee document storage',
        ],
    ],
    [
        'kicker' => 'Core scheduling',
        'title' => 'Schedule board & shift management',
        'icon' => '▦',
        'tone' => 'green',
        'summary' => 'Build, move, review, and publish schedules with eligibility intelligence embedded into every assignment.',
        'features' => [
            'Draft, open, review, published, and archived schedule periods',
            'Open, assigned, filled, and cancelled shifts',
            'Seven-day employee-centered schedule board',
            'Direct editing and drag-and-drop reassignment',
            'Location and department filters',
            'Ranked replacement recommendations',
            'Week copying, shift cancellation, and audited manager overrides',
            'Detailed before-and-after shift change history',
        ],
    ],
    [
        'kicker' => 'Guardrails',
        'title' => 'Eligibility engine',
        'icon' => '✓',
        'tone' => 'amber',
        'summary' => 'Atlas explains whether an employee can work a shift and why, instead of hiding scheduling rules behind a generic error.',
        'features' => [
            'Exact-position, selected-position, and reusable eligibility-group rules',
            'Location, department, and cross-department validation',
            'Qualification and credential checks',
            'Recurring availability and date-specific exception checks',
            'Time-off conflict detection',
            'Shift overlap detection',
            'Swap-aware trade eligibility',
            'Plain-language eligibility results and audited authorized overrides',
        ],
    ],
    [
        'kicker' => 'Recurring staffing',
        'title' => 'Master Schedule',
        'icon' => 'M',
        'tone' => 'coral',
        'summary' => 'Keep the normal week clean and reusable while callouts, PTO, trades, and one-off changes stay in the live schedule.',
        'features' => [
            'Named and effective-dated weekly baselines',
            'Bulk weekday entry and recurring assignment editing',
            'Versioned baseline copies and archived assignments',
            'Employee weekly-hour summaries',
            'Recurring coverage validation before generation',
            'Holiday, closure, and special-hours awareness',
            'Draft live-week generation with duplicate protection',
            'Conflict detection that converts problem assignments to open shifts for review',
            'Generation history, conflict resolution, and employee publication',
        ],
    ],
    [
        'kicker' => 'Ambulatory operations',
        'title' => 'Coverage intelligence',
        'icon' => '⌁',
        'tone' => 'blue',
        'summary' => 'Model the work that actually needs coverage across providers, stations, teams, and functions rather than relying on job titles alone.',
        'features' => [
            'Providers and provider sessions',
            'Teams, stations, work functions, and organization-defined qualifications',
            'Recurring provider, station, and work-function requirements',
            'Priority-aware daily gap detection',
            'Date-specific ambulatory coverage board',
            'Provider support assignments and support-gap indicators',
            'Master Schedule coverage validation',
        ],
    ],
    [
        'kicker' => 'Reusable patterns',
        'title' => 'Rotations & shift templates',
        'icon' => '↻',
        'tone' => 'violet',
        'summary' => 'Generate repeatable staffing patterns without rebuilding the same shifts and eligibility rules every week.',
        'features' => [
            'Recurring one-, two-, three-, and four-week rotations',
            'Shift generation from rotations',
            'Reusable shift templates',
            'Generate open or assigned shifts from templates',
            'Eligibility checking during generation',
            'Templates and rotations can operate alongside Master Schedule baselines',
        ],
    ],
    [
        'kicker' => 'Employee requests',
        'title' => 'Availability, preferences & time off',
        'icon' => '◷',
        'tone' => 'green',
        'summary' => 'Employees can tell Atlas when and how they prefer to work, while managers retain visibility and approval controls.',
        'features' => [
            'Recurring availability and date-specific exceptions',
            'Preferred hours, locations, departments, opening, and closing patterns',
            'Availability submission and manager approval workflow',
            'Organization-defined paid and unpaid time-off categories',
            'Full-day and partial-day time-off requests',
            'Conflict visibility, approval, denial, and cancellation',
            'Approved availability and time off automatically feed scheduling eligibility',
        ],
    ],
    [
        'kicker' => 'Employee flexibility',
        'title' => 'Self-scheduling, trades & relief',
        'icon' => '⇄',
        'tone' => 'amber',
        'summary' => 'Give employees controlled ways to pick up, exchange, give away, or partially cover work without bypassing scheduling rules.',
        'features' => [
            'Open-shift requests with eligibility results',
            'Manager review and approval',
            'Shift giveaways',
            'Direct employee-to-employee trades',
            'Recipient acceptance, manager approval, withdrawal, and expiration',
            'Partial-shift relief assignments',
            'Eligibility, availability, qualification, and overlap rechecks before completion',
        ],
    ],
    [
        'kicker' => 'Same-day operations',
        'title' => 'Callouts & urgent coverage',
        'icon' => '!',
        'tone' => 'coral',
        'summary' => 'Turn an absence into a structured replacement workflow instead of a chain of texts and phone calls.',
        'features' => [
            'Same-day callout reporting tied to scheduled work',
            'Automatic replacement coverage opening',
            'Eligibility-aware replacement candidates',
            'Urgent coverage offers and employee responses',
            'Acceptance tracking and manager selection',
            'Audited reassignment to the selected replacement',
        ],
    ],
    [
        'kicker' => 'Employee experience',
        'title' => 'Mobile workspace',
        'icon' => '▣',
        'tone' => 'blue',
        'summary' => 'A mobile-first home for the things employees actually need during the workday.',
        'features' => [
            'Today view and upcoming schedule',
            'My Schedule workspace and open opportunities',
            'Clocking and time-entry access',
            'Availability and time-off requests',
            'Trades, giveaways, and callouts',
            'Messages, alerts, notifications, and profile access',
            'Responsive employee and manager navigation',
        ],
    ],
    [
        'kicker' => 'Communication',
        'title' => 'Messaging & notifications',
        'icon' => '✦',
        'tone' => 'violet',
        'summary' => 'Keep workforce conversations and operational alerts inside the same account-wide experience.',
        'features' => [
            'Direct and group workforce messaging',
            'Account-wide messaging independent of schedule context',
            'Read and unread message state',
            'In-app notification inbox',
            'User notification preferences',
            'Deduplicated alerts for callouts, pending approvals, submitted timesheets, and expiring credentials',
        ],
    ],
    [
        'kicker' => 'Compliance',
        'title' => 'Credentials',
        'icon' => '◇',
        'tone' => 'green',
        'summary' => 'Track workforce readiness and expiration risk without turning Atlas into an employee health record.',
        'features' => [
            'Organization credential catalog',
            'Employee credential records and credential numbers',
            'Verification state and source verification tracking',
            'Issue and expiration dates',
            'Expiration warnings and compliance-risk indicators',
            'Credential information integrated into assignment eligibility',
        ],
    ],
    [
        'kicker' => 'Labor',
        'title' => 'Time clock, timesheets & payroll',
        'icon' => '$',
        'tone' => 'amber',
        'summary' => 'Connect scheduled work to approved work time, overtime visibility, payroll previews, and export history.',
        'features' => [
            'Employee clock-in and clock-out',
            'Optional scheduled-shift linkage and break capture',
            'Timesheet submission and manager approval',
            'Hourly and salary pay profiles',
            'Weekly overtime thresholds',
            'Approved-time payroll previews and gross estimates',
            'Audited payroll CSV exports and export history',
        ],
    ],
    [
        'kicker' => 'Decision support',
        'title' => 'Fairness & recommendations',
        'icon' => '≈',
        'tone' => 'coral',
        'summary' => 'Show managers workload patterns and explainable candidate guidance while keeping people, not algorithms, in charge of staffing decisions.',
        'features' => [
            'Scheduled-hours and target-hours comparison',
            'Opening, closing, and weekend assignment counts',
            'Target-hours variance',
            'Eligibility-aware candidate guidance',
            'Ranked replacement suggestions',
            'Explainable recommendation factors',
            'Advisory-only recommendations',
        ],
    ],
    [
        'kicker' => 'Management',
        'title' => 'Command center, reporting & audit',
        'icon' => '◆',
        'tone' => 'blue',
        'summary' => 'Pull exceptions, workload signals, reports, and accountability into one operational layer for managers.',
        'features' => [
            'Initial Scheduling Command Center queue',
            'Date-range operational reporting',
            'Workforce-hour, request, callout, coverage, labor, credential, payroll, and fairness reporting',
            'Workforce-hour and payroll CSV exports',
            'Administrator audit history',
            'Shift-change, override, reassignment, and payroll-export history',
        ],
    ],
    [
        'kicker' => 'Demo & validation',
        'title' => 'Full organization sandbox',
        'icon' => 'D',
        'tone' => 'green',
        'summary' => 'Create a disposable, populated organization that makes every major Atlas workflow visible without hours of manual setup.',
        'features' => [
            'Separate demo organization that never alters the source organization',
            'Owner, administrator, scheduler, supervisor, and member testing paths',
            'Twelve staff profiles with locations, departments, positions, teams, and qualifications',
            'Several weeks of assigned and open shifts plus a reusable Master Schedule',
            'Availability, time off, provider sessions, coverage assignments, and coverage requirements',
            'Credentials, pay profiles, time entries, callouts, replacement offers, messages, and notifications',
            'Compact one-time credential cards with username and password copy controls',
            'Incremental verified migrations and 64-check runtime validation foundation',
        ],
    ],
    [
        'kicker' => 'Access & reliability',
        'title' => 'Roles, navigation & system tools',
        'icon' => 'A',
        'tone' => 'violet',
        'summary' => 'A structured operational shell with role foundations, scoped access, cleaner navigation, and tools to keep the installation healthy.',
        'features' => [
            'Owner, administrator, scheduler, supervisor, and member roles',
            'Organization-level control over username-only local accounts',
            'Role-specific smoke-test accounts and a full demo-data generator',
            'Granular permission and resource-scope storage',
            'Grouped, collapsible, scrollable sidebar navigation',
            'Unified Atlas application shell and responsive interfaces',
            'System Status checks for runtime, configuration, database, and migrations',
            'Repository runtime audit and structured database migrations',
            'Prepared SQL, tenant ownership checks, password hashing, CSRF protection, and audit logging',
            'PHI-free workforce design',
        ],
    ],
];

$totalFeatures = array_sum(array_map(static fn(array $section): int => count($section['features']), $sections));
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="description" content="Explore the current capabilities of Atlas, an ambulatory workforce operations platform.">
    <title>Atlas Features</title>
    <link rel="stylesheet" href="assets/app.css">
    <style>
        body.feature-page{background:#f3f5fa;min-height:100vh}
        .feature-shell{width:min(1180px,calc(100% - 36px));margin:0 auto;padding:26px 0 64px}
        .feature-nav{display:flex;align-items:center;justify-content:space-between;margin-bottom:18px}
        .feature-brand{display:inline-flex;align-items:center;gap:10px;color:var(--ink);font:800 20px/1 'Manrope',sans-serif;text-decoration:none}
        .feature-brand .brand-mark{color:#fff}
        .feature-back{display:inline-flex;align-items:center;gap:8px;color:#5d687c;font-size:12px;font-weight:800;text-decoration:none;padding:9px 12px;border:1px solid var(--line);border-radius:11px;background:rgba(255,255,255,.75)}
        .feature-back:hover{background:#fff;color:var(--ink)}
        .feature-hero{position:relative;overflow:hidden;padding:42px 42px 36px;border-radius:28px;background:linear-gradient(135deg,#18233c 0%,#22345b 52%,#34417f 100%);color:#fff;box-shadow:0 24px 70px rgba(24,35,60,.18)}
        .feature-hero:before,.feature-hero:after{content:"";position:absolute;border-radius:50%;pointer-events:none}
        .feature-hero:before{width:310px;height:310px;right:-90px;top:-150px;background:radial-gradient(circle,rgba(130,102,236,.43),rgba(130,102,236,0) 70%)}
        .feature-hero:after{width:260px;height:260px;left:42%;bottom:-210px;background:radial-gradient(circle,rgba(45,128,238,.32),rgba(45,128,238,0) 70%)}
        .feature-hero-copy{position:relative;z-index:1;max-width:760px}
        .feature-hero .eyebrow{color:#b8a9ff!important;margin-bottom:10px!important}
        .feature-hero h1{margin:0;font:800 clamp(34px,5vw,58px)/.98 'Manrope',sans-serif;letter-spacing:-2.4px}
        .feature-hero p{max-width:720px;margin:17px 0 0;color:#ccd4e8;font-size:15px;line-height:1.7}
        .feature-stats{position:relative;z-index:1;display:flex;flex-wrap:wrap;gap:10px;margin-top:26px}
        .feature-stat{display:flex;align-items:baseline;gap:7px;padding:9px 12px;border:1px solid rgba(255,255,255,.13);border-radius:12px;background:rgba(255,255,255,.07);backdrop-filter:blur(10px)}
        .feature-stat strong{font:800 18px 'Manrope',sans-serif}
        .feature-stat span{color:#bdc7dc;font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.7px}
        .feature-toolbar{display:flex;align-items:center;justify-content:space-between;gap:16px;margin:24px 2px 14px}
        .feature-toolbar span{color:var(--muted);font-size:12px}
        .feature-toolbar-actions{display:flex;gap:8px}
        .feature-tool{border:0;background:transparent;color:var(--blue);font:800 11px 'DM Sans',sans-serif;cursor:pointer;padding:7px 8px;border-radius:8px}
        .feature-tool:hover{background:var(--blue-soft)}
        .feature-list{display:grid;gap:10px}
        .feature-group{--accent:var(--blue);--soft:var(--blue-soft);border:1px solid var(--line);border-radius:17px;background:#fff;box-shadow:0 5px 18px rgba(29,43,71,.035);overflow:hidden;transition:border-color .18s ease,box-shadow .18s ease,transform .18s ease}
        .feature-group.violet{--accent:var(--violet);--soft:var(--violet-soft)}
        .feature-group.green{--accent:var(--green);--soft:var(--green-soft)}
        .feature-group.coral{--accent:var(--coral);--soft:var(--coral-soft)}
        .feature-group.amber{--accent:var(--amber);--soft:var(--amber-soft)}
        .feature-group[open]{border-color:color-mix(in srgb,var(--accent) 25%,var(--line));box-shadow:0 12px 32px rgba(29,43,71,.065)}
        .feature-group summary{list-style:none;display:grid;grid-template-columns:42px minmax(0,1fr) auto;align-items:center;gap:14px;min-height:74px;padding:13px 17px;cursor:pointer;user-select:none}
        .feature-group summary::-webkit-details-marker{display:none}
        .feature-group summary:hover{background:#fbfcfe}
        .feature-symbol{width:38px;height:38px;display:grid;place-items:center;border-radius:12px;background:var(--soft);color:var(--accent);font:900 14px 'Manrope',sans-serif}
        .feature-heading{min-width:0}
        .feature-kicker{display:block;margin-bottom:2px;color:var(--accent);font-size:9px;font-weight:900;letter-spacing:.8px;text-transform:uppercase}
        .feature-heading strong{display:block;font:800 15px/1.25 'Manrope',sans-serif;letter-spacing:-.2px}
        .feature-heading small{display:block;max-width:780px;margin-top:4px;color:var(--muted);font-size:10.5px;line-height:1.4;font-weight:500}
        .feature-meta{display:flex;align-items:center;gap:10px;padding-left:10px}
        .feature-count{min-width:29px;height:25px;display:grid;place-items:center;border-radius:999px;background:#f2f4f8;color:#69758a;font-size:9px;font-weight:900}
        .feature-chevron{width:27px;height:27px;display:grid;place-items:center;border-radius:9px;color:#8791a3;font-size:17px;transition:transform .18s ease,background .18s ease}
        .feature-group[open] .feature-chevron{transform:rotate(45deg);background:var(--soft);color:var(--accent)}
        .feature-body{padding:0 18px 18px 74px}
        .feature-items{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:7px 22px;padding:15px 0 2px;border-top:1px solid #edf0f5}
        .feature-item{position:relative;padding-left:17px;color:#445066;font-size:11.5px;line-height:1.5}
        .feature-item:before{content:"";position:absolute;left:0;top:.58em;width:6px;height:6px;border-radius:50%;background:var(--accent);box-shadow:0 0 0 4px var(--soft)}
        .feature-footer{margin-top:20px;padding:18px 20px;border:1px solid var(--line);border-radius:17px;background:#fff;display:flex;align-items:center;justify-content:space-between;gap:20px}
        .feature-footer strong{display:block;font:800 13px 'Manrope',sans-serif}
        .feature-footer p{margin:4px 0 0;color:var(--muted);font-size:10.5px;line-height:1.5}
        .feature-badge{white-space:nowrap;padding:8px 11px;border-radius:999px;background:var(--green-soft);color:var(--green);font-size:9px;font-weight:900;letter-spacing:.5px;text-transform:uppercase}
        @media(max-width:760px){
            .feature-shell{width:min(100% - 22px,1180px);padding-top:14px}
            .feature-nav{margin:0 3px 12px}
            .feature-hero{padding:30px 24px 26px;border-radius:22px}
            .feature-hero h1{letter-spacing:-1.7px}
            .feature-toolbar{align-items:flex-end}
            .feature-group summary{grid-template-columns:38px minmax(0,1fr) auto;gap:11px;padding:12px 13px}
            .feature-symbol{width:36px;height:36px}
            .feature-heading small{display:none}
            .feature-meta{gap:4px;padding-left:0}
            .feature-count{display:none}
            .feature-body{padding:0 14px 15px 62px}
            .feature-items{grid-template-columns:1fr;gap:8px}
            .feature-footer{align-items:flex-start;flex-direction:column}
        }
        @media(max-width:430px){
            .feature-back span{display:none}
            .feature-hero p{font-size:13px}
            .feature-stats{gap:7px}
            .feature-stat{padding:8px 10px}
            .feature-stat strong{font-size:16px}
            .feature-toolbar-actions{gap:0}
            .feature-body{padding-left:21px}
        }
    </style>
</head>
<body class="feature-page">
<main class="feature-shell">
    <nav class="feature-nav" aria-label="Feature page navigation">
        <a class="feature-brand" href="index.php"><span class="brand-mark">A</span><span>Atlas</span></a>
        <a class="feature-back" href="index.php"><span>Back to Atlas</span><b>→</b></a>
    </nav>

    <header class="feature-hero">
        <div class="feature-hero-copy">
            <p class="eyebrow">AMBULATORY WORKFORCE OPERATIONS</p>
            <h1>One platform.<br>A very capable week.</h1>
            <p>Atlas brings workforce structure, scheduling, recurring staffing, employee requests, coverage intelligence, callouts, labor, credentials, communication, and operational reporting into one deliberately connected system.</p>
        </div>
        <div class="feature-stats" aria-label="Atlas feature summary">
            <div class="feature-stat"><strong><?= count($sections) ?></strong><span>capability areas</span></div>
            <div class="feature-stat"><strong><?= $totalFeatures ?>+</strong><span>current capabilities</span></div>
            <div class="feature-stat"><strong>7-day</strong><span>schedule board</span></div>
            <div class="feature-stat"><strong>1–4 wk</strong><span>rotations</span></div>
        </div>
    </header>

    <div class="feature-toolbar">
        <span>Explore what’s already built.</span>
        <div class="feature-toolbar-actions">
            <button class="feature-tool" type="button" id="expandAll">Expand all</button>
            <button class="feature-tool" type="button" id="collapseAll">Collapse</button>
        </div>
    </div>

    <section class="feature-list" aria-label="Atlas capabilities">
        <?php foreach ($sections as $index => $section): ?>
            <details class="feature-group <?= htmlspecialchars($section['tone'], ENT_QUOTES, 'UTF-8') ?>"<?= $index < 2 ? ' open' : '' ?>>
                <summary>
                    <span class="feature-symbol"><?= htmlspecialchars($section['icon'], ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="feature-heading">
                        <span class="feature-kicker"><?= htmlspecialchars($section['kicker'], ENT_QUOTES, 'UTF-8') ?></span>
                        <strong><?= htmlspecialchars($section['title'], ENT_QUOTES, 'UTF-8') ?></strong>
                        <small><?= htmlspecialchars($section['summary'], ENT_QUOTES, 'UTF-8') ?></small>
                    </span>
                    <span class="feature-meta">
                        <span class="feature-count"><?= count($section['features']) ?></span>
                        <span class="feature-chevron" aria-hidden="true">+</span>
                    </span>
                </summary>
                <div class="feature-body">
                    <div class="feature-items">
                        <?php foreach ($section['features'] as $feature): ?>
                            <div class="feature-item"><?= htmlspecialchars($feature, ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </details>
        <?php endforeach; ?>
    </section>

    <footer class="feature-footer">
        <div>
            <strong>Built around workforce operations, not patient records.</strong>
            <p>Atlas is intentionally PHI-free and keeps scheduling, employee operations, labor, and staffing intelligence in the workforce lane.</p>
        </div>
        <span class="feature-badge">Current build</span>
    </footer>
</main>
<script>
    const groups = [...document.querySelectorAll('.feature-group')];
    document.getElementById('expandAll').addEventListener('click', () => groups.forEach(group => group.open = true));
    document.getElementById('collapseAll').addEventListener('click', () => groups.forEach(group => group.open = false));
</script>
</body>
</html>
