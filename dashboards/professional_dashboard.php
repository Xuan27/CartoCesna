<?php
require_once __DIR__ . '/../classes/Env.php';
require_once __DIR__ . '/../classes/Security.php';

// Initialize secure session
Security::secureSession();
Security::setSecurityHeaders();

// Check authentication
require_once __DIR__ . '/../classes/Auth.php';
$auth = new Auth();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    $root_page = $_SESSION['root_page'] ?? '/';
    header('Location: ' . $root_page . 'login.php');
    exit();
}

// Check if user is admin, enterprise, or professional
$userRole = $auth->getUserRole($_SESSION['user_id']);
$allowedRoles = ['admin', 'enterprise_user', 'professional_user', 'surveyor_ww'];
if (!in_array($userRole, $allowedRoles)) {
    header('Location: ' . ($_SESSION['root_page'] ?? '/') . 'dashboard.php');
    exit();
}

// Determine user type for access control
$userType = 'personal';
if ($userRole === 'admin') {
    $userType = 'admin';
} elseif ($userRole === 'enterprise_user') {
    $userType = 'enterprise';
} elseif (in_array($userRole, ['professional_user', 'surveyor_ww'])) {
    $userType = 'professional';
}

// Check if user can view survey projects
$canViewSurveyProjects = in_array($userType, ['admin', 'enterprise']);

// Handle logout
if (isset($_POST['logout'])) {
    $root_page = $_SESSION['root_page'] ?? '/';
    session_unset();
    session_destroy();
    header('Location: ' . $root_page . 'login.php');
    exit();
}

// Get root page for constructing URLs
$root_page = $_SESSION['root_page'] ?? '/';

// Close session to release lock for AJAX requests
session_write_close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Professional Projects</title>
    <link rel="stylesheet" href="<?php echo $root_page; ?>Models/css/dashboard.css">
    <link rel="stylesheet" href="<?php echo $root_page; ?>Models/css/articles-style.css">
