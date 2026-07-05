<?php
// classes/PasswordReset.php - Admin-approved password reset logic
//
// State model (users.password_reset_token / users.password_reset_expires):
//   NULL / NULL            -> no request
//   'PENDING' / +24h       -> user requested, awaiting admin approval
//   sha256 hex / +24h      -> admin approved, link issued (raw token only in URL)
//   NULL / NULL            -> denied, used, or expired
require_once __DIR__ . '/../Private/db_config.php';

class PasswordReset {
    private $conn;

    const PENDING = 'PENDING';
    const VALIDITY_HOURS = 24;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();

        if (!$this->conn) {
            throw new Exception("Failed to establish database connection");
        }
    }

    // Record a reset request. Intentionally returns nothing: callers must show
    // the same generic message for every outcome so account existence is not leaked.
    public function requestReset($username_or_email) {
        $stmt = $this->conn->prepare(
            "SELECT id, is_active, is_verified, password_reset_token, password_reset_expires
             FROM users
             WHERE username = :username OR email = :email
             LIMIT 1"
        );
        $stmt->bindValue(':username', $username_or_email, PDO::PARAM_STR);
        $stmt->bindValue(':email', $username_or_email, PDO::PARAM_STR);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !$user['is_active'] || !$user['is_verified']) {
            return;
        }

        // Throttle: an unexpired request (pending or approved) already exists
        if ($user['password_reset_token'] !== null
            && $user['password_reset_expires'] !== null
            && strtotime($user['password_reset_expires']) > time()) {
            return;
        }

        $stmt = $this->conn->prepare(
            "UPDATE users
             SET password_reset_token = :token,
                 password_reset_expires = DATE_ADD(NOW(), INTERVAL " . self::VALIDITY_HOURS . " HOUR)
             WHERE id = :id"
        );
        $stmt->bindValue(':token', self::PENDING, PDO::PARAM_STR);
        $stmt->bindValue(':id', $user['id'], PDO::PARAM_INT);
        $stmt->execute();
    }

    // Unexpired requests for the admin panel
    public function listPending() {
        // Housekeeping: clear expired requests
        $this->conn->exec(
            "UPDATE users
             SET password_reset_token = NULL, password_reset_expires = NULL
             WHERE password_reset_token IS NOT NULL AND password_reset_expires <= NOW()"
        );

        $stmt = $this->conn->query(
            "SELECT id, username, email, first_name, last_name,
                    password_reset_token,
                    DATE_SUB(password_reset_expires, INTERVAL " . self::VALIDITY_HOURS . " HOUR) AS requested_at,
                    password_reset_expires AS expires_at
             FROM users
             WHERE password_reset_token IS NOT NULL AND password_reset_expires > NOW()
             ORDER BY password_reset_expires ASC"
        );

        $requests = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $requests[] = [
                'id'           => (int)$row['id'],
                'username'     => $row['username'],
                'email'        => $row['email'],
                'first_name'   => $row['first_name'],
                'last_name'    => $row['last_name'],
                'status'       => $row['password_reset_token'] === self::PENDING ? 'pending' : 'approved',
                'requested_at' => $row['requested_at'],
                'expires_at'   => $row['expires_at'],
            ];
        }
        return $requests;
    }

    // Approve (or re-approve) a request. Returns the raw token exactly once;
    // only its sha256 is stored, so the link cannot be recovered from the DB.
    public function approve($userId) {
        $stmt = $this->conn->prepare(
            "SELECT id FROM users
             WHERE id = :id AND password_reset_token IS NOT NULL AND password_reset_expires > NOW()"
        );
        $stmt->bindValue(':id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        if (!$stmt->fetch()) {
            return null;
        }

        $rawToken = bin2hex(random_bytes(32));

        $stmt = $this->conn->prepare(
            "UPDATE users
             SET password_reset_token = :token,
                 password_reset_expires = DATE_ADD(NOW(), INTERVAL " . self::VALIDITY_HOURS . " HOUR)
             WHERE id = :id"
        );
        $stmt->bindValue(':token', hash('sha256', $rawToken), PDO::PARAM_STR);
        $stmt->bindValue(':id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        $stmt = $this->conn->prepare("SELECT password_reset_expires FROM users WHERE id = :id");
        $stmt->bindValue(':id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'token'      => $rawToken,
            'expires_at' => $stmt->fetchColumn(),
        ];
    }

    public function deny($userId) {
        $stmt = $this->conn->prepare(
            "UPDATE users
             SET password_reset_token = NULL, password_reset_expires = NULL
             WHERE id = :id AND password_reset_token IS NOT NULL"
        );
        $stmt->bindValue(':id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    // Returns the matching user row for a valid raw token, or null
    public function validateToken($rawToken) {
        if (!is_string($rawToken) || !preg_match('/^[a-f0-9]{64}$/', $rawToken)) {
            return null;
        }

        $stmt = $this->conn->prepare(
            "SELECT id, username FROM users
             WHERE password_reset_token = :token_hash
               AND password_reset_token != :pending
               AND password_reset_expires > NOW()
             LIMIT 1"
        );
        $stmt->bindValue(':token_hash', hash('sha256', $rawToken), PDO::PARAM_STR);
        $stmt->bindValue(':pending', self::PENDING, PDO::PARAM_STR);
        $stmt->execute();

        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ?: null;
    }

    public function performReset($rawToken, $password) {
        $user = $this->validateToken($rawToken);
        if (!$user) {
            return ['success' => false, 'message' => 'This reset link is invalid or has expired.'];
        }

        $errors = $this->validatePasswordStrength($password);
        if ($errors) {
            return ['success' => false, 'message' => implode(' ', $errors)];
        }

        $stmt = $this->conn->prepare(
            "UPDATE users
             SET password_hash = :password_hash,
                 salt = :salt,
                 failed_login_attempts = 0,
                 account_locked_until = NULL,
                 password_reset_token = NULL,
                 password_reset_expires = NULL,
                 updated_at = CURRENT_TIMESTAMP
             WHERE id = :id"
        );
        $stmt->bindValue(':password_hash', password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]), PDO::PARAM_STR);
        $stmt->bindValue(':salt', bin2hex(random_bytes(16)), PDO::PARAM_STR);
        $stmt->bindValue(':id', $user['id'], PDO::PARAM_INT);
        $stmt->execute();

        // Force re-login everywhere with the new password
        $stmt = $this->conn->prepare("UPDATE user_sessions SET is_active = FALSE WHERE user_id = :id");
        $stmt->bindValue(':id', $user['id'], PDO::PARAM_INT);
        $stmt->execute();

        return ['success' => true, 'message' => 'Your password has been reset. You can now sign in.'];
    }

    // Server-side mirror of the register.php client-side rules
    public function validatePasswordStrength($password) {
        $errors = [];
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        }
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain an uppercase letter.';
        }
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain a lowercase letter.';
        }
        if (!preg_match('/\d/', $password)) {
            $errors[] = 'Password must contain a number.';
        }
        if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
            $errors[] = 'Password must contain a special character.';
        }
        return $errors;
    }
}
