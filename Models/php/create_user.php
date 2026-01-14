<?php
// create_user.php - Creates a new user in the database
require_once '../../classes/Security.php';

header('Content-Type: application/json');

// Set secure CORS headers
Security::setCorsHeaders();

require_once '../../Private/db_config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
    exit;
}

// Validate CSRF token (from header or body)
$csrf_token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $input['csrf_token'] ?? '';
if (!Security::validateCsrfToken($csrf_token)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid security token. Please refresh and try again.']);
    exit;
}

// Validate required fields
$required = ['username', 'email', 'firstName', 'lastName', 'role', 'password'];
foreach ($required as $field) {
    if (empty($input[$field])) {
        echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
        exit;
    }
}

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Check if username already exists
    $check_query = "SELECT id FROM users WHERE username = :username OR email = :email";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bindValue(':username', $input['username'], PDO::PARAM_STR);
    $check_stmt->bindValue(':email', $input['email'], PDO::PARAM_STR);
    $check_stmt->execute();
    
    if ($check_stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Username or email already exists']);
        exit;
    }
    
    // Generate password hash and salt
    $password_hash = password_hash($input['password'], PASSWORD_BCRYPT, ['cost' => 12]);
    $salt = bin2hex(random_bytes(16));
    
    // Insert new user
    $query = "INSERT INTO users (username, email, first_name, last_name, role, password_hash, salt, 
                                failed_login_attempts, account_locked_until, created_at, updated_at) 
              VALUES (:username, :email, :first_name, :last_name, :role, :password_hash, :salt, 
                      0, NULL, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)";
    
    $stmt = $conn->prepare($query);
    $stmt->bindValue(':username', $input['username'], PDO::PARAM_STR);
    $stmt->bindValue(':email', $input['email'], PDO::PARAM_STR);
    $stmt->bindValue(':first_name', $input['firstName'], PDO::PARAM_STR);
    $stmt->bindValue(':last_name', $input['lastName'], PDO::PARAM_STR);
    $stmt->bindValue(':role', $input['role'], PDO::PARAM_STR);
    $stmt->bindValue(':password_hash', $password_hash, PDO::PARAM_STR);
    $stmt->bindValue(':salt', $salt, PDO::PARAM_STR);
    
    if ($stmt->execute()) {
        $user_id = $conn->lastInsertId();
        
        // Return user info (without sensitive data)
        echo json_encode([
            'success' => true,
            'message' => 'User created successfully',
            'user' => [
                'id' => $user_id,
                'username' => $input['username'],
                'email' => $input['email'],
                'first_name' => $input['firstName'],
                'last_name' => $input['lastName'],
                'role' => $input['role'],
                'created_at' => date('Y-m-d H:i:s')
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to create user']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>