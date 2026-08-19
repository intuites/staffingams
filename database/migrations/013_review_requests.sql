-- 013: candidate review requests — a candidate can flag one of their own
-- transactions with a comment about a discrepancy. Routing:
--   * transaction locked at request time  → super admin queue (only super can edit locked)
--   * not locked                          → admin queue
CREATE TABLE IF NOT EXISTS transaction_review_requests (
    id BIGSERIAL PRIMARY KEY,
    transaction_id BIGINT NOT NULL REFERENCES transactions(id) ON DELETE CASCADE,
    candidate_id BIGINT NOT NULL REFERENCES candidates(id) ON DELETE CASCADE,
    comment TEXT NOT NULL,
    requires_super BOOLEAN NOT NULL DEFAULT FALSE,   -- txn was locked when requested
    status VARCHAR(12) NOT NULL DEFAULT 'open' CHECK (status IN ('open','resolved')),
    admin_response TEXT,
    resolved_by BIGINT REFERENCES admin_users(id),
    resolved_at TIMESTAMPTZ,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_review_requests_status ON transaction_review_requests(status, requires_super);
CREATE INDEX IF NOT EXISTS idx_review_requests_txn ON transaction_review_requests(transaction_id);
ALTER TABLE transaction_review_requests ENABLE ROW LEVEL SECURITY;
