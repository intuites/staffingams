<?php
/** Shared CLI bootstrap — env + DB only, no session/router. */

if (PHP_SAPI !== 'cli') {
    exit("CLI only.\n");
}

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/vendor/autoload.php';
require BASE_PATH . '/app/core/Env.php';
loadEnv(BASE_PATH . '/.env');
require BASE_PATH . '/app/core/Database.php';
