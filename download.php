<?php
/**
 * Graduands Excel Template Export
 */
require_once __DIR__ . '/db.php';

$pdo = get_db();
$stmt = $pdo->query("SELECT * FROM students WHERE is_selected = 1 ORDER BY CAST(sn AS INTEGER) ASC, id ASC");
$students = $stmt->fetchAll();

$templatePath = __DIR__ . '/template_graduands.xlsx';
if (!file_exists($templatePath)) {
    die("Template file not found.");
}

$tempFile = tempnam(sys_get_temp_dir(), 'grad');
copy($templatePath, $tempFile);

$zip = new ZipArchive();
if ($zip->open($tempFile) === true) {
    $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
           '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' .
           '<sheetViews><sheetView tabSelected="1" workbookViewId="0"/></sheetViews>' .
           '<cols><col min="1" max="1" width="8" customWidth="1"/><col min="2" max="2" width="22" customWidth="1"/><col min="3" max="3" width="35" customWidth="1"/><col min="4" max="4" width="15" customWidth="1"/></cols>' .
           '<sheetData>' .
           '<row r="1">' .
           '<c r="A1" t="inlineStr"><is><t>SN</t></is></c>' .
           '<c r="B1" t="inlineStr"><is><t>INDEXING</t></is></c>' .
           '<c r="C1" t="inlineStr"><is><t>FULLNAME</t></is></c>' .
           '<c r="D1" t="inlineStr"><is><t>REMARK</t></is></c>' .
           '</row>';

    $rowNum = 2;
    $snCounter = 1;
    foreach ($students as $stu) {
        $sn = htmlspecialchars((string)$snCounter++, ENT_XML1, 'UTF-8');
        $indexing = htmlspecialchars((string)($stu['indexing'] ?? ''), ENT_XML1, 'UTF-8');
        $fullname = htmlspecialchars((string)($stu['full_name'] ?? ''), ENT_XML1, 'UTF-8');
        $remark = htmlspecialchars(strtoupper((string)($stu['type'] ?? 'FRESH')), ENT_XML1, 'UTF-8');

        $xml .= '<row r="' . $rowNum . '">' .
                '<c r="A' . $rowNum . '" t="inlineStr"><is><t>' . $sn . '</t></is></c>' .
                '<c r="B' . $rowNum . '" t="inlineStr"><is><t>' . $indexing . '</t></is></c>' .
                '<c r="C' . $rowNum . '" t="inlineStr"><is><t>' . $fullname . '</t></is></c>' .
                '<c r="D' . $rowNum . '" t="inlineStr"><is><t>' . $remark . '</t></is></c>' .
                '</row>';
        $rowNum++;
    }

    $xml .= '</sheetData></worksheet>';
    $zip->addFromString('xl/worksheets/sheet1.xml', $xml);
    $zip->close();
}

if (php_sapi_name() !== 'cli') {
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="Selected_Graduands_' . date('Y_m_d_His') . '.xlsx"');
    header('Content-Length: ' . filesize($tempFile));
    header('Cache-Control: max-age=0');
    readfile($tempFile);
    @unlink($tempFile);
    exit;
} else {
    echo "Generated file at: $tempFile (Size: " . filesize($tempFile) . " bytes)\n";
}
