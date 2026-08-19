-- 005: transactions
CREATE TABLE IF NOT EXISTS transactions (
    id BIGSERIAL PRIMARY KEY,
    transaction_id VARCHAR(20) NOT NULL UNIQUE,
    candidate_id BIGINT NOT NULL REFERENCES candidates(id),
    type VARCHAR(30) NOT NULL CHECK (type IN ('Earnings', 'Company Payment', 'Candidate Payment', 'Expense')),
    direction CHAR(1) NOT NULL CHECK (direction IN ('+', '-')),
    transaction_date DATE NOT NULL,
    project_id BIGINT REFERENCES projects(id),
    effective_amount DECIMAL(12,2) NOT NULL,
    signed_amount DECIMAL(12,2) NOT NULL,
    amount_notes TEXT,
    description_notes TEXT,
    period_start_date DATE,
    period_end_date DATE,
    hours_worked DECIMAL(8,2),
    rate_applied DECIMAL(10,2),
    auto_calculated_amount DECIMAL(12,2),
    amount_override DECIMAL(12,2),
    payment_method VARCHAR(100),
    reference_number VARCHAR(100),
    period_covered VARCHAR(100),
    payment_amount DECIMAL(12,2),
    expense_type VARCHAR(100),
    paid_to_vendor VARCHAR(255),
    reimbursable_by_candidate BOOLEAN NOT NULL DEFAULT FALSE,
    expense_amount DECIMAL(12,2),
    reason_for_payment VARCHAR(100),
    method_received VARCHAR(50),
    reference VARCHAR(100),
    candidate_payment_amount DECIMAL(12,2),
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_transactions_candidate_date ON transactions(candidate_id, transaction_date DESC);
CREATE INDEX IF NOT EXISTS idx_transactions_type ON transactions(type);
CREATE INDEX IF NOT EXISTS idx_transactions_project_id ON transactions(project_id);
