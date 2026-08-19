-- ===========================================================================
-- DEMO DATA — realistic test dataset for evaluating the app end to end.
-- Self-contained: FKs resolved by code lookups, so it runs on any instance.
-- Remove it all with database/demo_cleanup.sql.
--
-- Portal test logins:  john@example.com / John@Portal1
--                      priya@example.com / Priya@Portal1
--                      (carlos@example.com exists but portal DISABLED)
-- ===========================================================================

-- Companies (payroll organizations)
INSERT INTO companies (company_id, company_name, address, primary_contact_name, primary_contact_email, primary_contact_phone, date_added, notes) VALUES
('COMP-0001', 'Intuites LLC',    '123 Main St, Alpharetta, GA 30004', 'Pavan Raj',   'pavan@intuites.com',  '770-555-0100', '2025-01-02', 'Primary payroll org'),
('COMP-0002', 'BrightStaff Inc', '450 Tech Pkwy, Austin, TX 78701',   'Maria Lopez', 'maria@brightstaff.example', '512-555-0188', '2025-02-10', 'Secondary payroll org')
ON CONFLICT (company_id) DO NOTHING;

-- Staffing partners (clients / vendors where candidates work)
INSERT INTO staffing_partners (partner_id, partner_name, partner_type, primary_contact_name, primary_contact_email, primary_contact_phone, date_added) VALUES
('PART-0001', 'Globex Client LLC',      'Client',  'Dan Field',   'dan@globex.example',   '212-555-0142', '2025-01-15'),
('PART-0002', 'TechVantage Solutions',  'Vendor',  'Anita Rao',   'anita@techvantage.example', '408-555-0177', '2025-02-01'),
('PART-0003', 'NorthBridge Consulting', 'Partner', 'Tom Berg',    'tom@northbridge.example',   '617-555-0129', '2025-05-20')
ON CONFLICT (partner_id) DO NOTHING;

-- Candidates
INSERT INTO candidates (candidate_id, first_name, last_name, email, phone, company_id, employment_status, date_registered, notes, portal_enabled, portal_password_hash) VALUES
('CAND-0001', 'John',   'Smith',  'john@example.com',   '555-0101', (SELECT id FROM companies WHERE company_id='COMP-0001'), 'Active',     '2025-02-15', 'H1B; project at Globex', TRUE,  '$2y$12$bpurU5rasMSGN5HEpO6xNOePK.X7AbqZpFkl1UWSV0C2LdeVcHYh6'),
('CAND-0002', 'Priya',  'Sharma', 'priya@example.com',  '555-0102', (SELECT id FROM companies WHERE company_id='COMP-0001'), 'Active',     '2025-11-01', 'GC in process',          TRUE,  '$2y$12$u3SFop4gzJDrAARiuqrUGu/4WLfZUmpvAFB8h.mmvNGS0JqxiqTmm'),
('CAND-0003', 'Carlos', 'Mendez', 'carlos@example.com', '555-0103', (SELECT id FROM companies WHERE company_id='COMP-0002'), 'On Bench',   '2025-04-20', 'Rolling off BI project', FALSE, NULL),
('CAND-0004', 'Wei',    'Chen',   'wei@example.com',    '555-0104', (SELECT id FROM companies WHERE company_id='COMP-0002'), 'Terminated', '2024-12-05', 'Fully settled',          FALSE, NULL)
ON CONFLICT (candidate_id) DO NOTHING;

-- Projects (candidate + staffing partner; rate math per spec)
INSERT INTO projects (project_id, candidate_id, staffing_partner_id, project_name, start_date, end_date,
                      rate_from_client, rate_informed_to_candidate, percent_paid_to_candidate,
                      auto_calculated_final_rate, final_rate_override, rate_paid_to_candidate) VALUES
('PROJ-0001', (SELECT id FROM candidates WHERE candidate_id='CAND-0001'), (SELECT id FROM staffing_partners WHERE partner_id='PART-0001'),
 'Acme ERP Implementation', '2026-01-05', NULL, 120.00, 110.00, 0.8500, 93.50, NULL, 93.50),
