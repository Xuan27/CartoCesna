<!DOCTYPE html>

<?php 
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

// Check if user is admin or professional
$userRole = $auth->getUserRole($_SESSION['user_id']);
if ($userRole !== 'admin' && $userRole !== 'professional_user') {
    header('Location: ' . ($_SESSION['root_page'] ?? '/') . 'dashboard.php');
    exit();
}

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
?>
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
                    <option value="personal">Personal</option>
                    <option value="professional">Professional</option>
                    <option value="client">Client Work</option>
                </select>
                <select class="filter-select" id="statusFilter">
                    <option value="">All Status</option>
                    <option value="completed">Completed</option>
                    <option value="in-progress">In Progress</option>
                    <option value="planning">Planning</option>
                </select>
            </div>

            <!--Survey Projects Manager dashboard link-->
            <div class="header">
                <h1>Professional Survey Projects</h1>
                <p>Manage and view all Professional Survey Projects</p>
                <button class="btn btn-primary" onclick="viewProject(1)">List Projects</button>
            </div>

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

        // Mock database data - replace this with actual database fetching
        const mockProjects = [
            {
                id: 1,
                title: "E-commerce Platform Redesign",
                type: "professional",
                status: "completed",
                description: "Complete redesign of the company's e-commerce platform with modern UI/UX principles and improved performance.",
                technologies: ["React", "Node.js", "MongoDB", "AWS"],
                startDate: "2024-01-15",
                completedDate: "2024-03-20",
                client: "TechCorp Solutions",
            },
            {
                id: 2,
                title: "Personal Portfolio Website",
                type: "personal",
                status: "completed",
                description: "A responsive portfolio website showcasing my work and skills with modern animations and interactive elements.",
                technologies: ["HTML5", "CSS3", "JavaScript", "GSAP"],
                startDate: "2023-11-01",
                completedDate: "2023-12-15"
            },
            {
                id: 3,
                title: "Mobile Banking App",
                type: "client",
                status: "in-progress",
                description: "Developing a secure mobile banking application with biometric authentication and real-time transactions.",
                technologies: ["React Native", "Firebase", "Node.js", "PostgreSQL"],
                startDate: "2024-02-01",
                estimatedCompletion: "2024-08-30",
                client: "FinanceFirst Bank"
            }
        ];

        let allProjects = [...mockProjects];
        let filteredProjects = [...mockProjects];

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

        // Simulate database fetch
        function fetchProjects() {
            return new Promise((resolve) => {
                setTimeout(() => {
                    resolve(mockProjects);
                }, 1500);
            });
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

            projectsGrid.innerHTML = filteredProjects.map(project => `
                <div class="project-card">
                    <div class="project-header">
                        <div>
                            <div class="project-title">${project.title}</div>
                            ${project.client ? `<div style="color: #95a5a6; font-size: 0.9em;">Client: ${project.client}</div>` : ''}
                        </div>
                        <span class="project-type type-${project.type}">${project.type}</span>
                    </div>
                    
                    <div class="project-description">${project.description}</div>
                    
                    <div class="project-meta">
                        <span class="project-status status-${project.status}">
                            ${project.status.replace('-', ' ').toUpperCase()}
                        </span>
                        <span class="project-date">
                            ${formatDate(project.status === 'completed' ? project.completedDate : 
                              project.status === 'in-progress' ? project.startDate : 
                              project.startDate)}
                        </span>
                    </div>
                    
                    <div class="project-tech">
                        ${project.technologies.map(tech => `<span class="tech-tag">${tech}</span>`).join('')}
                    </div>
                    
                    <div class="project-actions">
                        <button class="btn btn-primary" onclick="viewProject(${project.id})">View Details</button>
                        <button class="btn btn-secondary" onclick="editProject(${project.id})">Edit</button>
                    </div>
                </div>
            `).join('');
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

                const matchesType = !typeFilter_val || project.type === typeFilter_val;
                const matchesStatus = !statusFilter_val || project.status === statusFilter_val;

                return matchesSearch && matchesType && matchesStatus;
            });

            updateStatistics();
            renderProjects();
        }

        // Project actions
        function viewProject(projectId) {
            const project = allProjects.find(p => p.id === projectId);
            window.open(ROOT_PAGE + "Projects/Professional/survey_projects.php", '_blank');
        }

        function editProject(projectId) {
            const project = allProjects.find(p => p.id === projectId);
            alert(`Editing project: ${project.title}\n\nIn a real application, this would open an edit form.`);
        }

        // Initialize the dashboard when the page loads
        document.addEventListener('DOMContentLoaded', initDashboard);
    </script>
</body>
</html>