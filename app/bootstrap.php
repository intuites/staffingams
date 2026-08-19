<?php
/**
 * Application bootstrap — path-independent, so the same code runs locally
 * and on hosting.com without edits. Configuration lives in .env.
 */

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';
require BASE_PATH . '/app/core/Env.php';

loadEnv(BASE_PATH . '/.env');

date_default_timezone_set(env('APP_TIMEZONE', 'America/New_York'));

if (env('APP_ENV', 'production') === 'production') {
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', BASE_PATH . '/php-error.log');
} else {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
}

require BASE_PATH . '/app/core/Helpers.php';
require BASE_PATH . '/app/core/Database.php';
require BASE_PATH . '/app/core/Session.php';
require BASE_PATH . '/app/core/Csrf.php';
require BASE_PATH . '/app/core/Auth.php';
require BASE_PATH . '/app/core/PortalAuth.php';
require BASE_PATH . '/app/core/Turnstile.php';
require BASE_PATH . '/app/core/Mailer.php';
require BASE_PATH . '/app/core/Notification.php';
require BASE_PATH . '/app/core/Router.php';
require BASE_PATH . '/app/core/SupabaseStorage.php';
require BASE_PATH . '/app/core/Attachments.php';

// Models
require BASE_PATH . '/app/models/Company.php';
require BASE_PATH . '/app/models/Candidate.php';
require BASE_PATH . '/app/models/Project.php';
require BASE_PATH . '/app/models/Transaction.php';
require BASE_PATH . '/app/models/StaffingPartner.php';
require BASE_PATH . '/app/models/DropdownOption.php';
require BASE_PATH . '/app/models/AdminUser.php';
require BASE_PATH . '/app/models/ReviewRequest.php';

Session::start();

// Daily digest: flush lazily on authenticated requests (cron can also run
// scripts/send_digest.php for exact timing). Never blocks the request.
if (!empty($_SESSION['admin_id']) || !empty($_SESSION['portal_candidate_id'])) {
    try { Notification::flushIfDue(); } catch (\Throwable $e) { error_log('[digest] ' . $e->getMessage()); }
}

// ---------------------------------------------------------------------------
// Routes
// ---------------------------------------------------------------------------
$router = new Router();

// Auth
$router->get('/login',  'AuthController@showLogin');
$router->post('/login', 'AuthController@login');
$router->get('/logout', 'AuthController@logout');

// Candidate self-service portal (candidate-scoped; separate session)
$router->get('/portal/login',        'PortalController@showLogin');
$router->post('/portal/login',       'PortalController@login');
$router->get('/portal/logout',       'PortalController@logout');
$router->get('/portal',              'PortalController@dashboard');
$router->get('/portal/transactions', 'PortalController@transactions');
$router->get('/portal/export',       'PortalController@exportCsv');
$router->get('/portal/review/{id}',  'PortalController@reviewForm');
$router->post('/portal/review/{id}', 'PortalController@reviewSubmit');

// Dashboards
$router->get('/', 'DashboardController@index');                       // Company Dashboard
$router->get('/candidate-dashboard', 'DashboardController@candidate'); // Candidate Dashboard

// Companies
$router->get('/companies',              'CompaniesController@index');
$router->get('/companies/create',       'CompaniesController@create');
$router->post('/companies',             'CompaniesController@store');
$router->get('/companies/{id}',         'CompaniesController@show');
$router->get('/companies/{id}/edit',    'CompaniesController@edit');
$router->post('/companies/{id}',        'CompaniesController@update');
$router->post('/companies/{id}/delete', 'CompaniesController@destroy');
$router->get('/companies/{id}/candidates.json', 'CompaniesController@candidatesJson');

// Candidates
$router->get('/candidates',                 'CandidatesController@index');
$router->get('/candidates/create',          'CandidatesController@create');
$router->post('/candidates',                'CandidatesController@store');
$router->get('/candidates/{id}',            'CandidatesController@show');
$router->get('/candidates/{id}/edit',       'CandidatesController@edit');
$router->post('/candidates/{id}',           'CandidatesController@update');
$router->post('/candidates/{id}/delete',    'CandidatesController@destroy');
$router->get('/candidates/{id}/transactions',  'CandidatesController@transactions');
$router->get('/candidates/{id}/projects.json', 'CandidatesController@projectsJson');
$router->get('/candidates/{id}/types.json',    'CandidatesController@typesJson');

// Staffing partners (clients / vendors — where candidates work on projects)
$router->get('/partners',              'StaffingPartnersController@index');
$router->get('/partners/create',       'StaffingPartnersController@create');
$router->post('/partners',             'StaffingPartnersController@store');
$router->get('/partners/{id}',         'StaffingPartnersController@show');
$router->get('/partners/{id}/edit',    'StaffingPartnersController@edit');
$router->post('/partners/{id}',        'StaffingPartnersController@update');
$router->post('/partners/{id}/delete', 'StaffingPartnersController@destroy');

// Projects
$router->get('/projects',              'ProjectsController@index');
$router->get('/projects/create',       'ProjectsController@create');
$router->post('/projects',             'ProjectsController@store');
$router->get('/projects/{id}/edit',    'ProjectsController@edit');
$router->post('/projects/{id}',        'ProjectsController@update');
$router->post('/projects/{id}/delete', 'ProjectsController@destroy');

// Transactions
$router->get('/transactions',              'TransactionsController@index');
$router->get('/transactions/create',       'TransactionsController@create');
$router->post('/transactions',             'TransactionsController@store');
$router->get('/transactions/{id}/edit',    'TransactionsController@edit');
$router->post('/transactions/{id}',        'TransactionsController@update');
$router->post('/transactions/{id}/delete', 'TransactionsController@destroy');
$router->get('/approvals',                   'TransactionsController@approvals');
$router->get('/reviews',                     'TransactionsController@reviews');
$router->post('/reviews/{id}/resolve',       'TransactionsController@resolveReview');
$router->post('/transactions/{id}/approve',  'TransactionsController@approve');
$router->post('/transactions/{id}/lock',     'TransactionsController@lockTxn');
$router->post('/transactions/{id}/reject',   'TransactionsController@rejectTxn');
$router->get('/rejected',                    'TransactionsController@rejected');
$router->post('/transactions/{id}/unlock',   'TransactionsController@unlockTxn');

// Exports (transactions + reports)
$router->get('/export/transactions', 'ExportsController@transactions');
$router->get('/export/report',       'ExportsController@report');
$router->get('/export/statement',    'ExportsController@statement');

// Attachments
$router->get('/attachments/{entity}/{id}/download', 'AttachmentsController@download');
$router->post('/attachments/{entity}/{id}/delete',  'AttachmentsController@destroy');

// Reports
$router->get('/reports',             'ReportsController@index');
$router->get('/reports/per-project', 'ReportsController@perProject');
$router->get('/reports/per-candidate', 'ReportsController@perCandidate');
$router->get('/reports/per-company', 'ReportsController@perCompany');
$router->get('/reports/per-status',  'ReportsController@perStatus');

// Settings
$router->get('/settings/dropdowns',                'SettingsController@dropdowns');
$router->post('/settings/dropdowns',               'SettingsController@storeOption');
$router->post('/settings/dropdowns/{id}/update',   'SettingsController@updateOption');
$router->post('/settings/dropdowns/{id}/toggle',   'SettingsController@toggleOption');

// Dispatch
$path = $_GET['path'] ?? parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', '/' . ltrim((string) $path, '/'));
