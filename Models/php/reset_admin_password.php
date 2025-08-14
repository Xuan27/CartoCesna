<?php
// reset_admin_password.php - Reset the admin user password
require_once '../../Private/db_config.php';

// Set your desired password here
$new_password = 'SecurePass123!';  // Change this to whatever you want
$username = 'admin';  // The username to update

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    echo "<h2>Admin Password Reset</h2>";
    
    // Generate new password hash
    $new_hash = password_hash($new_password, PASSWORD_BCRYPT, ['cost' => 12]);
    $new_salt = bin2hex(random_bytes(16));
    
    echo "New password: <strong>" . htmlspecialchars($new_password) . "</strong><br>";
    echo "New hash: " . htmlspecialchars($new_hash) . "<br><br>";
    
    // Update the admin user's password
    $query = "UPDATE users SET password_hash = :password_hash, salt = :salt, failed_login_attempts = 0, account_locked_until = NULL, updated_at = CURRENT_TIMESTAMP WHERE username = :username;";
    
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        echo "❌ Prepare failed: ";
        print_r($conn->errorInfo());
        exit();
    }
    
    $stmt->bindValue(':password_hash', $new_hash, PDO::PARAM_STR);
    $stmt->bindValue(':salt', $new_salt, PDO::PARAM_STR);
    $stmt->bindValue(':username', $username, PDO::PARAM_STR);
    
    $result = $stmt->execute();
    
    if (!$result) {
        echo "❌ Execute failed: ";
        print_r($stmt->errorInfo());
        exit();
    }
    
    if ($stmt->rowCount() > 0) {
        echo "✅ <strong>Password updated successfully!</strong><br><br>";
        
        // Verify the new password works
        $verify_query = "SELECT password_hash FROM users WHERE username = :username";
        $verify_stmt = $conn->prepare($verify_query);
        $verify_stmt->bindValue(':username', $username, PDO::PARAM_STR);
        $verify_stmt->execute();
        $user = $verify_stmt->fetch();
        
        if ($user && password_verify($new_password, $user['password_hash'])) {
            echo "✅ <strong>Verification successful!</strong> You can now login with:<br>";
            echo "Username: <strong>" . htmlspecialchars($username) . "</strong><br>";
            echo "Password: <strong>" . htmlspecialchars($new_password) . "</strong><br>";
        } else {
            echo "❌ Verification failed - something went wrong";
        }
    } else {
        echo "❌ No user found with username: " . htmlspecialchars($username);
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}

// Command line version
if (php_sapi_name() === 'cli') {
    echo "\n=== Admin Password Reset ===\n";
    echo "Enter new password for admin user: ";
    $new_password = trim(fgets(STDIN));
    
    if (strlen($new_password) < 8) {
        echo "Password must be at least 8 characters long.\n";
        exit(1);
    }
    
    try {
        $database = new Database();
        $conn = $database->getConnection();
        
        $new_hash = password_hash($new_password, PASSWORD_BCRYPT, ['cost' => 12]);
        $new_salt = bin2hex(random_bytes(16));
        
        $query = "UPDATE users 
                  SET password_hash = :password_hash, 
                      salt = :salt,
                      failed_login_attempts = 0,
                      account_locked_until = NULL
                  WHERE username = 'admin'";
        
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':password_hash', $new_hash, PDO::PARAM_STR);
        $stmt->bindValue(':salt', $new_salt, PDO::PARAM_STR);
        
        if ($stmt->execute() && $stmt->rowCount() > 0) {
            echo "\n✅ Password updated successfully!\n";
            echo "Username: admin\n";
            echo "Password: " . $new_password . "\n";
        } else {
            echo "\n❌ Failed to update password\n";
        }
        
    } catch (Exception $e) {
        echo "\n❌ Error: " . $e->getMessage() . "\n";
    }
}
?>