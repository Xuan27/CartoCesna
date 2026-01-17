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

    <!-- Edit Project Modal -->
    <div id="projectModal" class="modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index: 9999; overflow-y: auto;">
        <div class="modal-content" style="max-width: 700px; margin: 2rem auto; background: white; border-radius: 12px; box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);">
            <div class="modal-header">
                <h2 id="projectModalTitle">Edit Project</h2>
                <button class="close-button" onclick="closeProjectModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="projectForm" onsubmit="saveProject(event)">
                    <input type="hidden" id="editProjectId" name="project_id">
                    <input type="hidden" name="project_type" value="personal">
                    <input type="hidden" name="is_new" value="0">

                    <div class="form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label class="form-label" for="projectTitle">Project Title *</label>
                            <input type="text" class="form-input" id="projectTitle" name="title" required placeholder="Enter project title">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="projectTypeCategory">Category</label>
                            <select class="form-select" id="projectTypeCategory" name="project_type_category">
                                <option value="learning">Learning</option>
                                <option value="web_development">Web Development</option>
                                <option value="mobile_app">Mobile App</option>
                                <option value="data_science">Data Science</option>
                                <option value="machine_learning">Machine Learning</option>
                                <option value="game_development">Game Development</option>
                                <option value="automation">Automation</option>
                                <option value="experiment">Experiment</option>
                                <option value="other">Other</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="projectStatus">Status</label>
                            <select class="form-select" id="projectStatus" name="status">
                                <option value="idea">Idea</option>
                                <option value="in_progress">In Progress</option>
                                <option value="on_hold">On Hold</option>
                                <option value="completed">Completed</option>
                                <option value="abandoned">Abandoned</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="projectPriority">Priority</label>
                            <select class="form-select" id="projectPriority" name="priority">
                                <option value="low">Low</option>
                                <option value="medium" selected>Medium</option>
                                <option value="high">High</option>
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
                            <label class="form-label" for="projectEndDate">End Date</label>
                            <input type="date" class="form-input" id="projectEndDate" name="end_date">
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
                            <label class="form-label" for="projectGithubUrl">GitHub URL</label>
                            <input type="url" class="form-input" id="projectGithubUrl" name="github_url" placeholder="https://github.com/...">
                        </div>

                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label class="form-label" for="projectNotes">Notes</label>
                            <textarea class="form-textarea" id="projectNotes" name="notes" rows="2" placeholder="Additional notes..."></textarea>
                        </div>

                        <div class="form-group" style="display: flex; align-items: center; gap: 1rem;">
                            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                                <input type="checkbox" id="projectPublic" name="is_public" checked>
                                <span>Public Project</span>
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

        let allProjects = [];
        let filteredProjects = [];

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

        // Fetch personal projects from API
        async function fetchProjects() {
            const response = await fetch(ROOT_PAGE + 'Models/php/projects_api.php?action=get_projects');
            const data = await response.json();

            if (data.success) {
                // Get only personal projects
                const projects = [];
                if (data.projects.personal) {
                    data.projects.personal.forEach(p => {
                        // Map category from project_type or purpose
                        let category = p.purpose || p.project_type || 'other';
                        // Normalize status format
                        let status = (p.status || 'in_progress').replace(/_/g, '-');

                        projects.push({
                            id: p.id,
                            project_id: p.project_id,
                            title: p.title || 'Untitled',
                            category: category,
                            status: status,
                            description: p.description || 'No description',
                            technologies: p.technologies_used || p.technologies || [],
                            startDate: p.start_date,
                            endDate: p.end_date,
                            completedDate: p.end_date,
                            created_at: p.created_at
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
                const displayDate = project.completedDate || project.endDate || project.startDate || project.created_at;

                return `
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
                            ${project.status.replace(/-/g, ' ').toUpperCase()}
                        </span>
                        <span class="project-date">
                            ${formatDate(displayDate)}
                        </span>
                    </div>

                    <div class="project-tech">
                        ${technologies.length > 0 ? technologies.map(tech => `<span class="tech-tag">${tech}</span>`).join('') : '<span style="color: #94a3b8; font-size: 0.85em;">No technologies specified</span>'}
                    </div>

                    <div class="project-actions">
                        <button class="btn btn-primary" onclick="viewProject('${project.project_id}')">View Details</button>
                        <button class="btn btn-secondary" onclick="editProject('${project.project_id}')">Edit</button>
                    </div>
                </div>
            `;
            }).join('');
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
            const project = allProjects.find(p => p.project_id === projectId);
            if (project) {
                const technologies = Array.isArray(project.technologies) ? project.technologies.join(', ') : 'None';
                alert(`Project: ${project.title}\n\nCategory: ${project.category}\nStatus: ${project.status}\nDescription: ${project.description}\nTechnologies: ${technologies}`);
            }
        }

        function editProject(projectId) {
            // Find the project in allProjects (need to get full data from API)
            fetchProjectDetails(projectId);
        }

        async function fetchProjectDetails(projectId) {
            try {
                const response = await fetch(ROOT_PAGE + 'Models/php/projects_api.php?action=get_project&project_id=' + projectId + '&project_type=personal');
                const data = await response.json();

                if (data.success && data.project) {
                    openEditProjectModal(data.project);
                } else {
                    showToast('Failed to load project details', 'error');
                }
            } catch (error) {
                console.error('Error fetching project:', error);
                showToast('Error loading project', 'error');
            }
        }

        function openEditProjectModal(project) {
            document.getElementById('projectModalTitle').textContent = 'Edit Project';
            document.getElementById('editProjectId').value = project.project_id;

            // Populate form fields
            document.getElementById('projectTitle').value = project.title || '';
            document.getElementById('projectTypeCategory').value = project.project_type || 'other';
            document.getElementById('projectStatus').value = project.status || 'idea';
            document.getElementById('projectPriority').value = project.priority || 'medium';
            document.getElementById('projectDescription').value = project.description || '';
            document.getElementById('projectStartDate').value = project.start_date || '';
            document.getElementById('projectEndDate').value = project.end_date || '';

            const technologies = Array.isArray(project.technologies_used) ? project.technologies_used.join(', ') :
                                 (Array.isArray(project.technologies) ? project.technologies.join(', ') : '');
            document.getElementById('projectTechnologies').value = technologies;

            document.getElementById('projectUrl').value = project.project_url || '';
            document.getElementById('projectGithubUrl').value = project.github_url || '';
            document.getElementById('projectNotes').value = project.notes || '';
            document.getElementById('projectPublic').checked = project.is_public == 1;

            const modal = document.getElementById('projectModal');
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        }

        function closeProjectModal() {
            document.getElementById('projectModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }

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