<?php

declare(strict_types=1);

require_once __DIR__ . '/Config.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Csrf.php';
require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/AtlasRepository.php';
require_once __DIR__ . '/SchedulingRepository.php';

Config::load(dirname(__DIR__));

session_name('atlas_session');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
    'httponly' => true,
    'samesite' => 'Lax',
]);
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