('PROJ-0002', (SELECT id FROM candidates WHERE candidate_id='CAND-0001'), (SELECT id FROM staffing_partners WHERE partner_id='PART-0002'),
 'Data Migration',          '2025-03-01', '2025-12-31', 100.00, 95.00, 0.8000, 76.00, NULL, 76.00),
('PROJ-0003', (SELECT id FROM candidates WHERE candidate_id='CAND-0002'), (SELECT id FROM staffing_partners WHERE partner_id='PART-0001'),
 'Cloud Platform Build',    '2026-02-01', NULL, 140.00, 130.00, 0.8000, 104.00, 105.00, 105.00),
('PROJ-0004', (SELECT id FROM candidates WHERE candidate_id='CAND-0003'), (SELECT id FROM staffing_partners WHERE partner_id='PART-0003'),
 'BI Dashboards',           '2025-06-01', '2026-03-31', 90.00, 85.00, 0.8000, 68.00, NULL, 68.00),
('PROJ-0005', (SELECT id FROM candidates WHERE candidate_id='CAND-0004'), (SELECT id FROM staffing_partners WHERE partner_id='PART-0002'),
 'QA Automation',           '2025-01-15', '2025-11-30', 80.00, 75.00, 0.8000, 60.00, NULL, 60.00)
ON CONFLICT (project_id) DO NOTHING;

-- Transactions --------------------------------------------------------------
-- John Smith: 2026 activity (balance this year +980) and 2025 (+80) → total +1,060
INSERT INTO transactions (transaction_id, candidate_id, type, direction, transaction_date, project_id,
                          effective_amount, signed_amount, amount_notes, description_notes,
                          period_start_date, period_end_date, hours_worked, rate_applied, auto_calculated_amount,
                          payment_method, reference_number, period_covered, payment_amount,
                          expense_type, paid_to_vendor, reimbursable_by_candidate, expense_amount,
                          reason_for_payment, method_received, reference, candidate_payment_amount) VALUES
('TXN-00001', (SELECT id FROM candidates WHERE candidate_id='CAND-0001'), 'Earnings', '+', '2026-06-15',
 (SELECT id FROM projects WHERE project_id='PROJ-0001'),
 7480.00, 7480.00, '80 hrs x $93.50', 'Standard payroll period',
 '2026-06-01', '2026-06-15', 80, 93.50, 7480.00,
 NULL, NULL, NULL, NULL, NULL, NULL, FALSE, NULL, NULL, NULL, NULL, NULL),
('TXN-00002', (SELECT id FROM candidates WHERE candidate_id='CAND-0001'), 'Expense', '-', '2026-06-10', NULL,
 2000.00, -2000.00, 'USCIS filing fee', 'H1B extension',
 NULL, NULL, NULL, NULL, NULL,
 NULL, NULL, NULL, NULL, 'H1B Filing Fee', 'USCIS', TRUE, 2000.00, NULL, NULL, NULL, NULL),
('TXN-00003', (SELECT id FROM candidates WHERE candidate_id='CAND-0001'), 'Company Payment', '-', '2026-06-20', NULL,
 5000.00, -5000.00, 'First half June salary', NULL,
 NULL, NULL, NULL, NULL, NULL,
 'Bank Transfer / ACH', 'ACH-8841', 'First half June 2026', 5000.00, NULL, NULL, FALSE, NULL, NULL, NULL, NULL, NULL),
('TXN-00004', (SELECT id FROM candidates WHERE candidate_id='CAND-0001'), 'Candidate Payment', '+', '2026-06-25', NULL,
 500.00, 500.00, 'Partial H1B reimbursement', NULL,
 NULL, NULL, NULL, NULL, NULL,
 NULL, NULL, NULL, NULL, NULL, NULL, FALSE, NULL, 'H1B Reimbursement', 'Zelle', 'ZL-3321', 500.00),
