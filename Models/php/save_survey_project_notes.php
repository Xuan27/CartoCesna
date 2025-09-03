<?php

require_once '../../Private/db_config.php';

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $db = new Database();
    $pdo = $db->getConnection();
    
    if ($_POST['action'] === 'add_project') {
        try {
            $sql = "INSERT INTO survey_projects (project_id, project_name, project_folder_link, survey_folder_link, drawing_folder_link, field_folder_link, contract_link, qaQc_folder_link, research_folder_link, created_by, project_status, location, scale_factor) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([
                $_POST['projectId'],
                $_POST['projectName'],
                $_POST['projectFolderLink'],
                $_POST['surveyFolderLink'],
                $_POST['drawingFolderLink'],
                $_POST['fieldFolderLink'],
                $_POST['contractLink'],
                $_POST['qaQcFolderLink'],
                $_POST['researchFolderLink'],
                $_POST['createdBy'],
                $_POST['projectStatus'],
                $_POST['location'],
                $_POST['scale_factor']
            ]);
            
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Project added successfully!']);
            }
        } catch(PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }
    
    if ($_POST['action'] === 'add_control_point') {
        try {
            $sql = "INSERT INTO project_control_points (
                project_id, point_name, point_type, elevation, coordinate_system,
                accuracy_class, point_wkt, latitude, longitude, notes
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([
                $_POST['project_id'],
                $_POST['point_name'],
                $_POST['point_type'],
                $_POST['elevation'],
                $_POST['coordinate_system'],
                $_POST['accuracy_class'],
                "POINT(" . $_POST['longitude'] . " " . $_POST['latitude'] . ")",
                $_POST['latitude'],
                $_POST['longitude'],
                $_POST['notes']
            ]);
            
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Control point added successfully!']);
            }
        } catch(PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }
    
    if ($_POST['action'] === 'get_projects') {
        try {
            $sql = "SELECT sp.*, COUNT(pcp.control_point_id) as control_point_count 
                    FROM survey_projects sp 
                    LEFT JOIN project_control_points pcp ON sp.project_id = pcp.project_id 
                    GROUP BY sp.project_id 
                    ORDER BY sp.created_date DESC";
            $stmt = $pdo->query($sql);
            $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($projects);
        } catch(PDOException $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }
}

// Get existing projects for dropdown
$db = new Database();
$pdo = $db->connect();
$projectsForDropdown = [];
try {
    $stmt = $pdo->query("SELECT project_id, project_name FROM survey_projects ORDER BY project_name");
    $projectsForDropdown = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    // Handle error silently for initial page load
}
?>