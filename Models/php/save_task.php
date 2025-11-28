<?php
header('Content-Type: application/json');
require_once '../../Private/db_config.php';
$db = new Database();
$conn = $db->getConnection();

try {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_task') {
        // Add new task
        $stmt = $conn->prepare("INSERT INTO tasks (project_id, phase_number, task_name, task_type, task_status, task_priority, task_link, start_date, due_date, completion_date, assigned_to, created_by, estimated_hours, actual_hours, notes) VALUES (:project_id, :phase_number, :task_name, :task_type, :task_status, :task_priority, :task_link, :start_date, :due_date, :completion_date, :assigned_to, :created_by, :estimated_hours, :actual_hours, :notes)");
        
        $stmt->execute([
            'project_id' => $_POST['projectId'] ?? '',
            'phase_number' => $_POST['phaseNumber'] ?? null,
            'task_name' => $_POST['taskName'] ?? '',
            'task_type' => $_POST['taskType'] ?? '',
            'task_status' => $_POST['taskStatus'] ?? 'Not Started',
            'task_priority' => $_POST['taskPriority'] ?? 'Medium',
            'task_link' => $_POST['taskLink'] ?? null,
            'start_date' => !empty($_POST['startDate']) ? $_POST['startDate'] : null,
            'due_date' => !empty($_POST['dueDate']) ? $_POST['dueDate'] : null,
            'completion_date' => !empty($_POST['completionDate']) ? $_POST['completionDate'] : null,
            'assigned_to' => $_POST['assignedTo'] ?? null,
            'created_by' => $_POST['createdBy'] ?? null,
            'estimated_hours' => !empty($_POST['estimatedHours']) ? $_POST['estimatedHours'] : null,
            'actual_hours' => !empty($_POST['actualHours']) ? $_POST['actualHours'] : null,
            'notes' => $_POST['notes'] ?? null
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Task added successfully',
            'task_id' => $conn->lastInsertId()
        ]);
        
    } elseif ($action === 'update_task') {
        // Update existing task
        $stmt = $conn->prepare("UPDATE tasks SET phase_number = :phase_number, task_name = :task_name, task_type = :task_type, task_status = :task_status, task_priority = :task_priority, task_link = :task_link, start_date = :start_date, due_date = :due_date, completion_date = :completion_date, assigned_to = :assigned_to, modified_by = :modified_by, estimated_hours = :estimated_hours, actual_hours = :actual_hours, notes = :notes WHERE task_id = :task_id");
        
        $stmt->execute([
            'task_id' => $_POST['taskId'] ?? '',
            'phase_number' => $_POST['phaseNumber'] ?? null,
            'task_name' => $_POST['taskName'] ?? '',
            'task_type' => $_POST['taskType'] ?? '',
            'task_status' => $_POST['taskStatus'] ?? 'Not Started',
            'task_priority' => $_POST['taskPriority'] ?? 'Medium',
            'task_link' => $_POST['taskLink'] ?? null,
            'start_date' => !empty($_POST['startDate']) ? $_POST['startDate'] : null,
            'due_date' => !empty($_POST['dueDate']) ? $_POST['dueDate'] : null,
            'completion_date' => !empty($_POST['completionDate']) ? $_POST['completionDate'] : null,
            'assigned_to' => $_POST['assignedTo'] ?? null,
            'modified_by' => $_POST['modifiedBy'] ?? null,
            'estimated_hours' => !empty($_POST['estimatedHours']) ? $_POST['estimatedHours'] : null,
            'actual_hours' => !empty($_POST['actualHours']) ? $_POST['actualHours'] : null,
            'notes' => $_POST['notes'] ?? null
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Task updated successfully'
        ]);
        
    }  elseif ($action === 'update_task_status') {
        // ✅ NEW HANDLER - Quick status update
        $stmt = $conn->prepare("UPDATE tasks SET task_status = :task_status, modified_date = CURRENT_TIMESTAMP WHERE task_id = :task_id");
        
        $stmt->execute([
            'task_id' => $_POST['taskId'] ?? '',
            'task_status' => $_POST['taskStatus'] ?? 'Not Started'
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Task status updated successfully'
        ]);
        
    }
    
    elseif ($action === 'delete_task') {
        // Delete task
        $stmt = $conn->prepare("DELETE FROM tasks WHERE task_id = :task_id");
        $stmt->execute(['task_id' => $_POST['taskId'] ?? '']);
        
        echo json_encode([
            'success' => true,
            'message' => 'Task deleted successfully'
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
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>