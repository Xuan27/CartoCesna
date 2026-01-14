<?php
// check_navbar.php - Check if navbar code is correct
session_start();

require_once 'classes/Auth.php';
$auth = new Auth();

echo "<h2>Navbar Switch Button Check</h2>";
echo "<style>
    body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .info { color: blue; }
    pre { background: white; padding: 10px; border: 1px solid #ddd; overflow-x: auto; }
    .code-block { background: #f9f9f9; padding: 15px; border-left: 4px solid #4CAF50; margin: 10px 0; }
</style>";

// Check if logged in
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    echo "<p class='error'>You are not logged in. <a href='login.php'>Login first</a></p>";
    exit();
}

$userRole = $auth->getUserRole($_SESSION['user_id']);

echo "<h3>Current Session Info:</h3>";
echo "<ul>";
echo "<li>User ID: " . ($_SESSION['user_id'] ?? 'NOT SET') . "</li>";
echo "<li>Username: " . ($_SESSION['username'] ?? 'NOT SET') . "</li>";
echo "<li>User Role: <strong>" . ($userRole ?: 'NOT FOUND') . "</strong></li>";
echo "</ul>";

if ($userRole === 'admin') {
    echo "<p class='success'>✓ You are an admin - switch button SHOULD appear</p>";
} else {
    echo "<p class='info'>You are not an admin - switch button will NOT appear</p>";
}

echo "<hr>";

echo "<h3>Check Professional Dashboard File:</h3>";
$professionalFile = 'dashboards/professional_dashboard.php';
if (file_exists($professionalFile)) {
    echo "<p class='success'>✓ File exists</p>";
    
    $content = file_get_contents($professionalFile);
    
    // Check if it has the switch button code
    if (strpos($content, 'Switch to Personal') !== false) {
        echo "<p class='success'>✓ Contains 'Switch to Personal' text</p>";
    } else {
        echo "<p class='error'>✗ Does NOT contain 'Switch to Personal' text</p>";
    }
    
    // Check if it has the role check
    if (strpos($content, "if (\$userRole === 'admin')") !== false || 
        strpos($content, 'if ($userRole === \'admin\')') !== false) {
        echo "<p class='success'>✓ Contains admin role check</p>";
    } else {
        echo "<p class='error'>✗ Does NOT contain admin role check</p>";
    }
    
    // Show the navbar section
    $pattern = '/<nav class="navbar">.*?<\/nav>/s';
    if (preg_match($pattern, $content, $matches)) {
        echo "<h4>Current Navbar Code:</h4>";
        echo "<pre>" . htmlspecialchars($matches[0]) . "</pre>";
    }
} else {
    echo "<p class='error'>✗ File not found at: $professionalFile</p>";
}

echo "<hr>";

echo "<h3>Check Personal Dashboard File:</h3>";
$personalFile = 'dashboards/personal_dashboard.php';
if (file_exists($personalFile)) {
    echo "<p class='success'>✓ File exists</p>";
    
    $content = file_get_contents($personalFile);
    
    // Check if it has the switch button code
    if (strpos($content, 'Switch to Professional') !== false) {
        echo "<p class='success'>✓ Contains 'Switch to Professional' text</p>";
    } else {
        echo "<p class='error'>✗ Does NOT contain 'Switch to Professional' text</p>";
    }
    
    // Show the navbar section
    $pattern = '/<nav class="navbar">.*?<\/nav>/s';
    if (preg_match($pattern, $content, $matches)) {
        echo "<h4>Current Navbar Code:</h4>";
        echo "<pre>" . htmlspecialchars($matches[0]) . "</pre>";
    }
} else {
    echo "<p class='error'>✗ File not found at: $personalFile</p>";
}

echo "<hr>";

echo "<h3>What Should Happen:</h3>";
echo "<ol>";
echo "<li>When you login as admin, dashboard.php redirects to professional_dashboard.php</li>";
echo "<li>Professional dashboard should show navbar with 'Switch to Personal' link</li>";
echo "<li>Click the link to go to personal_dashboard.php</li>";
echo "<li>Personal dashboard should show navbar with 'Switch to Professional' link</li>";
echo "</ol>";

echo "<h3>Test Links:</h3>";
echo "<ul>";
echo "<li><a href='dashboards/professional_dashboard.php'>Go to Professional Dashboard</a></li>";
echo "<li><a href='dashboards/personal_dashboard.php'>Go to Personal Dashboard</a></li>";
echo "</ul>";

echo "<hr>";

echo "<h3>Expected Navbar Code (Professional):</h3>";
echo "<div class='code-block'>";
echo "<pre>" . htmlspecialchars('
<nav class="navbar">
    <div class="nav-container">
        <div class="nav-brand">Professional Dashboard</div>
        <div class="nav-user">
            <span>Welcome, admin!</span>
            <?php if ($userRole === \'admin\'): ?>
                <a href="<?php echo $root_page; ?>dashboards/personal_dashboard.php" 
                   style="color: white; text-decoration: none; margin: 0 10px;">
                    Switch to Personal
                </a>
            <?php endif; ?>
            <form method="POST" style="display: inline;">
                <button type="submit" name="logout" class="logout-btn">Logout</button>
            </form>
        </div>
    </div>
</nav>
') . "</pre>";
echo "</div>";
?>