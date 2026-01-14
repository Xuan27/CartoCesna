<?php
/**
 * Security utilities for CSRF protection, headers, and input validation
 */
require_once __DIR__ . '/Env.php';

class Security {

    /**
     * Generate a CSRF token and store in session
     */
    public static function generateCsrfToken() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $_SESSION['csrf_token_time'] = time();
        }

        return $_SESSION['csrf_token'];
    }

    /**
     * Get the current CSRF token (generates one if not exists)
     */
    public static function getCsrfToken() {
        return self::generateCsrfToken();
    }

    /**
     * Output a hidden CSRF input field for forms
     */
    public static function csrfField() {
        $token = self::getCsrfToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
    }

    /**
     * Validate CSRF token from request
     */
    public static function validateCsrfToken($token = null) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Get token from parameter or POST data
        if ($token === null) {
            $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        }

        // Check if token exists in session
        if (empty($_SESSION['csrf_token'])) {
            return false;
        }

        // Check if token has expired (1 hour)
        $tokenTime = $_SESSION['csrf_token_time'] ?? 0;
        if (time() - $tokenTime > 3600) {
            unset($_SESSION['csrf_token'], $_SESSION['csrf_token_time']);
            return false;
        }

        // Constant-time comparison to prevent timing attacks
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Regenerate CSRF token (call after successful form submission)
     */
    public static function regenerateCsrfToken() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();

        return $_SESSION['csrf_token'];
    }

    /**
     * Set security headers
     */
    public static function setSecurityHeaders() {
        // Prevent clickjacking
        header('X-Frame-Options: SAMEORIGIN');

        // Prevent MIME type sniffing
        header('X-Content-Type-Options: nosniff');

        // Enable XSS filter in browsers
        header('X-XSS-Protection: 1; mode=block');

        // Referrer policy
        header('Referrer-Policy: strict-origin-when-cross-origin');

        // Content Security Policy (adjust as needed for your site)
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self';");

        // Permissions Policy
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    }

    /**
     * Set CORS headers for API endpoints
     */
    public static function setCorsHeaders() {
        Env::load();

        $allowedOrigins = array_map('trim', explode(',', Env::get('ALLOWED_ORIGINS', '')));
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

        if (in_array($origin, $allowedOrigins)) {
            header('Access-Control-Allow-Origin: ' . $origin);
        } elseif (Env::get('APP_ENV') === 'development') {
            // In development, allow localhost
            if (strpos($origin, 'localhost') !== false || strpos($origin, '127.0.0.1') !== false) {
                header('Access-Control-Allow-Origin: ' . $origin);
            }
        }

        header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');
        header('Access-Control-Allow-Credentials: true');

        // Handle preflight requests
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
    }

    /**
     * Enforce HTTPS in production
     */
    public static function enforceHttps() {
        Env::load();

        if (Env::isProduction()) {
            // HSTS header
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');

            // Redirect to HTTPS if not already
            if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') {
                $redirect = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
                header('Location: ' . $redirect, true, 301);
                exit;
            }
        }
    }

    /**
     * Set secure session configuration
     */
    public static function secureSession() {
        // Only configure session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            // Set secure session cookie parameters
            $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

            session_set_cookie_params([
                'lifetime' => 0,
                'path' => '/',
                'domain' => '',
                'secure' => $isHttps,
                'httponly' => true,
                'samesite' => 'Strict'
            ]);

            session_start();
        }

        // Note: Session regeneration is handled in Auth::login() to prevent
        // invalidating sessions on every page load
    }

    /**
     * Sanitize string input
     */
    public static function sanitizeString($input) {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Validate email format
     */
    public static function isValidEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}
