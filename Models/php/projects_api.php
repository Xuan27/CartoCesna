<?php
/**
 * Projects API - Load and save professional and personal projects
 * Implements role-based access control:
 * - admin: All access (personal + professional + survey projects)
 * - enterprise: All projects (personal + professional + survey projects link)
 * - professional: Personal + professional projects (NO survey projects link)
 * - personal: Only personal projects
 */

require_once __DIR__ . '/../../classes/Env.php';
require_once __DIR__ . '/../../classes/Security.php';
require_once __DIR__ . '/../../Private/db_config.php';

// Initialize secure session
Security::secureSession();

header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

$userId = $_SESSION['user_id'] ?? null;

// Close session to release lock
session_write_close();

try {
    $db = new Database();
    $conn = $db->getConnection();

    // Get user type
    $stmt = $conn->prepare("SELECT user_type, role FROM users WHERE id = :user_id");
    $stmt->execute(['user_id' => $userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    $userType = $user['user_type'] ?? 'personal';
    $userRole = $user['role'] ?? '';

    // Fallback for users without user_type set
    if (!$userType || $userType === 'personal') {
        if ($userRole === 'admin') {
            $userType = 'admin';
        } elseif ($userRole === 'enterprise_user') {
            $userType = 'enterprise';
        } elseif ($userRole === 'professional_user' || $userRole === 'surveyor_ww') {
            $userType = 'professional';
        }
    }

    $action = $_REQUEST['action'] ?? '';

    switch ($action) {
        case 'get_projects':
            getProjects($conn, $userType, $userId);
            break;

        case 'get_project':
            getProject($conn, $userType, $userId, $_REQUEST['project_id'] ?? '');
            break;

        case 'save_project':
            saveProject($conn, $userType, $userId);
            break;

        case 'delete_project':
            deleteProject($conn, $userType, $userId, $_REQUEST['project_id'] ?? '');
            break;

        case 'get_user_access':
            getUserAccess($userType);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }

} catch (PDOException $e) {
    error_log("Projects API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
} catch (Exception $e) {
    error_log("Projects API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred']);
}

/**
 * Get user access permissions based on user type
 */
function getUserAccess($userType) {
    $access = [
        'user_type' => $userType,
        'can_view_personal' => true,
        'can_view_professional' => in_array($userType, ['admin', 'enterprise', 'professional']),
        'can_view_survey_projects' => in_array($userType, ['admin', 'enterprise']),
        'can_create_projects' => in_array($userType, ['admin', 'enterprise', 'professional']),
        'can_edit_all' => in_array($userType, ['admin']),
    ];

    echo json_encode(['success' => true, 'access' => $access]);
}

/**
 * Get all projects based on user type
 */
function getProjects($conn, $userType, $userId) {
    $projects = [
        'personal' => [],
        'professional' => []
    ];

    // Personal projects - fetch based on is_public or all for admin
    // Note: personal_projects table may not have created_by column
    try {
        if ($userType === 'admin') {
            // Admin can see all personal projects
            $stmt = $conn->query("SELECT * FROM personal_projects ORDER BY updated_at DESC");
        } else {
            // Others see only public personal projects
            $stmt = $conn->query("SELECT * FROM personal_projects WHERE is_public = 1 ORDER BY updated_at DESC");
        }
        $projects['personal'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching personal projects: " . $e->getMessage());
        $projects['personal'] = [];
    }

    // Professional projects - based on user type
    if (in_array($userType, ['admin', 'enterprise', 'professional'])) {
        try {
            $visibilityCondition = '';

            if ($userType === 'professional') {
                // Professional users can see 'professional' and 'all' visibility
                $visibilityCondition = "WHERE visibility IN ('professional', 'all') AND is_active = 1";
            } elseif ($userType === 'enterprise') {
                // Enterprise users can see all except admin-only
                $visibilityCondition = "WHERE visibility IN ('enterprise_only', 'professional', 'all') AND is_active = 1";
            } else {
                // Admin can see everything
                $visibilityCondition = "WHERE 1=1";
            }

            $stmt = $conn->query("
                SELECT * FROM professional_projects
                $visibilityCondition
                ORDER BY updated_at DESC
            ");
            $projects['professional'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching professional projects: " . $e->getMessage());
            $projects['professional'] = [];
        }
    }

    // Parse JSON fields
    foreach (['personal', 'professional'] as $type) {
        foreach ($projects[$type] as &$project) {
            // Handle technologies (professional) or technologies_used (personal)
            if (isset($project['technologies']) && is_string($project['technologies'])) {
                $project['technologies'] = json_decode($project['technologies'], true) ?? [];
            }
            if (isset($project['technologies_used']) && is_string($project['technologies_used'])) {
                $project['technologies_used'] = json_decode($project['technologies_used'], true) ?? [];
            }
            if (isset($project['tags']) && is_string($project['tags'])) {
                $project['tags'] = json_decode($project['tags'], true) ?? [];
            }
        }
    }

    echo json_encode([
        'success' => true,
        'projects' => $projects,
        'user_type' => $userType,
        'can_view_survey' => in_array($userType, ['admin', 'enterprise'])
    ]);
}

/**
 * Get single project by ID
 */
function getProject($conn, $userType, $userId, $projectId) {
    if (empty($projectId)) {
        echo json_encode(['success' => false, 'message' => 'Project ID required']);
        return;
    }

    $projectType = $_REQUEST['project_type'] ?? 'professional';
    $table = $projectType === 'personal' ? 'personal_projects' : 'professional_projects';

    $stmt = $conn->prepare("SELECT * FROM $table WHERE project_id = :project_id");
    $stmt->execute(['project_id' => $projectId]);
    $project = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$project) {
        echo json_encode(['success' => false, 'message' => 'Project not found']);
        return;
    }

    // Parse JSON fields
    if (isset($project['technologies']) && is_string($project['technologies'])) {
        $project['technologies'] = json_decode($project['technologies'], true) ?? [];
    }
    if (isset($project['tags']) && is_string($project['tags'])) {
        $project['tags'] = json_decode($project['tags'], true) ?? [];
    }

    echo json_encode(['success' => true, 'project' => $project]);
}

/**
 * Save project (create or update)
 */
function saveProject($conn, $userType, $userId) {
    if (!in_array($userType, ['admin', 'enterprise', 'professional'])) {
        echo json_encode(['success' => false, 'message' => 'Insufficient permissions']);
        return;
    }

    $newProjectType = $_POST['project_type'] ?? 'professional';
    $projectId = $_POST['project_id'] ?? '';
    $isNew = empty($projectId) || ($_POST['is_new'] ?? false);

    // If editing an existing project, check if we need to move it between tables
    if (!$isNew && !empty($projectId)) {
        // Check which table the project currently exists in
        $currentTable = null;

        $stmt = $conn->prepare("SELECT project_id FROM professional_projects WHERE project_id = :project_id");
        $stmt->execute(['project_id' => $projectId]);
        if ($stmt->fetch()) {
            $currentTable = 'professional';
        } else {
            $stmt = $conn->prepare("SELECT project_id FROM personal_projects WHERE project_id = :project_id");
            $stmt->execute(['project_id' => $projectId]);
            if ($stmt->fetch()) {
                $currentTable = 'personal';
            }
        }

        // If the project type is changing, delete from old table and insert into new
        if ($currentTable && $currentTable !== $newProjectType) {
            // Delete from old table
            $oldTable = $currentTable === 'professional' ? 'professional_projects' : 'personal_projects';
            $stmt = $conn->prepare("DELETE FROM $oldTable WHERE project_id = :project_id");
            $stmt->execute(['project_id' => $projectId]);

            // Treat as new project for the new table (but keep the project_id)
            $isNew = true;
            $_POST['keep_project_id'] = true;
        }
    }

    if ($newProjectType === 'personal') {
        savePersonalProject($conn, $userId, $isNew);
    } else {
        saveProfessionalProject($conn, $userId, $userType, $isNew);
    }
}

/**
 * Save personal project
 */
function savePersonalProject($conn, $userId, $isNew) {
    // Handle technologies - already JSON encoded from client or needs encoding
    $technologies = null;
    if (!empty($_POST['technologies'])) {
        $techValue = $_POST['technologies'];
        if (is_string($techValue) && (json_decode($techValue) !== null || $techValue === '[]')) {
            $technologies = $techValue;
        } else {
            $technologies = json_encode($techValue);
        }
    }

    // Handle tags similarly
    $tags = null;
    if (!empty($_POST['tags'])) {
        $tagValue = $_POST['tags'];
        if (is_string($tagValue) && (json_decode($tagValue) !== null || $tagValue === '[]')) {
            $tags = $tagValue;
        } else {
            $tags = json_encode($tagValue);
        }
    }

    // Map project_type to valid personal project types
    // Personal valid types: web_development, mobile_app, data_science, machine_learning, game_development, automation, experiment, learning, other
    $personalProjectTypes = ['web_development', 'mobile_app', 'data_science', 'machine_learning', 'game_development', 'automation', 'experiment', 'learning', 'other'];
    $projectTypeCategory = $_POST['project_type_category'] ?? 'other';

    // Map professional types to personal types or default to 'other'
    $typeMapping = [
        'development' => 'web_development',
        'research' => 'experiment',
        'internal' => 'other',
        'consulting' => 'other',
        'collaboration' => 'other'
    ];

    if (isset($typeMapping[$projectTypeCategory])) {
        $projectTypeCategory = $typeMapping[$projectTypeCategory];
    } elseif (!in_array($projectTypeCategory, $personalProjectTypes)) {
        $projectTypeCategory = 'other';
    }

    // Map status to valid personal project statuses
    // Personal valid statuses: completed, in_progress, on_hold, idea, abandoned
    $statusMapping = [
        'planning' => 'idea',
        'review' => 'in_progress',
        'cancelled' => 'abandoned'
    ];
    $status = $_POST['status'] ?? 'idea';
    if (isset($statusMapping[$status])) {
        $status = $statusMapping[$status];
    }

    $data = [
        'title' => $_POST['title'] ?? '',
        'subtitle' => $_POST['subtitle'] ?? null,
        'description' => $_POST['description'] ?? null,
        'category' => $_POST['category'] ?? null,
        'project_type' => $projectTypeCategory,
        'status' => $status,
        'priority' => $_POST['priority'] ?? 'medium',
        'start_date' => $_POST['start_date'] ?: null,
        'end_date' => $_POST['end_date'] ?: null,
        'project_url' => $_POST['project_url'] ?? null,
        'github_url' => $_POST['github_url'] ?? null,
        'technologies_used' => $technologies,
        'tags' => $tags,
        'notes' => $_POST['notes'] ?? null,
        'is_public' => isset($_POST['is_public']) ? 1 : 0,
    ];

    try {
        if ($isNew) {
            // Keep existing project_id if moving between tables, otherwise generate new
            if (!empty($_POST['keep_project_id']) && !empty($_POST['project_id'])) {
                $data['project_id'] = $_POST['project_id'];
            } else {
                $data['project_id'] = 'PERS-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            }
            $data['created_by'] = $userId;

            $columns = implode(', ', array_keys($data));
            $placeholders = ':' . implode(', :', array_keys($data));

            $stmt = $conn->prepare("INSERT INTO personal_projects ($columns) VALUES ($placeholders)");
            $stmt->execute($data);

            $message = !empty($_POST['keep_project_id']) ? 'Project moved to Personal' : 'Project created';
            echo json_encode(['success' => true, 'message' => $message, 'project_id' => $data['project_id']]);
        } else {
            $projectId = $_POST['project_id'];
            $setClauses = [];
            foreach ($data as $key => $value) {
                $setClauses[] = "$key = :$key";
            }
            $setString = implode(', ', $setClauses);
            $data['project_id'] = $projectId;

            $stmt = $conn->prepare("UPDATE personal_projects SET $setString WHERE project_id = :project_id");
            $stmt->execute($data);

            echo json_encode(['success' => true, 'message' => 'Project updated']);
        }
    } catch (PDOException $e) {
        error_log("Save Personal Project Error: " . $e->getMessage());
        error_log("SQL Error Code: " . $e->getCode());
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

/**
 * Save professional project
 */
function saveProfessionalProject($conn, $userId, $userType, $isNew) {
    // Handle technologies - already JSON encoded from client or needs encoding
    $technologies = null;
    if (!empty($_POST['technologies'])) {
        $techValue = $_POST['technologies'];
        // Check if it's already a JSON string
        if (is_string($techValue) && (json_decode($techValue) !== null || $techValue === '[]')) {
            $technologies = $techValue;
        } else {
            $technologies = json_encode($techValue);
        }
    }

    // Handle tags similarly
    $tags = null;
    if (!empty($_POST['tags'])) {
        $tagValue = $_POST['tags'];
        if (is_string($tagValue) && (json_decode($tagValue) !== null || $tagValue === '[]')) {
            $tags = $tagValue;
        } else {
            $tags = json_encode($tagValue);
        }
    }

    $data = [
        'title' => $_POST['title'] ?? '',
        'subtitle' => $_POST['subtitle'] ?? null,
        'description' => $_POST['description'] ?? null,
        'detailed_description' => $_POST['detailed_description'] ?? null,
        'project_type' => $_POST['project_type_category'] ?? 'development',
        'category' => $_POST['category'] ?? null,
        'status' => $_POST['status'] ?? 'planning',
        'priority' => $_POST['priority'] ?? 'medium',
        'start_date' => $_POST['start_date'] ?: null,
        'due_date' => $_POST['due_date'] ?: null,
        'completed_date' => $_POST['completed_date'] ?: null,
        'technologies' => $technologies,
        'tags' => $tags,
        'project_url' => $_POST['project_url'] ?? null,
        'repository_url' => $_POST['repository_url'] ?? null,
        'documentation_url' => $_POST['documentation_url'] ?? null,
        'project_folder_link' => $_POST['project_folder_link'] ?? null,
        'visibility' => $_POST['visibility'] ?? 'professional',
        'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
        'estimated_hours' => $_POST['estimated_hours'] ?: null,
        'actual_hours' => $_POST['actual_hours'] ?: null,
        'notes' => $_POST['notes'] ?? null,
        'modified_by' => $userId,
    ];

    try {
        if ($isNew) {
            // Keep existing project_id if moving between tables, otherwise generate new
            if (!empty($_POST['keep_project_id']) && !empty($_POST['project_id'])) {
                $data['project_id'] = $_POST['project_id'];
            } else {
                $data['project_id'] = 'PROF-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            }
            $data['created_by'] = $userId;

            $columns = implode(', ', array_keys($data));
            $placeholders = ':' . implode(', :', array_keys($data));

            $stmt = $conn->prepare("INSERT INTO professional_projects ($columns) VALUES ($placeholders)");
            $stmt->execute($data);

            $message = !empty($_POST['keep_project_id']) ? 'Project moved successfully' : 'Project created';
            echo json_encode(['success' => true, 'message' => $message, 'project_id' => $data['project_id']]);
        } else {
            $projectId = $_POST['project_id'];
            $setClauses = [];
            foreach ($data as $key => $value) {
                $setClauses[] = "$key = :$key";
            }
            $setString = implode(', ', $setClauses);
            $data['project_id'] = $projectId;

            $stmt = $conn->prepare("UPDATE professional_projects SET $setString WHERE project_id = :project_id");
            $stmt->execute($data);

            echo json_encode(['success' => true, 'message' => 'Project updated']);
        }
    } catch (PDOException $e) {
        error_log("Save Professional Project Error: " . $e->getMessage());
        error_log("SQL Error Code: " . $e->getCode());
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

/**
 * Delete project
 */
function deleteProject($conn, $userType, $userId, $projectId) {
    if (!in_array($userType, ['admin', 'enterprise'])) {
        echo json_encode(['success' => false, 'message' => 'Insufficient permissions']);
        return;
    }

    $projectType = $_REQUEST['project_type'] ?? 'professional';
    $table = $projectType === 'personal' ? 'personal_projects' : 'professional_projects';

    $stmt = $conn->prepare("DELETE FROM $table WHERE project_id = :project_id");
    $stmt->execute(['project_id' => $projectId]);

    echo json_encode(['success' => true, 'message' => 'Project deleted']);
}
?>
