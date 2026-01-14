<?php
// Models/php/trigger_notification.php - Trigger notification when new user registers
header('Content-Type: application/json');

// This endpoint is called after a new user registers
// It creates a temporary flag file that SSE will detect

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['user_id']) || !isset($input['username'])) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit();
}

try {
    // Create notification flag file
    $flagFile = sys_get_temp_dir() . '/new_user_notification_' . time() . '.flag';
    $notificationData = [
        'user_id' => $input['user_id'],
        'username' => $input['username'],
        'email' => $input['email'] ?? '',
        'timestamp' => time()
    ];

    // After creating notification flag:
    mail(
        'jmshebar259@gmail.com',
        'New User Registration',
        "User $username needs verification"
    );
    
    file_put_contents($flagFile, json_encode($notificationData));
    
    // Optional: Log the notification
    error_log("New user registration notification: User ID " . $input['user_id'] . " - " . $input['username']);
    
    echo json_encode([
        'success' => true,
        'message' => 'Notification triggered'
    ]);
    
} catch (Exception $e) {
    error_log("Error triggering notification: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>