<?php
// login.php - Improved version with debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Start output buffering to prevent header issues
ob_start();

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set root page in session if not already set
if (!isset($_SESSION['root_page'])) {
    $scriptPath = $_SERVER['SCRIPT_NAME'];
    $pathParts = explode('/', $scriptPath);
    array_pop($pathParts); // Remove filename
    $rootPath = implode('/', $pathParts);
    
    if (empty($rootPath) || $rootPath === '') {
        $rootPath = '/';
    } else {
        if (strpos($rootPath, '/') !== 0) {
            $rootPath = '/' . $rootPath;
        }
        if (substr($rootPath, -1) !== '/') {
            $rootPath .= '/';
        }
    }
    
    $_SESSION['root_page'] = $rootPath;
}

// Debug logging function
function debugLog($message) {
    error_log("[LOGIN DEBUG] " . $message);
}

debugLog("Login page accessed");
debugLog("Root page set to: " . $_SESSION['root_page']);

// If already logged in, redirect immediately
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']) {
    debugLog("User already logged in, redirecting to dashboard");
    $dashboardUrl = $_SESSION['root_page'] . 'dashboard.php';
    debugLog("Dashboard URL: " . $dashboardUrl);
    ob_end_clean();
    header('Location: ' . $dashboardUrl);
    exit();
}

$message = '';
$message_type = '';
$form_data = [
    'username_or_email' => '',
    'remember_me' => false
];

$debug_info = [];

