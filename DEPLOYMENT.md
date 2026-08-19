# Deployment — hosting.com (cPanel)

## 0. Current state

Supabase project **Staffing_Accounting_IES** (`mtwnxuklnmauimjzqynw`, us-west-2)
already has: full schema (10 tables + `v_candidate_balances` view), RLS enabled
with Phase-2 candidate policies, seeded dropdown options, 4 private storage
buckets, and the first admin user (`pavan@intuites.com`).

So deployment is only: upload code + point `.env` at Supabase.

## 1. Prerequisites

- PHP **8.2+** with **pdo_pgsql** enabled.
  Verify: upload `<?php phpinfo();` as `phpinfo.php`, look for `pdo_pgsql`,
  then **delete the file immediately**. If missing, ask hosting.com support to enable it.
- SFTP access (SSH optional — not required).

## 2. Upload

1. Upload the whole project to `~/staffing-app/` (ABOVE `public_html/`), **including `vendor/`**.
2. Copy the **contents** of `~/staffing-app/public/` into `~/public_html/`
   (`index.php`, `.htaccess`, `assets/`).
3. Edit `~/public_html/index.php` if your host layout differs — it only contains
   `require __DIR__ . '/../staffing-app/app/bootstrap.php';`-style path. Default file
   expects `public/` beside `app/`; for the split cPanel layout change the require to:
   ```php
   require dirname(__DIR__) . '/staffing-app/app/bootstrap.php';
   ```
4. Upload `.env` to `~/staffing-app/.env` (never into `public_html/`), permissions **600**.

## 3. Production .env

```
APP_URL=https://yourdomain.com
APP_ENV=production
APP_TIMEZONE=America/New_York
SESSION_SECURE=true

# IMPORTANT: shared hosts are usually IPv4-only — use the SESSION POOLER:
DB_HOST=aws-1-us-west-2.pooler.supabase.com
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=postgres.mtwnxuklnmauimjzqynw
DB_PASSWORD=<database password>
DB_SSLMODE=require

SUPABASE_URL=https://mtwnxuklnmauimjzqynw.supabase.co
SUPABASE_SERVICE_KEY=<service_role key>
```

If the pooler host in the Supabase dashboard (Settings → Database → Connection
string → "Session pooler") shows a different region host (e.g. `aws-0-…`), use that one.

## 4. Verify

1. `https://yourdomain.com/` → redirects to `/login`.
2. Sign in with the admin account.
3. Create a test company → appears in Supabase Table Editor.
4. `https://yourdomain.com/.env` → must be 403/404.

## 5. Updates

Re-upload changed files via SFTP. New migrations: run
`php scripts/migrate.php` from any machine that can reach Supabase, or paste the
new SQL into Supabase's SQL Editor. All migrations are idempotent.

## 6. Post-deployment checklist

- [ ] HTTPS enabled (free Let's Encrypt in cPanel)
- [ ] `phpinfo.php` deleted
- [ ] `.env` not web-accessible, permissions 600
- [ ] Login works; test company round-trips to Supabase
- [ ] `APP_ENV=production` (error display off, errors logged to `php-error.log`)
- [ ] Rotate the database password and service_role key if they were ever shared in chat/email
