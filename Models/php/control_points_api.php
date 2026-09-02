<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

function sendJsonResponse($data) {
    header('Content-Type: application/json');
    echo json_encode($data, JSON_UNESCAPED_SLASHES);
    exit;
}

const CP_TYPES    = ['Control', 'Benchmark', 'Boundary Corner', 'GPS Base', 'Other'];
const CP_STATUSES = ['Proposed', 'Set', 'Verified', 'Destroyed', 'Lost'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['action'])) {
    sendJsonResponse([
        'success' => false,
        'message' => 'Invalid request method or missing action parameter'
    ]);
}

// Resolve the logged-in user; edits are stamped with their name
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$currentUser = trim($_SESSION['username'] ?? '');
session_write_close();

if ($currentUser === '') {
    sendJsonResponse(['success' => false, 'message' => 'Not logged in']);
}

try {
    require_once '../../Private/db_config.php';
    $db = new Database();
    $pdo = $db->getConnection();

    $action = $_POST['action'];

    // ── get_points ───────────────────────────────────────────────────────────
    if ($action === 'get_points') {

        $projectId = trim($_POST['project_id'] ?? '');
        $taskId    = (int)($_POST['task_id'] ?? 0);

        $sql = "SELECT cp.*,
                       p.project_name,
                       t.task_name,
                       s.collection_date AS session_date,
                       s.field_crew AS session_field_crew
                FROM control_points cp
                LEFT JOIN survey_projects p ON p.project_id = cp.project_id
                LEFT JOIN tasks t ON t.task_id = cp.task_id
                LEFT JOIN field_data_qc_sessions s ON s.session_id = cp.source_session_id
                WHERE 1=1";
        $params = [];
        if ($projectId !== '') {
            $sql .= " AND cp.project_id = :project_id";
            $params[':project_id'] = $projectId;
        }
        if ($taskId) {
            $sql .= " AND cp.task_id = :task_id";
            $params[':task_id'] = $taskId;
        }
        $sql .= " ORDER BY cp.project_id, (cp.point_number + 0), cp.point_number";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        sendJsonResponse([
            'success'  => true,
            'points'   => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'types'    => CP_TYPES,
            'statuses' => CP_STATUSES,
        ]);
    }

    // ── add_point / update_point ────────────────────────────────────────────
    elseif ($action === 'add_point' || $action === 'update_point') {

        $projectId = trim($_POST['project_id'] ?? '');
        if ($projectId === '') {
            sendJsonResponse(['success' => false, 'message' => 'project_id is required']);
        }

        $pointNumber = trim($_POST['point_number'] ?? '');
        if ($pointNumber === '' || mb_strlen($pointNumber) > 50) {
            sendJsonResponse(['success' => false, 'message' => 'point_number is required (max 50 characters)']);
        }

        $pointType = trim($_POST['point_type'] ?? 'Control');
        if (!in_array($pointType, CP_TYPES, true)) {
            sendJsonResponse(['success' => false, 'message' => 'Invalid point_type']);
        }

        $status = trim($_POST['status'] ?? 'Set');
        if (!in_array($status, CP_STATUSES, true)) {
            sendJsonResponse(['success' => false, 'message' => 'Invalid status']);
        }

        $dateEstablished = trim($_POST['date_established'] ?? '');
        if ($dateEstablished !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateEstablished)) {
            sendJsonResponse(['success' => false, 'message' => 'date_established must be YYYY-MM-DD']);
        }

        // Numeric fields are optional but must be numeric if supplied
        $numericFields = ['northing', 'easting', 'elevation', 'latitude', 'longitude'];
        $numeric = [];
        foreach ($numericFields as $field) {
            $raw = trim($_POST[$field] ?? '');
            if ($raw !== '' && !is_numeric($raw)) {
                sendJsonResponse(['success' => false, 'message' => "$field must be numeric"]);
            }
            $numeric[$field] = $raw !== '' ? $raw : null;
        }

        $taskId = (int)($_POST['task_id'] ?? 0) ?: null;
        $sourceSessionId = (int)($_POST['source_session_id'] ?? 0) ?: null;

        $fields = [
            ':project_id'         => $projectId,
            ':task_id'            => $taskId,
            ':source_session_id'  => $sourceSessionId,
            ':point_number'       => $pointNumber,
            ':point_name'         => trim($_POST['point_name'] ?? '') ?: null,
            ':point_type'         => $pointType,
            ':status'             => $status,
            ':northing'           => $numeric['northing'],
            ':easting'            => $numeric['easting'],
            ':elevation'          => $numeric['elevation'],
            ':latitude'           => $numeric['latitude'],
            ':longitude'          => $numeric['longitude'],
            ':coordinate_system'  => trim($_POST['coordinate_system'] ?? '') ?: null,
            ':datum_epoch'        => trim($_POST['datum_epoch'] ?? '') ?: null,
            ':geoid_model'        => trim($_POST['geoid_model'] ?? '') ?: null,
            ':vertical_datum'     => trim($_POST['vertical_datum'] ?? '') ?: null,
            ':units'              => trim($_POST['units'] ?? '') ?: null,
            ':monument_type'      => trim($_POST['monument_type'] ?? '') ?: null,
            ':order_class'        => trim($_POST['order_class'] ?? '') ?: null,
            ':date_established'   => $dateEstablished ?: null,
            ':established_by'     => trim($_POST['established_by'] ?? '') ?: null,
            ':description'        => trim($_POST['description'] ?? '') ?: null,
            ':photo_link'         => trim($_POST['photo_link'] ?? '') ?: null,
            ':notes'              => trim($_POST['notes'] ?? '') ?: null,
        ];

        if ($action === 'add_point') {
            $stmt = $pdo->prepare(
                "INSERT INTO control_points
                    (project_id, task_id, source_session_id, point_number, point_name, point_type, status,
                     northing, easting, elevation, latitude, longitude,
                     coordinate_system, datum_epoch, geoid_model, vertical_datum, units,
                     monument_type, order_class, date_established, established_by,
                     description, photo_link, notes, created_by)
                 VALUES
                    (:project_id, :task_id, :source_session_id, :point_number, :point_name, :point_type, :status,
                     :northing, :easting, :elevation, :latitude, :longitude,
                     :coordinate_system, :datum_epoch, :geoid_model, :vertical_datum, :units,
                     :monument_type, :order_class, :date_established, :established_by,
                     :description, :photo_link, :notes, :created_by)"
            );
            $stmt->execute($fields + [':created_by' => $currentUser]);
            sendJsonResponse(['success' => true, 'control_point_id' => (int)$pdo->lastInsertId()]);
        } else {
            $controlPointId = (int)($_POST['control_point_id'] ?? 0);
            if (!$controlPointId) {
                sendJsonResponse(['success' => false, 'message' => 'control_point_id is required']);
            }
            $stmt = $pdo->prepare(
                "UPDATE control_points SET
                    project_id = :project_id, task_id = :task_id, source_session_id = :source_session_id,
                    point_number = :point_number, point_name = :point_name, point_type = :point_type, status = :status,
                    northing = :northing, easting = :easting, elevation = :elevation,
                    latitude = :latitude, longitude = :longitude,
                    coordinate_system = :coordinate_system, datum_epoch = :datum_epoch, geoid_model = :geoid_model,
                    vertical_datum = :vertical_datum, units = :units,
                    monument_type = :monument_type, order_class = :order_class,
                    date_established = :date_established, established_by = :established_by,
                    description = :description, photo_link = :photo_link, notes = :notes,
                    modified_by = :modified_by
                 WHERE control_point_id = :control_point_id"
            );
            $stmt->execute($fields + [':modified_by' => $currentUser, ':control_point_id' => $controlPointId]);
            sendJsonResponse(['success' => true]);
        }
    }

    // ── delete_point ─────────────────────────────────────────────────────────
    elseif ($action === 'delete_point') {

        $controlPointId = (int)($_POST['control_point_id'] ?? 0);
        if (!$controlPointId) {
            sendJsonResponse(['success' => false, 'message' => 'control_point_id is required']);
        }
        $stmt = $pdo->prepare("DELETE FROM control_points WHERE control_point_id = :control_point_id");
        $stmt->execute([':control_point_id' => $controlPointId]);
        sendJsonResponse(['success' => true]);
    }

    // ── bulk_import ──────────────────────────────────────────────────────────
    elseif ($action === 'bulk_import') {

        $projectId = trim($_POST['project_id'] ?? '');
        $pointsJson = $_POST['points_json'] ?? '[]';
        $points = json_decode($pointsJson, true);

        if ($projectId === '') {
            sendJsonResponse(['success' => false, 'message' => 'project_id is required']);
        }

        if (!is_array($points) || empty($points)) {
            sendJsonResponse(['success' => false, 'message' => 'No points to import']);
        }

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare(
                "INSERT INTO control_points
                    (project_id, point_number, point_name, point_type, status,
                     northing, easting, elevation, latitude, longitude,
                     coordinate_system, datum_epoch, units, created_by)
                 VALUES
                    (:project_id, :point_number, :point_name, :point_type, :status,
                     :northing, :easting, :elevation, :latitude, :longitude,
                     :coordinate_system, :datum_epoch, :units, :created_by)"
            );

            $importedCount = 0;
            $skipped = [];

            foreach ($points as $idx => $p) {
                try {
                    $stmt->execute([
                        ':project_id' => $p['project_id'] ?? $projectId,
                        ':point_number' => $p['point_number'] ?? '',
                        ':point_name' => $p['point_name'] ?? null,
                        ':point_type' => $p['point_type'] ?? 'Control',
                        ':status' => $p['status'] ?? 'Set',
                        ':northing' => isset($p['northing']) ? (float)$p['northing'] : null,
                        ':easting' => isset($p['easting']) ? (float)$p['easting'] : null,
                        ':elevation' => isset($p['elevation']) ? (float)$p['elevation'] : null,
                        ':latitude' => isset($p['latitude']) ? (float)$p['latitude'] : null,
                        ':longitude' => isset($p['longitude']) ? (float)$p['longitude'] : null,
                        ':coordinate_system' => $p['coordinate_system'] ?? null,
                        ':datum_epoch' => $p['datum_epoch'] ?? null,
                        ':units' => $p['units'] ?? null,
                        ':created_by' => $currentUser
                    ]);
                    $importedCount++;
                } catch (Exception $e) {
                    $skipped[] = [
                        'row' => $idx + 1,
                        'point_number' => $p['point_number'] ?? 'unknown',
                        'error' => $e->getMessage()
                    ];
                }
            }

            $pdo->commit();

            sendJsonResponse([
                'success' => true,
                'imported_count' => $importedCount,
                'skipped' => $skipped
            ]);

        } catch (Exception $e) {
            $pdo->rollBack();
            error_log('bulk_import error: ' . $e->getMessage());
            sendJsonResponse(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
    }

    else {
        sendJsonResponse(['success' => false, 'message' => 'Invalid action parameter']);
    }

} catch (Exception $e) {
    error_log('control_points_api error: ' . $e->getMessage());
    sendJsonResponse(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
