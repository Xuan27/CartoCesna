<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Tasks - Survey Project Manager</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <link rel="stylesheet" href="../../Models/css/survey_projects_notes.css">
</head>
<body>

    <div class="container">
        <!-- Mobile Toggle Button -->
        <button class="mobile-toggle" id="mobileToggle">
            <i class="fas fa-bars"></i>
        </button>
        <div class="sidebar" id="sidebar">
            <button class="toggle-btn" id="toggleBtn">
                <i class="fas fa-chevron-left"></i>
            </button>
            <div class="sidebar-header">
                <h1><i class="fas fa-map-marked-alt"></i> Survey Pro</h1>
                <p>Professional Project Management</p>
            </div>

            <nav class="sidebar-nav">
                <a href="survey_projects.php" class="nav-item">
                    <i class="fas fa-th-large"></i>
                    Dashboard
                </a>
                <a href="survey_projects.php?view=todo" class="nav-item">
                    <i class="fas fa-star"></i>
                    My To-Do
                </a>
                <a href="all_tasks.php" class="nav-item active">
                    <i class="fas fa-folder-open"></i>
                    All Tasks
                </a>
                <a href="#" class="nav-item">
                    <i class="fas fa-chart-bar"></i>
                    Analytics
                </a>
                <a href="./checklists.php" class="nav-item">
                    <i class="fas fa-clipboard-check"></i>
                    Checklists
                </a>
                <a href="./map.php" class="nav-item">
                    <i class="fas fa-map"></i>
                    Map
                </a>
                <a href="./monuments.php" class="nav-item">
                    <i class="fas fa-map-pin"></i>
                    Monuments
                </a>
                <a href="./field_data_qc.php" class="nav-item">
                    <i class="fas fa-clipboard-list"></i>
                    Field Data QC
                </a>
                <a href="./control_points.php" class="nav-item">
                    <i class="fas fa-crosshairs"></i>
                    Control Points
                </a>
                <a href="#" class="nav-item">
                    <i class="fas fa-cog"></i>
                    Settings
                </a>
            </nav>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Bar -->
        <div class="top-bar">
            <div class="top-bar-left">
                <button class="mobile-menu-button" onclick="toggleSidebar()">
                    <i class="fas fa-bars"></i>
                </button>
                <div>
                    <h2>All Tasks</h2>
                    <p>View and manage tasks across all projects</p>
                </div>
            </div>
            <div class="top-bar-right">
                <button class="btn btn-secondary" onclick="exportTasksData()">
                    <i class="fas fa-download"></i> Export
                </button>
            </div>
        </div>

        <!-- Content Area -->
        <div class="content-area">
            <!-- Content Header with Search and Filters -->
            <div class="content-header">
                <div class="search-filters">
                    <input 
                        type="text" 
                        class="search-input" 
                        placeholder="Search tasks by name, project, or assignee..." 
                        id="searchInput"
                        onkeyup="searchTasks()"
                    >
                    <select class="filter-select" id="statusFilter" onchange="filterTasks()">
                        <option value="">All Status</option>
                        <option value="Not Started">Not Started</option>
                        <option value="In Progress">In Progress</option>
                        <option value="On Hold">On Hold</option>
                        <option value="Completed">Completed</option>
                        <option value="Cancelled">Cancelled</option>
                    </select>
                    <select class="filter-select" id="typeFilter" onchange="filterTasks()">
                        <option value="">All Types</option>
                        <option value="Easement">Easement</option>
                        <option value="ALTA">ALTA</option>
                        <option value="Plat">Plat</option>
                        <option value="Construction Staking">Construction Staking</option>
                        <option value="Boundary Survey">Boundary Survey</option>
                        <option value="Topographic Survey">Topographic Survey</option>
                        <option value="As-Built Survey">As-Built Survey</option>
                        <option value="Other">Other</option>
                    </select>
                    <select class="filter-select" id="priorityFilter" onchange="filterTasks()">
                        <option value="">All Priorities</option>
                        <option value="Low">Low</option>
                        <option value="Medium">Medium</option>
                        <option value="High">High</option>
                        <option value="Urgent">Urgent</option>
                    </select>
                    <select class="filter-select" id="itemsPerPage" onchange="changeItemsPerPage()">
                        <option value="10">10 per page</option>
                        <option value="25" selected>25 per page</option>
                        <option value="50">50 per page</option>
                        <option value="100">100 per page</option>
                    </select>
                </div>
            </div>

            <!-- Tasks Container -->
            <div class="projects-container">
                <div class="projects-header">
                    <h3>Task List</h3>
                    <div class="projects-count" id="tasksCount">
                        Showing 0 tasks
                    </div>
                </div>

                <!-- Tasks Table -->
                <div id="tasksTableContainer">
                    <table class="projects-table" id="tasksTable">
                        <thead>
                            <tr>
                                <th>Task</th>
                                <th>Project</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Priority</th>
                                <th>Assigned To</th>
                                <th>Due Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="tasksTableBody">
                            <!-- Tasks will be loaded here -->
                        </tbody>
                    </table>
                </div>

                <!-- Empty State -->
                <div class="empty-state" id="emptyState" style="display: none;">
                    <i class="fas fa-tasks"></i>
                    <h3>No Tasks Found</h3>
                    <p>No tasks match your current filters</p>
                </div>

                <!-- Pagination -->
                <div class="pagination" id="pagination">
                    <div class="pagination-info" id="paginationInfo">
                        Showing 0 to 0 of 0 entries
                    </div>
                    <div class="pagination-controls" id="paginationControls">
                        <!-- Pagination buttons will be generated here -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Task Details Modal -->
    <div id="taskDetailsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="taskDetailsTitle">Task Details</h2>
                <a id="viewInDashboardLink" href="#" class="btn btn-secondary" style="font-size:0.85rem; padding:0.35rem 0.75rem; display:flex; align-items:center; gap:0.4rem;">
                    <i class="fas fa-external-link-alt"></i> View in Dashboard
                </a>
                <button class="close-button" onclick="closeTaskDetailsModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div id="taskDetailsContent">
                    <!-- Task details will be loaded here -->
                </div>
                <div class="form-actions">
                    <button class="btn btn-secondary" onclick="closeTaskDetailsModal()">
                        Close
                    </button>
                    <button class="btn btn-primary" onclick="editTaskFromDetails()">
                        <i class="fas fa-edit"></i> Edit Task
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
    <div id="toast" class="toast">
        <div class="toast-content">
            <i class="fas fa-check-circle toast-icon"></i>
            <span id="toastMessage">Action completed successfully!</span>
        </div>
    </div>

    <script>
        // Initialize all variables
        let allTasks = [];
        let filteredTasks = [];
        let currentPage = 1;
        let itemsPerPage = 25;
        let currentTaskDetails = null;

        // Initialize the application
        document.addEventListener('DOMContentLoaded', function() {
            loadAllTasks();
            setupSidebar();
        });

        // Setup sidebar functionality
        function setupSidebar() {
            const sidebar = document.getElementById('sidebar');
            const toggleButton = document.getElementById('toggleBtn');
            const mobileToggle = document.getElementById('mobileToggle');
            const mainContent = document.querySelector('.main-content');

            if (toggleButton) {
                toggleButton.addEventListener('click', () => {
                    sidebar.classList.toggle('collapsed');
                    mainContent.classList.toggle('collapsed');
                });
            }

            if (mobileToggle) {
                mobileToggle.addEventListener('click', () => {
                    sidebar.classList.toggle('collapsed');
                });
            }
        }

        // Load all tasks from all projects. Loads projects (for names) and every
        // project's tasks in two batched requests, instead of one load_tasks.php
        // call per project - that per-project fan-out was what made this page
        // slow to display on accounts with many projects.
        function loadAllTasks() {
            const tasksCount = document.getElementById('tasksCount');
            if (tasksCount) {
                tasksCount.textContent = 'Loading tasks...';
            }

            const formData = new FormData();
            formData.append('action', 'load_project');

            fetch('../../Models/php/load_survey_project_notes.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    throw new Error(data.message || 'Failed to load projects');
                }
                const projectNameById = {};
                (data.projects || []).forEach(project => {
                    projectNameById[project.projectId] = project.projectName;
                });

                return fetch('../../Models/php/load_tasks.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=load_all_tasks'
                })
                .then(response => response.json())
                .then(taskData => {
                    if (!taskData.success) {
                        throw new Error(taskData.message || 'Failed to load tasks');
                    }
                    const tasksByProject = taskData.tasksByProject || {};
                    const tasks = [];
                    Object.keys(tasksByProject).forEach(projectId => {
                        tasksByProject[projectId].forEach(task => {
                            tasks.push({ ...task, projectName: projectNameById[projectId] });
                        });
                    });
                    return tasks;
                });
            })
            .then(tasks => {
                allTasks = tasks;
                console.log('All tasks loaded:', allTasks.length, 'tasks');
                searchTasks();
                showToast('Tasks loaded successfully!', 'success');
            })
            .catch(error => {
                console.error('Error loading tasks:', error);
                allTasks = [];
                searchTasks();
                showToast('Error loading tasks - check console', 'error');
            });
        }

        // Search tasks
        function searchTasks() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const statusFilter = document.getElementById('statusFilter').value;
            const typeFilter = document.getElementById('typeFilter').value;
            const priorityFilter = document.getElementById('priorityFilter').value;

            filteredTasks = allTasks.filter(task => {
                const matchesSearch = 
                    task.task_name.toLowerCase().includes(searchTerm) ||
                    (task.projectName && task.projectName.toLowerCase().includes(searchTerm)) ||
                    (task.assigned_to && task.assigned_to.toLowerCase().includes(searchTerm)) ||
                    (task.project_id && task.project_id.toLowerCase().includes(searchTerm));

                const matchesStatus = !statusFilter || task.task_status === statusFilter;
                const matchesType = !typeFilter || task.task_type === typeFilter;
                const matchesPriority = !priorityFilter || task.task_priority === priorityFilter;

                return matchesSearch && matchesStatus && matchesType && matchesPriority;
            });

            currentPage = 1;
            updateTasksDisplay();
        }

        // Filter tasks
        function filterTasks() {
            searchTasks();
        }

        // Change items per page
        function changeItemsPerPage() {
            itemsPerPage = parseInt(document.getElementById('itemsPerPage').value);
            currentPage = 1;
            updateTasksDisplay();
        }

        // Update tasks display
        function updateTasksDisplay() {
            const totalTasks = filteredTasks.length;
            const startIndex = (currentPage - 1) * itemsPerPage;
            const endIndex = Math.min(startIndex + itemsPerPage, totalTasks);
            const currentTasks = filteredTasks.slice(startIndex, endIndex);

            // Update task count
            document.getElementById('tasksCount').textContent = `Showing ${totalTasks} tasks`;

            // Show/hide empty state
            const emptyState = document.getElementById('emptyState');
            const tableContainer = document.getElementById('tasksTableContainer');
            const pagination = document.getElementById('pagination');

            if (totalTasks === 0) {
                emptyState.style.display = 'block';
                tableContainer.style.display = 'none';
                pagination.style.display = 'none';
                return;
            } else {
                emptyState.style.display = 'none';
                tableContainer.style.display = 'block';
                pagination.style.display = 'flex';
            }

            // Update table body
            const tbody = document.getElementById('tasksTableBody');
            tbody.innerHTML = '';

            currentTasks.forEach(task => {
                const row = createTaskRow(task);
                tbody.appendChild(row);
            });

            // Update pagination
            updatePagination(totalTasks);
        }

        // Create task row
        function createTaskRow(task) {
            const row = document.createElement('tr');
            row.className = 'project-row';
            row.style.cursor = 'pointer';

            const taskTypeClass = `task-type-${formatTaskTypeClass(task.task_type)}`;
            const taskStatusClass = `task-status-${formatTaskStatusClass(task.task_status)}`;
            const priorityClass = `priority-${task.task_priority.toLowerCase()}`;

            const dueDate = task.due_date ?
                new Date(task.due_date + 'T00:00:00').toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric'
                }) : 'No due date';

            // Check if task is overdue
            const isOverdue = task.due_date &&
                new Date(task.due_date + 'T00:00:00') < new Date() &&
                task.task_status !== 'Completed';

            row.innerHTML = `
                <td>
                    <div style="display: flex; flex-direction: column; gap: 0.25rem;">
                        <div class="project-name">${task.task_name}</div>
                        ${task.phase_number ? `<div class="project-id">Phase ${task.phase_number}</div>` : ''}
                    </div>
                </td>
                <td>
                    <div style="display: flex; flex-direction: column; gap: 0.25rem;">
                        <div class="project-name">${task.projectName || 'Unknown'}</div>
                        <div class="project-id">${task.project_id}</div>
                    </div>
                </td>
                <td>
                    <span class="task-type-badge ${taskTypeClass}">${task.task_type}</span>
                </td>
                <td>
                    <span class="task-status-badge ${taskStatusClass}">
                        <i class="fas fa-circle"></i>
                        ${task.task_status}
                    </span>
                </td>
                <td>
                    <span class="priority-badge ${priorityClass}">
                        ${task.task_priority}
                    </span>
                </td>
                <td>${task.assigned_to || 'Unassigned'}</td>
                <td ${isOverdue ? 'style="color: var(--danger-color); font-weight: 600;"' : ''}>
                    ${isOverdue ? '<i class="fas fa-exclamation-triangle"></i> ' : ''}
                    ${dueDate}
                </td>
                <td>
                    <button class="btn btn-xs btn-secondary" onclick="viewTaskDetails(${task.task_id}, event)" title="View details">
                        <i class="fas fa-eye"></i>
                    </button>
                </td>
            `;

            // Add click event to row (but not buttons)
            row.addEventListener('click', function(e) {
                if (!e.target.closest('button')) {
                    viewTaskDetails(task.task_id, e);
                }
            });

            return row;
        }

        // Helper functions
        function formatTaskTypeClass(taskType) {
            return taskType.toLowerCase().replace(/\s+/g, '-');
        }

        function formatTaskStatusClass(status) {
            return status.toLowerCase().replace(/\s+/g, '-');
        }

        // View task details
        function viewTaskDetails(taskId, event) {
            if (event) {
                event.stopPropagation();
            }

            const task = allTasks.find(t => t.task_id == taskId);
            if (!task) {
                showToast('Task not found', 'error');
                return;
            }

            currentTaskDetails = task;

            const taskTypeClass = `task-type-${formatTaskTypeClass(task.task_type)}`;
            const taskStatusClass = `task-status-${formatTaskStatusClass(task.task_status)}`;
            const priorityClass = `priority-${task.task_priority.toLowerCase()}`;

            const formatDate = (dateStr) => {
                if (!dateStr) return 'Not set';
                return new Date(dateStr + 'T00:00:00').toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                });
            };

            const content = `
                <div class="details-grid" style="margin-bottom: 1.5rem;">
                    <div class="detail-item">
                        <span class="detail-label">Project</span>
                        <span class="detail-value">${task.projectName || 'Unknown'} (${task.project_id})</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Task Type</span>
                        <span class="task-type-badge ${taskTypeClass}">${task.task_type}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Status</span>
                        <span class="task-status-badge ${taskStatusClass}">
                            <i class="fas fa-circle"></i>
                            ${task.task_status}
                        </span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Priority</span>
                        <span class="priority-badge ${priorityClass}">${task.task_priority}</span>
                    </div>
                    ${task.phase_number ? `
                    <div class="detail-item">
                        <span class="detail-label">Phase Number</span>
                        <span class="detail-value">${task.phase_number}</span>
                    </div>
                    ` : ''}
                    <div class="detail-item">
                        <span class="detail-label">Assigned To</span>
                        <span class="detail-value">${task.assigned_to || 'Unassigned'}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Start Date</span>
                        <span class="detail-value">${formatDate(task.start_date)}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Due Date</span>
                        <span class="detail-value">${formatDate(task.due_date)}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Completion Date</span>
                        <span class="detail-value">${formatDate(task.completion_date)}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Estimated Hours</span>
                        <span class="detail-value">${task.estimated_hours || 'Not set'}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Actual Hours</span>
                        <span class="detail-value">${task.actual_hours || 'Not set'}</span>
                    </div>
                    ${task.task_link ? `
                    <div class="detail-item" style="grid-column: 1 / -1;">
                        <span class="detail-label">Task Folder</span>
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <a href="file:///${task.task_link.replace(/N:\\/g, 'westwoodps.local/Global Projects/').replace(/\\/g, '/')}" target="_blank" class="detail-value" style="color: var(--primary-color);">
                                <i class="fas fa-folder"></i> ${task.task_link}
                            </a>
                            <button class="btn btn-xs btn-secondary" onclick="copyTaskPath('${task.task_link.replace(/\\/g, '\\\\')}', '${task.task_name.replace(/'/g, "\\'")}'); event.stopPropagation();" title="Copy task folder path">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                    </div>
                    ` : ''}
                    ${task.notes ? `
                    <div class="detail-item" style="grid-column: 1 / -1;">
                        <span class="detail-label">Notes</span>
                        <div class="detail-value" style="white-space: pre-wrap;">${task.notes}</div>
                    </div>
                    ` : ''}
                </div>
            `;

            document.getElementById('taskDetailsTitle').textContent = task.task_name;
            document.getElementById('taskDetailsContent').innerHTML = content;
            document.getElementById('viewInDashboardLink').href = `survey_projects.php?project=${task.project_id}&task=${task.task_id}`;
            document.getElementById('taskDetailsModal').style.display = 'block';
        }

        // Close task details modal
        function closeTaskDetailsModal() {
            document.getElementById('taskDetailsModal').style.display = 'none';
            currentTaskDetails = null;
        }

        // Edit task from details modal
        function editTaskFromDetails() {
            if (currentTaskDetails) {
                window.location.href = `survey_projects.php?editTask=${currentTaskDetails.task_id}&project=${currentTaskDetails.project_id}`;
            }
        }

        // Update pagination
        function updatePagination(totalTasks) {
            const totalPages = Math.ceil(totalTasks / itemsPerPage);
            const startIndex = (currentPage - 1) * itemsPerPage + 1;
            const endIndex = Math.min(currentPage * itemsPerPage, totalTasks);

            document.getElementById('paginationInfo').textContent = 
                `Showing ${startIndex} to ${endIndex} of ${totalTasks} entries`;

            const controls = document.getElementById('paginationControls');
            controls.innerHTML = '';

            const prevBtn = document.createElement('button');
            prevBtn.className = 'pagination-button';
            prevBtn.innerHTML = '<i class="fas fa-chevron-left"></i> Previous';
            prevBtn.disabled = currentPage === 1;
            prevBtn.onclick = () => goToPage(currentPage - 1);
            controls.appendChild(prevBtn);

            const maxVisiblePages = 5;
            let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
            let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);

            if (endPage - startPage + 1 < maxVisiblePages) {
                startPage = Math.max(1, endPage - maxVisiblePages + 1);
            }

            for (let i = startPage; i <= endPage; i++) {
                const pageBtn = document.createElement('button');
                pageBtn.className = `pagination-button ${i === currentPage ? 'active' : ''}`;
                pageBtn.textContent = i;
                pageBtn.onclick = () => goToPage(i);
                controls.appendChild(pageBtn);
            }

            const nextBtn = document.createElement('button');
            nextBtn.className = 'pagination-button';
            nextBtn.innerHTML = 'Next <i class="fas fa-chevron-right"></i>';
            nextBtn.disabled = currentPage === totalPages || totalPages === 0;
            nextBtn.onclick = () => goToPage(currentPage + 1);
            controls.appendChild(nextBtn);
        }

        // Go to specific page
        function goToPage(page) {
            currentPage = page;
            updateTasksDisplay();
        }

        // Export tasks data
        function exportTasksData() {
            const dataStr = JSON.stringify(filteredTasks, null, 2);
            const dataBlob = new Blob([dataStr], {type: 'application/json'});
            
            const link = document.createElement('a');
            link.href = URL.createObjectURL(dataBlob);
            link.download = `survey-tasks-${new Date().toISOString().split('T')[0]}.json`;
            link.click();
            
            showToast('Task data exported successfully!');
        }

        // Toggle sidebar
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('open');
        }

        // Show toast notification
        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            const toastMessage = document.getElementById('toastMessage');
            const toastIcon = document.querySelector('.toast-icon');
            
            toastMessage.textContent = message;
            
            if (type === 'error') {
                toastIcon.className = 'fas fa-exclamation-circle toast-icon';
                toast.style.borderLeftColor = 'var(--danger-color)';
                toastIcon.style.color = 'var(--danger-color)';
            } else if (type === 'warning') {
                toastIcon.className = 'fas fa-exclamation-triangle toast-icon';
                toast.style.borderLeftColor = 'var(--warning-color)';
                toastIcon.style.color = 'var(--warning-color)';
            } else {
                toastIcon.className = 'fas fa-check-circle toast-icon';
                toast.style.borderLeftColor = 'var(--success-color)';
                toastIcon.style.color = 'var(--success-color)';
            }
            
            toast.classList.add('show');
            
            setTimeout(() => {
                toast.classList.remove('show');
            }, 3000);
        }

        // Close modal when clicking outside
        document.addEventListener('click', function(event) {
            const taskDetailsModal = document.getElementById('taskDetailsModal');
            const sidebar = document.getElementById('sidebar');
            const mobileMenuButton = document.querySelector('.mobile-menu-button');
            
            if (event.target === taskDetailsModal) {
                closeTaskDetailsModal();
            }
            
            if (sidebar && mobileMenuButton && 
                !sidebar.contains(event.target) && !mobileMenuButton.contains(event.target)) {
                sidebar.classList.remove('open');
            }
        });

        // Handle window resize
        window.addEventListener('resize', () => {
            const sidebar = document.getElementById('sidebar');
            if (window.innerWidth > 768) {
                sidebar.classList.remove('mobile-open');
            }
        });

        // Copy task folder path
        function copyTaskPath(taskPath, taskName) {
            navigator.clipboard.writeText(taskPath).then(() => {
                showToast(`Task folder path for "${taskName}" copied to clipboard!`);
            }).catch(() => {
                showToast(`Failed to copy task folder path`, 'error');
            });
        }
    </script>

    <style>
        /* Priority badge styles */
        .priority-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .priority-badge.priority-low {
            background: rgba(59, 130, 246, 0.1);
            color: #3b82f6;
        }

        .priority-badge.priority-medium {
            background: rgba(251, 191, 36, 0.1);
            color: #f59e0b;
        }

        .priority-badge.priority-high {
            background: rgba(249, 115, 22, 0.1);
            color: #f97316;
        }

        .priority-badge.priority-urgent {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
        }

        /* Additional styles for task details modal */
        .detail-item {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .detail-label {
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            color: var(--gray-500);
            letter-spacing: 0.05em;
        }

        .detail-value {
            font-size: 0.875rem;
            color: var(--gray-900);
        }
    </style>
</body>
</html>