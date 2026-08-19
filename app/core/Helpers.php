<?php
/**
 * Small global helpers used across views and controllers.
 */

/** HTML-escape for output. */
function escape(mixed $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

/** Alias — shorter in templates. */
function e(mixed $value): string
{
    return escape($value);
}

/** $1,234.56 — always 2 decimals, $ prefix. Negative → -$1,234.56 */
function format_currency(mixed $amount): string
{
    $n = (float) ($amount ?? 0);
    $sign = $n < 0 ? '-' : '';
    return $sign . '$' . number_format(abs($n), 2);
}

/** dd-Mmm-yyyy, e.g. 15-Jun-2026 */
function format_date(?string $date): string
{
    if (!$date) {
        return '';
    }
    $ts = strtotime($date);
    return $ts ? date('d-M-Y', $ts) : '';
}

/** Redirect and stop. */
function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

/** Current request path (no query string). */
function current_path(): string
{
    return parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
}

/** True when the current path starts with the given prefix (for nav highlighting). */
function nav_active(string $prefix): bool
{
    $p = current_path();
    if ($prefix === '/') {
        return $p === '/';
    }
    return str_starts_with($p, $prefix);
}

/**
 * Render a view inside the master layout.
 * $view is relative to app/views, without .php — e.g. 'companies/index'.
 */
function render(string $view, array $data = [], string $layout = 'app'): void
{
    extract($data, EXTR_SKIP);
    ob_start();
    include BASE_PATH . '/app/views/' . $view . '.php';
    $content = ob_get_clean();
    include BASE_PATH . '/app/views/layouts/' . $layout . '.php';
}

/** Render a view WITHOUT layout (for PDF templates, fragments). */
function render_partial(string $view, array $data = []): string
{
    extract($data, EXTR_SKIP);
    ob_start();
    include BASE_PATH . '/app/views/' . $view . '.php';
    return ob_get_clean();
}

/** JSON response and stop. */
function json_response(mixed $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/** Trimmed POST value or null when blank. */
function post(string $key): ?string
{
    $v = $_POST[$key] ?? null;
    if ($v === null) {
        return null;
    }
    $v = trim((string) $v);
    return $v === '' ? null : $v;
}

/** POST value as float or null. */
function post_num(string $key): ?float
{
    $v = post($key);
    if ($v === null) {
        return null;
    }
    $v = str_replace([',', '$'], '', $v);
    return is_numeric($v) ? (float) $v : null;
}

/** GET value trimmed or null. */
function query(string $key): ?string
{
    $v = $_GET[$key] ?? null;
    if ($v === null) {
        return null;
    }
    $v = trim((string) $v);
    return $v === '' ? null : $v;
}

/** Active dropdown options for a category (value list). */
function dropdown(string $category): array
{
    return DropdownOption::values($category);
}

/** Fixed, non-extensible list per spec. */
function method_received_options(): array
{
    return ['Check', 'Bank Transfer / ACH', 'Wire Transfer', 'Zelle', 'PayPal', 'Cash', 'Other'];
}

/** Preset reporting periods for dashboard filters. */
function period_options(): array
{
    return [
        'all'          => 'All time',
        'this_month'   => 'This month',
        'last_month'   => 'Last month',
        'this_quarter' => 'This quarter',
        'this_year'    => 'This year',
        'last_year'    => 'Last year',
        'custom'       => 'Custom dates…',
    ];
}

/**
 * Resolve a period key (+ optional custom dates) to [from, to, label].
 * from/to are Y-m-d strings or null (open-ended).
 */
function resolve_period(?string $period, ?string $customFrom, ?string $customTo): array
{
    $y = (int) date('Y');
    switch ($period ?: 'all') {
        case 'this_month':
            return [date('Y-m-01'), date('Y-m-t'), 'This month'];
        case 'last_month':
            $t = strtotime('first day of last month');
            return [date('Y-m-01', $t), date('Y-m-t', $t), 'Last month'];
        case 'this_quarter':
            $q = intdiv((int) date('n') - 1, 3);
            $start = sprintf('%04d-%02d-01', $y, $q * 3 + 1);
            $end = date('Y-m-t', strtotime(sprintf('%04d-%02d-01', $y, $q * 3 + 3)));
            return [$start, $end, 'This quarter'];
        case 'this_year':
            return ["$y-01-01", "$y-12-31", 'This year'];
        case 'last_year':
            $ly = $y - 1;
            return ["$ly-01-01", "$ly-12-31", 'Last year'];
        case 'custom':
            $label = 'Custom';
            if ($customFrom || $customTo) {
                $label = ($customFrom ? format_date($customFrom) : '…') . ' – ' . ($customTo ? format_date($customTo) : '…');
            }
            return [$customFrom ?: null, $customTo ?: null, $label];
        default:
            return [null, null, 'All time'];
    }
}
