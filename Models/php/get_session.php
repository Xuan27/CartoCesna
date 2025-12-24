<?php
session_start();
header('Content-Type: application/json');

// Return specific session data
$response = [
    'success' => true,
    'data' => [
        'isLoggedIn' => isset($_SESSION['user_id']),
        'userId' => $_SESSION['user_id'] ?? null,
        'userName' => $_SESSION['username'] ?? '',
        'rootPage' => $_SESSION['root_page'] ?? '/CartoCesna/index.php',
    ]
];

echo json_encode($response);
?>