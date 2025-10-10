<?php
// login_improved.php - PHP processing BEFORE any HTML output
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// If already logged in, redirect immediately
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']) {
    header('Location: dashboard.php');
    exit();
}

$message = '';
$message_type = '';
$form_data = [
    'username_or_email' => '',
    'remember_me' => false
];

// Process login form submission ONLY if form was actually submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username_or_email'])) {
    require_once 'classes/Auth.php';
    
    $username_or_email = trim($_POST['username_or_email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember_me = isset($_POST['remember_me']);
    
    // Preserve form data for repopulation on error
    $form_data['username_or_email'] = $username_or_email;
    $form_data['remember_me'] = $remember_me;
    
    if (empty($username_or_email) || empty($password)) {
        $message = 'Please fill in all required fields.';
        $message_type = 'error';
    } else {
        try {
            $auth = new Auth();
            $result = $auth->login($username_or_email, $password, $remember_me);
            
            if ($result['success']) {
                // Debug: Log successful login
                error_log("Login successful for user: " . $username_or_email);
                
                // Redirect immediately - no HTML has been output yet
                header('Location: dashboard.php');
                exit();
            } else {
                $message = $result['message'];
                $message_type = 'error';
                error_log("Login failed for user: " . $username_or_email . " - " . $result['message']);
            }
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            $message = 'An error occurred. Please try again.';
            $message_type = 'error';
        }
    }
}

// NOW start the HTML output
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
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        required 
                        autocomplete="current-password"
                    >
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
        </div>
    </div>

    <!-- Include the loader script that loads header tabs-->
    <script src="Models/js/header_loader.js"></script>
    <script src="Models/js/header_tabs.js"></script>

    <script>
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