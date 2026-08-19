<?php
/**
 * CLI: run all pending SQL migrations in database/migrations, in order,
 * tracked in a _migrations table.
 *
 * Usage: php scripts/migrate.php
 */

require __DIR__ . '/_cli_bootstrap.php';

$pdo = Database::pdo();
$pdo->exec('CREATE TABLE IF NOT EXISTS _migrations (
    id SERIAL PRIMARY KEY,
    filename VARCHAR(255) NOT NULL UNIQUE,
    ran_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
)');

$done = array_column(Database::all('SELECT filename FROM _migrations'), 'filename');
$files = glob(BASE_PATH . '/database/migrations/[0-9]*.sql');
sort($files);

$ran = 0;
foreach ($files as $file) {
    $name = basename($file);
    if (in_array($name, $done, true)) {
        continue;
    }
    echo "Running {$name}... ";
    try {
        $pdo->exec(file_get_contents($file));
        Database::q('INSERT INTO _migrations (filename) VALUES (:f)', ['f' => $name]);
        echo "OK\n";
        $ran++;
    } catch (Throwable $ex) {
        echo "FAILED\n" . $ex->getMessage() . "\n";
        exit(1);
    }
}

echo $ran === 0 ? "Nothing to migrate — all up to date.\n" : "Done: {$ran} migration(s) applied.\n";
