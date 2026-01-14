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

// Check if user is admin
$userRole = $auth->getUserRole($_SESSION['user_id']);
if ($userRole !== 'admin' && $userRole !== 'personal_user') {
    // Not authorized for this dashboard
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
    <title>Personal Projects Dashboard</title>
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
            <div class="nav-brand">Personal Projects Dashboard</div>
            <div class="nav-user">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?>!</span>
                <?php if ($userRole === 'admin'): ?>
                    <a href="<?php echo $root_page; ?>dashboards/professional_dashboard.php" style="color: white; text-decoration: none; margin: 0 10px;">
                        Switch to Professional
                    </a>
                <?php endif; ?>
                <form method="POST" style="display: inline;">
                    <button type="submit" name="logout" class="logout-btn">Logout</button>
                </form>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="welcome-card">
            <h1>Personal Projects Dashboard</h1>
            <p>Manage and track your personal projects, hobbies, and side ventures.</p>
            
            <div class="user-info">
                <h3>Your Account Information:</h3>
                <p><strong>Username:</strong> <?php echo htmlspecialchars($_SESSION['username'] ?? 'Unknown'); ?></p>
                <p><strong>User ID:</strong> <?php echo htmlspecialchars($_SESSION['user_id'] ?? 'Unknown'); ?></p>
                <p><strong>Role:</strong> <?php echo htmlspecialchars($userRole); ?></p>
                <p><strong>Dashboard Type:</strong> Personal Projects</p>
            </div>
        </div>

        <div class="dashboard-container">
            <div class="header">
                <h1>Personal Projects Overview</h1>
                <p>Your creative ventures, learning projects, and personal endeavors</p>
            </div>

            <div class="controls">
                <input type="text" class="search-box" id="searchInput" placeholder="Search projects by name, description, or technology...">
                <select class="filter-select" id="categoryFilter">
                    <option value="">All Categories</option>
                    <option value="learning">Learning</option>
                    <option value="hobby">Hobby</option>
                    <option value="creative">Creative</option>
                    <option value="opensource">Open Source</option>
                    <option value="experiment">Experiment</option>
                </select>
                <select class="filter-select" id="statusFilter">
                    <option value="">All Status</option>
                    <option value="completed">Completed</option>
                    <option value="in-progress">In Progress</option>
                    <option value="planning">Planning</option>
                    <option value="on-hold">On Hold</option>
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
    </div>

    <script>
        // Store root page for JavaScript use
        const ROOT_PAGE = '<?php echo addslashes($root_page); ?>';
        
        // Mock personal projects data
        const mockProjects = [
            {
                id: 1,
                title: "Learning React and TypeScript",
                category: "learning",
                status: "in-progress",
                description: "Deep dive into React with TypeScript, building a series of progressively complex applications to master modern frontend development.",
                technologies: ["React", "TypeScript", "Vite", "TailwindCSS"],
                startDate: "2024-01-01",
                estimatedCompletion: "2024-06-30"
            },
            {
                id: 2,
                title: "Home Automation System",
                category: "hobby",
                status: "in-progress",
                description: "Building a smart home automation system using Raspberry Pi, sensors, and custom software to control lights, temperature, and security.",
                technologies: ["Python", "Raspberry Pi", "IoT", "MQTT"],
                startDate: "2023-10-15",
                estimatedCompletion: "2024-05-01"
            },
            {
                id: 3,
                title: "Photography Portfolio Site",
                category: "creative",
                status: "completed",
                description: "A beautiful, responsive portfolio website showcasing my photography work with galleries, lightbox effects, and contact form.",
                technologies: ["HTML5", "CSS3", "JavaScript", "PhotoSwipe"],
                startDate: "2023-08-01",
                completedDate: "2023-11-20"
            },
            {
                id: 4,
                title: "Contribute to Vue.js Docs",
                category: "opensource",
                status: "in-progress",
                description: "Contributing to Vue.js documentation by writing tutorials, fixing errors, and helping translate content to Spanish.",
                technologies: ["Vue.js", "Markdown", "Git", "Documentation"],
                startDate: "2024-02-01",
                estimatedCompletion: "Ongoing"
            },
            {
                id: 5,
                title: "3D Printing Projects",
                category: "hobby",
                status: "in-progress",
                description: "Learning 3D modeling and printing. Creating custom organizers, hobby parts, and artistic sculptures.",
                technologies: ["Blender", "Fusion 360", "3D Printing"],
                startDate: "2023-12-01",
                estimatedCompletion: "Ongoing"
            },
            {
                id: 6,
                title: "Machine Learning Study Group",
                category: "learning",
                status: "completed",
                description: "Completed Andrew Ng's Machine Learning course and built several ML projects including image classification and sentiment analysis.",
                technologies: ["Python", "TensorFlow", "Scikit-learn", "Jupyter"],
                startDate: "2023-06-01",
                completedDate: "2023-12-15"
            },
            {
                id: 7,
                title: "Music Production Experiments",
                category: "creative",
                status: "on-hold",
                description: "Learning electronic music production, sound design, and composition using digital audio workstation software.",
                technologies: ["Ableton Live", "Sound Design", "MIDI"],
                startDate: "2023-09-01",
                pausedDate: "2024-01-15"
            },
            {
                id: 8,
                title: "Arduino Weather Station",
                category: "experiment",
                status: "planning",
                description: "Planning to build a complete weather station with Arduino that measures temperature, humidity, pressure, and uploads data to the cloud.",
                technologies: ["Arduino", "C++", "Sensors", "Cloud API"],
                startDate: "2024-04-01",
                estimatedCompletion: "2024-08-01"
            },
            {
                id: 9,
                title: "Personal Blog Platform",
                category: "learning",
                status: "completed",
                description: "Built a custom blog platform from scratch to learn backend development, authentication, and database design.",
                technologies: ["Node.js", "Express", "MongoDB", "EJS"],
                startDate: "2023-04-01",
                completedDate: "2023-09-30"
            },
            {
                id: 10,
                title: "Game Development with Unity",
                category: "hobby",
                status: "planning",
                description: "Starting to learn Unity and C# to create 2D indie games. First project will be a platformer puzzle game.",
                technologies: ["Unity", "C#", "Game Design"],
                startDate: "2024-05-01",
                estimatedCompletion: "2024-12-31"
            }
        ];

        let allProjects = [...mockProjects];
        let filteredProjects = [...mockProjects];

        // DOM elements
        const searchInput = document.getElementById('searchInput');
        const categoryFilter = document.getElementById('categoryFilter');
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
                }, 1000);
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
                        </div>
                        <span class="project-type type-${project.category}">${project.category}</span>
                    </div>
                    
                    <div class="project-description">${project.description}</div>
                    
                    <div class="project-meta">
                        <span class="project-status status-${project.status}">
                            ${project.status.replace('-', ' ').toUpperCase()}
                        </span>
                        <span class="project-date">
                            ${formatDate(project.status === 'completed' ? project.completedDate : 
                              project.status === 'on-hold' ? project.pausedDate :
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
            if (!dateString) return 'No date';
            if (dateString === 'Ongoing') return 'Ongoing';
            const options = { year: 'numeric', month: 'short', day: 'numeric' };
            return new Date(dateString).toLocaleDateString('en-US', options);
        }

        function setupEventListeners() {
            searchInput.addEventListener('input', filterProjects);
            categoryFilter.addEventListener('change', filterProjects);
            statusFilter.addEventListener('change', filterProjects);
        }

        function filterProjects() {
            const searchTerm = searchInput.value.toLowerCase();
            const categoryFilter_val = categoryFilter.value;
            const statusFilter_val = statusFilter.value;

            filteredProjects = allProjects.filter(project => {
                const matchesSearch = !searchTerm || 
                    project.title.toLowerCase().includes(searchTerm) ||
                    project.description.toLowerCase().includes(searchTerm) ||
                    project.technologies.some(tech => tech.toLowerCase().includes(searchTerm));

                const matchesCategory = !categoryFilter_val || project.category === categoryFilter_val;
                const matchesStatus = !statusFilter_val || project.status === statusFilter_val;

                return matchesSearch && matchesCategory && matchesStatus;
            });

            updateStatistics();
            renderProjects();
        }

        // Project actions
        function viewProject(projectId) {
            const project = allProjects.find(p => p.id === projectId);
            alert(`Viewing project: ${project.title}\n\nIn a real application, this would open a detailed project view.`);
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