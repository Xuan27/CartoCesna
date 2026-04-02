<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

function sendJsonResponse($data) {
    header('Content-Type: application/json');
    echo json_encode($data, JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['action'])) {
    sendJsonResponse([
        'success' => false,
        'message' => 'Invalid request method or missing action parameter'
    ]);
}

try {
    require_once '../../Private/db_config.php';
    $db = new Database();
    $pdo = $db->getConnection();

    $action = $_POST['action'];

    // ── schema migration (idempotent) ─────────────────────────────────────────
    try {
        $pdo->exec("ALTER TABLE time_entries ADD COLUMN IF NOT EXISTS notes TEXT NULL");
    } catch (Exception $e) { /* column already exists or unsupported syntax — ignore */ }

    // ── start_timer ──────────────────────────────────────────────────────────
    if ($action === 'start_timer') {

        $projectId   = trim($_POST['project_id']   ?? '');
        $taskId      = (int)($_POST['task_id']      ?? 0);
        $taskName    = trim($_POST['task_name']     ?? '');
        $projectName = trim($_POST['project_name'] ?? '');

        // task_id=0 is allowed for admin/training (non-task) entries
        if (!$projectId) {
            sendJsonResponse(['success' => false, 'message' => 'project_id is required']);
        }

        // Auto-stop any currently running timer
        $pdo->exec(
            "UPDATE time_entries
             SET end_time = NOW(),
                 duration_seconds = GREATEST(0, TIMESTAMPDIFF(SECOND, start_time, NOW()))
             WHERE end_time IS NULL"
        );

        // Insert new entry
        $stmt = $pdo->prepare(
            "INSERT INTO time_entries (project_id, task_id, task_name, project_name, start_time)
             VALUES (:project_id, :task_id, :task_name, :project_name, NOW())"
        );
        $stmt->execute([
            ':project_id'   => $projectId,
            ':task_id'      => $taskId,
            ':task_name'    => $taskName,
            ':project_name' => $projectName,
        ]);

        $entryId = (int)$pdo->lastInsertId();

        $getStmt = $pdo->prepare("SELECT UNIX_TIMESTAMP(start_time) AS start_time FROM time_entries WHERE entry_id = :id");
        $getStmt->execute([':id' => $entryId]);
        $entry = $getStmt->fetch(PDO::FETCH_ASSOC);

        sendJsonResponse([
            'success'    => true,
            'entry_id'   => $entryId,
            'start_time' => $entry['start_time'],
        ]);
    }

    // ── stop_timer ───────────────────────────────────────────────────────────
    elseif ($action === 'stop_timer') {

        $entryId = (int)($_POST['entry_id'] ?? 0);
        $notes   = trim($_POST['notes'] ?? '');
        if (!$entryId) {
            sendJsonResponse(['success' => false, 'message' => 'entry_id is required']);
        }

        $stmt = $pdo->prepare(
            "UPDATE time_entries
             SET end_time = NOW(),
                 duration_seconds = GREATEST(0, TIMESTAMPDIFF(SECOND, start_time, NOW())),
                 notes = :notes
             WHERE entry_id = :entry_id AND end_time IS NULL"
        );
        $stmt->execute([':entry_id' => $entryId, ':notes' => $notes ?: null]);

        $getStmt = $pdo->prepare(
            "SELECT task_id, end_time, duration_seconds FROM time_entries WHERE entry_id = :id"
        );
        $getStmt->execute([':id' => $entryId]);
        $entry = $getStmt->fetch(PDO::FETCH_ASSOC);

        if (!$entry) {
            sendJsonResponse(['success' => false, 'message' => 'Entry not found or already stopped']);
        }

        $taskId     = (int)$entry['task_id'];
        $totalHours = 0;

        // Only update actual_hours for real tasks (task_id > 0)
        if ($taskId > 0) {
            $sumStmt = $pdo->prepare(
                "SELECT COALESCE(SUM(duration_seconds), 0) AS total_seconds
                 FROM time_entries
                 WHERE task_id = :task_id AND duration_seconds IS NOT NULL"
            );
            $sumStmt->execute([':task_id' => $taskId]);
            $sum        = $sumStmt->fetch(PDO::FETCH_ASSOC);
            $totalHours = round($sum['total_seconds'] / 3600, 2);

            $updateStmt = $pdo->prepare(
                "UPDATE tasks SET actual_hours = :hours WHERE task_id = :task_id"
            );
            $updateStmt->execute([':hours' => $totalHours, ':task_id' => $taskId]);
        }

        sendJsonResponse([
            'success'          => true,
            'duration_seconds' => (int)$entry['duration_seconds'],
            'end_time'         => $entry['end_time'],
            'actual_hours'     => $totalHours,
        ]);
    }

    // ── get_active_timer ─────────────────────────────────────────────────────
    elseif ($action === 'get_active_timer') {

        $stmt = $pdo->query(
            "SELECT entry_id, project_id, task_id, task_name, project_name, UNIX_TIMESTAMP(start_time) AS start_time
             FROM time_entries
             WHERE end_time IS NULL
             ORDER BY start_time DESC
             LIMIT 1"
        );
        $entry = $stmt->fetch(PDO::FETCH_ASSOC);

        sendJsonResponse([
            'success' => true,
            'entry'   => $entry ?: null,
        ]);
    }

    // ── update_entry_time ─────────────────────────────────────────────────────
    elseif ($action === 'update_entry_time') {

        $entryId       = (int)($_POST['entry_id']   ?? 0);
        $startUnixTime = (int)($_POST['start_time'] ?? 0);

        if (!$entryId || !$startUnixTime) {
            sendJsonResponse(['success' => false, 'message' => 'entry_id and start_time are required']);
        }

        $stmt = $pdo->prepare(
            "UPDATE time_entries
             SET start_time = FROM_UNIXTIME(:start_time)
             WHERE entry_id = :entry_id AND end_time IS NULL"
        );
        $stmt->execute([':entry_id' => $entryId, ':start_time' => $startUnixTime]);

        sendJsonResponse(['success' => true]);
    }

    // ── get_timesheet ─────────────────────────────────────────────────────────
    elseif ($action === 'get_timesheet') {

        $weekStart = $_POST['week_start'] ?? '';

        // Validate / default to current Monday
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $weekStart)) {
            $weekStart = date('Y-m-d', strtotime('monday this week'));
        }

        $weekEnd = date('Y-m-d', strtotime($weekStart . ' +6 days'));

        $sql = "SELECT
                    te.task_id,
                    te.task_name,
                    te.project_id,
                    te.project_name,
                    t.phase_number,
                    t.task_type,
                    DATE(te.start_time)      AS entry_date,
                    SUM(te.duration_seconds) AS total_seconds,
                    GROUP_CONCAT(te.notes ORDER BY te.start_time SEPARATOR ' | ') AS notes
                FROM time_entries te
                LEFT JOIN tasks t ON te.task_id = t.task_id
                WHERE DATE(te.start_time) BETWEEN :week_start AND :week_end
                  AND te.duration_seconds IS NOT NULL
                GROUP BY te.task_id,
                         te.task_name,
                         te.project_id,
                         te.project_name,
                         t.phase_number,
                         t.task_type,
                         DATE(te.start_time)
                ORDER BY te.project_id, te.task_id, entry_date";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':week_start' => $weekStart, ':week_end' => $weekEnd]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        sendJsonResponse([
            'success'    => true,
            'entries'    => $rows,
            'week_start' => $weekStart,
            'week_end'   => $weekEnd,
        ]);
    }

    // ── get_recent_notes ──────────────────────────────────────────────────────
    elseif ($action === 'get_recent_notes') {

        $taskId   = (int)($_POST['task_id']   ?? 0);
        $taskName = trim($_POST['task_name'] ?? '');

        // For real tasks filter by task_id; for admin/training (task_id=0)
        // also require task_name so Admin and Training notes stay separate.
        $stmt = $pdo->prepare(
            "SELECT notes
             FROM time_entries
             WHERE notes IS NOT NULL AND notes != ''
               AND task_id = :task_id
               AND (:task_id_gt > 0 OR task_name = :task_name)
             GROUP BY notes
             ORDER BY MAX(start_time) DESC
             LIMIT 20"
        );
        $stmt->execute([':task_id' => $taskId, ':task_id_gt' => $taskId, ':task_name' => $taskName]);
        $notes = $stmt->fetchAll(PDO::FETCH_COLUMN);

        sendJsonResponse(['success' => true, 'notes' => $notes]);
    }

    // ── update_phase_number ───────────────────────────────────────────────────
    elseif ($action === 'update_phase_number') {

        $taskId      = (int)($_POST['task_id']      ?? 0);
        $phaseNumber = trim($_POST['phase_number'] ?? '');

        if (!$taskId) {
            sendJsonResponse(['success' => false, 'message' => 'task_id is required']);
        }

        $stmt = $pdo->prepare("UPDATE tasks SET phase_number = :phase WHERE task_id = :id");
        $stmt->execute([':phase' => $phaseNumber ?: null, ':id' => $taskId]);

        sendJsonResponse(['success' => true]);
    }

    // ── delete_entry ──────────────────────────────────────────────────────────
    elseif ($action === 'delete_entry') {

        $entryId = (int)($_POST['entry_id'] ?? 0);
        if (!$entryId) {
            sendJsonResponse(['success' => false, 'message' => 'entry_id is required']);
        }

        $stmt = $pdo->prepare("DELETE FROM time_entries WHERE entry_id = :entry_id");
        $stmt->execute([':entry_id' => $entryId]);

        sendJsonResponse(['success' => true, 'message' => 'Entry deleted']);
    }

    else {
        sendJsonResponse(['success' => false, 'message' => 'Invalid action parameter']);
    }

} catch (Exception $e) {
    error_log("Time tracking API error: " . $e->getMessage());
    sendJsonResponse(['success' => false, 'message' => 'Server error occurred']);
}
?>
