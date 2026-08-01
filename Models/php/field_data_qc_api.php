<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

function sendJsonResponse($data) {
    header('Content-Type: application/json');
    echo json_encode($data, JSON_UNESCAPED_SLASHES);
    exit;
}

// Canonical QC workflow stages — order matters, mirrors the field-to-finish process.
// Adding a stage later only requires editing this array (stages live in a JSON column).
const QC_STAGES = [
    'downloaded_archived' => 'Data downloaded & archived',
    'imported_tbc'        => 'Imported to TBC',
    'raw_inspected'       => 'Raw data inspected (control checks / tolerances)',
    'errors_corrected'    => 'Errors flagged & corrected',
    'points_exported'     => 'Points exported (PNEZD CSV)',
    'final_signoff'       => 'Final review / sign-off',
];

const QC_CATEGORIES = [
    'Duplicate Point', 'Busted Point Number', 'Bad Rod Height/HI',
    'Wrong Code/Description', 'Out of Tolerance', 'Localization/Calibration Issue',
    'Missing Shot', 'Elevation Bust', 'Other',
];

const QC_SEVERITIES = ['Info', 'Minor', 'Major', 'Critical'];
const QC_STATUSES   = ['Open', 'Resolved', 'Waived'];

// Field crews live in a flat JSON file so they can be added/removed without a migration.
// Job file names are composed as [yyyymmdd][crew initials], e.g. 20260731JM.
// The .php extension plus the exit-guard first line keep the file's contents
// unreadable over HTTP (Apache here ignores .htaccess: AllowOverride None).
define('QC_CREWS_FILE', __DIR__ . '/../../Private/crews.json.php');
define('QC_CREWS_GUARD', "<?php exit; // data file — the guard makes direct web requests return nothing ?>\n");

function qcReadCrews() {
    if (!is_file(QC_CREWS_FILE)) {
        return [];
    }
    $raw = (string)file_get_contents(QC_CREWS_FILE);
    $raw = preg_replace('/^<\?php.*?\?>\s*/s', '', $raw);
    $data = json_decode($raw, true);
    $crews = is_array($data) ? ($data['crews'] ?? []) : [];
    $clean = [];
    foreach ($crews as $crew) {
        if (is_array($crew) && !empty($crew['initials'])) {
            $clean[] = [
                'initials' => strtoupper(trim((string)$crew['initials'])),
                'name'     => trim((string)($crew['name'] ?? '')),
            ];
        }
    }
    return $clean;
}

function qcWriteCrews(array $crews) {
    $json = json_encode(['crews' => array_values($crews)], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    return file_put_contents(QC_CREWS_FILE, QC_CREWS_GUARD . $json, LOCK_EX) !== false;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['action'])) {
    sendJsonResponse([
        'success' => false,
        'message' => 'Invalid request method or missing action parameter'
    ]);
}

