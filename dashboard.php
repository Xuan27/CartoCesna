<?php
// dashboard.php - Protected page that requires authentication
session_start();

require_once 'classes/Auth.php';

$auth = new Auth();

// Check if user is logged in
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit();
}

// Handle logout
if (isset($_POST['logout'])) {
    $auth->logout();
    header('Location: login.php');
    exit();
}

// Get user role
$userRole = $auth->getUserRole($_SESSION['user_id']);

//If user is not a professional user, redirect to personal dashboard
if($userRole == 'personal_user'){
    // Redirect non-professional users to a different page
    header('Location: dashboards/personal_dashboard.php');
    exit();
}
else{
    // Load dashboard content for professional users
    header('Location: dashboards/professional_dashboard.php');
    exit();
}
?>
