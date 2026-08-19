# Staffing Accounting Management System

Plain PHP 8.2+ admin application for a US IT staffing firm — tracks Companies,
Candidates, Projects and Transactions, and computes a live running balance per
candidate:

```
Current Balance = (Earnings + Candidate Payments) − (Company Payments + Expenses)
```

Positive → firm owes candidate · Negative → candidate owes firm · Zero → settled.

## Stack

- **Backend:** plain PHP (no framework), custom ~50-line regex router
- **Database:** Supabase Postgres (project `Staffing_Accounting_IES`), native PDO/pgsql
- **File storage:** Supabase Storage (4 private buckets: companies, candidates, projects, transactions)
- **Frontend:** custom CSS design system ported from the Intuites healthcare site
  (4 switchable themes: warm / **navy** (default) / violet / ink) + dependency-free vanilla JS
- **Exports:** PhpSpreadsheet (Excel), Dompdf (PDF), native CSV
- **Auth:** custom session-based, bcrypt, CSRF-protected, login rate limiting

## Quick start (local)

```bash
composer install                # or use the bundled vendor/ folder
cp .env.example .env            # fill in DB + Supabase values
php scripts/migrate.php         # runs database/migrations/*.sql, tracked in _migrations
php scripts/seed.php            # seeds dropdown options
php scripts/make_admin.php you@example.com 'StrongPass!' 'Your Name'
php -S localhost:8080 -t public scripts/dev-router.php
```

Open http://localhost:8080 and sign in.

> Note: the Supabase schema, seed data, storage buckets, RLS policies and the
> first admin user are **already applied** to project `mtwnxuklnmauimjzqynw`
> — the migrate/seed scripts are for fresh environments and are idempotent.

## Theme switching

Click the four colored dots in the top utility bar, or append `?theme=warm|navy|violet|ink`.
The choice persists in a cookie. All styling reads from CSS custom properties, so
adding a theme is ~40 lines of color tokens in `public/assets/css/app.css`.

## Layout

```
public/          web root (index.php front controller, .htaccess, assets)
app/core/        Router, Database (PDO), Session, Csrf, Auth, Helpers, SupabaseStorage, Attachments
app/models/      Company, Candidate, Project, Transaction, DropdownOption, AdminUser
app/controllers/ one per section + ExportsController, AttachmentsController
app/views/       native PHP templates (layouts, partials, pages)
app/exports/     ExcelExporter, PdfExporter
database/        numbered SQL migrations + seed.sql
scripts/         migrate.php, seed.php, make_admin.php, dev-router.php
```

## Business rules (spec §6)

- **Project rate:** `auto = min(rate_from_client, rate_informed) × percent_paid`,
  override wins when set and > 0. Computed server-side on save; live preview in the form.
- **Transactions:** direction is `+` for Earnings/Candidate Payment, `−` for
  Company Payment/Expense. Earnings amount = hours × rate unless overridden;
  transaction_date syncs to period_end_date for Earnings. `signed_amount` drives
  every balance in the app (via the `v_candidate_balances` view).
- **IDs:** COMP-0001 / CAND-0001 / PROJ-0001 / TXN-00001, generated inside a DB
  transaction on insert.
- **Cascade protection:** companies with candidates, candidates with
  transactions/projects, and projects with transactions cannot be deleted.

## Deployment

See `DEPLOYMENT.md` for the hosting.com (cPanel) procedure.

## Security checklist

All queries use prepared statements; all output goes through `escape()`;
every POST verifies a CSRF token; sessions are httponly/samesite=Lax and
regenerate on login; login is rate-limited; `.env` lives outside the web root;
the Supabase service key never reaches the browser; production error display is off.