// Resolve the logged-in user; QC edits are stamped with their name
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

    // ── get_sessions ─────────────────────────────────────────────────────────
    if ($action === 'get_sessions') {

        $projectId = trim($_POST['project_id'] ?? '');

        $sql = "SELECT s.*,
                       p.project_name,
                       t.task_name,
                       COALESCE(f.total_findings, 0) AS total_findings,
                       COALESCE(f.open_findings, 0)  AS open_findings,
                       f.worst_open_severity
                FROM field_data_qc_sessions s
                LEFT JOIN survey_projects p ON p.project_id = s.project_id
                LEFT JOIN tasks t ON t.task_id = s.task_id
                LEFT JOIN (
                    SELECT session_id,
                           COUNT(*) AS total_findings,
                           SUM(status = 'Open') AS open_findings,
                           SUBSTRING_INDEX(
                               GROUP_CONCAT(CASE WHEN status = 'Open' THEN severity END
                                   ORDER BY FIELD(severity, 'Critical', 'Major', 'Minor', 'Info')), ',', 1
                           ) AS worst_open_severity
                    FROM field_data_qc_findings
                    GROUP BY session_id
                ) f ON f.session_id = s.session_id";

        $params = [];
        if ($projectId !== '') {
            $sql .= " WHERE s.project_id = :project_id";
            $params[':project_id'] = $projectId;
        }
        $sql .= " ORDER BY s.project_id, s.collection_date DESC, s.session_id DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Attach point-range entries (job files / point numbers used) recorded on the
        // Tools page: the linked task's entries, or all project tasks' entries if no task
        $entriesByTask = [];
        $entriesByProject = [];
        if ($sessions) {
            $sessionProjectIds = array_values(array_unique(array_column($sessions, 'project_id')));
            $inList = implode(',', array_fill(0, count($sessionProjectIds), '?'));
            $prStmt = $pdo->prepare(
                "SELECT task_id, project_id, task_name, point_ranges
                 FROM tasks
                 WHERE point_ranges IS NOT NULL AND project_id IN ($inList)"
            );
            $prStmt->execute($sessionProjectIds);
            foreach ($prStmt->fetchAll(PDO::FETCH_ASSOC) as $taskRow) {
                $decoded = json_decode($taskRow['point_ranges'] ?? '', true);
                foreach (($decoded['entries'] ?? []) as $entry) {
                    if (!is_array($entry)) continue;
                    $entry['task_id'] = (int)$taskRow['task_id'];
                    $entry['task_name'] = $taskRow['task_name'];
                    $entriesByTask[$taskRow['task_id']][] = $entry;
                    $entriesByProject[$taskRow['project_id']][] = $entry;
                }
            }
        }

        foreach ($sessions as &$session) {
            $session['stages'] = json_decode($session['stages'] ?? '{}', true) ?: new stdClass();
            $session['point_range_entries'] = $session['task_id']
                ? ($entriesByTask[$session['task_id']] ?? [])
                : ($entriesByProject[$session['project_id']] ?? []);
        }
        unset($session);

        sendJsonResponse([
            'success'    => true,
            'sessions'   => $sessions,
            'stages'     => QC_STAGES,
            'categories' => QC_CATEGORIES,
            'severities' => QC_SEVERITIES,
            'statuses'   => QC_STATUSES,
        ]);
    }

    // ── create_session / update_session ──────────────────────────────────────
    elseif ($action === 'create_session' || $action === 'update_session') {

        $projectId = trim($_POST['project_id'] ?? '');
        if ($projectId === '') {
            sendJsonResponse(['success' => false, 'message' => 'project_id is required']);
        }

        $taskId = (int)($_POST['task_id'] ?? 0) ?: null;

        $collectionDate = trim($_POST['collection_date'] ?? '');
        if ($collectionDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $collectionDate)) {
            sendJsonResponse(['success' => false, 'message' => 'collection_date must be YYYY-MM-DD']);
        }

        $scaleFactor = trim($_POST['scale_factor'] ?? '');
        if ($scaleFactor !== '' && !is_numeric($scaleFactor)) {
            sendJsonResponse(['success' => false, 'message' => 'scale_factor must be numeric']);
        }

        $fields = [
            ':project_id'        => $projectId,
            ':task_id'           => $taskId,
            ':raw_data_path'     => trim($_POST['raw_data_path'] ?? '') ?: null,
            ':collection_date'   => $collectionDate ?: null,
            ':field_crew'        => trim($_POST['field_crew'] ?? '') ?: null,
            ':instrument'        => trim($_POST['instrument'] ?? '') ?: null,
            ':coordinate_system' => trim($_POST['coordinate_system'] ?? '') ?: null,
            ':datum_epoch'       => trim($_POST['datum_epoch'] ?? '') ?: null,
            ':geoid_model'       => trim($_POST['geoid_model'] ?? '') ?: null,
            ':vertical_datum'    => trim($_POST['vertical_datum'] ?? '') ?: null,
            ':scale_factor'      => $scaleFactor !== '' ? $scaleFactor : null,
            ':units'             => trim($_POST['units'] ?? '') ?: null,
            ':general_notes'     => trim($_POST['general_notes'] ?? '') ?: null,
        ];

        if ($action === 'create_session') {
            $stmt = $pdo->prepare(
                "INSERT INTO field_data_qc_sessions
                    (project_id, task_id, raw_data_path, collection_date,
                     field_crew, instrument, coordinate_system, datum_epoch, geoid_model,
                     vertical_datum, scale_factor, units, general_notes, stages, username)
                 VALUES
                    (:project_id, :task_id, :raw_data_path, :collection_date,
                     :field_crew, :instrument, :coordinate_system, :datum_epoch, :geoid_model,
                     :vertical_datum, :scale_factor, :units, :general_notes, '{}', :username)"
            );
            $stmt->execute($fields + [':username' => $currentUser]);
            sendJsonResponse(['success' => true, 'session_id' => (int)$pdo->lastInsertId()]);
        } else {
            $sessionId = (int)($_POST['session_id'] ?? 0);
            if (!$sessionId) {
                sendJsonResponse(['success' => false, 'message' => 'session_id is required']);
            }
            $stmt = $pdo->prepare(
                "UPDATE field_data_qc_sessions SET
                    project_id = :project_id, task_id = :task_id,
                    raw_data_path = :raw_data_path, collection_date = :collection_date,
                    field_crew = :field_crew, instrument = :instrument,
                    coordinate_system = :coordinate_system, datum_epoch = :datum_epoch,
                    geoid_model = :geoid_model, vertical_datum = :vertical_datum,
                    scale_factor = :scale_factor, units = :units, general_notes = :general_notes
                 WHERE session_id = :session_id"
            );
            $stmt->execute($fields + [':session_id' => $sessionId]);
            sendJsonResponse(['success' => true]);
        }
    }

    // ── delete_session ───────────────────────────────────────────────────────
    elseif ($action === 'delete_session') {

        $sessionId = (int)($_POST['session_id'] ?? 0);
        if (!$sessionId) {
            sendJsonResponse(['success' => false, 'message' => 'session_id is required']);
        }
        $stmt = $pdo->prepare("DELETE FROM field_data_qc_sessions WHERE session_id = :session_id");
        $stmt->execute([':session_id' => $sessionId]);
        sendJsonResponse(['success' => true]);
    }

    // ── update_stage ─────────────────────────────────────────────────────────
    elseif ($action === 'update_stage') {

        $sessionId = (int)($_POST['session_id'] ?? 0);
        $stageKey  = trim($_POST['stage_key'] ?? '');
        $done      = ($_POST['done'] ?? '') === '1';

        if (!$sessionId || !array_key_exists($stageKey, QC_STAGES)) {
            sendJsonResponse(['success' => false, 'message' => 'Valid session_id and stage_key are required']);
        }

        $getStmt = $pdo->prepare("SELECT stages FROM field_data_qc_sessions WHERE session_id = :session_id");
        $getStmt->execute([':session_id' => $sessionId]);
        $row = $getStmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            sendJsonResponse(['success' => false, 'message' => 'Session not found']);
        }

        // The audit object is built server-side; the client only says which stage and on/off
        $stages = json_decode($row['stages'] ?? '{}', true) ?: [];
        if ($done) {
            $stages[$stageKey] = ['done' => true, 'by' => $currentUser, 'at' => date('Y-m-d H:i:s')];
        } else {
            unset($stages[$stageKey]);
        }

        $updStmt = $pdo->prepare("UPDATE field_data_qc_sessions SET stages = :stages WHERE session_id = :session_id_upd");
        $updStmt->execute([':stages' => json_encode($stages ?: new stdClass()), ':session_id_upd' => $sessionId]);

        sendJsonResponse(['success' => true, 'stages' => $stages ?: new stdClass()]);
    }

    // ── save_task_point_ranges ───────────────────────────────────────────────
    // Point ranges live on tasks (tasks.point_ranges JSON); the QC page edits
    // them per task. An empty entries list clears the column.
    elseif ($action === 'save_task_point_ranges') {

        $taskId = (int)($_POST['task_id'] ?? 0);
        if (!$taskId) {
            sendJsonResponse(['success' => false, 'message' => 'task_id is required']);
        }

        $decoded = json_decode($_POST['point_ranges'] ?? '', true);
        // Reject malformed payloads outright — treating them as empty would silently wipe data
        if (!is_array($decoded) || !isset($decoded['entries']) || !is_array($decoded['entries'])) {
            sendJsonResponse(['success' => false, 'message' => 'point_ranges must be JSON with an entries array']);
        }
        $entriesIn = $decoded['entries'];

        $entries = [];
        foreach ($entriesIn as $entry) {
            if (!is_array($entry)) continue;
            $clean = [
                'job_file_name'     => trim((string)($entry['job_file_name'] ?? '')),
                'point_number_used' => trim((string)($entry['point_number_used'] ?? '')),
                'converted'         => ($entry['converted'] ?? '') === 'yes' ? 'yes' : 'no',
                'imported'          => ($entry['imported'] ?? '') === 'yes' ? 'yes' : 'no',
                'checked'           => ($entry['checked'] ?? '') === 'yes' ? 'yes' : 'no',
                'notes'             => trim((string)($entry['notes'] ?? '')),
            ];
            // Skip rows the user left completely empty
            if ($clean['job_file_name'] === '' && $clean['point_number_used'] === '' && $clean['notes'] === '') {
                continue;
            }
            $entries[] = $clean;
        }

        $checkStmt = $pdo->prepare("SELECT task_id FROM tasks WHERE task_id = :task_id");
        $checkStmt->execute([':task_id' => $taskId]);
        if (!$checkStmt->fetch()) {
            sendJsonResponse(['success' => false, 'message' => 'Task not found']);
        }

        $updStmt = $pdo->prepare("UPDATE tasks SET point_ranges = :point_ranges WHERE task_id = :task_id_upd");
        $updStmt->execute([
            ':point_ranges' => $entries ? json_encode(['entries' => $entries]) : null,
            ':task_id_upd'  => $taskId,
        ]);

        sendJsonResponse(['success' => true, 'entries' => $entries]);
    }

    // ── get_crews / add_crew / remove_crew ───────────────────────────────────
    elseif ($action === 'get_crews') {
        sendJsonResponse(['success' => true, 'crews' => qcReadCrews()]);
    }

    elseif ($action === 'add_crew') {
        $initials = strtoupper(trim($_POST['initials'] ?? ''));
        $name     = trim($_POST['name'] ?? '');

        if (!preg_match('/^[A-Z]{1,5}$/', $initials)) {
            sendJsonResponse(['success' => false, 'message' => 'Initials must be 1-5 letters']);
        }
        if (mb_strlen($name) > 100) {
            sendJsonResponse(['success' => false, 'message' => 'Name is too long (max 100 characters)']);
        }

        $crews = qcReadCrews();
        foreach ($crews as $crew) {
            if ($crew['initials'] === $initials) {
                sendJsonResponse(['success' => false, 'message' => "Crew \"$initials\" already exists"]);
            }
        }
        $crews[] = ['initials' => $initials, 'name' => $name];
        usort($crews, fn($a, $b) => strcmp($a['initials'], $b['initials']));

        if (!qcWriteCrews($crews)) {
            sendJsonResponse(['success' => false, 'message' => 'Could not write crews file — check permissions on Private/crews.json.php']);
        }
        sendJsonResponse(['success' => true, 'crews' => $crews]);
    }

    elseif ($action === 'remove_crew') {
        $initials = strtoupper(trim($_POST['initials'] ?? ''));
        if ($initials === '') {
            sendJsonResponse(['success' => false, 'message' => 'initials is required']);
        }

        $crews = array_values(array_filter(qcReadCrews(), fn($c) => $c['initials'] !== $initials));

        if (!qcWriteCrews($crews)) {
            sendJsonResponse(['success' => false, 'message' => 'Could not write crews file — check permissions on Private/crews.json.php']);
        }
        sendJsonResponse(['success' => true, 'crews' => $crews]);
    }

    // ── get_findings ─────────────────────────────────────────────────────────
    elseif ($action === 'get_findings') {

        $sessionId = (int)($_POST['session_id'] ?? 0);
        if (!$sessionId) {
            sendJsonResponse(['success' => false, 'message' => 'session_id is required']);
        }
        $stmt = $pdo->prepare(
            "SELECT * FROM field_data_qc_findings
             WHERE session_id = :session_id
             ORDER BY FIELD(severity, 'Critical', 'Major', 'Minor', 'Info'), created_date"
        );
        $stmt->execute([':session_id' => $sessionId]);
        sendJsonResponse(['success' => true, 'findings' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }

    // ── add_finding / update_finding ─────────────────────────────────────────
    elseif ($action === 'add_finding' || $action === 'update_finding') {

        $category = trim($_POST['category'] ?? '');
        $severity = trim($_POST['severity'] ?? '');
        $status   = trim($_POST['status'] ?? 'Open');

        // Category allows custom values via the "Other…" escape, but caps length to the column
        if ($category === '' || mb_strlen($category) > 50) {
            sendJsonResponse(['success' => false, 'message' => 'category is required (max 50 characters)']);
        }
        if (!in_array($severity, QC_SEVERITIES, true)) {
            sendJsonResponse(['success' => false, 'message' => 'Invalid severity']);
        }
        if (!in_array($status, QC_STATUSES, true)) {
            sendJsonResponse(['success' => false, 'message' => 'Invalid status']);
        }

        $pointNumbers = trim($_POST['point_numbers'] ?? '') ?: null;
        $description  = trim($_POST['description'] ?? '') ?: null;
        $resolution   = trim($_POST['resolution'] ?? '') ?: null;
        $isClosed     = ($status === 'Resolved' || $status === 'Waived');

        if ($action === 'add_finding') {
            $sessionId = (int)($_POST['session_id'] ?? 0);
            if (!$sessionId) {
                sendJsonResponse(['success' => false, 'message' => 'session_id is required']);
            }
            $stmt = $pdo->prepare(
                "INSERT INTO field_data_qc_findings
                    (session_id, point_numbers, category, severity, description, resolution,
                     status, resolved_by, resolved_date, username)
                 VALUES
                    (:session_id, :point_numbers, :category, :severity, :description, :resolution,
                     :status, :resolved_by, :resolved_date, :username)"
            );
            $stmt->execute([
                ':session_id'    => $sessionId,
                ':point_numbers' => $pointNumbers,
                ':category'      => $category,
                ':severity'      => $severity,
                ':description'   => $description,
                ':resolution'    => $resolution,
                ':status'        => $status,
                ':resolved_by'   => $isClosed ? $currentUser : null,
                ':resolved_date' => $isClosed ? date('Y-m-d H:i:s') : null,
                ':username'      => $currentUser,
            ]);
            sendJsonResponse(['success' => true, 'finding_id' => (int)$pdo->lastInsertId()]);
        } else {
            $findingId = (int)($_POST['finding_id'] ?? 0);
            if (!$findingId) {
                sendJsonResponse(['success' => false, 'message' => 'finding_id is required']);
            }
            // Stamp resolved_by/date on transition to closed, clear them on reopen,
            // and keep the original stamp if the finding was already closed
            $stmt = $pdo->prepare(
                "UPDATE field_data_qc_findings SET
                    point_numbers = :point_numbers, category = :category, severity = :severity,
                    description = :description, resolution = :resolution, status = :status,
                    resolved_by = CASE WHEN :is_closed = 1 THEN COALESCE(resolved_by, :current_user) ELSE NULL END,
                    resolved_date = CASE WHEN :is_closed_d = 1 THEN COALESCE(resolved_date, NOW()) ELSE NULL END
                 WHERE finding_id = :finding_id"
            );
            $stmt->execute([
                ':point_numbers' => $pointNumbers,
                ':category'      => $category,
                ':severity'      => $severity,
                ':description'   => $description,
                ':resolution'    => $resolution,
                ':status'        => $status,
                ':is_closed'     => $isClosed ? 1 : 0,
                ':current_user'  => $currentUser,
                ':is_closed_d'   => $isClosed ? 1 : 0,
                ':finding_id'    => $findingId,
            ]);
            sendJsonResponse(['success' => true]);
        }
    }

    // ── delete_finding ───────────────────────────────────────────────────────
    elseif ($action === 'delete_finding') {

        $findingId = (int)($_POST['finding_id'] ?? 0);
        if (!$findingId) {
            sendJsonResponse(['success' => false, 'message' => 'finding_id is required']);
        }
        $stmt = $pdo->prepare("DELETE FROM field_data_qc_findings WHERE finding_id = :finding_id");
        $stmt->execute([':finding_id' => $findingId]);
        sendJsonResponse(['success' => true]);
    }

    else {
        sendJsonResponse(['success' => false, 'message' => 'Invalid action parameter']);
    }

} catch (Exception $e) {
    error_log('field_data_qc_api error: ' . $e->getMessage());
    sendJsonResponse(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
