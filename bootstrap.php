<?php

declare(strict_types=1);

const ATLAS_ROOT = __DIR__;

require_once ATLAS_ROOT . '/src/Database.php';
require_once ATLAS_ROOT . '/src/Auth.php';
require_once ATLAS_ROOT . '/src/Csrf.php';
require_once ATLAS_ROOT . '/src/Router.php';
require_once ATLAS_ROOT . '/src/App.php';

Database::loadEnv(ATLAS_ROOT . '/.env');
Auth::startSession();
