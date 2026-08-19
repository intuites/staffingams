-- 010: staffing_partners — the client/vendor/partner organizations where
-- candidates are engaged on projects. NOT the candidate's employer:
-- companies = the staffing organization running payroll (employer of record);
-- a project links candidate + staffing partner, and reaches the company
-- indirectly through the candidate.
CREATE TABLE IF NOT EXISTS staffing_partners (
    id BIGSERIAL PRIMARY KEY,
    partner_id VARCHAR(20) NOT NULL UNIQUE,            -- PART-0001
    partner_name VARCHAR(255) NOT NULL UNIQUE,
    partner_type VARCHAR(50),                          -- Client, Vendor, Partner, Other
    address TEXT,
    primary_contact_name VARCHAR(255),
    primary_contact_email VARCHAR(255),
    primary_contact_phone VARCHAR(50),
    date_added DATE NOT NULL DEFAULT CURRENT_DATE,
    notes TEXT,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

ALTER TABLE projects ADD COLUMN IF NOT EXISTS staffing_partner_id BIGINT REFERENCES staffing_partners(id);
CREATE INDEX IF NOT EXISTS idx_projects_staffing_partner ON projects(staffing_partner_id);

ALTER TABLE staffing_partners ENABLE ROW LEVEL SECURITY;
