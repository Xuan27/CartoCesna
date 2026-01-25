<?php
header('Content-Type: application/json');
require_once '../../Private/db_config.php';
$db = new Database();
$conn = $db->getConnection();

try {
    $action = $_POST['action'] ?? $_GET['action'] ?? '';
    
    if ($action === 'load_tasks') {
        $project_id = $_POST['project_id'] ?? '';
        
        if (empty($project_id)) {
            echo json_encode(['success' => false, 'message' => 'Project ID is required']);
            exit;
        }
        
        // PDO uses named parameters (:project_id) or question marks
        $stmt = $conn->prepare("SELECT task_id, project_id, phase_number, task_name, task_type, task_status, task_priority, task_link, start_date, due_date, completion_date, assigned_to, created_by, modified_by, estimated_hours, actual_hours, notes, folder_overrides, point_ranges, created_date, modified_date FROM tasks WHERE project_id = :project_id ORDER BY FIELD(task_status, 'In Progress', 'Not Started', 'On Hold', 'Completed', 'Cancelled'), due_date ASC, task_id ASC");
        
        // PDO binding - use bindParam or bindValue, or pass array to execute()
        $stmt->execute(['project_id' => $project_id]);
        
        // PDO fetch method
        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'tasks' => $tasks,
            'count' => count($tasks)
        ]);
        
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error loading tasks: ' . $e->getMessage()
    ]);
}
?>