<?php
// check_verification_files.php - Check if all verification system files exist
echo "<h2>Verification System Files Check</h2>";
echo "<style>
    body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .file-check { background: white; padding: 15px; margin: 10px 0; border-radius: 5px; }
    code { background: #f0f0f0; padding: 2px 6px; border-radius: 3px; }
</style>";

$requiredFiles = [
    'Verification Page' => 'dashboards/verify_users.php',
    'Get Pending Users API' => 'Models/php/get_pending_users.php',
    'Verify User API' => 'Models/php/verify_user.php',
    'Notification Stream (SSE)' => 'Models/php/notifications_stream.php',
    'Trigger Notification API' => 'Models/php/trigger_notification.php',
    'Professional Dashboard' => 'dashboards/professional_dashboard.php',
    'Personal Dashboard' => 'dashboards/personal_dashboard.php'
];

$allGood = true;

foreach ($requiredFiles as $name => $file) {
    echo "<div class='file-check'>";
    echo "<strong>$name:</strong><br>";
    echo "<code>$file</code><br>";
    
    if (file_exists($file)) {
        echo "<span class='success'>✓ File exists</span><br>";
        
        // Check file size
        $size = filesize($file);
        if ($size > 0) {
            echo "<span class='success'>✓ File has content (" . number_format($size) . " bytes)</span>";
        } else {
            echo "<span class='error'>✗ File is empty!</span>";
            $allGood = false;
        }
    } else {
        echo "<span class='error'>✗ File NOT found!</span><br>";
        echo "<em>Please create this file</em>";
        $allGood = false;
    }
    echo "</div>";
}

echo "<hr>";

if ($allGood) {
    echo "<div style='background: #d4edda; color: #155724; padding: 20px; border-radius: 8px; border: 1px solid #c3e6cb;'>";
    echo "<h3>✅ All Files Present!</h3>";
    echo "<p>The verification system is ready to use.</p>";
    echo "<p><a href='dashboards/verify_users.php' style='color: #155724;'>→ Go to User Verification Page</a></p>";
    echo "</div>";
} else {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 20px; border-radius: 8px; border: 1px solid #f5c6cb;'>";
    echo "<h3>⚠️ Missing Files</h3>";
    echo "<p>Please create the missing files listed above.</p>";
    echo "<p><strong>File Contents:</strong></p>";
    echo "<ol>";
    echo "<li>Find the artifacts in our conversation</li>";
    echo "<li>Copy the code for each missing file</li>";
    echo "<li>Save to the correct location</li>";
    echo "<li>Refresh this page to verify</li>";
    echo "</ol>";
    echo "</div>";
}

echo "<hr>";
echo "<h3>Quick Actions:</h3>";
echo "<ul>";
echo "<li><a href='create_pending_user.php'>Create a Test Pending User</a></li>";
echo "<li><a href='dashboards/professional_dashboard.php'>Go to Professional Dashboard</a></li>";
echo "<li><a href='check_navbar.php'>Check Navbar Switch Button</a></li>";
echo "</ul>";

// Check database columns
echo "<hr>";
echo "<h3>Database Check:</h3>";
try {
    require_once 'Private/db_config.php';
    $db = new Database();
    $conn = $db->getConnection();
    
    $stmt = $conn->query("SHOW COLUMNS FROM users LIKE 'is_verified'");
    if ($stmt->rowCount() > 0) {
        echo "<span class='success'>✓ is_verified column exists</span><br>";
    } else {
        echo "<span class='error'>✗ is_verified column missing</span><br>";
        echo "<code>ALTER TABLE users ADD COLUMN is_verified TINYINT(1) DEFAULT 0;</code><br>";
    }
    
    $stmt = $conn->query("SHOW COLUMNS FROM users LIKE 'is_active'");
    if ($stmt->rowCount() > 0) {
        echo "<span class='success'>✓ is_active column exists</span><br>";
    } else {
        echo "<span class='error'>✗ is_active column missing</span><br>";
        echo "<code>ALTER TABLE users ADD COLUMN is_active TINYINT(1) DEFAULT 1;</code><br>";
    }
    
} catch (Exception $e) {
    echo "<span class='error'>Database error: " . htmlspecialchars($e->getMessage()) . "</span>";
}
?>