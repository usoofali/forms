<?php
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$pdo = get_db();

try {
    switch ($action) {
        case 'get_graduands':
            $search = trim($_GET['search'] ?? '');
            $cadre = trim($_GET['cadre'] ?? '');
            $status = trim($_GET['status'] ?? 'all');
            $typeFilter = trim($_GET['type_filter'] ?? 'all');

            $sql = "SELECT * FROM graduand WHERE 1=1";
            $params = [];

            if ($search !== '') {
                $sql .= " AND (full_name LIKE :search OR exam_number LIKE :search)";
                $params[':search'] = '%' . $search . '%';
            }
            if ($cadre !== '') {
                $sql .= " AND cadre = :cadre";
                $params[':cadre'] = $cadre;
            }
            if ($typeFilter === 'fresh') {
                $sql .= " AND LOWER(type) = 'fresh'";
            } elseif ($typeFilter === 'resit') {
                $sql .= " AND LOWER(type) = 'resit'";
            }

            if ($status === 'completed') {
                $sql .= " AND photo != '' AND photo IS NOT NULL AND dob != '' AND dob IS NOT NULL";
            } elseif ($status === 'pending') {
                $sql .= " AND (photo = '' OR photo IS NULL OR dob = '' OR dob IS NULL)";
            }

            // Prioritize fresh candidates first, then by exam number
            $sql .= " ORDER BY CASE WHEN LOWER(type) = 'fresh' THEN 0 ELSE 1 END, exam_number ASC";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $graduands = $stmt->fetchAll();

            // Compute statistics specifically focusing on Fresh candidates
            $totalFresh = (int)$pdo->query("SELECT COUNT(*) FROM graduand WHERE LOWER(type) = 'fresh'")->fetchColumn();
            $completedFresh = (int)$pdo->query("SELECT COUNT(*) FROM graduand WHERE LOWER(type) = 'fresh' AND photo != '' AND photo IS NOT NULL AND dob != '' AND dob IS NOT NULL")->fetchColumn();
            $pendingFresh = max(0, $totalFresh - $completedFresh);
            $totalResit = (int)$pdo->query("SELECT COUNT(*) FROM graduand WHERE LOWER(type) = 'resit'")->fetchColumn();

            // Distinct cadres
            $cadres = $pdo->query("SELECT DISTINCT cadre FROM graduand WHERE cadre != '' ORDER BY cadre")->fetchAll(PDO::FETCH_COLUMN);

            echo json_encode([
                'success' => true,
                'graduands' => $graduands,
                'stats' => [
                    'total_fresh' => $totalFresh,
                    'completed_fresh' => $completedFresh,
                    'pending_fresh' => $pendingFresh,
                    'total_resit' => $totalResit
                ],
                'filters' => [
                    'cadres' => $cadres
                ]
            ]);
            break;
        case 'export_csv':
            $search = trim($_GET['search'] ?? '');
            $cadre = trim($_GET['cadre'] ?? '');
            $status = trim($_GET['status'] ?? 'all');
            $typeFilter = trim($_GET['type_filter'] ?? 'all');

            $sql = "SELECT * FROM graduand WHERE 1=1";
            $params = [];

            if ($search !== '') {
                $sql .= " AND (full_name LIKE :search OR exam_number LIKE :search)";
                $params[':search'] = '%' . $search . '%';
            }
            if ($cadre !== '') {
                $sql .= " AND cadre = :cadre";
                $params[':cadre'] = $cadre;
            }
            if ($typeFilter === 'fresh') {
                $sql .= " AND LOWER(type) = 'fresh'";
            } elseif ($typeFilter === 'resit') {
                $sql .= " AND LOWER(type) = 'resit'";
            }

            if ($status === 'completed') {
                $sql .= " AND photo != '' AND photo IS NOT NULL AND dob != '' AND dob IS NOT NULL";
            } elseif ($status === 'pending') {
                $sql .= " AND (photo = '' OR photo IS NULL OR dob = '' OR dob IS NULL)";
            }

            $sql .= " ORDER BY CASE WHEN LOWER(type) = 'fresh' THEN 0 ELSE 1 END, exam_number ASC";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $graduands = $stmt->fetchAll();

            if (ob_get_level()) {
                ob_end_clean();
            }

            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="graduand_records_' . date('Y-m-d_H-i') . '.csv"');
            header('Pragma: no-cache');
            header('Expires: 0');

            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");

            fputcsv($output, [
                'Exam / Index Number',
                'Full Name',
                'Cadre / Department',
                'Papers / Subjects',
                'Candidate Type',
                'Date of Birth',
                'Blood Group',
                'Photo Path',
                'Submission Status'
            ]);

            foreach ($graduands as $row) {
                $hasCompleted = !empty($row['photo']) && !empty($row['dob']);
                $subStatus = strtolower(trim($row['type'])) !== 'fresh' ? 'Exempted (Resit)' : ($hasCompleted ? 'Completed' : 'Pending');

                fputcsv($output, [
                    $row['exam_number'] ?? '',
                    $row['full_name'] ?? '',
                    $row['cadre'] ?? '',
                    $row['papers'] ?? '',
                    $row['type'] ?? '',
                    $row['dob'] ?? '',
                    $row['blood_group'] ?? '',
                    $row['photo'] ?? '',
                    $subStatus
                ]);
            }
            fclose($output);
            exit;

        case 'get_single':
            $id = (int)($_GET['id'] ?? 0);
            $stmt = $pdo->prepare("SELECT * FROM graduand WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $student = $stmt->fetch();

            if ($student) {
                echo json_encode(['success' => true, 'student' => $student]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Student record not found']);
            }
            break;

        case 'update_profile':
            $id = (int)($_POST['id'] ?? 0);
            $dob = trim($_POST['dob'] ?? '');
            $bloodGroup = trim($_POST['blood_group'] ?? '');
            $photoData = $_POST['photo_data'] ?? '';

            $stmt = $pdo->prepare("SELECT * FROM graduand WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $student = $stmt->fetch();

            if (!$student) {
                echo json_encode(['success' => false, 'error' => 'Student record not found']);
                exit;
            }

            // Enforce Fresh eligibility restriction
            if (strtolower(trim($student['type'])) !== 'fresh') {
                echo json_encode([
                    'success' => false, 
                    'error' => 'Only Fresh graduand candidates are eligible/required to update passport and biodata records.'
                ]);
                exit;
            }

            $photoPath = $student['photo'];

            // Process new cropped photo if submitted
            if (!empty($photoData) && strpos($photoData, 'data:image') === 0) {
                // Delete old photo if exists
                if (!empty($student['photo'])) {
                    $oldPath = __DIR__ . '/' . $student['photo'];
                    if (file_exists($oldPath) && is_file($oldPath)) {
                        @unlink($oldPath);
                    }
                }

                // Decode base64
                $parts = explode(';', $photoData);
                if (count($parts) > 1) {
                    $dataParts = explode(',', $parts[1]);
                    if (count($dataParts) > 1) {
                        $rawImage = base64_decode($dataParts[1]);
                        if ($rawImage !== false) {
                            $assetsDir = __DIR__ . '/assets';
                            if (!is_dir($assetsDir)) {
                                mkdir($assetsDir, 0777, true);
                            }

                            // Sanitize exam number to dot notation (e.g. B/130/012/22 -> B.130.012.22.jpg)
                            $cleanExam = str_replace(['/', '\\', ' '], '.', $student['exam_number']);
                            $cleanExam = preg_replace('/[^A-Za-z0-9\.-]/', '', $cleanExam);
                            $cleanExam = trim(preg_replace('/\.\.+/', '.', $cleanExam), '.');
                            
                            if (empty($cleanExam)) {
                                $cleanExam = 'student_' . $id;
                            }

                            $relPath = 'assets/' . $cleanExam . '.jpg';
                            $absPath = __DIR__ . '/' . $relPath;

                            if (file_put_contents($absPath, $rawImage) !== false) {
                                $photoPath = $relPath;
                            } else {
                                echo json_encode(['success' => false, 'error' => 'Failed to save uploaded image file to assets/']);
                                exit;
                            }
                        }
                    }
                }
            }

            // Update database record
            $upStmt = $pdo->prepare("
                UPDATE graduand 
                SET dob = :dob, 
                    blood_group = :blood_group, 
                    photo = :photo, 
                    updated_at = datetime('now', 'localtime') 
                WHERE id = :id
            ");
            $upStmt->execute([
                ':dob' => $dob,
                ':blood_group' => $bloodGroup,
                ':photo' => $photoPath,
                ':id' => $id
            ]);

            // Fetch updated student
            $stmt->execute([':id' => $id]);
            $updated = $stmt->fetch();

            echo json_encode([
                'success' => true, 
                'message' => 'Record successfully updated.',
                'student' => $updated
            ]);
            break;

        default:
            echo json_encode(['success' => false, 'error' => 'Unknown action']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
