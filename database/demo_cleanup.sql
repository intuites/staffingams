-- Remove ALL business data (companies, partners, candidates, projects,
-- transactions, attachments) and reset ID sequences. Keeps admin users and
-- dropdown settings. Use this to clear the demo dataset before real use.
TRUNCATE TABLE
    transaction_attachments,
    project_attachments,
    candidate_attachments,
    company_attachments,
    transactions,
    projects,
    candidates,
    staffing_partners,
    companies
RESTART IDENTITY CASCADE;
