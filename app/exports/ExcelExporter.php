<?php

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class ExcelExporter
{
    /**
     * Stream a multi-worksheet .xlsx download.
     * $sheets: list of ['title' => string, 'blocks' => list of blocks]
     * Block types:
     *   ['type'=>'heading','text'=>string]
     *   ['type'=>'kv','rows'=>[[label, value], ...]]                (currency-formats float values)
     *   ['type'=>'table','headers'=>[],'rows'=>[[]],'totals'=>?[], 'currencyCols'=>[0-based]]
     */
    public static function workbook(string $filename, array $sheets): never
    {
        $ss = new Spreadsheet();
        $money = '$#,##0.00;-$#,##0.00';
        $navy = ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0E2136']]];
        $first = true;
        foreach ($sheets as $def) {
            $sheet = $first ? $ss->getActiveSheet() : $ss->createSheet();
            $first = false;
            $sheet->setTitle(substr($def['title'], 0, 31));
            $r = 1;
            $maxCols = 1;
            foreach ($def['blocks'] as $b) {
                switch ($b['type']) {
                    case 'heading':
                        $sheet->setCellValue("A{$r}", $b['text']);
                        $sheet->getStyle("A{$r}")->applyFromArray(['font' => ['bold' => true, 'size' => 12]]);
                        $r += 1;
                        break;
                    case 'kv':
                        foreach ($b['rows'] as $row) {
                            $sheet->fromArray($row, null, "A{$r}");
                            $sheet->getStyle("A{$r}")->applyFromArray(['font' => ['bold' => true]]);
                            if (isset($row[1]) && is_float($row[1])) {
                                $sheet->getStyle("B{$r}")->getNumberFormat()->setFormatCode($money);
                            }
                            $r++;
                        }
                        $maxCols = max($maxCols, 2);
                        $r++;
                        break;
                    case 'table':
                        $n = max(1, count($b['headers']));
                        $maxCols = max($maxCols, $n);
                        $lastCol = Coordinate::stringFromColumnIndex($n);
                        $sheet->fromArray($b['headers'], null, "A{$r}");
                        $sheet->getStyle("A{$r}:{$lastCol}{$r}")->applyFromArray($navy);
                        $r++;
                        $dataStart = $r;
                        foreach ($b['rows'] as $row) {
                            $sheet->fromArray(array_values($row), null, "A{$r}");
                            $r++;
                        }
                        if (!empty($b['totals'])) {
                            $sheet->fromArray(array_values($b['totals']), null, "A{$r}");
                            $sheet->getStyle("A{$r}:{$lastCol}{$r}")->applyFromArray([
                                'font' => ['bold' => true],
                                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E3F4FD']],
                                'borders' => ['top' => ['borderStyle' => Border::BORDER_MEDIUM]],
                            ]);
                            $r++;
                        }
                        foreach ($b['currencyCols'] ?? [] as $ci) {
                            $col = Coordinate::stringFromColumnIndex($ci + 1);
                            $sheet->getStyle("{$col}{$dataStart}:{$col}" . ($r - 1))
                                ->getNumberFormat()->setFormatCode($money);
                        }
                        $r++;
                        break;
                }
            }
            foreach (range(1, $maxCols) as $ci) {
                $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($ci))->setAutoSize(true);
            }
        }
        $ss->setActiveSheetIndex(0);
        if (ob_get_length()) { ob_end_clean(); }
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '.xlsx"');
        header('Cache-Control: max-age=0');
        (new Xlsx($ss))->save('php://output');
        exit;
    }

    /**
     * Stream an .xlsx download.
     * $headers: ['Col label', ...]
     * $rows:    [[v1, v2, ...], ...]
     * $totals:  optional row appended bold, e.g. ['TOTAL', '', 1234.56]
     * $currencyCols: 0-based column indexes to format as currency.
     */
    public static function download(string $filename, array $headers, array $rows, ?array $totals = null, array $currencyCols = []): never
    {
        $ss = new Spreadsheet();
        $sheet = $ss->getActiveSheet();

        $sheet->fromArray($headers, null, 'A1');
        $lastCol = Coordinate::stringFromColumnIndex(max(1, count($headers)));
        $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0E2136']],
        ]);

        $r = 2;
        foreach ($rows as $row) {
            $sheet->fromArray(array_values($row), null, 'A' . $r);
            $r++;
        }
        if ($totals !== null) {
            $sheet->fromArray(array_values($totals), null, 'A' . $r);
            $sheet->getStyle("A{$r}:{$lastCol}{$r}")->applyFromArray([
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E3F4FD']],
                'borders' => ['top' => ['borderStyle' => Border::BORDER_MEDIUM]],
            ]);
        }

        foreach ($currencyCols as $ci) {
            $col = Coordinate::stringFromColumnIndex($ci + 1);
            $sheet->getStyle("{$col}2:{$col}{$r}")
                ->getNumberFormat()->setFormatCode('$#,##0.00;-$#,##0.00');
        }
        foreach (range(1, count($headers)) as $ci) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($ci))->setAutoSize(true);
        }

        if (ob_get_length()) { ob_end_clean(); }
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '.xlsx"');
        header('Cache-Control: max-age=0');
        (new Xlsx($ss))->save('php://output');
        exit;
    }
}
