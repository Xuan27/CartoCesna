<?php
// create_user.php - Script to create new users with proper password hashing
require_once 'config/database.php';

class UserCreator {
    private $conn;
    private $table_users = "users";
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    public function createUser($username, $email, $password, $first_name = '', $last_name = '', $is_verified = false) {
        // Validate input
        if (strlen($username) < 3) {
            return ['success' => false, 'message' => 'Username must be at least 3 characters long.'];
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Invalid email format.'];
        }
        
        if (strlen($password) < 8) {
            return ['success' => false, 'message' => 'Password must be at least 8 characters long.'];
        }
        
        // Check if username already exists
        if ($this->usernameExists($username)) {
            return ['success' => false, 'message' => 'Username already exists.'];
        }
        
        // Check if email already exists
        if ($this->emailExists($email)) {
            return ['success' => false, 'message' => 'Email already exists.'];
        }
        
        // Hash password
        $password_hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $salt = bin2hex(random_bytes(16)); // Generate random salt
        
        // Insert user
        $query = "INSERT INTO " . $this->table_users . " 
                  (username, email, password_hash, salt, first_name, last_name, is_verified) 
                  VALUES (:username, :email, :password_hash, :salt, :first_name, :last_name, :is_verified)";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password_hash', $password_hash);
            $stmt->bindParam(':salt', $salt);
            $stmt->bindParam(':first_name', $first_name);
            $stmt->bindParam(':last_name', $last_name);
            $stmt->bindParam(':is_verified', $is_verified, PDO::PARAM_BOOL);
            
            $stmt->execute();
            
            return [
                'success' => true, 
                'message' => 'User created successfully.',
                'user_id' => $this->conn->lastInsertId()
            ];
            
        } catch (PDOException $e) {
            error_log("User creation error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to create user.'];
        }
    }
    
    private function usernameExists($username) {
        $query = "SELECT id FROM " . $this->table_users . " WHERE username = :username LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }
    
    private function emailExists($email) {
        $query = "SELECT id FROM " . $this->table_users . " WHERE email = :email LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }
}

// Example usage - uncomment to create test users
/*
try {
    $userCreator = new UserCreator();
    
    // Create a test user
    $result = $userCreator->createUser(
        'testuser',
        'test@example.com',
        'SecurePass123!',
        'Test',
        'User',
        true // Set to true to skip email verification
    );
    
    if ($result['success']) {
        echo "User created successfully! User ID: " . $result['user_id'] . "\n";
    } else {
        echo "Error: " . $result['message'] . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
*/

// Command line interface for creating users
if (php_sapi_name() === 'cli') {
    echo "=== User Creation Script ===\n";
    echo "Enter user details:\n";
    
    echo "Username: ";
    $username = trim(fgets(STDIN));
    
    echo "Email: ";
    $email = trim(fgets(STDIN));
    
    echo "Password: ";
    $password = trim(fgets(STDIN));
    
    echo "First Name (optional): ";
    $first_name = trim(fgets(STDIN));
    
    echo "Last Name (optional): ";
    $last_name = trim(fgets(STDIN));
    
    echo "Mark as verified? (y/n): ";
    $verified_input = trim(fgets(STDIN));
    $is_verified = strtolower($verified_input) === 'y';
    
    try {
        $userCreator = new UserCreator();
        $result = $userCreator->createUser($username, $email, $password, $first_name, $last_name, $is_verified);
        
        if ($result['success']) {
            echo "\n✓ User created successfully!\n";
            echo "User ID: " . $result['user_id'] . "\n";
            echo "Username: " . $username . "\n";
            echo "Email: " . $email . "\n";
        } else {
            echo "\n✗ Error: " . $result['message'] . "\n";
        }
        
    } catch (Exception $e) {
        echo "\n✗ Error: " . $e->getMessage() . "\n";
    }
}
?>