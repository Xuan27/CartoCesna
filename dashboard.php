<?php
// dashboard.php - Protected page that requires authentication
session_start();

require_once 'classes/Auth.php';

$auth = new Auth();

// Check if user is logged in
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit();
}

// Handle logout
if (isset($_POST['logout'])) {
    $auth->logout();
    header('Location: login.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Secure App</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #f8fafc;
            min-height: 100vh;
            color: #334155;
        }

        .dashboard-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 32px;
            margin-bottom: 32px;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
        }

        .header h1 {
            font-size: 2rem;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 8px;
        }

        .header p {
            color: #64748b;
            font-size: 1rem;
        }

        .controls {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
            flex-wrap: wrap;
            align-items: center;
        }

        .search-box, .filter-select {
            padding: 12px 20px;
            border: none;
            border-radius: 25px;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            font-size: 16px;
            outline: none;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .search-box {
            flex: 1;
            min-width: 300px;
        }

        .search-box:focus, .filter-select:focus {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }

        .filter-select {
            min-width: 150px;
            cursor: pointer;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 24px;
            text-align: center;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
            transition: box-shadow 0.2s ease;
        }

        .stat-card:hover {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 8px;
        }

        .stat-label {
            color: #64748b;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .projects-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
        }

        .project-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 24px;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
            transition: box-shadow 0.2s ease, border-color 0.2s ease;
            position: relative;
        }

        .project-card:hover {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            border-color: #cbd5e1;
        }

        .project-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }

        .project-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 4px;
        }

        .project-type {
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .type-personal {
            background: #dcfce7;
            color: #166534;
        }

        .type-professional {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .type-client {
            background: #fce7f3;
            color: #be185d;
        }

        .project-description {
            color: #64748b;
            margin-bottom: 16px;
            line-height: 1.5;
            font-size: 0.875rem;
        }

        .project-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .project-status {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .status-completed {
            background: #dcfce7;
            color: #166534;
        }

        .status-in-progress {
            background: #fef3c7;
            color: #92400e;
        }

        .status-planning {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .project-date {
            color: #64748b;
            font-size: 0.75rem;
        }

        .project-tech {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 20px;
        }

        .tech-tag {
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #e2e8f0;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .project-actions {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 8px 16px;
            border: 1px solid transparent;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-primary {
            background: #3b82f6;
            color: white;
            border-color: #3b82f6;
        }

        .btn-primary:hover {
            background: #2563eb;
            border-color: #2563eb;
        }

        .btn-secondary {
            background: #ffffff;
            color: #374151;
            border-color: #d1d5db;
        }

        .btn-secondary:hover {
            background: #f9fafb;
            border-color: #9ca3af;
        }

        .loading {
            text-align: center;
            padding: 50px;
            font-size: 1.2em;
            color: #7f8c8d;
        }

        .no-results {
            text-align: center;
            padding: 48px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            margin-top: 24px;
        }

        .no-results h3 {
            color: #374151;
            margin-bottom: 8px;
            font-weight: 500;
        }

        .no-results p {
            color: #64748b;
            font-size: 0.875rem;
        }

        @media (max-width: 768px) {
            .controls {
                flex-direction: column;
            }
            
            .search-box {
                min-width: 100%;
            }
            
            .projects-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            line-height: 1.6;
        }
        
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .nav-brand {
            font-size: 1.5rem;
            font-weight: bold;
        }
        
        .nav-user {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .logout-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            border: 1px solid rgba(255,255,255,0.3);
            padding: 0.5rem 1rem;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .logout-btn:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .welcome-card {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .welcome-card h1 {
            color: #333;
            margin-bottom: 1rem;
        }
        
        .user-info {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 5px;
            border-left: 4px solid #667eea;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-top: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <!--Header tabs-->
    <div id="header-container">
        <div class="loading">Loading header...</div>
    </div>
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-brand">Secure Dashboard</div>
            <div class="nav-user">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span>
                <form method="POST" style="display: inline;">
                    <button type="submit" name="logout" class="logout-btn">Logout</button>
                </form>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="welcome-card">
            <h1>Dashboard</h1>
            <p>You have successfully logged in to the secure area.</p>
            
            <div class="user-info">
                <h3>Your Account Information:</h3>
                <p><strong>Username:</strong> <?php echo htmlspecialchars($_SESSION['username']); ?></p>
                <p><strong>User ID:</strong> <?php echo htmlspecialchars($_SESSION['user_id']); ?></p>
                <p><strong>Session Started:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
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

    <script src="Models/js/header_loader.js"></script>
    <script src="Models/js/header_tabs.js"></script>

    <script>
        // Auto-logout warning (optional)
        let warningTimer;
        let logoutTimer;
        
        function resetTimers() {
            clearTimeout(warningTimer);
            clearTimeout(logoutTimer);
            
            // Warn user 5 minutes before session expires
            warningTimer = setTimeout(() => {
                if (confirm('Your session will expire in 5 minutes. Click OK to extend your session.')) {
                    // Make AJAX call to refresh session (implement as needed)
                    resetTimers();
                }
            }, 55 * 60 * 1000); // 55 minutes
            
            // Auto logout after 1 hour
            logoutTimer = setTimeout(() => {
                alert('Your session has expired. You will be redirected to the login page.');
                window.location.href = 'login.php';
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
            },
            {
                id: 4,
                title: "AI-Powered Task Manager",
                type: "personal",
                status: "planning",
                description: "An intelligent task management system that uses machine learning to prioritize tasks and predict completion times.",
                technologies: ["Python", "TensorFlow", "Flask", "React"],
                startDate: "2024-05-01",
                estimatedCompletion: "2024-10-15"
            },
            {
                id: 5,
                title: "Corporate CRM System",
                type: "professional",
                status: "completed",
                description: "Custom CRM system for managing customer relationships, sales pipeline, and automated reporting.",
                technologies: ["Vue.js", "Laravel", "MySQL", "Redis"],
                startDate: "2023-09-01",
                completedDate: "2024-01-30",
                client: "BusinessPro Inc"
            },
            {
                id: 6,
                title: "Fitness Tracking App",
                type: "client",
                status: "in-progress",
                description: "Mobile app for tracking workouts, nutrition, and health metrics with social features and gamification.",
                technologies: ["Flutter", "Dart", "Firebase", "Chart.js"],
                startDate: "2024-03-15",
                estimatedCompletion: "2024-09-01",
                client: "FitLife Studio"
            },
            {
                id: 7,
                title: "Open Source UI Library",
                type: "personal",
                status: "in-progress",
                description: "Creating a comprehensive UI component library for React applications with accessibility-first design.",
                technologies: ["React", "TypeScript", "Storybook", "Jest"],
                startDate: "2024-01-01",
                estimatedCompletion: "2024-07-01"
            },
            {
                id: 8,
                title: "Data Analytics Dashboard",
                type: "professional",
                status: "planning",
                description: "Real-time analytics dashboard for monitoring business KPIs and generating automated insights.",
                technologies: ["D3.js", "Python", "Django", "PostgreSQL", "Docker"],
                startDate: "2024-06-01",
                estimatedCompletion: "2024-11-15"
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
                }, 1500); // Simulate network delay
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
        function viewProject(projectId, url) {
            const project = allProjects.find(p => p.id === projectId);
            window.open("Projects/Professional/survey_projects.php", '_blank');
            //alert(`Viewing project: ${project.title}\n\nIn a real application, this would open a detailed project view.`);
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