<?php
// classes/Auth.php - Fixed version with better error handling
require_once __DIR__ . '/../Private/db_config.php';

class Auth {
    private $conn;
    private $table_users = "users";
    private $table_sessions = "user_sessions";
    private $table_login_attempts = "login_attempts";
    
    public function __construct() {
        try {
            $database = new Database();
            $this->conn = $database->getConnection();
            
            if (!$this->conn) {
                throw new Exception("Failed to establish database connection");
            }
        } catch (Exception $e) {
            error_log("Auth constructor error: " . $e->getMessage());
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }
    
    public function login($username_or_email, $password, $remember_me = false) {
        try {
            // Check if account is locked
            if ($this->isAccountLocked($username_or_email)) {
                $this->logLoginAttempt($username_or_email, false, $_SERVER['REMOTE_ADDR']);
                return ['success' => false, 'message' => 'Account is temporarily locked due to multiple failed attempts.'];
            }
            
            // Get user from database
            $user = $this->getUserByUsernameOrEmail($username_or_email);
            
            if (!$user) {
                $this->logLoginAttempt($username_or_email, false, $_SERVER['REMOTE_ADDR']);
                return ['success' => false, 'message' => 'Invalid credentials.'];
            }
            
            // Check if account is active and verified
            if (isset($user['is_active']) && !$user['is_active']) {
                return ['success' => false, 'message' => 'Account is deactivated.'];
            }
            
            if (isset($user['is_verified']) && !$user['is_verified']) {
                return ['success' => false, 'message' => 'Please verify your email address.'];
            }
            
            // Verify password
            if (!password_verify($password, $user['password_hash'])) {
                $this->incrementFailedAttempts($user['id']);
                $this->logLoginAttempt($username_or_email, false, $_SERVER['REMOTE_ADDR']);
                return ['success' => false, 'message' => 'Invalid credentials.'];
            }
            
            // Reset failed attempts on successful login
            $this->resetFailedAttempts($user['id']);
            $this->updateLastLogin($user['id']);
            $this->logLoginAttempt($username_or_email, true, $_SERVER['REMOTE_ADDR']);
            
            // Create session
            $session_token = $this->createSession($user['id'], $remember_me);
            
            // Start PHP session and store user data
            if (session_status() == PHP_SESSION_NONE) {
                session_start();
            }
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['session_token'] = $session_token;
            $_SESSION['logged_in'] = true;
            $_SESSION['last_activity'] = time();
            
            // Set root page to current directory
            $_SESSION['root_page'] = $this->getRootPage();
            
            return [
                'success' => true, 
                'message' => 'Login successful.',
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'first_name' => $user['first_name'] ?? '',
                    'last_name' => $user['last_name'] ?? ''
                ]
            ];
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            return ['success' => false, 'message' => 'An error occurred during login. Please try again.'];
        }
    }
    
    private function getRootPage() {
        // Try to get from existing session first
        if (isset($_SESSION['root_page'])) {
            return $_SESSION['root_page'];
        }
        
        // Get the directory of the current script
        $scriptPath = $_SERVER['SCRIPT_NAME'];  // Changed from PHP_SELF
        $pathParts = explode('/', $scriptPath);
        
        // Remove the filename to get directory
        array_pop($pathParts);
        
        // Join back to create root path
        $rootPath = implode('/', $pathParts);
        
        // Ensure it starts with / and ends with /
        if (empty($rootPath) || $rootPath === '') {
            $rootPath = '/';
        } else {
            // Add leading slash if missing
            if (strpos($rootPath, '/') !== 0) {
                $rootPath = '/' . $rootPath;
            }
            // Add trailing slash if missing
            if (substr($rootPath, -1) !== '/') {
                $rootPath .= '/';
            }
        }
        
        return $rootPath;
    }
    
    private function getUserByUsernameOrEmail($username_or_email) {
        $query = "SELECT id, username, email, password_hash, first_name, last_name, is_active, is_verified, failed_login_attempts, account_locked_until 
                  FROM " . $this->table_users . " 
                  WHERE username = :username OR email = :email 
                  LIMIT 1";
        
        try {
            $stmt = $this->conn->prepare($query);
            
            if (!$stmt) {
                error_log("Prepare failed: " . print_r($this->conn->errorInfo(), true));
                return false;
            }
            
            $stmt->bindValue(':username', $username_or_email, PDO::PARAM_STR);
            $stmt->bindValue(':email', $username_or_email, PDO::PARAM_STR);
            
            $result = $stmt->execute();
            
            if (!$result) {
                error_log("Execute failed: " . print_r($stmt->errorInfo(), true));
                return false;
            }
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Database error in getUserByUsernameOrEmail: " . $e->getMessage());
            return false;
        }
    }
    
