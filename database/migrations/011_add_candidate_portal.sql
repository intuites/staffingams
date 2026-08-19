-- 011: candidate self-service portal — candidates sign in with their email
-- and a password managed by the admin. The portal only ever queries by the
-- logged-in candidate's own id, so each candidate sees only their own data.
ALTER TABLE candidates ADD COLUMN IF NOT EXISTS portal_password_hash VARCHAR(255);
ALTER TABLE candidates ADD COLUMN IF NOT EXISTS portal_enabled BOOLEAN NOT NULL DEFAULT FALSE;
ALTER TABLE candidates ADD COLUMN IF NOT EXISTS portal_last_login_at TIMESTAMPTZ;
