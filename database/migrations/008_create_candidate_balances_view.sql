-- 008: v_candidate_balances
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
LEFT JOIN transactions t ON t.candidate_id = c.id
GROUP BY c.id, c.candidate_id, c.first_name, c.last_name, c.email, c.phone, c.company_id, c.employment_status, c.date_registered;
