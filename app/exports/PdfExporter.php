<?php

use Dompdf\Dompdf;
use Dompdf\Options;

class PdfExporter
{
    /**
     * Stream a PDF download rendered from a simple table.
     * Same inputs as ExcelExporter for consistency.
     */
    public static function download(string $filename, string $reportTitle, array $headers, array $rows, ?array $totals = null, array $currencyCols = []): never
    {
        $fmt = function ($v, $i) use ($currencyCols) {
            if (in_array($i, $currencyCols, true) && is_numeric($v)) {
                $sign = $v < 0 ? '-' : '';
                return $sign . '$' . number_format(abs((float) $v), 2);
            }
            return htmlspecialchars((string) $v, ENT_QUOTES);
        };

        $thead = '';
        foreach ($headers as $h) {
            $thead .= '<th>' . htmlspecialchars($h, ENT_QUOTES) . '</th>';
        }
        $tbody = '';
        foreach ($rows as $row) {
            $tbody .= '<tr>';
            foreach (array_values($row) as $i => $v) {
                $cls = in_array($i, $currencyCols, true) ? ' class="num"' : '';
                $tbody .= "<td{$cls}>" . $fmt($v, $i) . '</td>';
            }
            $tbody .= '</tr>';
        }
        if ($totals !== null) {
            $tbody .= '<tr class="total">';
            foreach (array_values($totals) as $i => $v) {
                $cls = in_array($i, $currencyCols, true) ? ' class="num"' : '';
                $tbody .= "<td{$cls}>" . $fmt($v, $i) . '</td>';
            }
            $tbody .= '</tr>';
        }

        $generated = date('d-M-Y H:i');
        $html = <<<HTML
<!DOCTYPE html><html><head><meta charset="utf-8"><style>
  body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1c2b3d; }
  h1 { font-size: 15px; margin: 0 0 2px; color: #0e2136; }
  .meta { color: #64798f; margin-bottom: 12px; }
  table { width: 100%; border-collapse: collapse; }
  th { background: #0e2136; color: #fff; text-align: left; padding: 6px 8px; font-size: 9px; text-transform: uppercase; }
  td { padding: 5px 8px; border-bottom: 1px solid #e9eff5; }
  td.num { text-align: right; }
  tr:nth-child(even) td { background: #f8fbfd; }
  tr.total td { background: #e3f4fd; font-weight: bold; border-top: 2px solid #0fb5ea; }
</style></head><body>
  <h1>{$reportTitle}</h1>
  <div class="meta">Generated {$generated} &middot; Staffing Accounting Management System</div>
  <table><thead><tr>{$thead}</tr></thead><tbody>{$tbody}</tbody></table>
</body></html>
HTML;

        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('letter', 'landscape');
        $dompdf->render();

        if (ob_get_length()) { ob_end_clean(); }
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '.pdf"');
        echo $dompdf->output();
        exit;
    }
}
