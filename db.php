<?php
/**
 * SQLite Database connection and automated Excel parser
 */

function get_db() {
    static $pdo = null;
    if ($pdo === null) {
        $dbPath = __DIR__ . '/students.sqlite';
        $pdo = new PDO('sqlite:' . $dbPath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        init_db($pdo);
    }
    return $pdo;
}

function init_db($pdo) {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS students (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            sn TEXT,
            full_name TEXT,
            indexing TEXT,
            cadre TEXT,
            year TEXT,
            type TEXT,
            gender TEXT,
            batch TEXT,
            is_selected INTEGER DEFAULT 0
        );
        CREATE INDEX IF NOT EXISTS idx_indexing ON students(indexing);
        CREATE INDEX IF NOT EXISTS idx_selected ON students(is_selected);
        CREATE INDEX IF NOT EXISTS idx_cadre ON students(cadre);
        CREATE INDEX IF NOT EXISTS idx_gender ON students(gender);

        CREATE TABLE IF NOT EXISTS graduand (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            exam_number TEXT UNIQUE,
            full_name TEXT,
            cadre TEXT,
            papers TEXT,
            type TEXT,
            photo TEXT DEFAULT '',
            dob TEXT DEFAULT '',
            blood_group TEXT DEFAULT '',
            updated_at DATETIME NULL
        );
        CREATE INDEX IF NOT EXISTS idx_graduand_exam ON graduand(exam_number);
        CREATE INDEX IF NOT EXISTS idx_graduand_name ON graduand(full_name);
        CREATE INDEX IF NOT EXISTS idx_graduand_type ON graduand(type);
    ");

    // Ensure all columns exist if table was created previously without them
    $cols = $pdo->query("PRAGMA table_info(graduand)")->fetchAll(PDO::FETCH_COLUMN, 1);
    $requiredCols = [
        'photo' => "TEXT DEFAULT ''",
        'dob' => "TEXT DEFAULT ''",
        'blood_group' => "TEXT DEFAULT ''",
        'updated_at' => "DATETIME NULL"
    ];
    foreach ($requiredCols as $colName => $colDef) {
        if (!in_array($colName, $cols)) {
            $pdo->exec("ALTER TABLE graduand ADD COLUMN {$colName} {$colDef}");
        }
    }

    // Migrate from plural graduands table if graduand is empty and graduands exists
    $countGraduand = (int)$pdo->query("SELECT COUNT(*) FROM graduand")->fetchColumn();
    if ($countGraduand === 0) {
        $hasGraduands = (int)$pdo->query("SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name='graduands'")->fetchColumn();
        if ($hasGraduands > 0) {
            $pdo->exec("INSERT OR IGNORE INTO graduand (exam_number, full_name, cadre, papers, type) SELECT exam_number, full_name, cadre, papers, type FROM graduands");
            $countGraduand = (int)$pdo->query("SELECT COUNT(*) FROM graduand")->fetchColumn();
        }
    }

    if ($countGraduand === 0) {
        import_graduand_excel($pdo, __DIR__ . '/graduand.xlsx');
    }

    $count = (int)$pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
    if ($count === 0) {
        import_excel_data($pdo, __DIR__ . '/2024 indexed.xlsx');
    }
}

function col_letter_to_index($ref) {
    if (!preg_match('/^[A-Z]+/', $ref, $matches)) {
        return 0;
    }
    $letters = $matches[0];
    $len = strlen($letters);
    $idx = 0;
    for ($i = 0; $i < $len; $i++) {
        $idx = $idx * 26 + (ord($letters[$i]) - ord('A') + 1);
    }
    return $idx - 1;
}

function import_excel_data($pdo, $filePath) {
    if (!file_exists($filePath)) {
        return;
    }

    $zip = new ZipArchive();
    if ($zip->open($filePath) !== true) {
        return;
    }

    // Parse Shared Strings
    $sharedStrings = [];
    if (($content = $zip->getFromName('xl/sharedStrings.xml')) !== false) {
        $xml = simplexml_load_string($content);
        foreach ($xml->si as $si) {
            $text = '';
            if (isset($si->t)) {
                $text .= (string)$si->t;
            }
            if (isset($si->r)) {
                foreach ($si->r as $r) {
                    $text .= (string)$r->t;
                }
            }
            $sharedStrings[] = $text;
        }
    }

    // Parse Sheet 1
    $rows = [];
    if (($content = $zip->getFromName('xl/worksheets/sheet1.xml')) !== false) {
        $xml = simplexml_load_string($content);
        if (isset($xml->sheetData) && isset($xml->sheetData->row)) {
            foreach ($xml->sheetData->row as $rowXml) {
                $rowData = [];
                foreach ($rowXml->c as $c) {
                    $ref = (string)$c['r'];
                    $colIdx = col_letter_to_index($ref);
                    $type = (string)$c['t'];
                    $val = '';
                    if (isset($c->v)) {
                        $val = (string)$c->v;
                        if ($type === 's' && is_numeric($val) && isset($sharedStrings[(int)$val])) {
                            $val = $sharedStrings[(int)$val];
                        }
                    }
                    $rowData[$colIdx] = trim($val);
                }
                
                // Ensure 8 columns
                for ($i = 0; $i < 8; $i++) {
                    if (!isset($rowData[$i])) {
                        $rowData[$i] = '';
                    }
                }
                ksort($rowData);
                $rows[] = $rowData;
            }
        }
    }
    $zip->close();

    // Skip header (row 0) and insert records
    if (count($rows) > 1) {
        $stmt = $pdo->prepare("
            INSERT INTO students (sn, full_name, indexing, cadre, year, type, gender, batch, is_selected)
            VALUES (:sn, :full_name, :indexing, :cadre, :year, :type, :gender, :batch, 0)
        ");

        $pdo->beginTransaction();
        for ($r = 1; $r < count($rows); $r++) {
            $row = $rows[$r];
            if (empty($row[1]) && empty($row[2])) {
                continue; // skip empty rows
            }
            $stmt->execute([
                ':sn' => $row[0],
                ':full_name' => $row[1],
                ':indexing' => $row[2],
                ':cadre' => $row[3],
                ':year' => $row[4],
                ':type' => $row[5],
                ':gender' => $row[6],
                ':batch' => $row[7],
            ]);
        }
        $pdo->commit();
    }
}

function import_graduand_excel($pdo, $filePath) {
    if (!file_exists($filePath)) {
        return 0;
    }

    $zip = new ZipArchive();
    if ($zip->open($filePath) !== true) {
        return 0;
    }

    // Parse Shared Strings
    $sharedStrings = [];
    if (($content = $zip->getFromName('xl/sharedStrings.xml')) !== false) {
        $xml = simplexml_load_string($content);
        if (isset($xml->si)) {
            foreach ($xml->si as $si) {
                $text = '';
                if (isset($si->t)) {
                    $text .= (string)$si->t;
                }
                if (isset($si->r)) {
                    foreach ($si->r as $r) {
                        $text .= (string)$r->t;
                    }
                }
                $sharedStrings[] = $text;
            }
        }
    }

    // Parse Sheet 1
    $rows = [];
    if (($content = $zip->getFromName('xl/worksheets/sheet1.xml')) !== false) {
        $xml = simplexml_load_string($content);
        if (isset($xml->sheetData) && isset($xml->sheetData->row)) {
            foreach ($xml->sheetData->row as $rowXml) {
                $rowData = [];
                foreach ($rowXml->c as $c) {
                    $ref = (string)$c['r'];
                    $colIdx = col_letter_to_index($ref);
                    $type = (string)$c['t'];
                    $val = '';
                    if (isset($c->v)) {
                        $val = (string)$c->v;
                        if ($type === 's' && is_numeric($val) && isset($sharedStrings[(int)$val])) {
                            $val = $sharedStrings[(int)$val];
                        }
                    }
                    $rowData[$colIdx] = trim($val);
                }
                // Ensure 5 columns (Exam Number, Full Name, Cadre, Papers, Type)
                for ($i = 0; $i < 5; $i++) {
                    if (!isset($rowData[$i])) {
                        $rowData[$i] = '';
                    }
                }
                ksort($rowData);
                $rows[] = $rowData;
            }
        }
    }
    $zip->close();

    $importedCount = 0;
    if (count($rows) > 1) {
        $stmt = $pdo->prepare("
            INSERT INTO graduand (exam_number, full_name, cadre, papers, type)
            VALUES (:exam_number, :full_name, :cadre, :papers, :type)
            ON CONFLICT(exam_number) DO UPDATE SET
                full_name = excluded.full_name,
                cadre = excluded.cadre,
                papers = excluded.papers,
                type = excluded.type
        ");

        $pdo->beginTransaction();
        for ($r = 1; $r < count($rows); $r++) {
            $row = $rows[$r];
            if (empty($row[0])) {
                continue; // skip if no exam number
            }
            $stmt->execute([
                ':exam_number' => $row[0],
                ':full_name' => $row[1],
                ':cadre' => $row[2],
                ':papers' => $row[3],
                ':type' => $row[4],
            ]);
            $importedCount++;
        }
        $pdo->commit();
    }
    return $importedCount;
}

