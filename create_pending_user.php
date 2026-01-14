<?php
// create_pending_user.php - Create a test pending user
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'Private/db_config.php';

echo "<h2>Create Pending Test User</h2>";
echo "<style>
    body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    form { background: white; padding: 20px; border-radius: 8px; max-width: 500px; }
    input, select { width: 100%; padding: 8px; margin: 5px 0 15px 0; border: 1px solid #ddd; border-radius: 4px; }
    button { background: #3b82f6; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
    button:hover { background: #2563eb; }
</style>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $firstName = trim($_POST['first_name']);
        $lastName = trim($_POST['last_name']);
        $role = $_POST['role'];
        $password = $_POST['password'];
        
        // Hash password
        $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $salt = bin2hex(random_bytes(16));
        
        // Insert user as unverified (requires admin approval)
        $query = "INSERT INTO users 
                  (username, email, first_name, last_name, role, password_hash, salt, 
                   is_verified, is_active, created_at, updated_at) 
                  VALUES 
                  (:username, :email, :first_name, :last_name, :role, :password_hash, :salt, 
                   0, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)";
        
        $stmt = $conn->prepare($query);
        $stmt->execute([
            'username' => $username,
            'email' => $email,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'role' => $role,
            'password_hash' => $passwordHash,
            'salt' => $salt
        ]);
        
        $userId = $conn->lastInsertId();
        
        echo "<p class='success'>✓ User registration submitted!</p>";
        echo "<p><strong>Status:</strong> Pending Admin Verification</p>";
        echo "<p>User ID: $userId</p>";
        echo "<p>Username: $username</p>";
        echo "<p>Email: $email</p>";
        echo "<p class='info' style='background: #d1ecf1; padding: 15px; border-radius: 8px; margin-top: 15px;'>";
        echo "<strong>⏳ What happens next?</strong><br>";
        echo "An administrator has been notified of your registration and will review your account shortly. ";
        echo "You will receive an email notification once your account is approved.";
        echo "</p>";
        echo "<hr>";
        echo "<p><a href='dashboards/verify_users.php'>Admins: Go to Verification Page</a></p>";
        
    } catch (Exception $e) {
        echo "<p class='error'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}
?>

<form method="POST">
    <h3>Create Pending Test User</h3>
    <p>This user will be created with is_verified=0 and will appear in the inbox.</p>
    
    <label>Username:</label>
    <input type="text" name="username" required value="testuser<?php echo rand(100,999); ?>">
    
    <label>Email:</label>
    <input type="email" name="email" required value="test<?php echo rand(100,999); ?>@example.com">
    
    <label>First Name:</label>
    <input type="text" name="first_name" required value="Test">
    
    <label>Last Name:</label>
    <input type="text" name="last_name" required value="User">
    
    <label>Role:</label>
    <select name="role" required>
        <option value="personal_user">Personal User</option>
        <option value="professional_user">Professional User</option>
        <option value="admin">Admin</option>
    </select>
    
    <label>Password:</label>
    <input type="password" name="password" required value="Test123!">
    
    <button type="submit">Create Pending User</button>
</form>

<hr>

<h3>Current Pending Users</h3>
<?php
try {
    $db = new Database();
    $conn = $db->getConnection();
    
    $query = "SELECT id, username, email, role, created_at 
              FROM users 
              WHERE is_verified = 0 AND is_active = 1 
              ORDER BY created_at DESC";
    $stmt = $conn->query($query);
    $pendingUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($pendingUsers) > 0) {
        echo "<table style='width: 100%; border-collapse: collapse; background: white;'>";
        echo "<tr style='background: #f8f9fa;'>";
        echo "<th style='padding: 10px; border: 1px solid #ddd;'>ID</th>";
        echo "<th style='padding: 10px; border: 1px solid #ddd;'>Username</th>";
        echo "<th style='padding: 10px; border: 1px solid #ddd;'>Email</th>";
        echo "<th style='padding: 10px; border: 1px solid #ddd;'>Role</th>";
        echo "<th style='padding: 10px; border: 1px solid #ddd;'>Created</th>";
        echo "</tr>";
        
        foreach ($pendingUsers as $user) {
            echo "<tr>";
            echo "<td style='padding: 10px; border: 1px solid #ddd;'>" . $user['id'] . "</td>";
            echo "<td style='padding: 10px; border: 1px solid #ddd;'>" . htmlspecialchars($user['username']) . "</td>";
            echo "<td style='padding: 10px; border: 1px solid #ddd;'>" . htmlspecialchars($user['email']) . "</td>";
            echo "<td style='padding: 10px; border: 1px solid #ddd;'>" . htmlspecialchars($user['role']) . "</td>";
            echo "<td style='padding: 10px; border: 1px solid #ddd;'>" . $user['created_at'] . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        echo "<p style='margin-top: 1rem;'><strong>Count:</strong> " . count($pendingUsers) . " pending user(s)</p>";
    } else {
        echo "<p>No pending users found.</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>