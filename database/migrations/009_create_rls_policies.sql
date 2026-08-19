-- 009: RLS (Phase 2 preparation). Phase 1 PHP app connects via service_role / postgres, which bypasses RLS.
-- The candidate SELECT policies use Supabase's auth.jwt()/auth.role(); on a plain
-- Postgres (local dev) the auth schema doesn't exist, so policies are created
-- only when it does. RLS itself is enabled everywhere.
ALTER TABLE candidates ENABLE ROW LEVEL SECURITY;
ALTER TABLE transactions ENABLE ROW LEVEL SECURITY;
ALTER TABLE projects ENABLE ROW LEVEL SECURITY;
ALTER TABLE companies ENABLE ROW LEVEL SECURITY;
ALTER TABLE admin_users ENABLE ROW LEVEL SECURITY;
ALTER TABLE dropdown_options ENABLE ROW LEVEL SECURITY;
ALTER TABLE company_attachments ENABLE ROW LEVEL SECURITY;
ALTER TABLE candidate_attachments ENABLE ROW LEVEL SECURITY;
ALTER TABLE project_attachments ENABLE ROW LEVEL SECURITY;
ALTER TABLE transaction_attachments ENABLE ROW LEVEL SECURITY;

DO $do$
BEGIN
    IF EXISTS (SELECT 1 FROM pg_namespace WHERE nspname = 'auth') THEN
        EXECUTE 'DROP POLICY IF EXISTS candidate_own_data ON candidates';
        EXECUTE $p$CREATE POLICY candidate_own_data ON candidates
            FOR SELECT
            USING (auth.jwt() ->> 'email' = email OR auth.role() = 'service_role')$p$;

        EXECUTE 'DROP POLICY IF EXISTS candidate_own_transactions ON transactions';
        EXECUTE $p$CREATE POLICY candidate_own_transactions ON transactions
            FOR SELECT
            USING (
                candidate_id IN (SELECT id FROM candidates WHERE email = auth.jwt() ->> 'email')
                OR auth.role() = 'service_role'
            )$p$;

        EXECUTE 'DROP POLICY IF EXISTS candidate_own_projects ON projects';
        EXECUTE $p$CREATE POLICY candidate_own_projects ON projects
            FOR SELECT
            USING (
                candidate_id IN (SELECT id FROM candidates WHERE email = auth.jwt() ->> 'email')
                OR auth.role() = 'service_role'
            )$p$;

        EXECUTE 'DROP POLICY IF EXISTS candidate_own_company ON companies';
        EXECUTE $p$CREATE POLICY candidate_own_company ON companies
            FOR SELECT
            USING (
                id IN (SELECT company_id FROM candidates WHERE email = auth.jwt() ->> 'email')
                OR auth.role() = 'service_role'
            )$p$;
    END IF;
END
$do$;
