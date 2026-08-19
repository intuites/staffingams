<?php

require_once BASE_PATH . '/app/exports/ExcelExporter.php';
require_once BASE_PATH . '/app/exports/PdfExporter.php';

class ExportsController
{
    /**
     * GET /export/transactions?format=xlsx|csv|pdf&candidate=&type=&project_id=&from_date=&to_date=
     */
    public function transactions(): void
    {
        Auth::requireLogin();
        $filters = [
            'candidate_id' => query('candidate'),
            'type'         => query('type'),
            'project_id'   => query('project_id'),
            'from_date'    => query('from_date'),
            'to_date'      => query('to_date'),
        ];
        $rows = Transaction::filtered($filters);
        $total = Transaction::filteredTotal($filters);

        $headers = ['Date', 'Transaction ID', 'Candidate', 'Type', 'Direction', 'Final Amount', 'Amount Notes', 'Project', 'Description'];
        $data = array_map(fn($t) => [
            format_date($t['transaction_date']),
            $t['transaction_id'],
            $t['candidate_name'],
            $t['type'],
            $t['direction'],
            (float) $t['effective_amount'],
            $t['amount_notes'],
            $t['project_name'],
            $t['description_notes'],
        ], $rows);
        $totals = ['TOTAL', '', '', '', '', $total, '', '', ''];
        $name = 'transactions_' . date('Ymd_His');

        if ((query('format') ?? 'csv') === 'xlsx') {
            $sheets = array_merge([[
                'title'  => 'Summary',
                'blocks' => [
                    ['type' => 'heading', 'text' => 'Transactions Export — Summary'],
                    ['type' => 'kv', 'rows' => self::txnSummaryKv($rows)],
                ],
            ]], self::txnCategorySheets($rows));
            ExcelExporter::workbook($name, $sheets);
        }
        $this->emit($name, 'Transactions', $headers, $data, $totals, [5]);
    }

    /**
     * GET /export/report?report=per_project|per_company|per_status&format=...
     */
    public function report(): void
    {
        Auth::requireLogin();
        $report = query('report') ?? 'per_project';
        require_once BASE_PATH . '/app/controllers/ReportsController.php';

        switch ($report) {
            case 'per_candidate':
                $rows = ReportsController::perCandidateRows(query('from_date'), query('to_date'));
                $headers = ['Candidate ID', 'Candidate', 'Company', 'Status', 'Earnings', 'Company Payments', 'Candidate Payments', 'Expenses', 'Current Balance', 'Position'];
                $data = array_map(fn($r) => [
                    $r['candidate_code'], $r['full_name'], $r['company_name'], $r['employment_status'],
                    (float) $r['total_earnings'], (float) $r['total_company_payments'],
                    (float) $r['total_candidate_payments'], (float) $r['total_expenses'],
                    (float) $r['current_balance'], $r['status'],
                ], $rows);
                $totals = ['TOTAL', '', '', '',
                    array_sum(array_column($data, 4)), array_sum(array_column($data, 5)),
                    array_sum(array_column($data, 6)), array_sum(array_column($data, 7)),
                    array_sum(array_column($data, 8)), ''];
                $cur = [4, 5, 6, 7, 8];
                $title = 'Per-Candidate Report';
                break;
            case 'per_company':
                $rows = ReportsController::perCompanyRows();
                $headers = ['Company ID', 'Company', '# Candidates', 'Total Earnings', 'Company Payments', 'Candidate Payments', 'Expenses', 'Net Balance'];
                $data = array_map(fn($r) => [
                    $r['company_id'], $r['company_name'], (int) $r['candidate_count'],
                    (float) $r['total_earnings'], (float) $r['total_company_payments'],
                    (float) $r['total_candidate_payments'], (float) $r['total_expenses'], (float) $r['net_balance'],
                ], $rows);
                $totals = ['TOTAL', '', array_sum(array_column($data, 2)),
                    array_sum(array_column($data, 3)), array_sum(array_column($data, 4)),
                    array_sum(array_column($data, 5)), array_sum(array_column($data, 6)), array_sum(array_column($data, 7))];
                $cur = [3, 4, 5, 6, 7];
                $title = 'Per-Company Report';
                break;
            case 'per_status':
                $rows = ReportsController::perStatusRows();
                $headers = ['Employment Status', '# Candidates', 'Aggregate Current Balance'];
                $data = array_map(fn($r) => [
                    $r['employment_status'], (int) $r['candidate_count'], (float) $r['aggregate_balance'],
                ], $rows);
                $totals = ['TOTAL', array_sum(array_column($data, 1)), array_sum(array_column($data, 2))];
                $cur = [2];
                $title = 'Per-Employment-Status Report';
                break;
            default:
                $rows = ReportsController::perProjectRows(query('from_date'), query('to_date'));
                $headers = ['Project ID', 'Project', 'Staffing Partner', 'Candidate', 'Total Earnings', 'Company Payments', 'Net'];
                $data = array_map(fn($r) => [
                    $r['project_id'], $r['project_name'], $r['partner_name'] ?? '', $r['candidate_name'],
                    (float) $r['total_earnings'], (float) $r['total_company_payments'], (float) $r['net'],
                ], $rows);
                $totals = ['TOTAL', '', '', '',
                    array_sum(array_column($data, 4)), array_sum(array_column($data, 5)), array_sum(array_column($data, 6))];
                $cur = [4, 5, 6];
                $title = 'Per-Project Report';
        }

        $this->emit($report . '_' . date('Ymd_His'), $title, $headers, $data, $totals, $cur);
    }

