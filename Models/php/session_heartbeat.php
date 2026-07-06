<?php
// Keeps the PHP session alive while a page is open in the background —
// Auth::isLoggedIn() both refreshes last_activity and writes to $_SESSION,
// which resets the file's mtime so PHP's session GC won't reap it.
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

require_once __DIR__ . '/../../classes/Auth.php';

try {
    $auth = new Auth();
    $loggedIn = $auth->isLoggedIn();
    echo json_encode(['success' => true, 'loggedIn' => $loggedIn]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'loggedIn' => false]);
}
