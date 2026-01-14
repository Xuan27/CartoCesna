<?php
// dashboard.php - Secure dashboard router
require_once __DIR__ . '/classes/Env.php';
require_once __DIR__ . '/classes/Security.php';

// Load environment and configure error reporting
Env::load();
if (Env::isDebug()) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}
ini_set('log_errors', 1);

// Debug logging (only in debug mode)
function debugLog($message) {
    if (Env::isDebug()) {
        error_log("[DASHBOARD DEBUG] " . $message);
    }
}

// Initialize secure session and headers
Security::secureSession();
Security::setSecurityHeaders();
Security::enforceHttps();

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

// Check if user is logged in
$isLoggedIn = $auth->isLoggedIn();
debugLog("isLoggedIn() returns: " . ($isLoggedIn ? 'TRUE' : 'FALSE'));

// Prevent redirect loop - check if we've been here before
if (!$isLoggedIn) {
    // Check if this is a redirect loop
    if (isset($_SESSION['dashboard_redirect_count'])) {
        $_SESSION['dashboard_redirect_count']++;
        if ($_SESSION['dashboard_redirect_count'] > 2) {
            debugLog("REDIRECT LOOP DETECTED - clearing session");
            session_unset();
            session_destroy();
            die("Redirect loop detected. Session cleared. Please <a href='login.php'>login again</a>.");
        }
    } else {
        $_SESSION['dashboard_redirect_count'] = 1;
    }
    
    debugLog("User not logged in, redirecting to login.php");

    $loginUrl = $_SESSION['root_page'] . 'login.php';
    debugLog("Login URL: " . $loginUrl);
    header('Location: ' . $loginUrl);
    exit();
}

// Clear redirect counter on successful auth
unset($_SESSION['dashboard_redirect_count']);

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

// Route based on user role
if ($userRole === 'admin') {
    // Admin can access both - default to professional
    debugLog("Admin user - redirecting to professional dashboard");
    header('Location: dashboards/professional_dashboard.php');
    exit();
} elseif ($userRole === 'personal_user') {
    debugLog("Personal user - redirecting to personal dashboard");
    header('Location: dashboards/personal_dashboard.php');
    exit();
} elseif ($userRole === 'professional_user') {
    debugLog("Professional user - redirecting to professional dashboard");
    header('Location: dashboards/professional_dashboard.php');
    exit();
} else {
    // Unknown role
    debugLog("Unknown role: " . $userRole);
    $_SESSION['error_message'] = 'Invalid user role. Please contact administrator.';
    header('Location: ' . ($_SESSION['root_page'] ?? '/') . 'login.php');
    exit();
}
?>