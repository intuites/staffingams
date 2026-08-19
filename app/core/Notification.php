<?php
/**
 * Daily digest notifications. Events are queued in notification_queue and
 * flushed as ONE email per audience per calendar day (app timezone).
 *
 * Flushing happens lazily on authenticated requests (see bootstrap) and can
 * also be run from cron via scripts/send_digest.php for guaranteed timing.
 */

class Notification
{
    public const TYPE_LABELS = [
        'txn_pending'    => 'New transactions awaiting approval',
        'txn_edited'     => 'Edited transactions awaiting re-approval',
        'txn_rejected'   => 'Rejected transactions needing correction',
        'review_request' => 'Candidate review requests',
    ];

    /** Queue an event for an audience's next daily digest. */
    public static function queue(string $audience, string $eventType, string $summaryHtml): void
    {
        try {
            Database::q(
                'INSERT INTO notification_queue (audience, event_type, summary_html) VALUES (:a, :t, :s)',
                ['a' => $audience, 't' => $eventType, 's' => $summaryHtml]
            );
        } catch (\Throwable $ex) {
            error_log('[digest] queue failed: ' . $ex->getMessage());
        }
    }

    /**
     * Send the daily digest for each audience that has unsent items and has
     * not received a digest yet today. $force ignores the once-per-day rule
     * (used by the cron script).
     */
    public static function flushIfDue(bool $force = false): void
    {
        foreach (['super_admin', 'admin'] as $audience) {
            try {
                self::flushAudience($audience, $force);
            } catch (\Throwable $ex) {
                error_log('[digest] flush failed for ' . $audience . ': ' . $ex->getMessage());
            }
        }
    }

    private static function flushAudience(string $audience, bool $force): void
    {
        // True digest semantics: today's events are held and delivered together
        // in tomorrow's digest (or by the daily cron). --force sends everything now.
        $sql = 'SELECT * FROM notification_queue WHERE audience = :a AND sent_at IS NULL';
        $params = ['a' => $audience];
        if (!$force) {
            $sql .= ' AND (created_at AT TIME ZONE :tz)::date < (NOW() AT TIME ZONE :tz)::date';
            $params['tz'] = date_default_timezone_get();
        }
        $sql .= ' ORDER BY created_at';
        $unsent = Database::all($sql, $params);
        if (!$unsent) {
            return;
        }
        if (!$force) {
            $sentToday = Database::scalar(
                "SELECT COUNT(*) FROM notification_queue
                 WHERE audience = :a AND sent_at IS NOT NULL
                   AND (sent_at AT TIME ZONE :tz)::date = (NOW() AT TIME ZONE :tz)::date",
                ['a' => $audience, 'tz' => date_default_timezone_get()]
            );
            if ((int) $sentToday > 0) {
                return; // this audience already got today's digest
            }
        }

        // Mark first so concurrent requests can't double-send.
        $ids = implode(',', array_map(fn($r) => (int) $r['id'], $unsent));
        Database::q("UPDATE notification_queue SET sent_at = NOW() WHERE id IN ({$ids}) AND sent_at IS NULL");

        // Group by event type.
        $groups = [];
        foreach ($unsent as $item) {
            $groups[$item['event_type']][] = $item;
        }
        $body = '';
        foreach (self::TYPE_LABELS as $type => $label) {
            if (empty($groups[$type])) {
                continue;
            }
            $body .= '<h3 style="margin:16px 0 6px;font-size:14px;color:#0e2136;border-bottom:2px solid #0fb5ea;padding-bottom:3px">'
                . $label . ' (' . count($groups[$type]) . ')</h3><ul style="margin:0;padding-left:18px">';
            foreach ($groups[$type] as $item) {
                $body .= '<li style="margin:6px 0;color:#33465c">' . $item['summary_html']
                    . ' <span style="color:#93a5b8;font-size:12px">(' . format_date($item['created_at']) . ')</span></li>';
            }
            $body .= '</ul>';
        }
        $n = count($unsent);
        Mailer::send(
            AdminUser::emailsByRole($audience),
            'Daily Digest — ' . $n . ' update' . ($n === 1 ? '' : 's') . ' (' . date('d-M-Y') . ')',
            Mailer::wrap('Your daily activity digest', $body
                . '<p style="margin-top:14px;color:#64798f;font-size:12px">You receive at most one digest per day. Items above are everything since your last digest.</p>')
        );
    }
}
