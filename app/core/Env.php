<?php
/**
 * Tiny .env loader. No library — reads KEY=VALUE lines, ignores comments,
 * strips optional quotes, and populates $_ENV / getenv().
 */

function loadEnv(string $path): void
{
    if (!is_file($path)) {
        return;
    }
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        $pos = strpos($line, '=');
        if ($pos === false) {
            continue;
        }
        $key = trim(substr($line, 0, $pos));
        $val = trim(substr($line, $pos + 1));
        // Strip surrounding quotes.
        if (strlen($val) >= 2 && ($val[0] === '"' || $val[0] === "'") && str_ends_with($val, $val[0])) {
            $val = substr($val, 1, -1);
        }
        $_ENV[$key] = $val;
        putenv("$key=$val");
    }
}

function env(string $key, ?string $default = null): ?string
{
    $v = $_ENV[$key] ?? getenv($key);
    return ($v === false || $v === null || $v === '') ? $default : $v;
}
