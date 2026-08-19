<?php
/**
 * CLI: run database/migrations/seed.sql (idempotent — uses ON CONFLICT DO NOTHING).
 *
 * Usage: php scripts/seed.php
 */

require __DIR__ . '/_cli_bootstrap.php';

$file = BASE_PATH . '/database/migrations/seed.sql';
if (!is_file($file)) {
    echo "seed.sql not found.\n";
    exit(1);
}
Database::pdo()->exec(file_get_contents($file));
echo "Seed complete.\n";
