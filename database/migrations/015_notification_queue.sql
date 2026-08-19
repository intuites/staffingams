-- 015: daily digest notifications — events are queued here instead of being
-- emailed one-by-one. Each audience (super_admin / admin) gets at most ONE
-- digest email per calendar day containing everything queued since the last one.
CREATE TABLE IF NOT EXISTS notification_queue (
    id BIGSERIAL PRIMARY KEY,
    audience VARCHAR(20) NOT NULL CHECK (audience IN ('super_admin', 'admin')),
    event_type VARCHAR(30) NOT NULL,     -- txn_pending | txn_edited | txn_rejected | review_request
    summary_html TEXT NOT NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    sent_at TIMESTAMPTZ
);
CREATE INDEX IF NOT EXISTS idx_notification_queue_pending ON notification_queue(audience, sent_at);
ALTER TABLE notification_queue ENABLE ROW LEVEL SECURITY;
