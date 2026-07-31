<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Read session data immediately
$session_root_page = $_SESSION['root_page'] ?? null;
$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'];
$username = isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : '';
$userId = $_SESSION['user_id'] ?? null;

// Close session to release lock (allows other requests to proceed)
session_write_close();

// Determine whether this user has access to the Survey Projects page
$canViewSurveyProjects = false;
if ($isLoggedIn && $userId) {
    try {
        require_once __DIR__ . '/../../classes/Auth.php';
        $auth = new Auth();
        $userRole = $auth->getUserRole($userId);
        $canViewSurveyProjects = in_array($userRole, ['admin', 'surveyor_ww'], true);
    } catch (Exception $e) {
        error_log('header_tabs: unable to resolve user role - ' . $e->getMessage());
        $canViewSurveyProjects = false;
    }
}

// Determine root path for assets
if ($session_root_page && $session_root_page !== '/') {
    $root_page = $session_root_page;
} else {
    // Detect from current script location
    $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
    if (strpos($scriptDir, '/Models/php') !== false) {
        $root_page = str_replace('/Models/php', '', $scriptDir) . '/';
    } elseif (strpos($scriptDir, '/Models') !== false) {
        $root_page = str_replace('/Models', '', $scriptDir) . '/';
    } else {
        $parts = explode('/', trim($_SERVER['SCRIPT_NAME'], '/'));
        $root_page = '/' . $parts[0] . '/';
    }
}
?>
<!-- header-tabs.html - Reusable Header Component -->
<link rel="stylesheet" href="<?php echo $root_page; ?>Models/css/header_tabs.css">

<div class="header-tabs-container">
    <nav class="header-tabs">
        <div class="logo-section">
            Cartocesna
        </div>

        <a href="<?php echo $root_page; ?>index.php" class="tab-item" data-page="index">
            <span class="tab-icon">🏠</span>
            Home
        </a>

        <a href="<?php echo $root_page; ?>about.html" class="tab-item" data-page="about">
            <span class="tab-icon">👋</span>
            About
        </a>

        <?php if ($canViewSurveyProjects): ?>
            <a href="<?php echo $root_page; ?>Projects/Professional/survey_projects.php" class="tab-item" data-page="survey_projects">
                <span class="tab-icon">🛠️</span>
                Survey Projects
            </a>
        <?php else: ?>
            <a href="<?php echo $root_page; ?>services.html" class="tab-item" data-page="services">
                <span class="tab-icon">🛠️</span>
                Services
            </a>
        <?php endif; ?>

        <a href="<?php echo $root_page; ?>list.php" class="tab-item" data-page="article">
            <span class="tab-icon">📖</span>
            Articles
        </a>

        <a href="<?php echo $root_page; ?>contact.html" class="tab-item" data-page="contact">
            <span class="tab-icon">📞</span>
            Contact
        </a>

        <div class="user-section">
            <?php if ($isLoggedIn): ?>
                <a href="<?php echo $root_page; ?>dashboard.php" class="tab-item" data-page="dashboard" id="dashboard-link">
                    <span class="tab-icon">📊</span>
                    <?php echo $username; ?>
                </a>
            <?php else: ?>
                <a href="<?php echo $root_page; ?>login.php" class="tab-item" data-page="login" id="login-link">
                    <span class="tab-icon">🔐</span>
                    Login
                </a>
            <?php endif; ?>
            <div class="user-avatar" id="user-avatar" style="display: <?php echo $isLoggedIn ? 'flex' : 'none'; ?>;">
                <?php echo $isLoggedIn ? strtoupper(substr($username, 0, 2)) : ''; ?>
            </div>
        </div>
    </nav>
</div>