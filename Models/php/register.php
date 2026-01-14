<?php
require_once __DIR__ . '/../../classes/Security.php';
Security::secureSession();
Security::setSecurityHeaders();
$csrf_token = Security::getCsrfToken();

// Determine root page for CSS path
$scriptPath = $_SERVER['SCRIPT_NAME'];
$pathParts = explode('/', $scriptPath);
array_pop($pathParts); // remove filename
array_pop($pathParts); // remove php
array_pop($pathParts); // remove Models
$root_page = implode('/', $pathParts) . '/';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Registration System</title>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($root_page); ?>Models/css/register.css">
</head>
<body>
    <div class="container">
        <h1>Create New User</h1>

        <div id="messageContainer"></div>

        <form id="userForm">
            <input type="hidden" id="csrf_token" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required minlength="3" maxlength="50">
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required>
            </div>

            <div class="form-group">
                <label for="firstName">First Name</label>
                <input type="text" id="firstName" name="firstName" required maxlength="100">
            </div>

            <div class="form-group">
                <label for="lastName">Last Name</label>
                <input type="text" id="lastName" name="lastName" required maxlength="100">
            </div>

            <div class="form-group">
                <label for="role">User Role</label>
                <select id="role" name="role" required>
                    <option value="">Select a role</option>
                    <option value="personal_user">Personal User</option>
                    <option value="professional_user">Professional User</option>
                </select>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required minlength="8">
                
                <div class="password-requirements">
                    <div class="requirement" id="req-length">At least 8 characters</div>
                    <div class="requirement" id="req-uppercase">At least one uppercase letter</div>
                    <div class="requirement" id="req-lowercase">At least one lowercase letter</div>
                    <div class="requirement" id="req-number">At least one number</div>
                    <div class="requirement" id="req-special">At least one special character</div>
                </div>
            </div>

            <div class="form-group">
                <label for="confirmPassword">Confirm Password</label>
                <input type="password" id="confirmPassword" name="confirmPassword" required>
            </div>

            <button type="submit" class="submit-btn" id="submitBtn">
                Create User Account
            </button>
        </form>

        <div id="userPreview" class="user-preview" style="display: none;">
            <h3>Registration Pending</h3>
            <div class="user-info" id="userInfo"></div>
            <div class="message info" style="margin-top: 15px;">
                <strong>⏳ Pending Admin Approval</strong><br>
                Your account has been created but requires administrator verification before you can log in. 
                You will receive an email once your account is approved.
            </div>
        </div>
    </div>

    <script>
        // Password validation
        const passwordInput = document.getElementById('password');
        const confirmPasswordInput = document.getElementById('confirmPassword');

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

        function showMessage(message, type) {
            const container = document.getElementById('messageContainer');
            container.innerHTML = `<div class="message ${type}">${message}</div>`;
        }

        function clearMessage() {
            document.getElementById('messageContainer').innerHTML = '';
        }

        function displayUserInfo(user) {
            const userInfo = document.getElementById('userInfo');
            const userPreview = document.getElementById('userPreview');
            
            userInfo.innerHTML = `
                <strong>ID:</strong> <span>${user.id}</span>
                <strong>Username:</strong> <span>${user.username}</span>
                <strong>Email:</strong> <span>${user.email}</span>
                <strong>Name:</strong> <span>${user.first_name} ${user.last_name}</span>
                <strong>Role:</strong> <span>${user.role}</span>
                <strong>Status:</strong> <span style="color: #f59e0b;">⏳ Pending Verification</span>
            `;
            
            userPreview.style.display = 'block';
        }

        // Trigger admin notification
        async function triggerAdminNotification(userId, username, email) {
            try {
                await fetch('Models/php/trigger_notification.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        user_id: userId,
                        username: username,
                        email: email
                    })
                });
            } catch (error) {
                console.error('Failed to trigger notification:', error);
            }
        }

        // Form submission
        document.getElementById('userForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            clearMessage();

            const submitBtn = document.getElementById('submitBtn');
            const originalText = submitBtn.innerHTML;

            // Client-side validation
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirmPassword').value;

            if (!validatePassword()) {
                showMessage('❌ Password does not meet requirements', 'error');
                return;
            }

            if (password !== confirmPassword) {
                showMessage('❌ Passwords do not match', 'error');
                return;
            }

            // Show loading state
            submitBtn.innerHTML = '<span class="loading"></span>Creating User...';
            submitBtn.disabled = true;

            const formData = {
                username: document.getElementById('username').value.trim(),
                email: document.getElementById('email').value.trim(),
                firstName: document.getElementById('firstName').value.trim(),
                lastName: document.getElementById('lastName').value.trim(),
                role: document.getElementById('role').value,
                password: password,
                csrf_token: document.getElementById('csrf_token').value
            };

            try {
                // Send request to PHP backend
                const response = await fetch('create_user.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': document.getElementById('csrf_token').value
                    },
                    body: JSON.stringify(formData)
                });

                const result = await response.json();

                if (result.success) {
                    showMessage(`✅ ${result.message}! Your account is pending admin approval.`, 'success');
                    displayUserInfo(result.user);
                    
                    // Trigger admin notification
                    await triggerAdminNotification(
                        result.user.id, 
                        result.user.username,
                        result.user.email
                    );
                    
                    this.reset();
                    validatePassword(); // Reset password requirements display
                } else {
                    showMessage('❌ ' + result.message, 'error');
                }

            } catch (error) {
                showMessage('❌ Network error: Unable to connect to server. Make sure create_user.php is available.', 'error');
                console.error('Error:', error);
            }

            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        });

        // Real-time validation feedback
        document.getElementById('confirmPassword').addEventListener('input', function() {
            const password = passwordInput.value;
            const confirmPassword = this.value;
            
            if (confirmPassword && password !== confirmPassword) {
                this.style.borderColor = '#dc3545';
            } else {
                this.style.borderColor = '#e0e0e0';
            }
        });
    </script>
</body>
</html>