<?php
/**
 * Tools API - Database management for survey_projects and tasks tables
 * Provides CRUD operations for the Tools page
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json');

function sendJsonResponse($data) {
    echo json_encode($data, JSON_UNESCAPED_SLASHES);
    exit;
}

// Connect to database
try {
    require_once '../../Private/db_config.php';
    $db = new Database();
    $pdo = $db->getConnection();
} catch (Exception $e) {
    error_log("Database connection error: " . $e->getMessage());
    sendJsonResponse([
        'success' => false,
        'message' => 'Database connection failed'
    ]);
}

// Get counts for both tables
function getTableCounts($pdo) {
    $counts = [];

    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM survey_projects");
        $counts['survey_projects'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    } catch (Exception $e) {
        $counts['survey_projects'] = 0;
    }

    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM tasks");
        $counts['tasks'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    } catch (Exception $e) {
        $counts['tasks'] = 0;
    }

    return $counts;
}

// Validate table name
function validateTable($table) {
    $allowedTables = ['survey_projects', 'tasks'];
    return in_array($table, $allowedTables) ? $table : null;
}

// Get primary key for table
function getPrimaryKey($table) {
    return $table === 'survey_projects' ? 'project_id' : 'task_id';
}

// Handle GET requests
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    $table = validateTable($_GET['table'] ?? '');

    if ($action === 'get_all' && $table) {
        try {
            $orderBy = $table === 'survey_projects' ? 'created_date DESC' : 'task_id DESC';
            $stmt = $pdo->query("SELECT * FROM $table ORDER BY $orderBy");
            $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

            sendJsonResponse([
                'success' => true,
                'records' => $records,
                'counts' => getTableCounts($pdo)
            ]);
        } catch (PDOException $e) {
            error_log("Error fetching records: " . $e->getMessage());
            sendJsonResponse([
                'success' => false,
                'message' => 'Error fetching records'
            ]);
        }
    }

    sendJsonResponse([
        'success' => false,
        'message' => 'Invalid request'
    ]);
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        sendJsonResponse([
            'success' => false,
            'message' => 'Invalid JSON input'
        ]);
    }

    $action = $input['action'] ?? '';
    $table = validateTable($input['table'] ?? '');

    if (!$table) {
        sendJsonResponse([
            'success' => false,
            'message' => 'Invalid table specified'
        ]);
    }

    $primaryKey = getPrimaryKey($table);

    // ADD record
    if ($action === 'add') {
        try {
            // Define allowed fields for each table
            $allowedFields = [];
            if ($table === 'survey_projects') {
                $allowedFields = [
                    'project_id', 'project_name', 'project_folder_link', 'survey_folder_link',
                    'drawing_folder_link', 'field_folder_link', 'contract_link', 'qaQc_folder_link',
                    'research_folder_link', 'created_by', 'project_status', 'notes', 'location', 'scale_factor'
                ];
            } else {
                $allowedFields = [
                    'project_id', 'phase_number', 'task_name', 'task_type', 'task_status',
                    'task_priority', 'task_link', 'start_date', 'due_date', 'completion_date',
                    'assigned_to', 'created_by', 'estimated_hours', 'actual_hours', 'notes',
                    'point_ranges'
                ];
            }

            $fields = [];
            $values = [];
            $placeholders = [];

            foreach ($allowedFields as $field) {
                if (isset($input[$field]) && $input[$field] !== null && $input[$field] !== '') {
                    $fields[] = $field;
                    $values[] = $input[$field];
                    $placeholders[] = '?';
                }
            }

            if (empty($fields)) {
                sendJsonResponse([
                    'success' => false,
                    'message' => 'No valid fields provided'
                ]);
            }

            $sql = "INSERT INTO $table (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($values);

            sendJsonResponse([
                'success' => true,
                'message' => 'Record added successfully',
                'id' => $table === 'tasks' ? $pdo->lastInsertId() : $input['project_id']
            ]);

        } catch (PDOException $e) {
            error_log("Error adding record: " . $e->getMessage());
            sendJsonResponse([
                'success' => false,
                'message' => 'Error adding record: ' . $e->getMessage()
            ]);
        }
    }

    // UPDATE record
    if ($action === 'update') {
        $id = $input['id'] ?? null;

        if (!$id) {
            sendJsonResponse([
                'success' => false,
                'message' => 'Record ID is required'
            ]);
        }

        try {
            // Define allowed fields for each table (excluding primary key)
            $allowedFields = [];
            if ($table === 'survey_projects') {
                $allowedFields = [
                    'project_name', 'project_folder_link', 'survey_folder_link',
                    'drawing_folder_link', 'field_folder_link', 'contract_link', 'qaQc_folder_link',
                    'research_folder_link', 'modified_by', 'project_status', 'notes', 'location', 'scale_factor'
                ];
            } else {
                $allowedFields = [
                    'project_id', 'phase_number', 'task_name', 'task_type', 'task_status',
                    'task_priority', 'task_link', 'start_date', 'due_date', 'completion_date',
                    'assigned_to', 'modified_by', 'estimated_hours', 'actual_hours', 'notes',
                    'point_ranges'
                ];
            }

            $updates = [];
            $values = [];

            foreach ($allowedFields as $field) {
                if (array_key_exists($field, $input)) {
                    $value = $input[$field];
                    // Handle empty strings for date/number/json fields
                    if (($field === 'start_date' || $field === 'due_date' || $field === 'completion_date' ||
                         $field === 'estimated_hours' || $field === 'actual_hours') && $value === '') {
                        $value = null;
                    }
                    // Handle point_ranges JSON - allow null
                    if ($field === 'point_ranges' && ($value === '' || $value === 'null')) {
                        $value = null;
                    }
                    $updates[] = "$field = ?";
                    $values[] = $value;
                }
            }

            if (empty($updates)) {
                sendJsonResponse([
                    'success' => false,
                    'message' => 'No fields to update'
                ]);
            }

            $values[] = $id;
            $sql = "UPDATE $table SET " . implode(', ', $updates) . " WHERE $primaryKey = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($values);

            sendJsonResponse([
                'success' => true,
                'message' => 'Record updated successfully'
            ]);

        } catch (PDOException $e) {
            error_log("Error updating record: " . $e->getMessage());
            sendJsonResponse([
                'success' => false,
                'message' => 'Error updating record: ' . $e->getMessage()
            ]);
        }
    }

    // DELETE record
    if ($action === 'delete') {
        $id = $input['id'] ?? null;

        if (!$id) {
            sendJsonResponse([
                'success' => false,
                'message' => 'Record ID is required'
            ]);
        }

        try {
            $stmt = $pdo->prepare("DELETE FROM $table WHERE $primaryKey = ?");
            $stmt->execute([$id]);

            if ($stmt->rowCount() > 0) {
                sendJsonResponse([
                    'success' => true,
                    'message' => 'Record deleted successfully'
                ]);
            } else {
                sendJsonResponse([
                    'success' => false,
                    'message' => 'Record not found'
                ]);
            }

        } catch (PDOException $e) {
            error_log("Error deleting record: " . $e->getMessage());
            sendJsonResponse([
                'success' => false,
                'message' => 'Error deleting record: ' . $e->getMessage()
            ]);
        }
    }

    // BULK DELETE
    if ($action === 'bulk_delete') {
        $ids = $input['ids'] ?? [];

        if (empty($ids)) {
            sendJsonResponse([
                'success' => false,
                'message' => 'No records selected'
            ]);
        }

        try {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $pdo->prepare("DELETE FROM $table WHERE $primaryKey IN ($placeholders)");
            $stmt->execute($ids);

            sendJsonResponse([
                'success' => true,
                'message' => $stmt->rowCount() . ' record(s) deleted successfully'
            ]);

        } catch (PDOException $e) {
            error_log("Error bulk deleting records: " . $e->getMessage());
            sendJsonResponse([
                'success' => false,
                'message' => 'Error deleting records: ' . $e->getMessage()
            ]);
        }
    }

    // BULK UPDATE
    if ($action === 'bulk_update') {
        $ids = $input['ids'] ?? [];
        $field = $input['field'] ?? null;
        $value = $input['value'] ?? null;

        if (empty($ids) || !$field) {
            sendJsonResponse([
                'success' => false,
                'message' => 'Missing required parameters'
            ]);
        }

        // Validate field name (prevent SQL injection)
        $allowedStatusFields = ['project_status', 'task_status', 'task_priority'];
        if (!in_array($field, $allowedStatusFields)) {
            sendJsonResponse([
                'success' => false,
                'message' => 'Invalid field for bulk update'
            ]);
        }

        try {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $sql = "UPDATE $table SET $field = ? WHERE $primaryKey IN ($placeholders)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(array_merge([$value], $ids));

            sendJsonResponse([
                'success' => true,
                'message' => $stmt->rowCount() . ' record(s) updated successfully'
            ]);

        } catch (PDOException $e) {
            error_log("Error bulk updating records: " . $e->getMessage());
            sendJsonResponse([
                'success' => false,
                'message' => 'Error updating records: ' . $e->getMessage()
            ]);
        }
    }

    sendJsonResponse([
        'success' => false,
        'message' => 'Invalid action'
    ]);
}

sendJsonResponse([
    'success' => false,
    'message' => 'Invalid request method'
]);
