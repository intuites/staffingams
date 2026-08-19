-- 004: projects
CREATE TABLE IF NOT EXISTS projects (
    id BIGSERIAL PRIMARY KEY,
    project_id VARCHAR(20) NOT NULL UNIQUE,
    candidate_id BIGINT NOT NULL REFERENCES candidates(id),
    project_name VARCHAR(255) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE,
    rate_from_client DECIMAL(10,2) NOT NULL,
    rate_informed_to_candidate DECIMAL(10,2) NOT NULL,
    percent_paid_to_candidate DECIMAL(5,4) NOT NULL,
    auto_calculated_final_rate DECIMAL(10,2) NOT NULL,
    final_rate_override DECIMAL(10,2),
    rate_paid_to_candidate DECIMAL(10,2) NOT NULL,
    notes TEXT,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_projects_candidate_id ON projects(candidate_id);
