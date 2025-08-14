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
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .login-container {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 400px;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .login-header h1 {
            color: #333;
            margin-bottom: 0.5rem;
        }
        
        .login-header p {
            color: #666;
            font-size: 0.9rem;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: 500;
        }
        
        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e1e5e9;
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .checkbox-group input[type="checkbox"] {
            margin-right: 0.5rem;
        }
        
        .checkbox-group label {
            margin-bottom: 0;
            font-size: 0.9rem;
            color: #666;
        }
        
        .login-button {
            width: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0.75rem;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, opacity 0.2s;
        }
        
        .login-button:hover {
            transform: translateY(-2px);
            opacity: 0.9;
        }
        
        .login-button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        .alert {
            padding: 0.75rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }
        
        .alert-error {
            background-color: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }
        
        .alert-success {
            background-color: #efe;
            color: #383;
            border: 1px solid #cfc;
        }
        
        .forgot-password {
            text-align: center;
            margin-top: 1rem;
        }
        
        .forgot-password a {
            color: #667eea;
            text-decoration: none;
            font-size: 0.9rem;
        }
        
        .forgot-password a:hover {
            text-decoration: underline;
        }
        
        .loading {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid #ffffff;
            border-radius: 50%;
            border-top-color: transparent;
            animation: spin 1s ease-in-out infinite;
            margin-right: 0.5rem;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .debug-info {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 1rem;
            margin-top: 1rem;
            font-size: 0.85rem;
            color: #6c757d;
        }
    </style>
</head>
<body>
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

        <!-- Debug information (remove in production) -->
        <?php if (isset($_GET['debug'])): ?>
        <div class="debug-info">
            <strong>Debug Info:</strong><br>
            Session Status: <?php echo session_status(); ?><br>
            Session ID: <?php echo session_id(); ?><br>
            POST Method: <?php echo $_SERVER['REQUEST_METHOD']; ?><br>
            Headers Sent: <?php echo headers_sent() ? 'Yes' : 'No'; ?><br>
            Current URL: <?php echo $_SERVER['REQUEST_URI']; ?><br>
        </div>
        <?php endif; ?>
    </div>

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