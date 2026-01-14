<!DOCTYPE html>
<?php 
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Check authentication
require_once '../classes/Auth.php';
$auth = new Auth();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    $root_page = $_SESSION['root_page'] ?? '/';
    header('Location: ' . $root_page . 'login.php');
    exit();
}

// Check if user is admin
$userRole = $auth->getUserRole($_SESSION['user_id']);
if ($userRole !== 'admin') {
    header('Location: ' . ($_SESSION['root_page'] ?? '/') . 'dashboard.php');
    exit();
}

// Get root page for constructing URLs
$root_page = $_SESSION['root_page'] ?? '/';
?>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Verification - Admin Panel</title>
    <link rel="stylesheet" href="<?php echo $root_page; ?>Models/css/dashboard.css">
    <style>
        .verification-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        
        .page-header {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .page-header h1 {
            margin: 0 0 0.5rem 0;
            color: #1e293b;
        }
        
        .page-header p {
            margin: 0;
            color: #64748b;
        }
        
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-box {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #3b82f6;
        }
        
        .stat-label {
            color: #64748b;
            margin-top: 0.5rem;
        }
        
        .users-table {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .table-header {
            background: #f8fafc;
            padding: 1rem 2rem;
            border-bottom: 1px solid #e2e8f0;
            font-weight: 600;
            display: grid;
            grid-template-columns: 1fr 2fr 1fr 1.5fr 1.5fr;
            gap: 1rem;
        }
        
        .user-row {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid #e2e8f0;
            display: grid;
            grid-template-columns: 1fr 2fr 1fr 1.5fr 1.5fr;
            gap: 1rem;
            align-items: center;
            transition: background-color 0.2s;
        }
        
        .user-row:hover {
            background: #f8fafc;
        }
        
        .user-info {
            display: flex;
            flex-direction: column;
        }
        
        .user-name {
            font-weight: 600;
            color: #1e293b;
        }
        
        .user-email {
            font-size: 0.875rem;
            color: #64748b;
        }
        
        .user-role {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .role-admin {
            background: #fee;
            color: #dc2626;
        }
        
        .role-professional {
            background: #dbeafe;
            color: #1d4ed8;
        }
        
        .role-personal {
            background: #dcfce7;
            color: #166534;
        }
        
        .user-date {
            font-size: 0.875rem;
            color: #64748b;
        }
        
        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn-approve {
            background: #10b981;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            transition: background 0.2s;
        }
        
        .btn-approve:hover {
            background: #059669;
        }
        
        .btn-reject {
            background: #ef4444;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            transition: background 0.2s;
        }
        
        .btn-reject:hover {
            background: #dc2626;
        }
        
        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #64748b;
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            display: block;
        }
        
        .loading {
            text-align: center;
            padding: 2rem;
            color: #64748b;
        }
        
        .toast {
            position: fixed;
            top: 2rem;
            right: 2rem;
            background: white;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            display: none;
            z-index: 1000;
            border-left: 4px solid #10b981;
        }
        
        .toast.show {
            display: block;
            animation: slideIn 0.3s ease;
        }
        
        .toast.error {
            border-left-color: #ef4444;
        }
        
        @keyframes slideIn {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: #3b82f6;
            text-decoration: none;
            margin-bottom: 1rem;
            font-weight: 500;
        }
        
        .back-button:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <!-- Include the loader script that loads header tabs-->
    <script src="<?php echo $root_page; ?>Models/js/header_loader.js"></script>
    <script src="<?php echo $root_page; ?>Models/js/header_tabs.js"></script>
    
    <!--Header tabs-->
    <div id="header-container">
        <div class="loading">Loading header...</div>
    </div>
    
    <div class="verification-container">
        <a href="<?php echo $root_page; ?>dashboards/professional_dashboard.php" class="back-button">
            ← Back to Dashboard
        </a>
        
        <div class="page-header">
            <h1>User Verification</h1>
            <p>Review and approve pending user registrations</p>
        </div>
        
        <div class="stats-row">
            <div class="stat-box">
                <div class="stat-number" id="pendingCount">-</div>
                <div class="stat-label">Pending Verifications</div>
            </div>
        </div>
        
        <div class="users-table">
            <div class="table-header">
                <div>Username</div>
                <div>Email / Name</div>
                <div>Role</div>
                <div>Registration Date</div>
                <div>Actions</div>
            </div>
            
            <div id="usersContainer">
                <div class="loading">Loading pending users...</div>
            </div>
        </div>
    </div>
    
    <div id="toast" class="toast"></div>
    
    <script>
        const ROOT_PAGE = '<?php echo addslashes($root_page); ?>';
        
        // Load pending users
        async function loadPendingUsers() {
            try {
                const response = await fetch(ROOT_PAGE + 'Models/php/get_pending_users.php');
                const data = await response.json();
                
                if (!data.success) {
                    showToast(data.message, 'error');
                    return;
                }
                
                // Update count
                document.getElementById('pendingCount').textContent = data.count;
                
                // Render users
                const container = document.getElementById('usersContainer');
                
                if (data.users.length === 0) {
                    container.innerHTML = `
                        <div class="empty-state">
                            <span style="font-size: 4rem;">✅</span>
                            <h3>All Clear!</h3>
                            <p>No pending user verifications at this time.</p>
                        </div>
                    `;
                    return;
                }
                
                container.innerHTML = data.users.map(user => `
                    <div class="user-row" data-user-id="${user.id}">
                        <div class="user-name">${escapeHtml(user.username)}</div>
                        <div class="user-info">
                            <div class="user-email">${escapeHtml(user.email)}</div>
                            <div style="font-size: 0.875rem; color: #64748b; margin-top: 0.25rem;">
                                ${escapeHtml(user.first_name || '')} ${escapeHtml(user.last_name || '')}
                            </div>
                        </div>
                        <div>
                            <span class="user-role role-${user.role || 'personal'}">
                                ${user.role || 'personal_user'}
                            </span>
                        </div>
                        <div class="user-date">${formatDate(user.created_at)}</div>
                        <div class="action-buttons">
                            <button class="btn-approve" onclick="verifyUser(${user.id}, 'approve')">
                                ✓ Approve
                            </button>
                            <button class="btn-reject" onclick="verifyUser(${user.id}, 'reject')">
                                ✗ Reject
                            </button>
                        </div>
                    </div>
                `).join('');
                
            } catch (error) {
                console.error('Error loading users:', error);
                showToast('Failed to load pending users', 'error');
            }
        }
        
        // Verify user (approve or reject)
        async function verifyUser(userId, action) {
            const row = document.querySelector(`[data-user-id="${userId}"]`);
            const buttons = row.querySelectorAll('button');
            
            // Disable buttons
            buttons.forEach(btn => btn.disabled = true);
            
            try {
                const response = await fetch(ROOT_PAGE + 'Models/php/verify_user.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        user_id: userId,
                        action: action
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showToast(data.message, 'success');
                    
                    // Fade out and remove row
                    row.style.opacity = '0';
                    row.style.transition = 'opacity 0.3s';
                    
                    setTimeout(() => {
                        row.remove();
                        
                        // Reload if no more users
                        if (document.querySelectorAll('.user-row').length === 0) {
                            loadPendingUsers();
                        } else {
                            // Update count
                            const currentCount = parseInt(document.getElementById('pendingCount').textContent);
                            document.getElementById('pendingCount').textContent = currentCount - 1;
                        }
                    }, 300);
                } else {
                    showToast(data.message, 'error');
                    buttons.forEach(btn => btn.disabled = false);
                }
                
            } catch (error) {
                console.error('Error verifying user:', error);
                showToast('Failed to process request', 'error');
                buttons.forEach(btn => btn.disabled = false);
            }
        }
        
        // Helper functions
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
        
        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.className = 'toast show ' + type;
            
            setTimeout(() => {
                toast.classList.remove('show');
            }, 3000);
        }
        
        // Load users on page load
        document.addEventListener('DOMContentLoaded', loadPendingUsers);
    </script>
</body>
</html>