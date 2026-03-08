<?php
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in response
ini_set('log_errors', 1); // Log errors to file

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
    require_once __DIR__ . '/olc_helper.php';
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
                    // Additional fields from your database
                    'fieldFolderLink' => $project['field_folder_link'],
                    'modifiedDate' => $project['modified_date'],
                    'modifiedBy' => $project['modified_by'],
                    'location' => $project['location'],
                    'plus_code' => $project['plus_code'],
                    'scale_factor' => $project['scale_factor'],
                    'needs_monuments' => (int)$project['needs_monuments'],
                    'latitude'  => $project['latitude']  !== null ? (float)$project['latitude']  : null,
                    'longitude' => $project['longitude'] !== null ? (float)$project['longitude'] : null
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
    } 
    elseif ($_POST['action'] === 'update_project') {
        try {
            // Validate required fields
            if (!isset($_POST['projectId']) || empty($_POST['projectId'])) {
                sendJsonResponse([
                    'success' => false,
                    'message' => 'Project ID is required'
                ]);
            }

            $projectId = $_POST['projectId'];
            
            // Build dynamic SQL update query based on provided fields
            $updateFields = [];
            $params = [':project_id' => $projectId];
            
            // Map JavaScript field names to database column names
            $fieldMapping = [
                'projectName' => 'project_name',
                'projectStatus' => 'project_status',
                'projectFolderLink' => 'project_folder_link',
                'surveyFolderLink' => 'survey_folder_link',
                'drawingFolderLink' => 'drawing_folder_link',
                'contractLink' => 'contract_link',
                'qaQcFolderLink' => 'qaQc_folder_link',
                'researchFolderLink' => 'research_folder_link',
                'modifiedBy' => 'modified_by',
                'location' => 'location',
                'plus_code' => 'plus_code',
                'scale_factor' => 'scale_factor',
                'needs_monuments' => 'needs_monuments'
            ];

            // Check which fields are being updated
            foreach ($fieldMapping as $jsField => $dbField) {
                if (isset($_POST[$jsField])) {
                    $updateFields[] = "$dbField = :$dbField";
                    $params[":$dbField"] = $_POST[$jsField];
                }
            }

            // Always update modified_date
            $updateFields[] = "modified_date = NOW()";

            if (empty($updateFields)) {
                sendJsonResponse([
                    'success' => false,
                    'message' => 'No fields to update'
                ]);
            }

            // Build and execute the update query
            $sql = "UPDATE survey_projects SET " . implode(', ', $updateFields) . " WHERE project_id = :project_id";
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute($params);

            if ($result && $stmt->rowCount() > 0) {
                // Expand plus code to full OLC and store lat/lon if plus_code changed
                if (!empty($_POST['plus_code'])) {
                    $decoded = olcProcessRaw($pdo, $_POST['plus_code']);
                    if ($decoded) {
                        $pdo->prepare("UPDATE survey_projects SET plus_code = :code, latitude = :lat, longitude = :lon WHERE project_id = :id")
                            ->execute([':code' => $decoded['full_code'], ':lat' => $decoded['lat'], ':lon' => $decoded['lon'], ':id' => $projectId]);
                    }
                } elseif (isset($_POST['plus_code']) && $_POST['plus_code'] === '') {
                    // plus_code cleared — clear coordinates too
                    $pdo->prepare("UPDATE survey_projects SET latitude = NULL, longitude = NULL WHERE project_id = :id")
                        ->execute([':id' => $projectId]);
                }

                // Fetch the updated project
                $selectSql = "SELECT * FROM survey_projects WHERE project_id = :project_id";
                $selectStmt = $pdo->prepare($selectSql);
                $selectStmt->execute([':project_id' => $projectId]);
                $updatedProject = $selectStmt->fetch(PDO::FETCH_ASSOC);

                if ($updatedProject) {
                    // Transform the updated project data
                    $transformedProject = [
                        'projectId' => $updatedProject['project_id'],
                        'projectName' => $updatedProject['project_name'],
                        'projectStatus' => $updatedProject['project_status'],
                        'createdBy' => $updatedProject['created_by'],
                        'createdDate' => $updatedProject['created_date'],
                        'projectFolderLink' => $updatedProject['project_folder_link'],
                        'surveyFolderLink' => $updatedProject['survey_folder_link'],
                        'drawingFolderLink' => $updatedProject['drawing_folder_link'],
                        'contractLink' => $updatedProject['contract_link'],
                        'qaQcFolderLink' => $updatedProject['qaQc_folder_link'],
                        'researchFolderLink' => $updatedProject['research_folder_link'],
                        'fieldFolderLink' => $updatedProject['field_folder_link'],
                        'modifiedDate' => $updatedProject['modified_date'],
                        'modifiedBy' => $updatedProject['modified_by'],
                        'location' => $updatedProject['location'],
                        'plus_code' => $updatedProject['plus_code'],
                        'scale_factor' => $updatedProject['scale_factor'],
                        'needs_monuments' => (int)$updatedProject['needs_monuments'],
                        'latitude'  => $updatedProject['latitude']  !== null ? (float)$updatedProject['latitude']  : null,
                        'longitude' => $updatedProject['longitude'] !== null ? (float)$updatedProject['longitude'] : null,
                    ];

                    sendJsonResponse([
                        'success' => true,
                        'message' => 'Project updated successfully',
                        'project' => $transformedProject
                    ]);
                } else {
                    sendJsonResponse([
                        'success' => false,
                        'message' => 'Project not found after update'
                    ]);
                }
            } else {
                sendJsonResponse([
                    'success' => false,
                    'message' => 'No changes made or project not found'
                ]);
            }

        } catch(PDOException $e) {
            error_log("Database update error: " . $e->getMessage());
            sendJsonResponse([
                'success' => false,
                'message' => 'Database error occurred while updating project'
            ]);
        }
    } 
    else {
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