('TXN-00005', (SELECT id FROM candidates WHERE candidate_id='CAND-0001'), 'Earnings', '+', '2025-10-15',
 (SELECT id FROM projects WHERE project_id='PROJ-0002'),
 6080.00, 6080.00, '80 hrs x $76.00', 'October first half',
 '2025-10-01', '2025-10-15', 80, 76.00, 6080.00,
 NULL, NULL, NULL, NULL, NULL, NULL, FALSE, NULL, NULL, NULL, NULL, NULL),
('TXN-00006', (SELECT id FROM candidates WHERE candidate_id='CAND-0001'), 'Company Payment', '-', '2025-10-20', NULL,
 6000.00, -6000.00, 'October payroll', NULL,
 NULL, NULL, NULL, NULL, NULL,
 'Payroll Provider (ADP)', 'ADP-10-25', 'Oct 2025 first half', 6000.00, NULL, NULL, FALSE, NULL, NULL, NULL, NULL, NULL),

-- Priya Sharma: 2026 only → +1,310
('TXN-00007', (SELECT id FROM candidates WHERE candidate_id='CAND-0002'), 'Earnings', '+', '2026-05-15',
 (SELECT id FROM projects WHERE project_id='PROJ-0003'),
 9240.00, 9240.00, '88 hrs x $105.00 (override rate)', 'May first half',
 '2026-05-01', '2026-05-15', 88, 105.00, 9240.00,
 NULL, NULL, NULL, NULL, NULL, NULL, FALSE, NULL, NULL, NULL, NULL, NULL),
('TXN-00008', (SELECT id FROM candidates WHERE candidate_id='CAND-0002'), 'Earnings', '+', '2026-05-31',
 (SELECT id FROM projects WHERE project_id='PROJ-0003'),
 8820.00, 8820.00, '84 hrs x $105.00', 'May second half',
 '2026-05-16', '2026-05-31', 84, 105.00, 8820.00,
 NULL, NULL, NULL, NULL, NULL, NULL, FALSE, NULL, NULL, NULL, NULL, NULL),
('TXN-00009', (SELECT id FROM candidates WHERE candidate_id='CAND-0002'), 'Company Payment', '-', '2026-06-05', NULL,
 15000.00, -15000.00, 'May payroll (both halves)', NULL,
 NULL, NULL, NULL, NULL, NULL,
 'Payroll Provider (Gusto)', 'GU-2214', 'May 2026', 15000.00, NULL, NULL, FALSE, NULL, NULL, NULL, NULL, NULL),
('TXN-00010', (SELECT id FROM candidates WHERE candidate_id='CAND-0002'), 'Expense', '-', '2026-04-10', NULL,
 3500.00, -3500.00, 'GC attorney retainer', 'PERM stage',
 NULL, NULL, NULL, NULL, NULL,
 NULL, NULL, NULL, NULL, 'GC Attorney Fee', 'Miller Immigration LLP', TRUE, 3500.00, NULL, NULL, NULL, NULL),
('TXN-00011', (SELECT id FROM candidates WHERE candidate_id='CAND-0002'), 'Candidate Payment', '+', '2026-06-20', NULL,
 1750.00, 1750.00, 'Half of GC attorney fee', NULL,
 NULL, NULL, NULL, NULL, NULL,
 NULL, NULL, NULL, NULL, NULL, NULL, FALSE, NULL, 'Green Card Reimbursement', 'Bank Transfer / ACH', 'ACH-9917', 1750.00),

-- Carlos Mendez: settled 2025, open +200 in 2026
('TXN-00012', (SELECT id FROM candidates WHERE candidate_id='CAND-0003'), 'Earnings', '+', '2025-09-30',
 (SELECT id FROM projects WHERE project_id='PROJ-0004'),
 10880.00, 10880.00, '160 hrs x $68.00', 'September full month',
 '2025-09-01', '2025-09-30', 160, 68.00, 10880.00,
 NULL, NULL, NULL, NULL, NULL, NULL, FALSE, NULL, NULL, NULL, NULL, NULL),
