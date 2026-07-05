<?php
// forgot-password.php - Request an admin-approved password reset
require_once __DIR__ . '/classes/Env.php';
require_once __DIR__ . '/classes/Security.php';

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

// If already logged in, no need for this page
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']) {
    $dashboardUrl = ($_SESSION['root_page'] ?? '/') . 'dashboard.php';
    ob_end_clean();
    header('Location: ' . $dashboardUrl);
    exit();
}

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username_or_email'])) {
    if (!Security::validateCsrfToken()) {
        $message = 'Invalid request. Please try again.';
        $message_type = 'error';
    } else {
        $username_or_email = trim($_POST['username_or_email'] ?? '');

        if (empty($username_or_email)) {
            $message = 'Please enter your username or email.';
            $message_type = 'error';
        } else {
            // Same generic message for every outcome so account existence is not leaked
            try {
                require_once __DIR__ . '/classes/PasswordReset.php';
                (new PasswordReset())->requestReset($username_or_email);
            } catch (Exception $e) {
                error_log("Forgot password error: " . $e->getMessage());
            }
            $message = 'If an eligible account exists, a password reset request has been recorded. An administrator will review it and provide you with a reset link.';
            $message_type = 'success';
        }
        Security::regenerateCsrfToken();
    }
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
    <title>Forgot Password</title>
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
                <h1>Reset Your Password</h1>
                <p>Enter your username or email. An administrator will review your request and provide you with a reset link.</p>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo htmlspecialchars($message_type); ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <?php if ($message_type !== 'success'): ?>
            <form method="POST" action="" id="forgotForm">
                <?php echo Security::csrfField(); ?>
                <div class="form-group">
                    <label for="username_or_email">Username or Email</label>
                    <input
                        type="text"
                        id="username_or_email"
                        name="username_or_email"
                        required
                        autocomplete="username"
                    >
                </div>

                <button type="submit" class="login-button" id="submitButton">
                    <span id="submitText">Request Reset</span>
                </button>
            </form>
            <?php endif; ?>

            <div class="forgot-password">
                <a href="login.php">Back to Sign In</a>
            </div>
        </div>
    </div>

    <!-- Include the loader script that loads header tabs-->
    <script src="Models/js/header_loader.js"></script>
    <script src="Models/js/header_tabs.js"></script>

    <script>
        const form = document.getElementById('forgotForm');
        if (form) {
            let submitted = false;
            form.addEventListener('submit', function(e) {
                if (submitted) {
                    e.preventDefault();
                    return false;
                }
                submitted = true;

                const button = document.getElementById('submitButton');
                const buttonText = document.getElementById('submitText');
                button.disabled = true;
                buttonText.innerHTML = '<span class="loading"></span>Submitting...';
            });
        }
    </script>
</body>
</html>
