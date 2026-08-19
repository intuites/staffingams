-- 003: candidates
CREATE TABLE IF NOT EXISTS candidates (
    id BIGSERIAL PRIMARY KEY,
    candidate_id VARCHAR(20) NOT NULL UNIQUE,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    phone VARCHAR(50) NOT NULL,
    company_id BIGINT NOT NULL REFERENCES companies(id),
    employment_status VARCHAR(50) NOT NULL,
    date_registered DATE NOT NULL DEFAULT CURRENT_DATE,
    notes TEXT,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_candidates_company_id ON candidates(company_id);
CREATE INDEX IF NOT EXISTS idx_candidates_employment_status ON candidates(employment_status);
