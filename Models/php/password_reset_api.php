<?php
// password_reset_api.php - Admin management of password reset requests
header('Content-Type: application/json');

require_once __DIR__ . '/../../classes/Security.php';
require_once __DIR__ . '/../../classes/Auth.php';
require_once __DIR__ . '/../../classes/PasswordReset.php';

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

if (!$input || !isset($input['action'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// Validate CSRF token (from header or body)
$csrf_token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $input['csrf_token'] ?? '';
if (!Security::validateCsrfToken($csrf_token)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid security token. Please refresh and try again.']);
    exit;
}

$action = $input['action'];

try {
    $passwordReset = new PasswordReset();

    if ($action === 'list_pending') {
        $requests = $passwordReset->listPending();
        echo json_encode([
            'success'  => true,
            'count'    => count($requests),
            'requests' => $requests,
        ]);

    } elseif ($action === 'approve') {
        $userId = (int)($input['user_id'] ?? 0);
        if (!$userId) {
            echo json_encode(['success' => false, 'message' => 'Missing user_id']);
            exit;
        }

        $result = $passwordReset->approve($userId);
        if (!$result) {
            echo json_encode(['success' => false, 'message' => 'Request not found or expired']);
            exit;
        }

        // Build the reset URL: this API lives two directories below the app root
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $rootPath = dirname(dirname(dirname($_SERVER['SCRIPT_NAME'])));
        $rootPath = ($rootPath === '/' || $rootPath === '\\') ? '/' : $rootPath . '/';
        $resetUrl = $scheme . '://' . $_SERVER['HTTP_HOST'] . $rootPath . 'reset-password.php?token=' . $result['token'];

        echo json_encode([
            'success'    => true,
            'message'    => 'Reset link generated',
            'reset_url'  => $resetUrl,
            'expires_at' => $result['expires_at'],
        ], JSON_UNESCAPED_SLASHES);

    } elseif ($action === 'deny') {
        $userId = (int)($input['user_id'] ?? 0);
        if (!$userId) {
            echo json_encode(['success' => false, 'message' => 'Missing user_id']);
            exit;
        }

        if ($passwordReset->deny($userId)) {
            echo json_encode(['success' => true, 'message' => 'Reset request denied']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Request not found']);
        }

    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }

} catch (Exception $e) {
    error_log("Password reset API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error occurred']);
}