</head>
<body>
<!-- Include the loader script that loads header tabs-->
<script src="<?php echo $root_page; ?>Models/js/header_loader.js"></script>
<script src="<?php echo $root_page; ?>Models/js/header_tabs.js"></script>

    <!--Header tabs-->
    <div id="header-container">
        <div class="loading">Loading header...</div>
    </div>
    
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-brand">Professional Dashboard</div>
            <div class="nav-user">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?>!</span>
                
                <?php if ($userRole === 'admin') { ?>
                    <!-- Inbox/Notifications Icon -->
                    <a href="<?php echo $root_page; ?>dashboards/verify_users.php" 
                       id="inboxIcon"
                       style="position: relative; color: white; text-decoration: none; margin: 0 10px; padding: 8px 12px; background: rgba(255,255,255,0.1); border-radius: 5px; display: inline-flex; align-items: center; gap: 5px;"
                       title="Pending User Verifications">
                        📬 Inbox
                        <span id="inboxBadge" 
                              style="display: none; position: absolute; top: -5px; right: -5px; background: #ef4444; color: white; border-radius: 50%; width: 20px; height: 20px; font-size: 11px; font-weight: bold; text-align: center; line-height: 20px;">
                            0
                        </span>
                    </a>
                    
                    <a href="<?php echo $root_page; ?>dashboards/personal_dashboard.php" 
                       style="color: white; text-decoration: none; margin: 0 10px; padding: 8px 15px; background: rgba(255,255,255,0.1); border-radius: 5px;">
                        🔄 Switch to Personal
                    </a>
                <?php } ?>
                
                <form method="POST" style="display: inline;">
                    <button type="submit" name="logout" class="logout-btn">Logout</button>
                </form>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="welcome-card">
            <h1>Professional Dashboard</h1>
            <p>Manage your professional and client projects</p>
            
            <div class="user-info">
                <h3>Your Account Information:</h3>
                <p><strong>Username:</strong> <?php echo htmlspecialchars($_SESSION['username'] ?? 'Unknown'); ?></p>
                <p><strong>User ID:</strong> <?php echo htmlspecialchars($_SESSION['user_id'] ?? 'Unknown'); ?></p>
                <p><strong>Role:</strong> <?php echo htmlspecialchars($userRole); ?></p>
                <p><strong>Dashboard Type:</strong> Professional Projects</p>
            </div>
        </div>

        <div class="dashboard-container">
            <div class="header">
                <h1>Projects Dashboard</h1>
                <p>Navigate through your personal and professional projects</p>
            </div>

            <div class="controls">
                <input type="text" class="search-box" id="searchInput" placeholder="Search projects by name, description, or technology...">
                <select class="filter-select" id="typeFilter">
                    <option value="">All Types</option>
                    <option value="development">Development</option>
                    <option value="gis">GIS</option>
                    <option value="land_surveying">Land Surveying</option>
                    <option value="3d_scanning">3D Scanning</option>
                    <option value="internal">Internal</option>
                    <option value="consulting">Consulting</option>
                    <option value="collaboration">Collaboration</option>
                    <option value="research">Research</option>
                    <option value="other">Other</option>
                </select>
                <select class="filter-select" id="statusFilter">
                    <option value="">All Status</option>
                    <option value="completed">Completed</option>
                    <option value="in_progress">In Progress</option>
                    <option value="planning">Planning</option>
                    <option value="on_hold">On Hold</option>
                </select>
                <button class="btn btn-primary" onclick="openAddProjectModal()">
                    <span style="margin-right: 5px;">+</span> Add Project
                </button>
            </div>

            <?php if ($canViewSurveyProjects): ?>
            <!--Survey Projects Manager dashboard link - Only for admin and enterprise users-->
            <div class="header survey-projects-section">
                <h1>Professional Survey Projects</h1>
                <p>Manage and view all Professional Survey Projects</p>
                <button class="btn btn-primary" onclick="openSurveyProjects()">List Projects</button>
            </div>
            <?php endif; ?>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number" id="totalProjects">0</div>
                    <div class="stat-label">Total Projects</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" id="completedProjects">0</div>
                    <div class="stat-label">Completed</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" id="inProgressProjects">0</div>
                    <div class="stat-label">In Progress</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" id="planningProjects">0</div>
                    <div class="stat-label">Planning</div>
                </div>
            </div>

            <div id="projectsContainer">
                <div class="loading" id="loadingIndicator">Loading projects...</div>
                <div class="projects-grid" id="projectsGrid" style="display: none;"></div>
                <div class="no-results" id="noResults" style="display: none;">
                    <h3>No projects found</h3>
                    <p>Try adjusting your search criteria or filters.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Add/Edit Project Modal -->
    <div id="projectModal" class="modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index: 9999; overflow-y: auto;">
        <div class="modal-content" style="max-width: 700px; margin: 2rem auto; background: white; border-radius: 12px; box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);">
            <div class="modal-header">
                <h2 id="projectModalTitle">Add New Project</h2>
                <button class="close-button" onclick="closeProjectModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="projectForm" onsubmit="saveProject(event)">
                    <input type="hidden" id="editProjectId" name="project_id">
                    <input type="hidden" id="isNewProject" name="is_new" value="1">

                    <div class="form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label class="form-label" for="projectTitle">Project Title *</label>
                            <input type="text" class="form-input" id="projectTitle" name="title" required placeholder="Enter project title">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="projectCategory">Project Category</label>
                            <select class="form-select" id="projectCategory" name="project_type">
                                <option value="personal">Personal</option>
                                <option value="professional">Professional</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="projectTypeCategory">Type</label>
                            <select class="form-select" id="projectTypeCategory" name="project_type_category">
                                <option value="development">Development</option>
                                <option value="gis">GIS</option>
                                <option value="land_surveying">Land Surveying</option>
                                <option value="3d_scanning">3D Scanning</option>
                                <option value="internal">Internal</option>
                                <option value="consulting">Consulting</option>
                                <option value="collaboration">Collaboration</option>
                                <option value="research">Research</option>
                                <option value="other">Other</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="projectStatus">Status</label>
                            <select class="form-select" id="projectStatus" name="status">
                                <option value="planning">Planning</option>
                                <option value="in_progress">In Progress</option>
                                <option value="review">Review</option>
                                <option value="on_hold">On Hold</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="projectPriority">Priority</label>
                            <select class="form-select" id="projectPriority" name="priority">
                                <option value="low">Low</option>
                                <option value="medium" selected>Medium</option>
                                <option value="high">High</option>
                                <option value="urgent">Urgent</option>
                            </select>
                        </div>

                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label class="form-label" for="projectDescription">Description</label>
                            <textarea class="form-textarea" id="projectDescription" name="description" rows="3" placeholder="Brief description of the project"></textarea>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="projectStartDate">Start Date</label>
                            <input type="date" class="form-input" id="projectStartDate" name="start_date">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="projectDueDate">Due Date</label>
                            <input type="date" class="form-input" id="projectDueDate" name="due_date">
                        </div>

                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label class="form-label" for="projectTechnologies">Technologies (comma-separated)</label>
                            <input type="text" class="form-input" id="projectTechnologies" name="technologies_input" placeholder="e.g., React, Node.js, PostgreSQL">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="projectUrl">Project URL</label>
                            <input type="url" class="form-input" id="projectUrl" name="project_url" placeholder="https://...">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="projectRepoUrl">Repository URL</label>
                            <input type="url" class="form-input" id="projectRepoUrl" name="repository_url" placeholder="https://github.com/...">
                        </div>

                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label class="form-label" for="projectNotes">Notes</label>
                            <textarea class="form-textarea" id="projectNotes" name="notes" rows="2" placeholder="Additional notes..."></textarea>
                        </div>

                        <div class="form-group" id="visibilityGroup">
                            <label class="form-label" for="projectVisibility">Visibility</label>
                            <select class="form-select" id="projectVisibility" name="visibility">
                                <option value="all">All Users</option>
                                <option value="professional" selected>Professional & Above</option>
                                <option value="enterprise_only">Enterprise Only</option>
                            </select>
                        </div>

                        <div class="form-group" style="display: flex; align-items: center; gap: 1rem;">
                            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                                <input type="checkbox" id="projectFeatured" name="is_featured">
                                <span>Featured Project</span>
                            </label>
                        </div>
                    </div>

                    <div class="form-actions" style="display: flex; justify-content: flex-end; gap: 1rem; margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid #e2e8f0;">
                        <button type="button" class="btn btn-secondary" onclick="closeProjectModal()">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Project</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
    <div id="toast" class="toast" style="position: fixed; top: 20px; right: 20px; padding: 15px 25px; border-radius: 8px; display: none; z-index: 10000;">
        <span id="toastMessage"></span>
    </div>

    <script>
        // Store root page for JavaScript use
        const ROOT_PAGE = '<?php echo addslashes($root_page); ?>';
        
        // Real-time notification system using Server-Sent Events
        let eventSource = null;
        
        function initializeNotifications() {
            if (!document.getElementById('inboxBadge')) {
                return; // Not admin, skip
            }
            
            // Close existing connection if any
            if (eventSource) {
                eventSource.close();
            }
            
            // Create SSE connection
            eventSource = new EventSource(ROOT_PAGE + 'Models/php/notifications_stream.php');
            
            eventSource.onmessage = function(event) {
                try {
                    const data = JSON.parse(event.data);
                    
                    if (data.type === 'pending_users_update') {
                        updateInboxBadge(data.count);
                        
                        // Show notification if there are new users
                        if (data.new_users && data.new_users.length > 0) {
                            showNotificationAlert(data.new_users);
                        }
                    }
                } catch (error) {
                    console.error('Error parsing SSE message:', error);
                }
            };
            
            eventSource.onerror = function(error) {
                console.error('SSE connection error:', error);
                eventSource.close();
                
                // Retry connection after 10 seconds
                setTimeout(() => {
                    console.log('Reconnecting to notification stream...');
                    initializeNotifications();
                }, 10000);
            };
            
            // Initial load
            fetchInitialCount();
        }
        
        async function fetchInitialCount() {
            try {
                const response = await fetch(ROOT_PAGE + 'Models/php/get_pending_users.php');
                const data = await response.json();
                
                if (data.success) {
                    updateInboxBadge(data.count);
                }
            } catch (error) {
                console.error('Error fetching initial count:', error);
            }
        }
        
        function updateInboxBadge(count) {
            const badge = document.getElementById('inboxBadge');
            const icon = document.getElementById('inboxIcon');
            
            if (!badge || !icon) return;
            
            if (count > 0) {
                badge.textContent = count;
                badge.style.display = 'block';
                badge.style.animation = 'pulse 2s infinite';
            } else {
                badge.style.display = 'none';
                badge.style.animation = 'none';
            }
        }
        
        function showNotificationAlert(newUsers) {
            // Create notification toast
            const toast = document.createElement('div');
            toast.style.cssText = `
                position: fixed;
                top: 80px;
                right: 20px;
                background: white;
                padding: 20px;
                border-radius: 12px;
                box-shadow: 0 10px 40px rgba(0,0,0,0.2);
                z-index: 10000;
                min-width: 300px;
                max-width: 400px;
                border-left: 4px solid #3b82f6;
                animation: slideInRight 0.3s ease;
            `;
            
            const userName = newUsers[0].username;
            const userEmail = newUsers[0].email;
            const count = newUsers.length;
            
            toast.innerHTML = `
                <div style="display: flex; align-items: start; gap: 15px;">
                    <div style="font-size: 2rem;">👤</div>
                    <div style="flex: 1;">
                        <div style="font-weight: bold; color: #1e293b; margin-bottom: 5px;">
                            New User Registration
                        </div>
                        <div style="color: #64748b; font-size: 0.9rem; margin-bottom: 10px;">
                            <strong>${userName}</strong><br>
                            ${userEmail}
                            ${count > 1 ? `<br><em>+${count - 1} more user(s)</em>` : ''}
                        </div>
                        <a href="${ROOT_PAGE}dashboards/verify_users.php" 
                           style="display: inline-block; background: #3b82f6; color: white; padding: 8px 16px; border-radius: 6px; text-decoration: none; font-size: 0.9rem; font-weight: 500;">
                            Review Now →
                        </a>
                    </div>
                    <button onclick="this.parentElement.parentElement.remove()" 
                            style="background: none; border: none; color: #94a3b8; cursor: pointer; font-size: 1.5rem; padding: 0; line-height: 1;">
                        ×
                    </button>
                </div>
            `;
            
            document.body.appendChild(toast);
            
            // Auto-remove after 10 seconds
            setTimeout(() => {
                toast.style.animation = 'slideOutRight 0.3s ease';
                setTimeout(() => toast.remove(), 300);
            }, 10000);
            
            // Play notification sound (optional)
            playNotificationSound();
        }
        
        function playNotificationSound() {
            // Create a simple notification beep
            try {
                const audioContext = new (window.AudioContext || window.webkitAudioContext)();
                const oscillator = audioContext.createOscillator();
                const gainNode = audioContext.createGain();
                
                oscillator.connect(gainNode);
                gainNode.connect(audioContext.destination);
                
                oscillator.frequency.value = 800;
                oscillator.type = 'sine';
                
                gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
                gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.3);
                
                oscillator.start(audioContext.currentTime);
                oscillator.stop(audioContext.currentTime + 0.3);
            } catch (error) {
                // Sound not supported or blocked
                console.log('Notification sound not available');
            }
        }
        
        // Add animation styles
        const style = document.createElement('style');
        style.textContent = `
            @keyframes pulse {
                0%, 100% { transform: scale(1); }
                50% { transform: scale(1.1); }
            }
            @keyframes slideInRight {
                from {
                    transform: translateX(400px);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
            @keyframes slideOutRight {
                from {
                    transform: translateX(0);
                    opacity: 1;
                }
                to {
                    transform: translateX(400px);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(style);
        
        // Initialize on page load
        if (document.getElementById('inboxBadge')) {
            initializeNotifications();
        }
        
        // Clean up SSE connection when page unloads
        window.addEventListener('beforeunload', () => {
            if (eventSource) {
                eventSource.close();
            }
        });
        
        // Auto-logout warning (optional)
        let warningTimer;
        let logoutTimer;
        
        function resetTimers() {
            clearTimeout(warningTimer);
            clearTimeout(logoutTimer);
            
            // Warn user 5 minutes before session expires
            warningTimer = setTimeout(() => {
                if (confirm('Your session will expire in 5 minutes. Click OK to extend your session.')) {
                    resetTimers();
                }
            }, 55 * 60 * 1000); // 55 minutes
            
            // Auto logout after 1 hour
            logoutTimer = setTimeout(() => {
                alert('Your session has expired. You will be redirected to the login page.');
                window.location.href = ROOT_PAGE + 'login.php';
            }, 60 * 60 * 1000); // 60 minutes
        }
        
        // Start timers on page load
        resetTimers();
        
        // Reset timers on user activity
        document.addEventListener('click', resetTimers);
        document.addEventListener('keypress', resetTimers);
        document.addEventListener('mousemove', resetTimers);

        // User type from PHP for access control
        const USER_TYPE = '<?php echo $userType; ?>';
        const CAN_VIEW_SURVEY = <?php echo $canViewSurveyProjects ? 'true' : 'false'; ?>;

        let allProjects = [];
        let filteredProjects = [];
        let userAccess = {};

        // DOM elements
        const searchInput = document.getElementById('searchInput');
        const typeFilter = document.getElementById('typeFilter');
        const statusFilter = document.getElementById('statusFilter');
        const projectsGrid = document.getElementById('projectsGrid');
        const loadingIndicator = document.getElementById('loadingIndicator');
        const noResults = document.getElementById('noResults');

        // Statistics elements
        const totalProjectsEl = document.getElementById('totalProjects');
        const completedProjectsEl = document.getElementById('completedProjects');
        const inProgressProjectsEl = document.getElementById('inProgressProjects');
        const planningProjectsEl = document.getElementById('planningProjects');

        // Fetch projects from API
        async function fetchProjects() {
            const response = await fetch(ROOT_PAGE + 'Models/php/projects_api.php?action=get_projects');
            const data = await response.json();

            if (data.success) {
                // Only show professional projects in professional dashboard
                const projects = [];

                // Add professional projects only
                if (data.projects.professional) {
                    data.projects.professional.forEach(p => {
                        projects.push({
                            ...p,
                            type: 'professional',
                            id: p.id || p.project_id,
                            technologies: p.technologies || []
                        });
                    });
                }

                return projects;
            } else {
                throw new Error(data.message || 'Failed to fetch projects');
            }
        }

        // Initialize dashboard
        async function initDashboard() {
            try {
                const projects = await fetchProjects();
                allProjects = projects;
                filteredProjects = projects;
                
                hideLoading();
                updateStatistics();
                renderProjects();
                setupEventListeners();
            } catch (error) {
                console.error('Error fetching projects:', error);
                showError('Failed to load projects. Please try again.');
            }
        }

        function hideLoading() {
            loadingIndicator.style.display = 'none';
            projectsGrid.style.display = 'grid';
        }

        function showError(message) {
            loadingIndicator.textContent = message;
        }

        function updateStatistics() {
            const completed = filteredProjects.filter(p => p.status === 'completed').length;
            const inProgress = filteredProjects.filter(p => p.status === 'in-progress').length;
            const planning = filteredProjects.filter(p => p.status === 'planning').length;

            animateCounter(totalProjectsEl, filteredProjects.length);
            animateCounter(completedProjectsEl, completed);
            animateCounter(inProgressProjectsEl, inProgress);
            animateCounter(planningProjectsEl, planning);
        }

        function animateCounter(element, target) {
            const current = parseInt(element.textContent) || 0;
            const increment = Math.ceil(Math.abs(target - current) / 20);
            
            if (current < target) {
                element.textContent = Math.min(current + increment, target);
                setTimeout(() => animateCounter(element, target), 50);
            } else if (current > target) {
                element.textContent = Math.max(current - increment, target);
                setTimeout(() => animateCounter(element, target), 50);
            }
        }

        function renderProjects() {
            if (filteredProjects.length === 0) {
                projectsGrid.style.display = 'none';
                noResults.style.display = 'block';
                return;
            }

            noResults.style.display = 'none';
            projectsGrid.style.display = 'grid';

            projectsGrid.innerHTML = filteredProjects.map(project => {
                const technologies = Array.isArray(project.technologies) ? project.technologies : [];
                const statusDisplay = (project.status || 'planning').replace(/_/g, ' ').toUpperCase();
                const statusClass = (project.status || 'planning').replace(/_/g, '-');
                const displayDate = project.completed_date || project.end_date || project.start_date || project.created_at;

                const projectType = project.project_type || 'development';
                // Format project type display (handle underscores and special cases)
                const projectTypeDisplay = projectType.replace(/_/g, ' ').replace(/3d/i, '3D').split(' ')
                    .map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');

                // Thumbnail image
                const thumbnailUrl = project.thumbnail_url ? ROOT_PAGE + project.thumbnail_url : null;
                const thumbnailHtml = thumbnailUrl
                    ? `<div class="project-thumbnail" style="width: 100%; height: 150px; overflow: hidden; border-radius: 8px 8px 0 0; margin: -20px -20px 15px -20px; width: calc(100% + 40px);">
                         <img src="${thumbnailUrl}" alt="${project.title}" style="width: 100%; height: 100%; object-fit: cover;">
                       </div>`
                    : '';

                // Website link
                const websiteLink = project.project_url
                    ? `<a href="${project.project_url}" target="_blank" class="btn btn-link" style="color: #3b82f6; text-decoration: none; font-size: 0.85em; display: inline-flex; align-items: center; gap: 4px;" title="Visit Website">
                         <span style="font-size: 1.1em;">🔗</span> Website
                       </a>`
                    : '';

                return `
                <div class="project-card">
                    ${thumbnailHtml}
                    <div class="project-header">
                        <div>
                            <div class="project-title">${project.title || 'Untitled Project'}</div>
                            ${project.subtitle ? `<div style="color: #95a5a6; font-size: 0.9em;">${project.subtitle}</div>` : ''}
                            <div style="color: #64748b; font-size: 0.8em; margin-top: 4px;">ID: ${project.project_id}</div>
                        </div>
                        <span class="project-type type-${projectType.replace(/_/g, '-')}">${projectTypeDisplay}</span>
                    </div>

                    <div class="project-description">${project.description || 'No description available.'}</div>

                    <div class="project-meta">
                        <span class="project-status status-${statusClass}">
                            ${statusDisplay}
                        </span>
                        <span class="project-date">
                            ${displayDate ? formatDate(displayDate) : 'No date'}
                        </span>
                    </div>

                    <div class="project-tech">
                        ${technologies.length > 0 ? technologies.map(tech => `<span class="tech-tag">${tech}</span>`).join('') : '<span style="color: #94a3b8; font-size: 0.85em;">No technologies specified</span>'}
                    </div>

                    <div class="project-actions" style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <button class="btn btn-primary" onclick="viewProjectDetails('${project.project_id}', 'professional')">View</button>
                            <button class="btn btn-secondary" onclick="editProject('${project.project_id}', 'professional')">Edit</button>
                        </div>
                        ${websiteLink}
                    </div>
                </div>
            `;
            }).join('');
        }

        function formatDate(dateString) {
            const options = { year: 'numeric', month: 'short', day: 'numeric' };
            return new Date(dateString).toLocaleDateString('en-US', options);
        }

        function setupEventListeners() {
            searchInput.addEventListener('input', filterProjects);
            typeFilter.addEventListener('change', filterProjects);
            statusFilter.addEventListener('change', filterProjects);
        }

        function filterProjects() {
            const searchTerm = searchInput.value.toLowerCase();
            const typeFilter_val = typeFilter.value;
            const statusFilter_val = statusFilter.value;

            filteredProjects = allProjects.filter(project => {
                const matchesSearch = !searchTerm || 
                    project.title.toLowerCase().includes(searchTerm) ||
                    project.description.toLowerCase().includes(searchTerm) ||
                    project.technologies.some(tech => tech.toLowerCase().includes(searchTerm)) ||
                    (project.client && project.client.toLowerCase().includes(searchTerm));

                const matchesType = !typeFilter_val || project.project_type === typeFilter_val;
                const matchesStatus = !statusFilter_val || project.status === statusFilter_val;

                return matchesSearch && matchesType && matchesStatus;
            });

            updateStatistics();
            renderProjects();
        }

        // Project actions
        function openSurveyProjects() {
            window.open(ROOT_PAGE + "Projects/Professional/survey_projects.php", '_blank');
        }

        function viewProjectDetails(projectId, projectType) {
            const project = allProjects.find(p => p.project_id === projectId);
            if (project) {
                // For now, show an alert with project details - can be enhanced to a modal
                const technologies = Array.isArray(project.technologies) ? project.technologies.join(', ') : 'None';
                alert(`Project: ${project.title}\n\nType: ${projectType}\nStatus: ${project.status}\nDescription: ${project.description || 'N/A'}\nTechnologies: ${technologies}`);
            }
        }

        function editProject(projectId, projectType) {
            const project = allProjects.find(p => p.project_id === projectId);
            if (project) {
                openEditProjectModal(project, projectType);
            }
        }

        // Modal functions
        function openAddProjectModal() {
            document.getElementById('projectModalTitle').textContent = 'Add New Project';
            document.getElementById('projectForm').reset();
            document.getElementById('editProjectId').value = '';
            document.getElementById('isNewProject').value = '1';
            document.getElementById('projectCategory').value = 'professional';

            // Show visibility field only for professional projects
            updateVisibilityField();

            const modal = document.getElementById('projectModal');
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden'; // Prevent body scrolling
        }

        function openEditProjectModal(project, projectType) {
            document.getElementById('projectModalTitle').textContent = 'Edit Project';
            document.getElementById('editProjectId').value = project.project_id;
            document.getElementById('isNewProject').value = '0';

            // Populate form fields
            document.getElementById('projectTitle').value = project.title || '';
            document.getElementById('projectCategory').value = projectType;
            document.getElementById('projectTypeCategory').value = project.project_type || 'development';
            document.getElementById('projectStatus').value = project.status || 'planning';
            document.getElementById('projectPriority').value = project.priority || 'medium';
            document.getElementById('projectDescription').value = project.description || '';
            document.getElementById('projectStartDate').value = project.start_date || '';
            document.getElementById('projectDueDate').value = project.due_date || project.end_date || '';

            const technologies = Array.isArray(project.technologies) ? project.technologies.join(', ') : '';
            document.getElementById('projectTechnologies').value = technologies;

            document.getElementById('projectUrl').value = project.project_url || '';
            document.getElementById('projectRepoUrl').value = project.repository_url || project.github_url || '';
            document.getElementById('projectNotes').value = project.notes || '';
            document.getElementById('projectVisibility').value = project.visibility || 'professional';
            document.getElementById('projectFeatured').checked = project.is_featured == 1;

            updateVisibilityField();

            const modal = document.getElementById('projectModal');
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        }

        function closeProjectModal() {
            document.getElementById('projectModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        function updateVisibilityField() {
            const category = document.getElementById('projectCategory').value;
            const visibilityGroup = document.getElementById('visibilityGroup');
            visibilityGroup.style.display = category === 'professional' ? 'block' : 'none';
        }

        // Listen for category change
        document.getElementById('projectCategory').addEventListener('change', updateVisibilityField);

        async function saveProject(event) {
            event.preventDefault();

            const form = event.target;
            const formData = new FormData(form);

            // Convert technologies input to array
            const techInput = document.getElementById('projectTechnologies').value;
            if (techInput) {
                const techArray = techInput.split(',').map(t => t.trim()).filter(t => t);
                formData.append('technologies', JSON.stringify(techArray));
            }

            formData.append('action', 'save_project');

            const submitBtn = form.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Saving...';

            try {
                const response = await fetch(ROOT_PAGE + 'Models/php/projects_api.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    showToast(data.message || 'Project saved successfully!', 'success');
                    closeProjectModal();
                    // Reload projects
                    initDashboard();
                } else {
                    showToast(data.message || 'Failed to save project', 'error');
                }
            } catch (error) {
                console.error('Error saving project:', error);
                showToast('An error occurred while saving', 'error');
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Save Project';
            }
        }

        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            const toastMessage = document.getElementById('toastMessage');

            toastMessage.textContent = message;
            toast.style.background = type === 'success' ? '#10b981' : '#ef4444';
            toast.style.color = 'white';
            toast.style.display = 'block';

            setTimeout(() => {
                toast.style.display = 'none';
            }, 3000);
        }

        // Close modal when clicking outside
        document.getElementById('projectModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeProjectModal();
            }
        });

        // Initialize the dashboard when the page loads
        document.addEventListener('DOMContentLoaded', initDashboard);
    </script>
</body>
</html>