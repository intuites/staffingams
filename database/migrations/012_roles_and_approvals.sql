-- 012: admin roles + transaction approval workflow.
--   admin_users.role: 'super_admin' | 'admin'
--   transactions.status: 'pending' (created by admin, awaiting review)
--                        'approved' (counts toward balances)
--                        'locked'   (final — immutable until a super admin unlocks)
-- Pending transactions are EXCLUDED from all balances/aggregates.
ALTER TABLE admin_users ADD COLUMN IF NOT EXISTS role VARCHAR(20) NOT NULL DEFAULT 'admin';
UPDATE admin_users SET role = 'super_admin' WHERE email = 'pavan@intuites.com';

ALTER TABLE transactions ADD COLUMN IF NOT EXISTS status VARCHAR(10) NOT NULL DEFAULT 'approved';
ALTER TABLE transactions ADD COLUMN IF NOT EXISTS approved_by BIGINT REFERENCES admin_users(id);
ALTER TABLE transactions ADD COLUMN IF NOT EXISTS approved_at TIMESTAMPTZ;
ALTER TABLE transactions ADD COLUMN IF NOT EXISTS locked_by BIGINT REFERENCES admin_users(id);
ALTER TABLE transactions ADD COLUMN IF NOT EXISTS locked_at TIMESTAMPTZ;
DO $$ BEGIN
    ALTER TABLE transactions ADD CONSTRAINT chk_txn_status CHECK (status IN ('pending','approved','locked'));
EXCEPTION WHEN duplicate_object THEN NULL; END $$;
CREATE INDEX IF NOT EXISTS idx_transactions_status ON transactions(status);

-- Balances count only approved + locked transactions.
CREATE OR REPLACE VIEW v_candidate_balances AS
SELECT
    c.id AS candidate_id,
    c.candidate_id AS candidate_code,
    c.first_name || ' ' || c.last_name AS full_name,
    c.email,
    c.phone,
    c.company_id,
    c.employment_status,
    c.date_registered,
    COALESCE(SUM(CASE WHEN t.type = 'Earnings' THEN t.effective_amount END), 0) AS total_earnings,
    COALESCE(SUM(CASE WHEN t.type = 'Company Payment' THEN t.effective_amount END), 0) AS total_company_payments,
    COALESCE(SUM(CASE WHEN t.type = 'Candidate Payment' THEN t.effective_amount END), 0) AS total_candidate_payments,
    COALESCE(SUM(CASE WHEN t.type = 'Expense' THEN t.effective_amount END), 0) AS total_expenses,
    COALESCE(SUM(t.signed_amount), 0) AS current_balance,
    CASE
        WHEN COALESCE(SUM(t.signed_amount), 0) > 0 THEN 'Company owes candidate'
        WHEN COALESCE(SUM(t.signed_amount), 0) < 0 THEN 'Candidate owes company'
        ELSE 'Settled'
    END AS status
FROM candidates c
LEFT JOIN transactions t ON t.candidate_id = c.id AND t.status <> 'pending'
GROUP BY c.id, c.candidate_id, c.first_name, c.last_name, c.email, c.phone, c.company_id, c.employment_status, c.date_registered;
