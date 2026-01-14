<?php
// verify_user.php - Approve or reject user registration
header('Content-Type: application/json');

require_once __DIR__ . '/../../classes/Security.php';
require_once __DIR__ . '/../../classes/Auth.php';
require_once __DIR__ . '/../../Private/db_config.php';

Security::secureSession();
Security::setCorsHeaders();

// Check if user is logged in and is admin
$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$userRole = $auth->getUserRole($_SESSION['user_id'] ?? null);
if ($userRole !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['user_id']) || !isset($input['action'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$userId = (int)$input['user_id'];
$action = $input['action'];

if (!in_array($action, ['approve', 'reject'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit;
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    if ($action === 'approve') {
        // Activate and verify the user
        $query = "UPDATE users SET is_active = 1, is_verified = 1, updated_at = CURRENT_TIMESTAMP WHERE id = :id";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'User approved successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'User not found']);
        }

    } else {
        // Reject - delete the user
        $query = "DELETE FROM users WHERE id = :id AND (is_verified = 0 OR is_active = 0)";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'User rejected and removed']);
        } else {
            echo json_encode(['success' => false, 'message' => 'User not found or already verified']);
        }
    }

} catch (Exception $e) {
    error_log("Error verifying user: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
