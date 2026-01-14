<?php
// Suppress error output to prevent JSON corruption
error_reporting(0);
ini_set('display_errors', 0);

// Set JSON header first
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Start session safely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Read session data
$response = [
    'success' => true,
    'data' => [
        'isLoggedIn' => isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true,
        'userId' => $_SESSION['user_id'] ?? null,
        'userName' => $_SESSION['username'] ?? '',
        'rootPage' => $_SESSION['root_page'] ?? '/',
    ]
];

// Close session immediately to release lock
session_write_close();

try {
    echo json_encode($response);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Session error'
    ]);
}