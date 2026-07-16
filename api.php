<?php
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$pdo = get_db();

try {
    switch ($action) {
        case 'get_students':
            $search = trim($_GET['search'] ?? '');
            $cadre = trim($_GET['cadre'] ?? '');
            $gender = trim($_GET['gender'] ?? '');
            $status = trim($_GET['status'] ?? 'all');

            $sql = "SELECT * FROM students WHERE 1=1";
            $params = [];

            if ($search !== '') {
                $sql .= " AND (full_name LIKE :search OR indexing LIKE :search)";
                $params[':search'] = '%' . $search . '%';
            }
            if ($cadre !== '') {
                $sql .= " AND cadre = :cadre";
                $params[':cadre'] = $cadre;
            }
            if ($gender !== '') {
                $sql .= " AND gender = :gender";
                $params[':gender'] = $gender;
            }
            if ($status === 'selected') {
                $sql .= " AND is_selected = 1";
            } elseif ($status === 'unselected') {
                $sql .= " AND is_selected = 0";
            }

            $sql .= " ORDER BY CAST(sn AS INTEGER) ASC, id ASC";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $students = $stmt->fetchAll();

            // Stats
            $stats = [
                'total' => (int)$pdo->query("SELECT COUNT(*) FROM students")->fetchColumn(),
                'selected' => (int)$pdo->query("SELECT COUNT(*) FROM students WHERE is_selected = 1")->fetchColumn(),
                'fresh' => (int)$pdo->query("SELECT COUNT(*) FROM students WHERE is_selected = 1 AND LOWER(type) = 'fresh'")->fetchColumn(),
                'resit' => (int)$pdo->query("SELECT COUNT(*) FROM students WHERE is_selected = 1 AND LOWER(type) = 'resit'")->fetchColumn(),
            ];

            // Distinct dropdown options
            $cadres = $pdo->query("SELECT DISTINCT cadre FROM students WHERE cadre != '' ORDER BY cadre")->fetchAll(PDO::FETCH_COLUMN);
            $genders = $pdo->query("SELECT DISTINCT gender FROM students WHERE gender != '' ORDER BY gender")->fetchAll(PDO::FETCH_COLUMN);

            echo json_encode([
                'success' => true,
                'students' => $students,
                'stats' => $stats,
                'filters' => [
                    'cadres' => $cadres,
                    'genders' => $genders
                ]
            ]);
            break;

        case 'toggle_select':
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 0) {
                $stmt = $pdo->prepare("UPDATE students SET is_selected = CASE WHEN is_selected = 1 THEN 0 ELSE 1 END WHERE id = :id");
                $stmt->execute([':id' => $id]);
                
                $newStatus = (int)$pdo->query("SELECT is_selected FROM students WHERE id = $id")->fetchColumn();
                echo json_encode(['success' => true, 'is_selected' => $newStatus]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Invalid student ID']);
            }
            break;

        case 'bulk_select':
            $select = (int)($_POST['select'] ?? 1);
            $ids = $_POST['ids'] ?? [];
            if (!is_array($ids)) {
                $ids = explode(',', $ids);
            }
            $ids = array_filter(array_map('intval', $ids));

            if (!empty($ids)) {
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $stmt = $pdo->prepare("UPDATE students SET is_selected = ? WHERE id IN ($placeholders)");
                $stmt->execute(array_merge([$select], array_values($ids)));
                echo json_encode(['success' => true, 'affected' => $stmt->rowCount()]);
            } else {
                echo json_encode(['success' => false, 'error' => 'No IDs provided']);
            }
            break;

        case 'reset_all':
            $pdo->exec("UPDATE students SET is_selected = 0");
            echo json_encode(['success' => true]);
            break;

        case 'upload_graduand_excel':
            if (isset($_FILES['excel_file']) && $_FILES['excel_file']['error'] === UPLOAD_ERR_OK) {
                $tmpName = $_FILES['excel_file']['tmp_name'];
                $count = import_graduand_excel($pdo, $tmpName);
                echo json_encode(['success' => true, 'count' => $count]);
            } else {
                echo json_encode(['success' => false, 'error' => 'No valid Excel file uploaded.']);
            }
            break;

        default:
            echo json_encode(['success' => false, 'error' => 'Unknown action']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