    /**
     * GET /export/statement?candidate=&from_date=&to_date=&format=xlsx|csv|pdf
     * Full single-candidate statement: summary, per-project breakdown, and
     * every transaction in the window.
     */
    public function statement(): void
    {
        Auth::requireLogin();
        $cid = (int) (query('candidate') ?? 0);
        $candidate = Candidate::find($cid);
        if (!$candidate) {
            http_response_code(404);
            exit('Candidate not found');
        }
        $from = query('from_date');
        $to   = query('to_date');
        $periodLabel = ($from || $to)
            ? (($from ? format_date($from) : '…') . ' – ' . ($to ? format_date($to) : '…'))
            : 'All time';

        $balances = Candidate::balancesFor($cid, $from, $to);
        $summary = Project::financialSummaryByCandidate($cid, $from, $to);
        $txns = Transaction::filtered(['candidate_id' => $cid, 'from_date' => $from, 'to_date' => $to]);
        $total = Transaction::filteredTotal(['candidate_id' => $cid, 'from_date' => $from, 'to_date' => $to]);

        $name = 'statement_' . strtolower($candidate['candidate_id']) . '_' . date('Ymd_His');
        $title = 'Candidate Statement — ' . $candidate['first_name'] . ' ' . $candidate['last_name']
               . ' (' . $candidate['candidate_id'] . ') · ' . $periodLabel;

        // ---- Section datasets ----
        $sumRows = [
            ['Candidate', $candidate['first_name'] . ' ' . $candidate['last_name'] . ' (' . $candidate['candidate_id'] . ')'],
            ['Payroll Company', $candidate['company_name']],
            ['Employment Status', $candidate['employment_status']],
            ['Period', $periodLabel],
            ['Earnings', (float) ($balances['total_earnings'] ?? 0)],
            ['Company Payments', (float) ($balances['total_company_payments'] ?? 0)],
            ['Candidate Payments', (float) ($balances['total_candidate_payments'] ?? 0)],
            ['Expenses', (float) ($balances['total_expenses'] ?? 0)],
            ['Balance', (float) ($balances['current_balance'] ?? 0)],
            ['Position', $balances['status'] ?? 'Settled'],
        ];
        $projHead = ['Project', 'Staffing Partner', 'Earnings', 'Company Payments', 'Candidate Payments', 'Expenses', 'Balance'];
        $projRows = array_map(fn($p) => [
            $p['project_name'] . ' (' . $p['project_id'] . ')', $p['partner_name'] ?? '',
            (float) $p['earnings'], (float) $p['company_payments'],
            (float) $p['candidate_payments'], (float) $p['expenses'], (float) $p['balance'],
        ], $summary['projects']);
        if ($summary['unassigned']) {
            $u = $summary['unassigned'];
            $projRows[] = ['Not linked to a project', '', (float) $u['earnings'], (float) $u['company_payments'],
                (float) $u['candidate_payments'], (float) $u['expenses'], (float) $u['balance']];
        }
        $txnHead = ['Date', 'Transaction ID', 'Type', 'Direction', 'Status', 'Amount', 'Amount Notes', 'Project', 'Description'];
        $txnRows = array_map(fn($t) => [
            format_date($t['transaction_date']), $t['transaction_id'], $t['type'], $t['direction'],
            ucfirst($t['status']), (float) $t['effective_amount'], $t['amount_notes'],
            $t['project_name'], $t['description_notes'],
        ], $txns);
        $txnTotals = ['TOTAL (pending/rejected excluded)', '', '', '', '', $total, '', '', ''];

        $format = query('format') ?? 'pdf';
        if ($format === 'xlsx') {
            $projTotals = ['OVERALL', '',
                (float) ($balances['total_earnings'] ?? 0), (float) ($balances['total_company_payments'] ?? 0),
                (float) ($balances['total_candidate_payments'] ?? 0), (float) ($balances['total_expenses'] ?? 0),
                (float) ($balances['current_balance'] ?? 0)];
            $sheets = array_merge([[
                'title'  => 'Summary',
                'blocks' => [
                    ['type' => 'heading', 'text' => $title],
                    ['type' => 'kv', 'rows' => $sumRows],
                    ['type' => 'heading', 'text' => 'By Project'],
                    ['type' => 'table', 'headers' => $projHead, 'rows' => $projRows,
                     'totals' => $projTotals, 'currencyCols' => [2, 3, 4, 5, 6]],
                ],
            ]], self::txnCategorySheets($txns));
            ExcelExporter::workbook($name, $sheets);
        }
        if ($format === 'csv') {
            if (ob_get_length()) { ob_end_clean(); }
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $name . '.csv"');
            $out = fopen('php://output', 'w');
            $w = fn(array $row) => fputcsv($out, $row, ',', '"', '\\');
            $w([$title]);
            $w([]);
            $w(['SUMMARY']);
            foreach ($sumRows as $r) { $w($r); }
            $w([]);
            $w(['BY PROJECT']);
            $w($projHead);
            foreach ($projRows as $r) { $w($r); }
            $w([]);
            $w(['TRANSACTIONS']);
            $w($txnHead);
            foreach ($txnRows as $r) { $w($r); }
            $w($txnTotals);
            fclose($out);
            exit;
        }
        // PDF statement
        $this->statementPdf($name, $title, $sumRows, $projHead, $projRows, $txnHead, $txnRows, $txnTotals);
    }

    private function statementPdf(string $name, string $title, array $sumRows, array $projHead, array $projRows, array $txnHead, array $txnRows, array $txnTotals): never
    {
        $fmt = function ($v) {
            if (is_float($v) || is_int($v)) {
                $sign = $v < 0 ? '-' : '';
                return $sign . '$' . number_format(abs((float) $v), 2);
            }
            return htmlspecialchars((string) $v, ENT_QUOTES);
        };
        $table = function (array $head, array $rows, ?array $totals = null) use ($fmt): string {
            $h = '<tr>' . implode('', array_map(fn($c) => '<th>' . htmlspecialchars($c, ENT_QUOTES) . '</th>', $head)) . '</tr>';
            $b = '';
            foreach ($rows as $row) {
                $b .= '<tr>' . implode('', array_map(fn($c) => '<td' . (is_float($c) || is_int($c) ? ' class="num"' : '') . '>' . $fmt($c) . '</td>', array_values($row))) . '</tr>';
            }
            if ($totals !== null) {
                $b .= '<tr class="total">' . implode('', array_map(fn($c) => '<td' . (is_float($c) || is_int($c) ? ' class="num"' : '') . '>' . $fmt($c) . '</td>', array_values($totals))) . '</tr>';
            }
            return '<table><thead>' . $h . '</thead><tbody>' . $b . '</tbody></table>';
        };
        $sum = '<table class="kv">';
        foreach ($sumRows as [$k, $v]) {
            $sum .= '<tr><td class="k">' . htmlspecialchars($k, ENT_QUOTES) . '</td><td>' . $fmt($v) . '</td></tr>';
        }
        $sum .= '</table>';
        $generated = date('d-M-Y H:i');
        $html = '<!DOCTYPE html><html><head><meta charset="utf-8"><style>
            body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1c2b3d; }
            h1 { font-size: 15px; margin: 0 0 2px; color: #0e2136; }
            h2 { font-size: 12px; margin: 18px 0 6px; color: #0e2136; border-bottom: 2px solid #0fb5ea; padding-bottom: 3px; }
            .meta { color: #64798f; margin-bottom: 10px; }
            table { width: 100%; border-collapse: collapse; }
            th { background: #0e2136; color: #fff; text-align: left; padding: 6px 8px; font-size: 9px; text-transform: uppercase; }
            td { padding: 5px 8px; border-bottom: 1px solid #e9eff5; }
            td.num { text-align: right; }
            tr.total td { background: #e3f4fd; font-weight: bold; border-top: 2px solid #0fb5ea; }
            table.kv td.k { font-weight: bold; width: 180px; color: #33465c; }
        </style></head><body>'
            . '<h1>' . htmlspecialchars($title, ENT_QUOTES) . '</h1>'
            . '<div class="meta">Generated ' . $generated . ' &middot; Staffing Accounting System</div>'
            . '<h2>Summary</h2>' . $sum
            . '<h2>By Project</h2>' . $table($projHead, $projRows)
            . '<h2>Transactions</h2>' . $table($txnHead, $txnRows, $txnTotals)
            . '</body></html>';

        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', false);
        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('letter', 'landscape');
        $dompdf->render();
        if (ob_get_length()) { ob_end_clean(); }
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $name . '.pdf"');
        echo $dompdf->output();
        exit;
    }

    /** Per-category worksheets: Earnings, Company Payments, Expenses, Candidate Payments. */
    private static function txnCategorySheets(array $txns): array
    {
        $byType = fn(string $t) => array_values(array_filter($txns, fn($x) => $x['type'] === $t));
        $sumOk = fn(array $list) => array_sum(array_map(
            fn($x) => in_array($x['status'], ['approved', 'locked'], true) ? (float) $x['effective_amount'] : 0.0,
            $list
        ));
        $pad = fn(int $n, int $amountCol, float $sum) => array_replace(
            array_fill(0, $n, ''), [0 => 'TOTAL (approved + locked)', $amountCol => $sum]
        );

        $earn = $byType('Earnings');
        $cpay = $byType('Company Payment');
        $exp  = $byType('Expense');
        $capy = $byType('Candidate Payment');

        return [
            [
                'title'  => 'Earnings',
                'blocks' => [[
                    'type' => 'table',
                    'headers' => ['Date', 'Transaction ID', 'Candidate', 'Project', 'Period Start', 'Period End', 'Hours', 'Rate', 'Auto Amount', 'Override', 'Final Amount', 'Status', 'Amount Notes'],
                    'rows' => array_map(fn($t) => [
                        format_date($t['transaction_date']), $t['transaction_id'], $t['candidate_name'] ?? '',
                        $t['project_name'] ?? '', format_date($t['period_start_date'] ?? null), format_date($t['period_end_date'] ?? null),
                        $t['hours_worked'] !== null ? (float) $t['hours_worked'] : '',
                        $t['rate_applied'] !== null ? (float) $t['rate_applied'] : '',
                        $t['auto_calculated_amount'] !== null ? (float) $t['auto_calculated_amount'] : '',
                        $t['amount_override'] !== null ? (float) $t['amount_override'] : '',
                        (float) $t['effective_amount'], ucfirst($t['status']), $t['amount_notes'],
                    ], $earn),
                    'totals' => $pad(13, 10, $sumOk($earn)),
                    'currencyCols' => [7, 8, 9, 10],
                ]],
            ],
            [
                'title'  => 'Company Payments',
                'blocks' => [[
                    'type' => 'table',
                    'headers' => ['Date', 'Transaction ID', 'Candidate', 'Project', 'Payment Method', 'Reference #', 'Period Covered', 'Amount', 'Status', 'Amount Notes'],
                    'rows' => array_map(fn($t) => [
                        format_date($t['transaction_date']), $t['transaction_id'], $t['candidate_name'] ?? '',
                        $t['project_name'] ?? '', $t['payment_method'], $t['reference_number'], $t['period_covered'],
                        (float) $t['effective_amount'], ucfirst($t['status']), $t['amount_notes'],
                    ], $cpay),
                    'totals' => $pad(10, 7, $sumOk($cpay)),
                    'currencyCols' => [7],
                ]],
            ],
            [
                'title'  => 'Expenses',
                'blocks' => [[
                    'type' => 'table',
                    'headers' => ['Date', 'Transaction ID', 'Candidate', 'Expense Type', 'Paid To (Vendor)', 'Reimbursable', 'Amount', 'Status', 'Amount Notes'],
                    'rows' => array_map(fn($t) => [
                        format_date($t['transaction_date']), $t['transaction_id'], $t['candidate_name'] ?? '',
                        $t['expense_type'], $t['paid_to_vendor'],
                        ($t['reimbursable_by_candidate'] && $t['reimbursable_by_candidate'] !== 'f') ? 'Yes' : 'No',
                        (float) $t['effective_amount'], ucfirst($t['status']), $t['amount_notes'],
                    ], $exp),
                    'totals' => $pad(9, 6, $sumOk($exp)),
                    'currencyCols' => [6],
                ]],
            ],
            [
                'title'  => 'Candidate Payments',
                'blocks' => [[
                    'type' => 'table',
                    'headers' => ['Date', 'Transaction ID', 'Candidate', 'Reason', 'Method Received', 'Reference', 'Amount', 'Status', 'Amount Notes'],
                    'rows' => array_map(fn($t) => [
                        format_date($t['transaction_date']), $t['transaction_id'], $t['candidate_name'] ?? '',
                        $t['reason_for_payment'], $t['method_received'], $t['reference'],
                        (float) $t['effective_amount'], ucfirst($t['status']), $t['amount_notes'],
                    ], $capy),
                    'totals' => $pad(9, 6, $sumOk($capy)),
                    'currencyCols' => [6],
                ]],
            ],
        ];
    }

    /** Summary key-value rows for a transaction set (counts + approved/locked totals + net). */
    private static function txnSummaryKv(array $txns): array
    {
        $rows = [];
        $net = 0.0;
        foreach (['Earnings', 'Company Payment', 'Expense', 'Candidate Payment'] as $type) {
            $list = array_filter($txns, fn($x) => $x['type'] === $type);
            $ok = array_filter($list, fn($x) => in_array($x['status'], ['approved', 'locked'], true));
            $sum = array_sum(array_map(fn($x) => (float) $x['effective_amount'], $ok));
            $net += array_sum(array_map(fn($x) => (float) $x['signed_amount'], $ok));
            $rows[] = [$type . ' (' . count($list) . ' transactions)', $sum];
        }
        $rows[] = ['Net Balance (approved + locked)', $net];
        $rows[] = ['Note', 'Pending/rejected transactions are listed on category sheets but excluded from totals.'];
        return $rows;
    }

    private function emit(string $name, string $title, array $headers, array $data, array $totals, array $currencyCols): never
    {
        $format = query('format') ?? 'csv';
        if ($format === 'xlsx') {
            ExcelExporter::download($name, $headers, $data, $totals, $currencyCols);
        }
        if ($format === 'pdf') {
            PdfExporter::download($name, $title, $headers, $data, $totals, $currencyCols);
        }
        // CSV — native PHP
        if (ob_get_length()) { ob_end_clean(); }
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $name . '.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, $headers, ',', '"', '\\');
        foreach ($data as $row) {
            fputcsv($out, $row, ',', '"', '\\');
        }
        fputcsv($out, $totals, ',', '"', '\\');
        fclose($out);
        exit;
    }
}