// Process login form submission ONLY if form was actually submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username_or_email'])) {
    debugLog("Processing login form submission");
    
    require_once 'classes/Auth.php';
    
    $username_or_email = trim($_POST['username_or_email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember_me = isset($_POST['remember_me']);
    
    // Preserve form data for repopulation on error
    $form_data['username_or_email'] = $username_or_email;
    $form_data['remember_me'] = $remember_me;
    
    debugLog("Username/Email: " . $username_or_email);
    
    if (empty($username_or_email) || empty($password)) {
        $message = 'Please fill in all required fields.';
        $message_type = 'error';
        debugLog("Validation failed: empty fields");
    } else {
        try {
            $auth = new Auth();
            debugLog("Auth object created");
            
            $result = $auth->login($username_or_email, $password, $remember_me);
            debugLog("Login result: " . json_encode($result));
            
            if ($result['success']) {
                debugLog("Login successful!");
                
                // Double check session is set
                debugLog("Session after login - logged_in: " . ($_SESSION['logged_in'] ?? 'NOT SET'));
                debugLog("Session after login - user_id: " . ($_SESSION['user_id'] ?? 'NOT SET'));
                debugLog("Session after login - username: " . ($_SESSION['username'] ?? 'NOT SET'));
                
                // Verify we can call isLoggedIn
                $checkLogin = $auth->isLoggedIn();
                debugLog("isLoggedIn() check: " . ($checkLogin ? 'TRUE' : 'FALSE'));
                
                if (!$checkLogin) {
                    debugLog("WARNING: Login succeeded but isLoggedIn() returns false!");
                    $debug_info[] = "Login succeeded but isLoggedIn() returns false";
                }
                
                // Clear output buffer and redirect
                ob_end_clean();
                $dashboardUrl = $_SESSION['root_page'] . 'dashboard.php';
                debugLog("Attempting redirect to: " . $dashboardUrl);
                header('Location: ' . $dashboardUrl);
                exit();
            } else {
                $message = $result['message'];
                $message_type = 'error';
                debugLog("Login failed: " . $result['message']);
            }
        } catch (Exception $e) {
            error_log("Login exception: " . $e->getMessage());
            debugLog("Login exception: " . $e->getMessage());
            $message = 'An error occurred. Please try again.';
            $message_type = 'error';
            $debug_info[] = "Exception: " . $e->getMessage();
        }
    }
}

// End output buffering for HTML output
ob_end_flush();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Login</title>
    <link rel="stylesheet" href="Models/css/login.css">
</head>
<body>
    <!-- Header container -->
    <header class="navigation-header">
        <div id="header-container">
            <div class="loading">Loading header...</div>
        </div>
    </header>

    <div class="main-container">
        <div class="login-container">
            <div class="login-header">
                <h1>Welcome Back</h1>
                <p>Please sign in to your account</p>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo htmlspecialchars($message_type); ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($debug_info) && (isset($_GET['debug']) || true)): ?>
                <div class="debug-info">
                    <strong>Debug Info:</strong><br>
                    <?php foreach ($debug_info as $info): ?>
                        <?php echo htmlspecialchars($info); ?><br>
                    <?php endforeach; ?>
                    <br>
                    <strong>Session Data:</strong><br>
                    <pre><?php print_r($_SESSION); ?></pre>
                </div>
            <?php endif; ?>

            <form method="POST" action="" id="loginForm">
                <div class="form-group">
                    <label for="username_or_email">Username or Email</label>
                    <input 
                        type="text" 
                        id="username_or_email" 
                        name="username_or_email" 
                        value="<?php echo htmlspecialchars($form_data['username_or_email']); ?>"
                        required 
                        autocomplete="username"
                    >
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div style="position: relative;">
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            required 
                            autocomplete="current-password"
                            style="padding-right: 45px;"
                        >
                        <button 
                            type="button" 
                            id="togglePassword" 
                            style="
                                position: absolute;
                                right: 10px;
                                top: 50%;
                                transform: translateY(-50%);
                                background: none;
                                border: none;
                                cursor: pointer;
                                font-size: 1.2rem;
                                color: #667eea;
                                padding: 5px;
                                display: flex;
                                align-items: center;
                                justify-content: center;
                            "
                            title="Show/Hide Password"
                        >
                            👁️
                        </button>
                    </div>
                </div>

                <div class="checkbox-group">
                    <input 
                        type="checkbox" 
                        id="remember_me" 
                        name="remember_me" 
                        <?php echo ($form_data['remember_me'] ? 'checked' : ''); ?>
                    >
                    <label for="remember_me">Remember me for 30 days</label>
                </div>

                <button type="submit" class="login-button" id="loginButton">
                    <span id="loginText">Sign In</span>
                </button>
            </form>

            <div class="forgot-password">
                <a href="forgot-password.php">Forgot your password?</a>
            </div>
            
            <div style="margin-top: 20px; padding: 10px; background: #f0f0f0; border-radius: 5px; font-size: 12px;">
                <strong>Debug Links:</strong><br>
                <a href="debug_session.php">Check Session Status</a> | 
                <a href="debug_login.php">Login Diagnostics</a>
            </div>
        </div>
    </div>

    <!-- Include the loader script that loads header tabs-->
    <script src="Models/js/header_loader.js"></script>
    <script src="Models/js/header_tabs.js"></script>

    <script>
        // Password visibility toggle
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');
        
        togglePassword.addEventListener('click', function() {
            // Toggle the type attribute
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            
            // Toggle the eye icon
            this.textContent = type === 'password' ? '👁️' : '🙈';
            this.title = type === 'password' ? 'Show Password' : 'Hide Password';
        });
        
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const button = document.getElementById('loginButton');
            const buttonText = document.getElementById('loginText');
            
            button.disabled = true;
            buttonText.innerHTML = '<span class="loading"></span>Signing in...';
        });

        // Prevent multiple form submissions
        let submitted = false;
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            if (submitted) {
                e.preventDefault();
                return false;
            }
            submitted = true;
        });

        // Basic client-side validation
        document.getElementById('username_or_email').addEventListener('blur', function() {
            if (this.value.trim() === '') {
                this.style.borderColor = '#dc3545';
            } else {
                this.style.borderColor = '#e1e5e9';
            }
        });

        document.getElementById('password').addEventListener('blur', function() {
            if (this.value === '') {
                this.style.borderColor = '#dc3545';
            } else {
                this.style.borderColor = '#e1e5e9';
            }
        });
    </script>
</body>
</html>