('TXN-00013', (SELECT id FROM candidates WHERE candidate_id='CAND-0003'), 'Company Payment', '-', '2025-10-05',
 (SELECT id FROM projects WHERE project_id='PROJ-0004'),
 10880.00, -10880.00, 'September payroll — full', NULL,
 NULL, NULL, NULL, NULL, NULL,
 'Check', 'CHK-1042', 'Sep 2025', 10880.00, NULL, NULL, FALSE, NULL, NULL, NULL, NULL, NULL),
('TXN-00014', (SELECT id FROM candidates WHERE candidate_id='CAND-0003'), 'Earnings', '+', '2026-01-31',
 (SELECT id FROM projects WHERE project_id='PROJ-0004'),
 10200.00, 10200.00, '150 hrs x $68.00', 'January full month',
 '2026-01-01', '2026-01-31', 150, 68.00, 10200.00,
 NULL, NULL, NULL, NULL, NULL, NULL, FALSE, NULL, NULL, NULL, NULL, NULL),
('TXN-00015', (SELECT id FROM candidates WHERE candidate_id='CAND-0003'), 'Company Payment', '-', '2026-02-05', NULL,
 10000.00, -10000.00, 'January payroll (partial)', 'Remainder pending timesheet approval',
 NULL, NULL, NULL, NULL, NULL,
 'Bank Transfer / ACH', 'ACH-2201', 'Jan 2026', 10000.00, NULL, NULL, FALSE, NULL, NULL, NULL, NULL, NULL),

-- Wei Chen: fully settled 2025
('TXN-00016', (SELECT id FROM candidates WHERE candidate_id='CAND-0004'), 'Earnings', '+', '2025-08-15',
 (SELECT id FROM projects WHERE project_id='PROJ-0005'),
 4800.00, 4800.00, '80 hrs x $60.00', 'August first half',
 '2025-08-01', '2025-08-15', 80, 60.00, 4800.00,
 NULL, NULL, NULL, NULL, NULL, NULL, FALSE, NULL, NULL, NULL, NULL, NULL),
('TXN-00017', (SELECT id FROM candidates WHERE candidate_id='CAND-0004'), 'Company Payment', '-', '2025-08-20', NULL,
 4800.00, -4800.00, 'August payroll', NULL,
 NULL, NULL, NULL, NULL, NULL,
 'Wire Transfer', 'WT-7734', 'Aug 2025 first half', 4800.00, NULL, NULL, FALSE, NULL, NULL, NULL, NULL, NULL),
('TXN-00018', (SELECT id FROM candidates WHERE candidate_id='CAND-0004'), 'Expense', '-', '2025-09-10', NULL,
 800.00, -800.00, 'Visa stamping fees', 'Chennai consulate trip',
 NULL, NULL, NULL, NULL, NULL,
 NULL, NULL, NULL, NULL, 'Visa Stamping', 'US Consulate', TRUE, 800.00, NULL, NULL, NULL, NULL),
('TXN-00019', (SELECT id FROM candidates WHERE candidate_id='CAND-0004'), 'Candidate Payment', '+', '2025-09-25', NULL,
 800.00, 800.00, 'Visa stamping reimbursement — full', NULL,
 NULL, NULL, NULL, NULL, NULL,
 NULL, NULL, NULL, NULL, NULL, NULL, FALSE, NULL, 'Visa Stamping Reimbursement', 'Zelle', 'ZL-8810', 800.00)
ON CONFLICT (transaction_id) DO NOTHING;

-- ===========================================================================
-- Expected reference numbers (for verification):
--   John Smith    balance +1,060.00  (2026: +980.00, 2025: +80.00)
--   Priya Sharma  balance +1,310.00
--   Carlos Mendez balance   +200.00
--   Wei Chen      balance      0.00  (Settled)
--   Firm-wide: earnings 57,500 | company payments 51,680 |
--              candidate payments 3,050 | expenses 6,300 | net position −2,570
--   COMP-0001 net +2,370 (owed to candidates) · COMP-0002 net +200
-- ===========================================================================