    private function isAccountLocked($username_or_email) {
        $user = $this->getUserByUsernameOrEmail($username_or_email);
        
        if (!$user) return false;
        
        if (isset($user['account_locked_until']) && $user['account_locked_until']) {
            $lockUntil = new DateTime($user['account_locked_until']);
            $now = new DateTime();
            if ($lockUntil > $now) {
                return true;
            }
        }
        
        // Check if too many failed attempts in short period
        if (isset($user['failed_login_attempts']) && $user['failed_login_attempts'] >= 5) {
            $this->lockAccount($user['id']);
            return true;
        }
        
        return false;
    }
    
    private function lockAccount($user_id) {
        $lock_until = date('Y-m-d H:i:s', strtotime('+15 minutes'));
        
        $query = "UPDATE " . $this->table_users . " 
                  SET account_locked_until = :lock_until 
                  WHERE id = :user_id";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':lock_until', $lock_until);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error locking account: " . $e->getMessage());
        }
    }
    
    private function incrementFailedAttempts($user_id) {
        $query = "UPDATE " . $this->table_users . " 
                  SET failed_login_attempts = failed_login_attempts + 1,
                      last_failed_login = CURRENT_TIMESTAMP 
                  WHERE id = :user_id";
        
        try {
            $stmt = $this->conn->prepare($query);
            
            if (!$stmt) {
                error_log("Prepare failed in incrementFailedAttempts: " . print_r($this->conn->errorInfo(), true));
                return false;
            }
            
            $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
            $result = $stmt->execute();
            
            if (!$result) {
                error_log("Execute failed in incrementFailedAttempts: " . print_r($stmt->errorInfo(), true));
                return false;
            }
            
            return true;
            
        } catch (PDOException $e) {
            error_log("Database error in incrementFailedAttempts: " . $e->getMessage());
            return false;
        }
    }
    
    private function resetFailedAttempts($user_id) {
        $query = "UPDATE " . $this->table_users . " 
                  SET failed_login_attempts = 0,
                      account_locked_until = NULL 
                  WHERE id = :user_id";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error resetting failed attempts: " . $e->getMessage());
        }
    }
    
    private function updateLastLogin($user_id) {
        $query = "UPDATE " . $this->table_users . " 
                  SET last_login = CURRENT_TIMESTAMP 
                  WHERE id = :user_id";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error updating last login: " . $e->getMessage());
        }
    }
    
    private function createSession($user_id, $remember_me = false) {
        $session_token = bin2hex(random_bytes(32));
        $expires_at = $remember_me ? 
            date('Y-m-d H:i:s', strtotime('+30 days')) : 
            date('Y-m-d H:i:s', strtotime('+24 hours'));
        
        $query = "INSERT INTO " . $this->table_sessions . " 
                  (user_id, session_token, expires_at, ip_address, user_agent) 
                  VALUES (:user_id, :session_token, :expires_at, :ip_address, :user_agent)";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':session_token', $session_token);
            $stmt->bindParam(':expires_at', $expires_at);
            $stmt->bindParam(':ip_address', $_SERVER['REMOTE_ADDR']);
            $stmt->bindParam(':user_agent', $_SERVER['HTTP_USER_AGENT']);
            $stmt->execute();
            
            return $session_token;
        } catch (PDOException $e) {
            error_log("Error creating session: " . $e->getMessage());
            return null;
        }
    }
    
    private function logLoginAttempt($username_or_email, $success, $ip_address) {
        $query = "INSERT INTO " . $this->table_login_attempts . " 
                  (username_or_email, ip_address, success, user_agent) 
                  VALUES (:username_or_email, :ip_address, :success, :user_agent)";
        
        try {
            $stmt = $this->conn->prepare($query);
            
            if (!$stmt) {
                error_log("Prepare failed in logLoginAttempt: " . print_r($this->conn->errorInfo(), true));
                return false;
            }
            
            $stmt->bindValue(':username_or_email', $username_or_email, PDO::PARAM_STR);
            $stmt->bindValue(':ip_address', $ip_address, PDO::PARAM_STR);
            $stmt->bindValue(':success', $success, PDO::PARAM_BOOL);
            $stmt->bindValue(':user_agent', $_SERVER['HTTP_USER_AGENT'] ?? '', PDO::PARAM_STR);
            
            $result = $stmt->execute();
            
            if (!$result) {
                error_log("Execute failed in logLoginAttempt: " . print_r($stmt->errorInfo(), true));
                return false;
            }
            
            return true;
            
        } catch (PDOException $e) {
            error_log("Database error in logLoginAttempt: " . $e->getMessage());
            return false;
        }
    }
    
    public function logout() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        if (isset($_SESSION['session_token'])) {
            // Invalidate session in database
            $query = "UPDATE " . $this->table_sessions . " 
                      SET is_active = FALSE 
                      WHERE session_token = :session_token";
            
            try {
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':session_token', $_SESSION['session_token']);
                $stmt->execute();
            } catch (PDOException $e) {
                error_log("Error invalidating session: " . $e->getMessage());
            }
        }
        
        // Clear PHP session
        session_unset();
        session_destroy();
        
        return ['success' => true, 'message' => 'Logged out successfully.'];
    }
    
    public function isLoggedIn() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        // Check for inactivity (30 minutes)
        if (!isset($_SESSION['last_activity']) || (time() - $_SESSION['last_activity'] > 1800)) {
            // Invalidate session
            if (isset($_SESSION['session_token'])) {
                try {
                    $query = "UPDATE " . $this->table_sessions . " 
                              SET is_active = FALSE 
                              WHERE session_token = :session_token";
                    $stmt = $this->conn->prepare($query);
                    $stmt->bindParam(':session_token', $_SESSION['session_token']);
                    $stmt->execute();
                } catch (PDOException $e) {
                    error_log("Error invalidating inactive session: " . $e->getMessage());
                }
            }
            session_unset();
            session_destroy();
            return false;
        }
        
        // Update last activity
        $_SESSION['last_activity'] = time();
        
        if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in'] || !isset($_SESSION['session_token'])) {
            return false;
        }
        
        // Verify session in database
        $query = "SELECT us.*, u.is_active 
                  FROM " . $this->table_sessions . " us
                  JOIN " . $this->table_users . " u ON us.user_id = u.id
                  WHERE us.session_token = :session_token 
                    AND us.expires_at > NOW() 
                    AND us.is_active = TRUE 
                    AND u.is_active = TRUE";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':session_token', $_SESSION['session_token']);
            $stmt->execute();
            
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Error checking session: " . $e->getMessage());
            return false;
        }
    }

    public function getUserRole($user_identifier = null) {
        // If no identifier provided, try to read from session
        if ($user_identifier === null) {
            if (session_status() == PHP_SESSION_NONE) {
                session_start();
            }

            if (!isset($_SESSION['user_id'])) {
                return false;
            }

            $user_id = (int) $_SESSION['user_id'];
        }

        try {
            if (isset($user_id)) {
                $query = "SELECT role, user_role, is_admin FROM " . $this->table_users . " WHERE id = :id LIMIT 1";
                $stmt = $this->conn->prepare($query);
                $stmt->bindValue(':id', $user_id, PDO::PARAM_INT);
            } else {
                $username_or_email = $user_identifier;
                $query = "SELECT role, user_role, is_admin FROM " . $this->table_users . " WHERE username = :u OR email = :u LIMIT 1";
                $stmt = $this->conn->prepare($query);
                $stmt->bindValue(':u', $username_or_email, PDO::PARAM_STR);
            }

            if (!$stmt) {
                error_log("Prepare failed in getUserRole: " . print_r($this->conn->errorInfo(), true));
                return false;
            }

            $result = $stmt->execute();

            if (!$result) {
                error_log("Execute failed in getUserRole: " . print_r($stmt->errorInfo(), true));
                return false;
            }

            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                return false;
            }

            if (!empty($row['role'])) {
                return $row['role'];
            }

            if (!empty($row['user_role'])) {
                return $row['user_role'];
            }

            if (isset($row['is_admin'])) {
                return $row['is_admin'] ? 'admin' : 'user';
            }

            return null;
        } catch (PDOException $e) {
            error_log("Database error in getUserRole: " . $e->getMessage());
            return false;
        }
    }
}