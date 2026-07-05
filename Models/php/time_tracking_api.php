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

// Resolve the logged-in user; every timesheet action is scoped to them
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

    // ── schema migration (idempotent) ─────────────────────────────────────────
    try {
        $pdo->exec("ALTER TABLE time_entries ADD COLUMN IF NOT EXISTS notes TEXT NULL");
    } catch (Exception $e) { /* column already exists or unsupported syntax — ignore */ }
    try {
        $hasUsername = $pdo->query(
            "SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'time_entries' AND COLUMN_NAME = 'username'"
        )->fetchColumn();
        if (!$hasUsername) {
            $pdo->exec("ALTER TABLE time_entries ADD COLUMN username VARCHAR(100) NULL AFTER task_id");
            $pdo->exec("UPDATE time_entries SET username = 'admin' WHERE username IS NULL");
            $pdo->exec("CREATE INDEX idx_username ON time_entries (username)");
        }
    } catch (Exception $e) { /* ignore */ }

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

        // Auto-stop the current user's running timer (other users' timers keep running)
        $stopStmt = $pdo->prepare(
            "UPDATE time_entries
             SET end_time = NOW(),
                 duration_seconds = GREATEST(0, TIMESTAMPDIFF(SECOND, start_time, NOW()))
             WHERE end_time IS NULL AND username = :username"
        );
        $stopStmt->execute([':username' => $currentUser]);

        // Insert new entry
        $stmt = $pdo->prepare(
            "INSERT INTO time_entries (project_id, task_id, username, task_name, project_name, start_time)
             VALUES (:project_id, :task_id, :username, :task_name, :project_name, NOW())"
        );
        $stmt->execute([
            ':project_id'   => $projectId,
            ':task_id'      => $taskId,
            ':username'     => $currentUser,
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
             WHERE entry_id = :entry_id AND end_time IS NULL AND username = :username"
        );
        $stmt->execute([':entry_id' => $entryId, ':notes' => $notes ?: null, ':username' => $currentUser]);

        $getStmt = $pdo->prepare(
            "SELECT task_id, end_time, duration_seconds FROM time_entries
             WHERE entry_id = :id AND username = :username"
        );
        $getStmt->execute([':id' => $entryId, ':username' => $currentUser]);
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

        $stmt = $pdo->prepare(
            "SELECT entry_id, project_id, task_id, task_name, project_name, UNIX_TIMESTAMP(start_time) AS start_time
             FROM time_entries
             WHERE end_time IS NULL AND username = :username
             ORDER BY start_time DESC
             LIMIT 1"
        );
        $stmt->execute([':username' => $currentUser]);
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
             WHERE entry_id = :entry_id AND end_time IS NULL AND username = :username"
        );
        $stmt->execute([':entry_id' => $entryId, ':start_time' => $startUnixTime, ':username' => $currentUser]);

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
                  AND te.username = :username
                GROUP BY te.task_id,
                         te.task_name,
                         te.project_id,
                         te.project_name,
                         t.phase_number,
                         t.task_type,
                         DATE(te.start_time)
                ORDER BY te.project_id, te.task_id, entry_date";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':week_start' => $weekStart, ':week_end' => $weekEnd, ':username' => $currentUser]);
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
               AND username = :username
               AND (:task_id_gt > 0 OR task_name = :task_name)
             GROUP BY notes
             ORDER BY MAX(start_time) DESC
             LIMIT 20"
        );
        $stmt->execute([':task_id' => $taskId, ':task_id_gt' => $taskId, ':task_name' => $taskName, ':username' => $currentUser]);
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

    // ── update_entry_project ──────────────────────────────────────────────────
    elseif ($action === 'update_entry_project') {

        $taskId       = (int)($_POST['task_id']        ?? 0);
        $taskName     = trim($_POST['task_name']       ?? '');
        $weekStart    = trim($_POST['week_start']      ?? '');
        $weekEnd      = trim($_POST['week_end']        ?? '');
        $newProjectId = trim($_POST['new_project_id']  ?? '');

        if (!$newProjectId) {
            sendJsonResponse(['success' => false, 'message' => 'new_project_id is required']);
        }

        // Validate new project ID exists in survey_projects
        $checkStmt = $pdo->prepare("SELECT project_name FROM survey_projects WHERE project_id = :pid LIMIT 1");
        $checkStmt->execute([':pid' => $newProjectId]);
        $proj = $checkStmt->fetch(PDO::FETCH_ASSOC);
        if (!$proj) {
            sendJsonResponse(['success' => false, 'message' => 'Project ID not found']);
        }
        $newProjectName = $proj['project_name'];

        if ($taskId > 0) {
            // Real task: update the user's entries for this task across all weeks
            $stmt = $pdo->prepare(
                "UPDATE time_entries
                 SET project_id = :project_id, project_name = :project_name
                 WHERE task_id = :task_id AND username = :username"
            );
            $stmt->execute([
                ':project_id'   => $newProjectId,
                ':project_name' => $newProjectName,
                ':task_id'      => $taskId,
                ':username'     => $currentUser,
            ]);
        } else {
            // Admin/training entry: scope to the current week and task_name
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $weekStart) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $weekEnd)) {
                sendJsonResponse(['success' => false, 'message' => 'week_start and week_end are required for non-task entries']);
            }
            $stmt = $pdo->prepare(
                "UPDATE time_entries
                 SET project_id = :project_id, project_name = :project_name
                 WHERE task_id = 0 AND task_name = :task_name AND username = :username
                   AND DATE(start_time) BETWEEN :week_start AND :week_end"
            );
            $stmt->execute([
                ':project_id'   => $newProjectId,
                ':project_name' => $newProjectName,
                ':task_name'    => $taskName,
                ':username'     => $currentUser,
                ':week_start'   => $weekStart,
                ':week_end'     => $weekEnd,
            ]);
        }

        sendJsonResponse(['success' => true, 'project_name' => $newProjectName]);
    }

    // ── update_day_hours ──────────────────────────────────────────────────────
    elseif ($action === 'update_day_hours') {

        $taskId    = (int)($_POST['task_id']    ?? 0);
        $taskName  = trim($_POST['task_name']   ?? '');
        $entryDate = trim($_POST['entry_date']  ?? '');
        $hours     = (float)($_POST['hours']    ?? -1);

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $entryDate) || $hours < 0) {
            sendJsonResponse(['success' => false, 'message' => 'Invalid parameters']);
        }

        $newSeconds = (int)round($hours * 3600);

        // Find the canonical entry for this user+task+date
        $stmt = $pdo->prepare(
            "SELECT MIN(entry_id) AS first_id FROM time_entries
             WHERE task_id = :task_id AND task_name = :task_name AND username = :username
               AND DATE(start_time) = :entry_date
               AND duration_seconds IS NOT NULL"
        );
        $stmt->execute([':task_id' => $taskId, ':task_name' => $taskName, ':username' => $currentUser, ':entry_date' => $entryDate]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row || !$row['first_id']) {
            sendJsonResponse(['success' => false, 'message' => 'No entries found for that day']);
        }

        $firstId = (int)$row['first_id'];

        // Set new seconds on the first entry; zero out the user's others for that task+date
        $stmt = $pdo->prepare(
            "UPDATE time_entries
             SET duration_seconds = CASE WHEN entry_id = :first_id THEN :seconds ELSE 0 END
             WHERE task_id = :task_id AND task_name = :task_name AND username = :username
               AND DATE(start_time) = :entry_date"
        );
        $stmt->execute([
            ':first_id'   => $firstId,
            ':seconds'    => $newSeconds,
            ':task_id'    => $taskId,
            ':task_name'  => $taskName,
            ':username'   => $currentUser,
            ':entry_date' => $entryDate,
        ]);

        // Keep tasks.actual_hours in sync for real tasks
        if ($taskId > 0) {
            $sumStmt = $pdo->prepare(
                "SELECT COALESCE(SUM(duration_seconds), 0) AS total_seconds
                 FROM time_entries WHERE task_id = :task_id AND duration_seconds IS NOT NULL"
            );
            $sumStmt->execute([':task_id' => $taskId]);
            $sum = $sumStmt->fetch(PDO::FETCH_ASSOC);
            $totalHours = round($sum['total_seconds'] / 3600, 2);

            $pdo->prepare("UPDATE tasks SET actual_hours = :hours WHERE task_id = :task_id")
                ->execute([':hours' => $totalHours, ':task_id' => $taskId]);
        }

        sendJsonResponse(['success' => true]);
    }

    // ── update_day_note ───────────────────────────────────────────────────────
    elseif ($action === 'update_day_note') {

        $taskId    = (int)($_POST['task_id']   ?? 0);
        $taskName  = trim($_POST['task_name']  ?? '');
        $entryDate = trim($_POST['entry_date'] ?? '');
        $note      = trim($_POST['note']       ?? '');

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $entryDate)) {
            sendJsonResponse(['success' => false, 'message' => 'Invalid entry_date']);
        }

        // Find the canonical entry (lowest entry_id) for this user+task+date
        $stmt = $pdo->prepare(
            "SELECT MIN(entry_id) AS first_id FROM time_entries
             WHERE task_id = :task_id AND task_name = :task_name AND username = :username
               AND DATE(start_time) = :entry_date
               AND duration_seconds IS NOT NULL"
        );
        $stmt->execute([':task_id' => $taskId, ':task_name' => $taskName, ':username' => $currentUser, ':entry_date' => $entryDate]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row || !$row['first_id']) {
            sendJsonResponse(['success' => false, 'message' => 'No completed entries found for that day']);
        }

        $firstId = (int)$row['first_id'];

        // Set note on the first entry; clear it from the user's others for that task+date
        $stmt = $pdo->prepare(
            "UPDATE time_entries
             SET notes = CASE WHEN entry_id = :first_id THEN :note ELSE NULL END
             WHERE task_id = :task_id AND task_name = :task_name AND username = :username
               AND DATE(start_time) = :entry_date"
        );
        $stmt->execute([
            ':first_id'   => $firstId,
            ':note'       => $note ?: null,
            ':task_id'    => $taskId,
            ':task_name'  => $taskName,
            ':username'   => $currentUser,
            ':entry_date' => $entryDate,
        ]);

        sendJsonResponse(['success' => true]);
    }

    // ── delete_entry ──────────────────────────────────────────────────────────
    elseif ($action === 'delete_entry') {

        $entryId = (int)($_POST['entry_id'] ?? 0);
        if (!$entryId) {
            sendJsonResponse(['success' => false, 'message' => 'entry_id is required']);
        }

        $stmt = $pdo->prepare("DELETE FROM time_entries WHERE entry_id = :entry_id AND username = :username");
        $stmt->execute([':entry_id' => $entryId, ':username' => $currentUser]);

        if ($stmt->rowCount() === 0) {
            sendJsonResponse(['success' => false, 'message' => 'Entry not found']);
        }

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
