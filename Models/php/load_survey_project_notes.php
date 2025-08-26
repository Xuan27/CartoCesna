<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Error reporting for development (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in response
ini_set('log_errors', 1);     // Log errors to file

function sendJsonResponse($data) {
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
    
    if ($_POST['action'] === 'load_project') {
        try {
            // Use the correct column name from your database schema
            $sql = "SELECT * FROM survey_projects ORDER BY created_date DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            
            $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Transform database field names to match JavaScript expectations
            $transformedProjects = array_map(function($project) {
                return [
                    'projectId' => $project['project_id'],
                    'projectName' => $project['project_name'],
                    'projectStatus' => $project['project_status'],
                    'createdBy' => $project['created_by'],
                    'createdDate' => $project['created_date'],
                    'projectFolderLink' => $project['project_folder_link'],
                    'surveyFolderLink' => $project['survey_folder_link'],
                    'drawingFolderLink' => $project['drawing_folder_link'],
                    'contractLink' => $project['contract_link'],
                    'qaQcFolderLink' => $project['qaQc_folder_link'],
                    'researchFolderLink' => $project['research_folder_link'],
                    'notes' => $project['notes'],
                    // Additional fields from your database
                    'fieldFolderLink' => $project['field_folder_link'],
                    'modifiedDate' => $project['modified_date'],
                    'modifiedBy' => $project['modified_by']
                ];
            }, $projects);
            
            sendJsonResponse([
                'success' => true,
                'projects' => $transformedProjects,
                'count' => count($transformedProjects)
            ]);
            
        } catch(PDOException $e) {
            error_log("Database query error: " . $e->getMessage());
            sendJsonResponse([
                'success' => false,
                'message' => 'Database error occurred'
            ]);
        }
        
    } else {
        sendJsonResponse([
            'success' => false,
            'message' => 'Invalid action parameter'
        ]);
    }
    
} catch(Exception $e) {
    error_log("Server error: " . $e->getMessage());
    sendJsonResponse([
        'success' => false,
        'message' => 'Server error occurred'
    ]);
}
?>