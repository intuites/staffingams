<?php
/**
 * CLI: send the daily digest emails now (one per audience with unsent items).
 * Ideal as a daily cron job, e.g. at 8:00 AM:
 *   0 8 * * *  php /path/to/staffing-app/scripts/send_digest.php
 *
 * Without cron, digests still go out lazily on the first authenticated app
 * request of the day. Pass --force to bypass the once-per-day guard.
 */

require __DIR__ . '/_cli_bootstrap.php';
require BASE_PATH . '/app/core/Helpers.php';
require BASE_PATH . '/app/core/Mailer.php';
require BASE_PATH . '/app/core/Notification.php';
require BASE_PATH . '/app/models/AdminUser.php';

date_default_timezone_set(env('APP_TIMEZONE', 'America/New_York'));

$force = in_array('--force', $argv, true);
Notification::flushIfDue($force);
echo "Digest flush complete" . ($force ? ' (forced)' : '') . ".\n";
