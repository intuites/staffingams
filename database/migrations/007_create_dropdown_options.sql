-- 007: dropdown_options
CREATE TABLE IF NOT EXISTS dropdown_options (
    id BIGSERIAL PRIMARY KEY,
    category VARCHAR(50) NOT NULL,
    value VARCHAR(200) NOT NULL,
    display_order INT NOT NULL DEFAULT 0,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    UNIQUE(category, value)
);
CREATE INDEX IF NOT EXISTS idx_dropdown_category_active ON dropdown_options(category, is_active, display_order);
