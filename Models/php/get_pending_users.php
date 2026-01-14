<?php
// get_pending_users.php - Fetch users pending verification

// Suppress errors in output
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

try {
    require_once __DIR__ . '/../../classes/Security.php';
    require_once __DIR__ . '/../../classes/Auth.php';
    require_once __DIR__ . '/../../Private/db_config.php';
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server configuration error']);
    exit;
}

Security::secureSession();

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

// Close session to release lock
session_write_close();

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Get users where is_verified = 0 or is_active = 0
    $query = "SELECT id, username, email, first_name, last_name, role, created_at
              FROM users
              WHERE is_verified = 0 OR is_active = 0
              ORDER BY created_at DESC";

    $stmt = $conn->prepare($query);
    $stmt->execute();

    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'count' => count($users),
        'users' => $users
    ]);

} catch (Exception $e) {
    error_log("Error fetching pending users: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
