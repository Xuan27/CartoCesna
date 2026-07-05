<?php
// reset-password.php - Set a new password using an admin-issued one-time reset link
require_once __DIR__ . '/classes/Env.php';
require_once __DIR__ . '/classes/Security.php';
require_once __DIR__ . '/classes/PasswordReset.php';

Env::load();
if (Env::isDebug()) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}
ini_set('log_errors', 1);

ob_start();

Security::secureSession();
Security::setSecurityHeaders();
Security::enforceHttps();

$message = '';
$message_type = '';
$show_form = false;
$reset_done = false;
$token = $_POST['token'] ?? $_GET['token'] ?? '';

try {
    $passwordReset = new PasswordReset();

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
        if (!Security::validateCsrfToken()) {
            $message = 'Invalid request. Please try again.';
            $message_type = 'error';
            $show_form = $passwordReset->validateToken($token) !== null;
        } else {
            $password = $_POST['password'] ?? '';
            $password_confirm = $_POST['password_confirm'] ?? '';

            if ($password !== $password_confirm) {
                $message = 'Passwords do not match.';
                $message_type = 'error';
                $show_form = $passwordReset->validateToken($token) !== null;
            } else {
                $result = $passwordReset->performReset($token, $password);
                $message = $result['message'];
                if ($result['success']) {
                    $message_type = 'success';
                    $reset_done = true;
                } else {
                    $message_type = 'error';
                    // Validation errors keep the token usable; re-show the form if it's still valid
                    $show_form = $passwordReset->validateToken($token) !== null;
                }
            }
            Security::regenerateCsrfToken();
        }
    } else {
        // GET: only show the form for a valid, unexpired, approved token
        $show_form = $passwordReset->validateToken($token) !== null;
        if (!$show_form) {
            $message = 'This reset link is invalid or has expired.';
            $message_type = 'error';
        }
    }
} catch (Exception $e) {
    error_log("Reset password error: " . $e->getMessage());
    $message = 'An error occurred. Please try again.';
    $message_type = 'error';
    $show_form = false;
}

// Close session to release lock for AJAX requests
session_write_close();

ob_end_flush();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set New Password</title>
    <link rel="stylesheet" href="Models/css/login.css">
    <style>
        .password-requirements {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-top: 10px;
            font-size: 14px;
        }

        .requirement {
            display: flex;
            align-items: center;
            margin-bottom: 5px;
            color: #666;
        }

        .requirement.met {
            color: #28a745;
        }

        .requirement::before {
            content: "✗";
            margin-right: 8px;
            font-weight: bold;
        }

        .requirement.met::before {
            content: "✓";
            color: #28a745;
        }
    </style>
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
                <h1>Set New Password</h1>
                <?php if ($show_form): ?>
                    <p>Choose a new password for your account</p>
                <?php endif; ?>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo htmlspecialchars($message_type); ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <?php if ($show_form): ?>
            <form method="POST" action="" id="resetForm">
                <?php echo Security::csrfField(); ?>
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                <div class="form-group">
                    <label for="password">New Password</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        required
                        minlength="8"
                        autocomplete="new-password"
                    >
                    <div class="password-requirements">
                        <div class="requirement" id="req-length">At least 8 characters</div>
                        <div class="requirement" id="req-uppercase">At least one uppercase letter</div>
                        <div class="requirement" id="req-lowercase">At least one lowercase letter</div>
                        <div class="requirement" id="req-number">At least one number</div>
                        <div class="requirement" id="req-special">At least one special character</div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password_confirm">Confirm New Password</label>
                    <input
                        type="password"
                        id="password_confirm"
                        name="password_confirm"
                        required
                        autocomplete="new-password"
                    >
                </div>

                <button type="submit" class="login-button" id="submitButton">
                    <span id="submitText">Reset Password</span>
                </button>
            </form>
            <?php endif; ?>

            <div class="forgot-password">
                <a href="login.php"><?php echo $reset_done ? 'Sign in with your new password' : 'Back to Sign In'; ?></a>
            </div>
        </div>
    </div>

    <!-- Include the loader script that loads header tabs-->
    <script src="Models/js/header_loader.js"></script>
    <script src="Models/js/header_tabs.js"></script>

    <script>
        const form = document.getElementById('resetForm');
        if (form) {
            const passwordInput = document.getElementById('password');

            function validatePassword() {
                const password = passwordInput.value;
                const requirements = [
                    { id: 'req-length', test: password.length >= 8 },
                    { id: 'req-uppercase', test: /[A-Z]/.test(password) },
                    { id: 'req-lowercase', test: /[a-z]/.test(password) },
                    { id: 'req-number', test: /\d/.test(password) },
                    { id: 'req-special', test: /[!@#$%^&*(),.?":{}|<>]/.test(password) }
                ];

                requirements.forEach(req => {
                    const element = document.getElementById(req.id);
                    if (req.test) {
                        element.classList.add('met');
                    } else {
                        element.classList.remove('met');
                    }
                });

                return requirements.every(req => req.test);
            }

            passwordInput.addEventListener('input', validatePassword);

            let submitted = false;
            form.addEventListener('submit', function(e) {
                if (!validatePassword()) {
                    e.preventDefault();
                    alert('Please meet all password requirements.');
                    return false;
                }

                const confirm = document.getElementById('password_confirm').value;
                if (passwordInput.value !== confirm) {
                    e.preventDefault();
                    alert('Passwords do not match.');
                    return false;
                }

                if (submitted) {
                    e.preventDefault();
                    return false;
                }
                submitted = true;

                const button = document.getElementById('submitButton');
                const buttonText = document.getElementById('submitText');
                button.disabled = true;
                buttonText.innerHTML = '<span class="loading"></span>Resetting...';
            });
        }
    </script>
</body>
</html>
