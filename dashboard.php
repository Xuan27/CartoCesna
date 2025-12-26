<?php
// dashboard.php - Improved with debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Debug logging
function debugLog($message) {
    error_log("[DASHBOARD DEBUG] " . $message);
}

debugLog("Dashboard accessed");

session_start();
debugLog("Session started - ID: " . session_id());

// Set root page if not set
if (!isset($_SESSION['root_page'])) {
    $scriptPath = $_SERVER['SCRIPT_NAME'];
    $pathParts = explode('/', $scriptPath);
    array_pop($pathParts);
    $rootPath = implode('/', $pathParts);
    
    if (empty($rootPath) || $rootPath === '') {
        $rootPath = '/';
    } else {
        if (strpos($rootPath, '/') !== 0) {
            $rootPath = '/' . $rootPath;
        }
        if (substr($rootPath, -1) !== '/') {
            $rootPath .= '/';
        }
    }
    
    $_SESSION['root_page'] = $rootPath;
}

debugLog("Root page: " . $_SESSION['root_page']);

require_once 'classes/Auth.php';

$auth = new Auth();

// Debug session data
debugLog("Session data: " . json_encode($_SESSION));
debugLog("Session logged_in: " . (isset($_SESSION['logged_in']) ? ($_SESSION['logged_in'] ? 'TRUE' : 'FALSE') : 'NOT SET'));
debugLog("Session user_id: " . ($_SESSION['user_id'] ?? 'NOT SET'));

// Check if user is logged in
$isLoggedIn = $auth->isLoggedIn();
debugLog("isLoggedIn() returns: " . ($isLoggedIn ? 'TRUE' : 'FALSE'));

if (!$isLoggedIn) {
    debugLog("User not logged in, redirecting to login.php");
    
    // Store debug info in session for display
    $_SESSION['debug_redirect'] = [
        'from' => 'dashboard.php',
        'reason' => 'Not logged in',
        'session_data' => $_SESSION
    ];
    
    $loginUrl = $_SESSION['root_page'] . 'login.php';
    debugLog("Login URL: " . $loginUrl);
    header('Location: ' . $loginUrl);
    exit();
}

debugLog("User is logged in, showing dashboard");

// Handle logout
if (isset($_POST['logout'])) {
    debugLog("Logout requested");
    $auth->logout();
    $loginUrl = $_SESSION['root_page'] . 'login.php';
    header('Location: ' . $loginUrl);
    exit();
}

// Get user role
$userRole = $auth->getUserRole($_SESSION['user_id']);
debugLog("User role: " . ($userRole ?: 'NOT SET'));

//If user is not a professional user, redirect to personal dashboard
if($userRole == 'personal_user'){
    debugLog("Redirecting to personal dashboard");
    header('Location: dashboards/personal_dashboard.php');
    exit();
}
else{
    debugLog("Redirecting to professional dashboard");
    header('Location: dashboards/professional_dashboard.php');
    exit();
}
?>