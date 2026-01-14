<?php
// Models/php/notifications_stream.php - Server-Sent Events endpoint for real-time notifications
session_start();

require_once '../../classes/Auth.php';

// Check if user is admin
$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    exit();
}

$userRole = $auth->getUserRole($_SESSION['user_id']);
if ($userRole !== 'admin') {
    http_response_code(403);
    exit();
}

// Set headers for SSE
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no'); // Disable buffering in nginx

// Function to send SSE message
function sendSSE($id, $data) {
    echo "id: $id\n";
    echo "data: " . json_encode($data) . "\n\n";
    ob_flush();
    flush();
}

require_once '../../Private/db_config.php';
$db = new Database();
$conn = $db->getConnection();

// Track last check time
$lastCheckFile = sys_get_temp_dir() . '/notification_last_check_' . $_SESSION['user_id'] . '.txt';
$lastCheck = file_exists($lastCheckFile) ? file_get_contents($lastCheckFile) : date('Y-m-d H:i:s');

// Keep connection alive and check for new pending users
$lastCount = -1;
$eventId = 0;

while (true) {
    // Check if connection is still alive
    if (connection_aborted()) {
        break;
    }
    
    try {
        // Get current count of pending users
        $query = "SELECT COUNT(*) as count FROM users WHERE is_verified = 0 AND is_active = 1";
        $stmt = $conn->query($query);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $currentCount = (int)$result['count'];
        
        // Check for new users since last check
        $newUsersQuery = "SELECT id, username, email, role, created_at 
                          FROM users 
                          WHERE is_verified = 0 
                          AND is_active = 1 
                          AND created_at > :last_check 
                          ORDER BY created_at DESC";
        $newUsersStmt = $conn->prepare($newUsersQuery);
        $newUsersStmt->execute(['last_check' => $lastCheck]);
        $newUsers = $newUsersStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // If count changed or new users found, send notification
        if ($currentCount !== $lastCount || !empty($newUsers)) {
            $eventId++;
            
            $data = [
                'type' => 'pending_users_update',
                'count' => $currentCount,
                'timestamp' => time(),
                'new_users' => $newUsers
            ];
            
            sendSSE($eventId, $data);
            
            $lastCount = $currentCount;
            $lastCheck = date('Y-m-d H:i:s');
            file_put_contents($lastCheckFile, $lastCheck);
        }
        
        // Send heartbeat every 15 seconds to keep connection alive
        if ($eventId % 3 === 0) {
            sendSSE($eventId, [
                'type' => 'heartbeat',
                'timestamp' => time()
            ]);
        }
        
    } catch (Exception $e) {
        error_log("SSE Error: " . $e->getMessage());
        sendSSE($eventId, [
            'type' => 'error',
            'message' => 'Connection error'
        ]);
        break;
    }
    
    // Wait 5 seconds before next check
    sleep(5);
}
?>