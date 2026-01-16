<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Survey Project Manager - Professional Dashboard</title>
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
                <a href="#" class="nav-item active">
                    <i class="fas fa-th-large"></i>
                    Dashboard
                </a>
                <a href="./all_tasks.php" class="nav-item">
                    <i class="fas fa-folder-open"></i>
                    All Tasks
                </a>
                <a href="#" class="nav-item">
                    <i class="fas fa-chart-bar"></i>
                    Analytics
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
                    <h2>Survey Projects</h2>
                    <p>Manage and organize your surveying projects</p>
                </div>
            </div>
            <div class="top-bar-right">
                <button class="btn btn-secondary" onclick="exportData()">
                    <i class="fas fa-download"></i> Export
                </button>
                <button class="btn btn-primary" onclick="openCreateModal()">
                    <i class="fas fa-plus"></i> New Project
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
                        placeholder="Search projects by name or ID..." 
                        id="searchInput"
                        onkeyup="searchProjects()"
                    >
                    <select class="filter-select" id="statusFilter" onchange="filterProjects()">
                        <option value="">All Status</option>
                        <option value="Active">Active</option>
                        <option value="Completed">Completed</option>
                        <option value="On Hold">On Hold</option>
                    </select>
                    <select class="filter-select" id="itemsPerPage" onchange="changeItemsPerPage()">
                        <option value="5">5 per page</option>
                        <option value="10" selected>10 per page</option>
                        <option value="25">25 per page</option>
                        <option value="50">50 per page</option>
                    </select>
                </div>
            </div>

            <!-- Projects Container -->
            <div class="projects-container">
                <div class="projects-header">
                    <h3>Project List</h3>
                    <div class="projects-count" id="projectsCount">
                        Showing 0 projects
                    </div>
                </div>

                <!-- Projects Table -->
                <div id="projectsTableContainer">
                    <table class="projects-table" id="projectsTable">
                        <thead>
                            <tr>
                                <th>Project</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Links</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="projectsTableBody">
                            <!-- Projects will be loaded here -->
                        </tbody>
                    </table>
                </div>

                <!-- Empty State -->
                <div class="empty-state" id="emptyState" style="display: none;">
                    <i class="fas fa-folder-open"></i>
                    <h3>No Projects Found</h3>
                    <p>Create your first survey project to get started</p>
                    <button class="btn btn-primary" onclick="openCreateModal()" style="margin-top: 1rem;">
                        <i class="fas fa-plus"></i> Create Project
                    </button>
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

    <!-- Create/Edit Project Modal -->
    <div id="projectModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Create New Project</h2>
                <button class="close-button" onclick="closeModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="projectForm" onsubmit="saveProject(event)">
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label" for="projectId">Project ID *</label>
                            <input type="text" class="form-input" id="projectId" name="projectId" required 
                                   placeholder="0012345.00">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="projectName">Project Name *</label>
                            <input type="text" class="form-input" id="projectName" name="projectName" required 
                                   placeholder="e.g., Downtown Commercial Survey">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="projectStatus">Project Status</label>
                            <select class="form-select" id="projectStatus" name="projectStatus">
                                <option value="Active">Active</option>
                                <option value="Completed">Completed</option>
                                <option value="On Hold">On Hold</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="createdBy">Created By</label>
                            <input type="text" class="form-input" id="createdBy" name="createdBy" 
                                   placeholder="Your name or email">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="projectFolderLink">Project Folder Link</label>
                            <input type="text" class="form-input" id="projectFolderLink" name="projectFolderLink"
                                placeholder="N:\">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="surveyFolderLink">Survey Folder Link</label>
                            <input type="text" class="form-input" id="surveyFolderLink" name="surveyFolderLink" 
                                   placeholder="N:\0012345.00\Survey">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="drawingFolderLink">Drawing Folder Link</label>
                            <input type="text" class="form-input" id="drawingFolderLink" name="drawingFolderLink" 
                                   placeholder="N:\0012345.00\DWG\Survey C3D 2018">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="contractLink">Contract Link</label>
                            <input type="url" class="form-input" id="contractLink" name="contractLink" 
                                   placeholder="N:\0012345.00\Administration\Contracts">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="qaQcFolderLink">QA/QC Folder Link</label>
                            <input type="url" class="form-input" id="qaQcFolderLink" name="qaQcFolderLink" 
                                   placeholder="N:\0012345.00\07 QA-QC\5 - Plan and Report Markups\Land Surveying">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="researchFolderLink">Research Folder Link</label>
                            <input type="url" class="form-input" id="researchFolderLink" name="researchFolderLink" 
                                   placeholder="N:\0012345.00\09 Research\Survey Research">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="location">City/County/State</label>
                            <input type="text" class="form-input" id="location" name="location"
                                   placeholder="Austin, Travis County, Texas">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="scale_factor">Scale Factor</label>
                            <input type="text" class="form-input" id="scale_factor" name="scale_factor"
                                   placeholder="1.0000000">
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" onclick="closeModal()">
                            Cancel
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Project
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add/Edit Task Modal -->
    <div id="taskModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="taskModalTitle">Add New Task</h2>
                <button class="close-button" onclick="closeTaskModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="taskForm" onsubmit="saveTask(event)">
                    <input type="hidden" id="taskId" name="taskId">
                    <input type="hidden" id="taskProjectId" name="projectId">
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label" for="taskName">Task Name *</label>
                            <input type="text" class="form-input" id="taskName" name="taskName" required 
                                placeholder="e.g., Field survey and staking">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="taskType">Task Type *</label>
                            <select class="form-select" id="taskType" name="taskType" required>
                                <option value="">Select task type</option>
                                <option value="Easement">Easement</option>
                                <option value="ALTA">ALTA</option>
                                <option value="Plat">Plat</option>
                                <option value="Construction Staking">Construction Staking</option>
                                <option value="Boundary Survey">Boundary Survey</option>
                                <option value="Topographic Survey">Topographic Survey</option>
                                <option value="As-Built Survey">As-Built Survey</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="taskStatus">Task Status</label>
                            <select class="form-select" id="taskStatus" name="taskStatus">
                                <option value="Not Started">Not Started</option>
                                <option value="In Progress">In Progress</option>
                                <option value="Review">Review</option>
                                <option value="On Hold">On Hold</option>
                                <option value="Completed">Completed</option>
                                <option value="Cancelled">Cancelled</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="taskPriority">Priority</label>
                            <select class="form-select" id="taskPriority" name="taskPriority">
                                <option value="Low">Low</option>
                                <option value="Medium" selected>Medium</option>
                                <option value="High">High</option>
                                <option value="Urgent">Urgent</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="phaseNumber">Phase Number</label>
                            <input type="text" class="form-input" id="phaseNumber" name="phaseNumber" 
                                placeholder="e.g., 1, 2A, 3">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="assignedTo">Assigned To</label>
                            <input type="text" class="form-input" id="assignedTo" name="assignedTo" 
                                placeholder="Person or team name">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="startDate">Start Date</label>
                            <input type="date" class="form-input" id="startDate" name="startDate">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="dueDate">Due Date</label>
                            <input type="date" class="form-input" id="dueDate" name="dueDate">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="completionDate">Completion Date</label>
                            <input type="date" class="form-input" id="completionDate" name="completionDate">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="estimatedHours">Estimated Hours</label>
                            <input type="number" class="form-input" id="estimatedHours" name="estimatedHours" 
                                step="0.5" min="0" placeholder="e.g., 8.0">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="actualHours">Actual Hours</label>
                            <input type="number" class="form-input" id="actualHours" name="actualHours" 
                                step="0.5" min="0" placeholder="e.g., 8.5">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="taskLink">Task Folder Link</label>
                            <input type="text" class="form-input" id="taskLink" name="taskLink"
                            placeholder="N:\0012345.00\Survey\Task Folder">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="rawDataPath">Raw Data Folder <span style="color: #6b7280; font-weight: normal;">(Optional)</span></label>
                            <input type="text" class="form-input" id="rawDataPath" name="rawDataPath"
                                placeholder="e.g., C:\Users\YourName\FieldData">
                            <small style="color: #6b7280; font-size: 0.75rem;">Override path for raw data files (varies per user)</small>
                        </div>

                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label class="form-label" for="taskNotes">Notes</label>
                            <textarea class="form-textarea" id="taskNotes" name="notes" rows="3"
                                    placeholder="Additional notes or details about this task..."></textarea>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" onclick="closeTaskModal()">
                            Cancel
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Task
                        </button>
                    </div>
                </form>
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

// Initialize all variables at the top
let allProjects = [];
let filteredProjects = [];
let currentPage = 1;
let itemsPerPage = 10;
let currentEditingProject = null;

// DOM element references (will be set after DOM loads)
let projectIdInput, projectFolderLinkInput, projectSurveyFolderLinkInput, 
    projectDrawingFolderLinkInput, projectContractLinkInput, projectQAQCLinkInput, 
    projectResearchLinkInput, projectNameInput;

// Initialize the application
document.addEventListener('DOMContentLoaded', function() {
    // Set up DOM references after DOM is loaded
    projectIdInput = document.getElementById('projectId');
    projectFolderLinkInput = document.getElementById('projectFolderLink');
    projectSurveyFolderLinkInput = document.getElementById('surveyFolderLink');
    projectDrawingFolderLinkInput = document.getElementById('drawingFolderLink');
    projectContractLinkInput = document.getElementById('contractLink');
    projectQAQCLinkInput = document.getElementById('qaQcFolderLink');
    projectResearchLinkInput = document.getElementById('researchFolderLink');
    projectNameInput = document.getElementById('projectName');

    // Set up auto-fill functionality
    setupAutoFill();
    
    // Load projects from server
    loadProjects();
});

function loadProjects() {
    // Show loading state
    const projectsCount = document.getElementById('projectsCount');
    if (projectsCount) {
        projectsCount.textContent = 'Loading projects...';
    }
    
    const formData = new FormData();
    formData.append('action', 'load_project');
    
    fetch('../../Models/php/load_survey_project_notes.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        return response.text().then(text => {
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('JSON parse error:', e);
                console.error('Response text:', text);
                throw new Error('Invalid JSON response from server');
            }
        });
    })
    .then(data => {
        console.log('Parsed data:', data);
        
        if (data.success) {
            allProjects = data.projects || [];
            console.log('Projects loaded successfully:', allProjects.length, 'projects');
            searchProjects(); // Update display
            showToast('Projects loaded successfully!', 'success');
        } else {
            throw new Error(data.message || 'Unknown error occurred');
        }
    })
    .catch(error => {
        console.error('Error loading projects:', error);
        
        // Use fallback sample data
        allProjects = getSampleData();
        searchProjects();
        showToast('Using sample data - check console for connection issues', 'warning');
    });
}

// Loads the different tasks per project
function loadTasksForProject(projectId) {
    return fetch('../../Models/php/load_tasks.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=load_tasks&project_id=${encodeURIComponent(projectId)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            return data.tasks || [];
        }
        return [];
    })
    .catch(error => {
        console.error('Error loading tasks:', error);
        return [];
    });
}

// Helper function to format task type for CSS class
function formatTaskTypeClass(taskType) {
    return taskType.toLowerCase().replace(/\s+/g, '-');
}

// Helper function to format task status for CSS class
function formatTaskStatusClass(status) {
    return status.toLowerCase().replace(/\s+/g, '-');
}

// Create tasks HTML
function createTasksHTML(tasks) {
    if (!tasks || tasks.length === 0) {
        return `
            <div class="no-tasks-message">
                <i class="fas fa-tasks" style="margin-right: 0.5rem;"></i>
                No tasks assigned to this project yet
            </div>
        `;
    }
    
    return tasks.map(task => {
        const taskTypeClass = `task-type-${formatTaskTypeClass(task.task_type)}`;
        const taskStatusClass = `task-status-${formatTaskStatusClass(task.task_status)}`;
        const dueDate = task.due_date ? new Date(task.due_date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : 'No due date';
        const uncPath = "westwoodps.local\\\\Global Projects";
        
        return `
            <div class="task-item">
                <div class="task-info">
                    <div class="task-header">
                        <span class="task-type-badge ${taskTypeClass}">${task.task_type}</span>
                        <span class="task-name">${task.task_name}</span>
                    </div>
                    <div class="task-meta">
                        ${task.phase_number ? `<span><i class="fas fa-layer-group"></i> Phase ${task.phase_number}</span>` : ''}
                        <span><i class="fas fa-calendar"></i> ${dueDate}</span>
                        ${task.assigned_to ? `<span><i class="fas fa-user"></i> ${task.assigned_to}</span>` : ''}
                        ${task.task_link ? `
                            <span style="display: flex; align-items: center; gap: 0.25rem;">
                                <a href="file:///${task.task_link.replace(/N:\\\\/g, uncPath)}" target="_blank" style="color: var(--primary-color);">
                                    <i class="fas fa-folder"></i> Task Folder
                                </a>
                                <button class="btn btn-xs btn-secondary" onclick="event.stopPropagation(); copyTaskPath('${task.task_link.replace(/\\/g, '\\\\')}', '${task.task_name.replace(/'/g, "\\'")}'); return false;" title="Copy task folder path" style="padding: 0.15rem 0.35rem;">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </span>
                        ` : ''}
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <span class="task-status-badge ${taskStatusClass}">
                        <i class="fas fa-circle"></i>
                        ${task.task_status}
                    </span>
                    <button class="btn btn-xs btn-secondary" onclick="editTask(${task.task_id}, '${task.project_id}')" title="Edit task">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-xs" style="background: var(--danger-color); color: white;" onclick="deleteTask(${task.task_id}, '${task.project_id}')" title="Delete task">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `;
    }).join('');
}

// Sample data for fallback
function getSampleData() {
    return [
        {
            projectId: 'SURV2024-001',
            projectName: 'Downtown Commercial Development Survey',
            projectStatus: 'Active',
            createdBy: 'John Surveyor',
            createdDate: '2024-01-15',
            projectFolderLink: 'N:\\SURV2024-001',
            surveyFolderLink: 'N:\\SURV2024-001\\05 Service Groups\\Survey',
            drawingFolderLink: 'N:\\SURV2024-001\\06 CACD\\DWG\\Survey',
            contractLink: 'N:\\SURV2024-001\\Administration\\Contracts',
            qaQcFolderLink: 'N:\\SURV2024-001\\07 QA-QC\\5 - Plan and Report Markups\\Land Surveying',
            researchFolderLink: 'N:\\SURV2024-001\\09 Research\\Survey Research',
            notes: 'Initial boundary survey for commercial development project'
        },
        {
            projectId: 'SURV2024-002',
            projectName: 'Residential Subdivision Plat',
            projectStatus: 'Completed',
            createdBy: 'Sarah Smith',
            createdDate: '2024-01-20',
            projectFolderLink: 'N:\\SURV2024-002',
            surveyFolderLink: 'N:\\SURV2024-002\\05 Service Groups\\Survey',
            drawingFolderLink: 'N:\\SURV2024-002\\06 CACD\\DWG\\Survey',
            contractLink: 'N:\\SURV2024-002\\Administration\\Contracts',
            qaQcFolderLink: 'N:\\SURV2024-002\\07 QA-QC\\5 - Plan and Report Markups\\Land Surveying',
            researchFolderLink: 'N:\\SURV2024-002\\09 Research\\Survey Research',
            notes: '45-lot residential subdivision with utility easements'
        },
        {
            projectId: 'SURV2024-003',
            projectName: 'Highway Expansion Topographic Survey',
            projectStatus: 'Active',
            createdBy: 'Mike Johnson',
            createdDate: '2024-02-01',
            projectFolderLink: 'N:\\SURV2024-003',
            surveyFolderLink: 'N:\\SURV2024-003\\05 Service Groups\\Survey',
            drawingFolderLink: 'N:\\SURV2024-003\\06 CACD\\DWG\\Survey',
            contractLink: 'N:\\SURV2024-003\\Administration\\Contracts',
            qaQcFolderLink: 'N:\\SURV2024-003\\07 QA-QC\\5 - Plan and Report Markups\\Land Surveying',
            researchFolderLink: 'N:\\SURV2024-003\\09 Research\\Survey Research',
            notes: 'State highway expansion project - 5 mile corridor'
        }
    ];
}

// Search functionality
function searchProjects() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const statusFilter = document.getElementById('statusFilter').value;
    
    filteredProjects = allProjects.filter(project => {
        const matchesSearch = project.projectName.toLowerCase().includes(searchTerm) || 
                            project.projectId.toLowerCase().includes(searchTerm) ||
                            project.createdBy.toLowerCase().includes(searchTerm);
        
        const matchesStatus = !statusFilter || project.projectStatus === statusFilter;
        
        return matchesSearch && matchesStatus;
    });
    
    currentPage = 1;
    updateProjectsDisplay();
}

// Filter by status
function filterProjects() {
    searchProjects();
}

// Change items per page
function changeItemsPerPage() {
    itemsPerPage = parseInt(document.getElementById('itemsPerPage').value);
    currentPage = 1;
    updateProjectsDisplay();
}

// Update the projects display
function updateProjectsDisplay() {
    const totalProjects = filteredProjects.length;
    const startIndex = (currentPage - 1) * itemsPerPage;
    const endIndex = Math.min(startIndex + itemsPerPage, totalProjects);
    const currentProjects = filteredProjects.slice(startIndex, endIndex);
    
    // Update project count
    document.getElementById('projectsCount').textContent = `Showing ${totalProjects} projects`;
    
    // Show/hide empty state
    const emptyState = document.getElementById('emptyState');
    const tableContainer = document.getElementById('projectsTableContainer');
    const pagination = document.getElementById('pagination');
    
    if (totalProjects === 0) {
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
    const tbody = document.getElementById('projectsTableBody');
    tbody.innerHTML = '';

    currentProjects.forEach(project => {
        const { row, detailsRow } = createProjectRow(project);
        tbody.appendChild(row);
        tbody.appendChild(detailsRow);
    });
        
    // Update pagination
    updatePagination(totalProjects);
}
// Create a project row with collapsible details
function createProjectRow(project) {
    // Create main row
    const row = document.createElement('tr');
    row.className = 'project-row';
    row.dataset.projectId = project.projectId;
    
    // Format date
    const createdDate = new Date(project.createdDate).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
    
    // Status badge class
    const statusClass = `status-${project.projectStatus.toLowerCase().replace(' ', '-')}`;
    
    // Task summary (will be populated after loading)
    const taskSummary = `<span class="task-summary" id="task-summary-${project.projectId}">
        <i class="fas fa-spinner fa-spin"></i> Loading tasks...
    </span>`;
    
    row.innerHTML = `
        <td>
            <div style="display: flex; align-items: center;">
                <i class="fas fa-chevron-right expand-icon"></i>
                <div>
                    <div class="project-id">${project.projectId}</div>
                    <div class="project-name">${project.projectName}</div>
                </div>
            </div>
        </td>
        <td>
            <span class="status-badge ${statusClass}">
                <i class="fas fa-circle"></i>
                ${project.projectStatus}
            </span>
        </td>
        <td>${createdDate}</td>
        <td>${taskSummary}</td>
        <td>
            <span style="color: var(--gray-500); font-size: 0.875rem;">Click to expand</span>
        </td>
    `;
    
    // Create details row
    const detailsRow = document.createElement('tr');
    detailsRow.className = 'details-row';
    detailsRow.dataset.projectId = project.projectId;
    
    // Create links
    const links = createProjectLinks(project);
    
    detailsRow.innerHTML = `
        <td colspan="5">
            <div class="details-content">
                <div class="details-grid">
                    <div class="detail-item">
                        <span class="detail-label">Created By</span>
                        <span class="detail-value">${project.createdBy || 'N/A'}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Location</span>
                        <span class="detail-value">${project.location || 'N/A'}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Scale Factor</span>
                        <span class="detail-value">${project.scale_factor || 'N/A'}</span>
                    </div>
                </div>
                
                <div class="tasks-section">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                        <h4><i class="fas fa-tasks"></i> Project Tasks</h4>
                        <button class="btn btn-sm btn-primary" onclick="openAddTaskModal('${project.projectId}')">
                            <i class="fas fa-plus"></i> Add Task
                        </button>
                    </div>
                    <div class="tasks-list" id="tasks-list-${project.projectId}">
                        <div style="text-align: center; padding: 1rem; color: var(--gray-500);">
                            <i class="fas fa-spinner fa-spin"></i> Loading tasks...
                        </div>
                    </div>
                </div>
                
                <div class="links-section">
                    <h4>Project Links</h4>
                    <div class="links-grid">
                        ${links}
                    </div>
                </div>
                
                <div class="actions-section">
                    <button class="btn btn-sm btn-secondary" onclick="editProject('${project.projectId}')">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                    <button class="btn btn-sm btn-secondary" onclick="copyProjectId('${project.projectId}')">
                        <i class="fas fa-copy"></i> Copy ID
                    </button>
                    <button class="btn btn-sm" style="background: var(--danger-color); color: white;" onclick="deleteProject('${project.projectId}')">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                </div>
            </div>
        </td>
    `;
    
    // Load tasks for this project and update display
    loadTasksForProject(project.projectId).then(tasks => {
        // Update task summary in main row
        const taskSummaryElement = document.getElementById(`task-summary-${project.projectId}`);
        if (taskSummaryElement) {
            const totalTasks = tasks.length;
            const completedTasks = tasks.filter(t => t.task_status === 'Completed').length;
            taskSummaryElement.innerHTML = `
                <i class="fas fa-tasks"></i> ${completedTasks}/${totalTasks} tasks
            `;
        }
        
        // Update tasks list in details row
        const tasksListElement = document.getElementById(`tasks-list-${project.projectId}`);
        if (tasksListElement) {
            tasksListElement.innerHTML = createTasksHTML(tasks);
        }
    });
    
    // Add click event to toggle details
    row.addEventListener('click', function(e) {
        // Don't toggle if clicking on a button or link
        if (e.target.closest('button') || e.target.closest('a')) {
            return;
        }
        
        this.classList.toggle('expanded');
        detailsRow.classList.toggle('show');
    });
    
    return { row, detailsRow };
}

// Create project links
function createProjectLinks(project) {
    uncPath = "westwoodps.local\\Global Projects";
    //projectHref = project[type.key].replace(/N:\\/g, 'westwoodps.local\\Global Projects\\')
    const linkTypes = [
        { key: 'projectFolderLink', icon: 'fas fa-folder', label: 'Project' },
        { key: 'surveyFolderLink', icon: 'fas fa-map', label: 'Survey' },
        { key: 'drawingFolderLink', icon: 'fas fa-drafting-compass', label: 'Drawings' },
        { key: 'contractLink', icon: 'fas fa-file-contract', label: 'Contract' },
        { key: 'qaQcFolderLink', icon: 'fas fa-check-double', label: 'QA/QC' },
        { key: 'researchFolderLink', icon: 'fas fa-search', label: 'Research' }
    ];

    // Auto-generated links based on Survey folder path
    const surveyPath = project.surveyFolderLink;
    const autoGeneratedLinks = surveyPath ? [
        {
            path: `${surveyPath}\\\\Downloads`,
            icon: 'fas fa-download',
            label: 'Field Data',
            color: '#3b82f6'
        },
        {
            path: `${surveyPath}\\\\Control`,
            icon: 'fas fa-map-pin',
            label: 'Control',
            color: '#10b981'
        }
    ] : [];

    // Build links from database fields
    const dbLinks = linkTypes
        .filter(type => project[type.key])
        .map(type => `
            <a href="file:///${project[type.key].replace(/N:\\/g, uncPath)}" class="link-button">
                <i class="${type.icon}"></i>
                ${type.label}
                <button class="btn btn-sm btn-secondary" onclick="copyFolderPath('${project[type.key]}', '${type.label}')" title="Copy ${type.label} path">
                    <i class="fas fa-copy"></i>
                </button>
            </a>
        `).join('');

    // Build auto-generated links
    const autoLinks = autoGeneratedLinks
        .map(link => `
            <a href="file:///${link.path.replace(/N:\\/g, uncPath)}" class="link-button" style="border-color: ${link.color};">
                <i class="${link.icon}" style="color: ${link.color};"></i>
                ${link.label}
                <button class="btn btn-sm btn-secondary" onclick="copyFolderPath('${link.path}', '${link.label}')" title="Copy ${link.label} path">
                    <i class="fas fa-copy"></i>
                </button>
            </a>
        `).join('');

    return dbLinks + autoLinks;
}

// Generate task-level Raw Data folder link (only shown if override is set)
// Field Data and Control are at the project level
function generateTaskFolderLinks(projectId, task) {
    const uncPath = "westwoodps.local\\Global Projects";

    // Parse folder overrides if they exist
    let overrides = {};
    if (task.folder_overrides) {
        try {
            overrides = typeof task.folder_overrides === 'string'
                ? JSON.parse(task.folder_overrides)
                : task.folder_overrides;
        } catch (e) {
            console.warn('Failed to parse folder_overrides:', e);
        }
    }

    // Only show Raw Data link if override is set
    if (!overrides.rawData) {
        return ''; // No raw data path set for this task
    }

    const path = overrides.rawData;
    const fileUrl = `file:///${path.replace(/N:\\/g, uncPath).replace(/\\/g, '/')}`;

    return `
        <div class="task-folder-links">
            <a href="${fileUrl}" target="_blank" class="task-folder-link" style="border-color: #f59e0b;" title="Raw Data: ${path}">
                <i class="fas fa-database" style="color: #f59e0b;"></i>
                <span>Raw Data</span>
            </a>
        </div>
    `;
}

// Copy task folder path to clipboard
function copyTaskFolderPath(path, label) {
    event.stopPropagation();
    navigator.clipboard.writeText(path.replace(/\\\\/g, '\\')).then(() => {
        showToast(`${label} path copied to clipboard`, 'success');
    }).catch(err => {
        console.error('Failed to copy:', err);
        showToast('Failed to copy path', 'error');
    });
}

// Update pagination
function updatePagination(totalProjects) {
    const totalPages = Math.ceil(totalProjects / itemsPerPage);
    const startIndex = (currentPage - 1) * itemsPerPage + 1;
    const endIndex = Math.min(currentPage * itemsPerPage, totalProjects);
    
    // Update pagination info
    document.getElementById('paginationInfo').textContent = 
        `Showing ${startIndex} to ${endIndex} of ${totalProjects} entries`;
    
    // Update pagination controls
    const controls = document.getElementById('paginationControls');
    controls.innerHTML = '';
    
    // Previous button
    const prevBtn = document.createElement('button');
    prevBtn.className = 'pagination-button';
    prevBtn.innerHTML = '<i class="fas fa-chevron-left"></i> Previous';
    prevBtn.disabled = currentPage === 1;
    prevBtn.onclick = () => goToPage(currentPage - 1);
    controls.appendChild(prevBtn);
    
    // Page numbers
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
    
    // Next button
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
    updateProjectsDisplay();
}

// Open create modal
function openCreateModal() {
    currentEditingProject = null;
    document.getElementById('modalTitle').textContent = 'Create New Project';
    document.getElementById('projectForm').reset();
    document.getElementById('projectModal').style.display = 'block';
}

// Edit project
function editProject(projectId) {
    const project = allProjects.find(p => p.projectId === projectId);
    if (!project) {
        alert('Project not found');
        return;
    }
    
    currentEditingProject = project;
    document.getElementById('modalTitle').textContent = 'Edit Project';
    
    // Populate form
    Object.keys(project).forEach(key => {
        const element = document.getElementById(key);
        if (element) {
            element.value = project[key] || '';
        }
    });
    
    // Show the modal
    document.getElementById('projectModal').style.display = 'block';
    
    // Change save button text to indicate editing
    const saveButton = document.querySelector('#projectModal .btn-primary');
    if (saveButton) {
        saveButton.textContent = 'Update Project';
        saveButton.onclick = saveProjectChanges;
    }
}

// Save project changes
function saveProjectChanges() {
    if (!currentEditingProject) {
        alert('No project selected for editing');
        return;
    }

    // Get form data
    const formData = new FormData();
    formData.append('action', 'update_project');
    formData.append('projectId', currentEditingProject.projectId);

    // Get all form fields
    const fieldIds = [
        'projectName', 'projectStatus', 'projectFolderLink', 'surveyFolderLink',
        'drawingFolderLink', 'contractLink', 'qaQcFolderLink', 'researchFolderLink',
        'fieldFolderLink', 'notes', 'modifiedBy', 'location', 'scale_factor'
    ];

    // Add form data
    fieldIds.forEach(fieldId => {
        const element = document.getElementById(fieldId);
        if (element && element.value !== undefined) {
            formData.append(fieldId, element.value);
        }
    });

    // Show loading state
    const saveButton = document.querySelector('#projectModal .btn-primary');
    const originalText = saveButton.textContent;
    saveButton.textContent = 'Updating...';
    saveButton.disabled = true;

    // Send update request
    fetch('../../Models/php/load_survey_project_notes.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update the project in allProjects array
            const projectIndex = allProjects.findIndex(p => p.projectId === currentEditingProject.projectId);
            if (projectIndex !== -1) {
                allProjects[projectIndex] = data.project;
            }
            
            // Refresh the project display
            loadProjects();
            
            // Close the modal
            closeModal();
            
            // Show success message
            showToast('Project updated successfully!', 'success');
            
        } else {
            alert('Error updating project: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error updating project. Please try again.');
    })
    .finally(() => {
        // Restore button state
        saveButton.textContent = originalText;
        saveButton.disabled = false;
    });
}

// Close modal
function closeModal() {
    document.getElementById('projectModal').style.display = 'none';
    currentEditingProject = null;
}

// Save project
function saveProject(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    formData.append('action', 'add_project');
    
    const submitButton = form.querySelector('button[type="submit"]');
    const originalText = submitButton.textContent;
    submitButton.textContent = 'Saving...';
    submitButton.disabled = true;
    
    fetch('../../Models/php/save_survey_project_notes.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        submitButton.textContent = originalText;
        submitButton.disabled = false;
        
        if (data.success) {
            showToast('Project saved successfully!', 'success');
            form.reset();
            closeModal();
            loadProjects(); // Reload projects
        } else {
            showToast(data.message || 'Error saving project', 'error');
        }
    })
    .catch(error => {
        submitButton.textContent = originalText;
        submitButton.disabled = false;
        console.error('Error saving project:', error);
        showToast('Network error: Unable to save project. Please try again.', 'error');
    });
}

// Delete project
function deleteProject(projectId) {
    if (confirm('Are you sure you want to delete this project? This action cannot be undone.')) {
        const index = allProjects.findIndex(p => p.projectId === projectId);
        if (index !== -1) {
            allProjects.splice(index, 1);
            showToast('Project deleted successfully!');
            searchProjects();
        }
    }
}

// Copy project ID
function copyFolderPath(folderPath, linkType) {
    navigator.clipboard.writeText(folderPath).then(() => {
        showToast(`${linkType} ": ${folderPath}" copied to clipboard!`);
    }).catch(() => {
        showToast(`"Failed to copy: ${linkType}"`);
    });
}

// Copy task folder path
function copyTaskPath(taskPath, taskName) {
    navigator.clipboard.writeText(taskPath).then(() => {
        showToast(`Task folder path for "${taskName}" copied to clipboard!`);
    }).catch(() => {
        showToast(`Failed to copy task folder path`, 'error');
    });
}

// Export data
function exportData() {
    const dataStr = JSON.stringify(filteredProjects, null, 2);
    const dataBlob = new Blob([dataStr], {type: 'application/json'});
    
    const link = document.createElement('a');
    link.href = URL.createObjectURL(dataBlob);
    link.download = `survey-projects-${new Date().toISOString().split('T')[0]}.json`;
    link.click();
    
    showToast('Project data exported successfully!');
}

const sidebar = document.getElementById('sidebar'); // Make sure your sidebar has id="sidebar"
const toggleButton = document.querySelector('.toggle-btn'); // or use getElementById('toggleBtn')
const main_content = document.querySelector('.main-content');

toggleButton.addEventListener('click', () => {
    sidebar.classList.toggle('collapsed');
    main_content.classList.toggle('collapsed');
});

mobileToggle.addEventListener('click', () => {
    sidebar.classList.toggle('collapsed');
});


// Toggle sidebar (mobile)
function toggleSidebar() {
    sidebar.classList.toggle('open');
}

// Show toast notification with type support
function showToast(message, type = 'success') {
    const toast = document.getElementById('toast');
    const toastMessage = document.getElementById('toastMessage');
    const toastIcon = document.querySelector('.toast-icon');
    
    toastMessage.textContent = message;
    
    // Update icon and color based on type
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

// Set up auto-fill functionality
function setupAutoFill() {
    // Function to update dependent fields based on project ID
    function updateDependentFields() {
        const projectId = projectIdInput.value.trim();
        
        if (projectId) {
            // Update all folder links
            projectFolderLinkInput.value = `N:\\\\${projectId}`;
            projectSurveyFolderLinkInput.value = `N:\\\\${projectId}\\\\05 Service Groups\\\\Survey`;
            projectDrawingFolderLinkInput.value = `N:\\\\${projectId}\\\\06 CAD\\\\DWG\\\\Survey C3D`;
            projectContractLinkInput.value = `N:\\\\${projectId}\\\\Administration\\\\Contracts`;
            projectQAQCLinkInput.value = `N:\\\\${projectId}\\\\07 QA-QC\\\\5 - Plan and Report Markups\\\\Land Surveying`;
            projectResearchLinkInput.value = `N:\\\\${projectId}\\\\09 Research\\\\Survey Research`;
            
            // Add auto-filled class
            [projectFolderLinkInput, projectSurveyFolderLinkInput, projectDrawingFolderLinkInput,
             projectContractLinkInput, projectQAQCLinkInput, projectResearchLinkInput].forEach(input => {
                input.classList.add('auto-filled');
            });
        } else {
            // Clear all fields
            [projectFolderLinkInput, projectSurveyFolderLinkInput, projectDrawingFolderLinkInput,
             projectContractLinkInput, projectQAQCLinkInput, projectResearchLinkInput].forEach(input => {
                input.value = '';
                input.classList.remove('auto-filled');
            });
        }
    }

    // Listen for changes to the project ID input
    projectIdInput.addEventListener('input', updateDependentFields);
    projectIdInput.addEventListener('paste', function() {
        setTimeout(updateDependentFields, 10);
    });

    // Allow manual editing of auto-filled fields
    [projectFolderLinkInput, projectSurveyFolderLinkInput, projectDrawingFolderLinkInput,
     projectContractLinkInput, projectQAQCLinkInput, projectResearchLinkInput].forEach(input => {
        input.addEventListener('focus', function() {
            this.classList.remove('auto-filled');
        });
    });
}

// Close modal when clicking outside
document.addEventListener('click', function(event) {
    const projectModal = document.getElementById('projectModal');
    const taskModal = document.getElementById('taskModal');
    const sidebar = document.getElementById('sidebar');
    const mobileMenuButton = document.querySelector('.mobile-menu-button');
    
    // Close project modal if clicking outside
    if (event.target === projectModal) {
        closeModal();
    }
    
    // Close task modal if clicking outside
    if (event.target === taskModal) {
        closeTaskModal();
    }
    
    // Close sidebar if clicking outside (mobile)
    if (sidebar && mobileMenuButton && 
        !sidebar.contains(event.target) && !mobileMenuButton.contains(event.target)) {
        sidebar.classList.remove('open');
    }
});

        // Handle window resize
        window.addEventListener('resize', () => {
            if (window.innerWidth > 768) {
                sidebar.classList.remove('mobile-open');
            }
        });

// Current editing task
let currentEditingTask = null;

// Open add task modal
function openAddTaskModal(projectId) {
    currentEditingTask = null;
    document.getElementById('taskModalTitle').textContent = 'Add New Task';
    document.getElementById('taskForm').reset();
    document.getElementById('taskId').value = '';
    document.getElementById('taskProjectId').value = projectId;
    document.getElementById('taskModal').style.display = 'block';
}

// Open edit task modal
function editTask(taskId, projectId) {
    // Find the task in the loaded tasks
    loadTasksForProject(projectId).then(tasks => {
        const task = tasks.find(t => t.task_id == taskId);
        if (!task) {
            showToast('Task not found', 'error');
            return;
        }
        
        currentEditingTask = task;
        document.getElementById('taskModalTitle').textContent = 'Edit Task';
        
        // Populate form
        document.getElementById('taskId').value = task.task_id;
        document.getElementById('taskProjectId').value = task.project_id;
        document.getElementById('taskName').value = task.task_name || '';
        document.getElementById('taskType').value = task.task_type || '';
        document.getElementById('taskStatus').value = task.task_status || 'Not Started';
        document.getElementById('taskPriority').value = task.task_priority || 'Medium';
        document.getElementById('phaseNumber').value = task.phase_number || '';
        document.getElementById('assignedTo').value = task.assigned_to || '';
        document.getElementById('startDate').value = task.start_date || '';
        document.getElementById('dueDate').value = task.due_date || '';
        document.getElementById('completionDate').value = task.completion_date || '';
        document.getElementById('estimatedHours').value = task.estimated_hours || '';
        document.getElementById('actualHours').value = task.actual_hours || '';
        document.getElementById('taskLink').value = task.task_link || '';
        document.getElementById('taskNotes').value = task.notes || '';

        // Populate raw data path from folder_overrides
        let rawDataPath = '';
        if (task.folder_overrides) {
            try {
                const overrides = typeof task.folder_overrides === 'string'
                    ? JSON.parse(task.folder_overrides)
                    : task.folder_overrides;
                rawDataPath = overrides.rawData || '';
            } catch (e) {
                console.warn('Failed to parse folder_overrides:', e);
            }
        }
        document.getElementById('rawDataPath').value = rawDataPath;

        document.getElementById('taskModal').style.display = 'block';
    });
}

// Close task modal
function closeTaskModal() {
    document.getElementById('taskModal').style.display = 'none';
    currentEditingTask = null;
}

// Save task
function saveTask(event) {
    event.preventDefault();

    const form = event.target;
    const formData = new FormData(form);
    const taskId = document.getElementById('taskId').value;

    // Handle folder overrides (raw data path)
    const rawDataPath = document.getElementById('rawDataPath').value.trim();
    if (rawDataPath) {
        const folderOverrides = { rawData: rawDataPath };
        formData.append('folderOverrides', JSON.stringify(folderOverrides));
    }

    // Determine action based on whether we're editing or adding
    formData.append('action', taskId ? 'update_task' : 'add_task');
    
    const submitButton = form.querySelector('button[type="submit"]');
    const originalText = submitButton.textContent;
    submitButton.textContent = 'Saving...';
    submitButton.disabled = true;
    
    fetch('../../Models/php/save_task.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        submitButton.textContent = originalText;
        submitButton.disabled = false;
        
        if (data.success) {
            showToast(taskId ? 'Task updated successfully!' : 'Task added successfully!', 'success');
            form.reset();
            closeTaskModal();
            
            // Reload tasks for the project
            const projectId = document.getElementById('taskProjectId').value;
            loadTasksForProject(projectId).then(tasks => {
                const tasksListElement = document.getElementById(`tasks-list-${projectId}`);
                if (tasksListElement) {
                    tasksListElement.innerHTML = createTasksHTML(tasks);
                }
                
                // Update task summary
                const taskSummaryElement = document.getElementById(`task-summary-${projectId}`);
                if (taskSummaryElement) {
                    const totalTasks = tasks.length;
                    const completedTasks = tasks.filter(t => t.task_status === 'Completed').length;
                    taskSummaryElement.innerHTML = `
                        <i class="fas fa-tasks"></i> ${completedTasks}/${totalTasks} tasks
                    `;
                }
            });
        } else {
            showToast(data.message || 'Error saving task', 'error');
        }
    })
    .catch(error => {
        submitButton.textContent = originalText;
        submitButton.disabled = false;
        console.error('Error saving task:', error);
        showToast('Network error: Unable to save task. Please try again.', 'error');
    });
}

// Delete task
function deleteTask(taskId, projectId) {
    if (!confirm('Are you sure you want to delete this task? This action cannot be undone.')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'delete_task');
    formData.append('taskId', taskId);
    
    fetch('../../Models/php/save_task.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Task deleted successfully!', 'success');
            
            // Reload tasks for the project
            loadTasksForProject(projectId).then(tasks => {
                const tasksListElement = document.getElementById(`tasks-list-${projectId}`);
                if (tasksListElement) {
                    tasksListElement.innerHTML = createTasksHTML(tasks);
                }
                
                // Update task summary
                const taskSummaryElement = document.getElementById(`task-summary-${projectId}`);
                if (taskSummaryElement) {
                    const totalTasks = tasks.length;
                    const completedTasks = tasks.filter(t => t.task_status === 'Completed').length;
                    taskSummaryElement.innerHTML = `
                        <i class="fas fa-tasks"></i> ${completedTasks}/${totalTasks} tasks
                    `;
                }
            });
        } else {
            showToast(data.message || 'Error deleting task', 'error');
        }
    })
    .catch(error => {
        console.error('Error deleting task:', error);
        showToast('Network error: Unable to delete task. Please try again.', 'error');
    });
}

// Create tasks HTML with edit/delete buttons and clickable status
function createTasksHTML(tasks) {
    if (!tasks || tasks.length === 0) {
        return `
            <div class="no-tasks-message">
                <i class="fas fa-tasks" style="margin-right: 0.5rem;"></i>
                No tasks assigned to this project yet
            </div>
        `;
    }
    
    return tasks.map(task => {
        const taskTypeClass = `task-type-${formatTaskTypeClass(task.task_type)}`;
        const taskStatusClass = `task-status-${formatTaskStatusClass(task.task_status)}`;
        const dueDate = task.due_date ? new Date(task.due_date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : 'No due date';
        const uncPath = "westwoodps.local\\\\Global Projects";

        return `
            <div class="task-item">
                <div class="task-info">
                    <div class="task-header">
                        <span class="task-type-badge ${taskTypeClass}">${task.task_type}</span>
                        <span class="task-name">${task.task_name}</span>
                    </div>
                    <div class="task-meta">
                        ${task.phase_number ? `<span><i class="fas fa-layer-group"></i> Phase ${task.phase_number}</span>` : ''}
                        <span><i class="fas fa-calendar"></i> ${dueDate}</span>
                        ${task.assigned_to ? `<span><i class="fas fa-user"></i> ${task.assigned_to}</span>` : ''}
                    </div>
                    <div class="task-folder-links">
                        ${generateTaskFolderLinks(task.project_id, task)}
                        ${task.task_link ? `
                            <a href="file:///${task.task_link.replace(/N:\\\\/g, uncPath)}" target="_blank" class="task-folder-link" style="border-color: #6366f1;" title="Task Folder: ${task.task_link}">
                                <i class="fas fa-folder" style="color: #6366f1;"></i>
                                <span>Task</span>
                            </a>
                        ` : ''}
                    </div>
                    ${task.notes ? `
                        <div class="task-notes">
                            <i class="fas fa-sticky-note"></i>
                            <span>${task.notes}</span>
                        </div>
                    ` : ''}
                </div>
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <div class="task-status-wrapper">
                        <span class="task-status-badge ${taskStatusClass}" onclick="toggleStatusDropdown(event, ${task.task_id})" title="Click to change status">
                            <i class="fas fa-circle"></i>
                            ${task.task_status}
                        </span>
                        <div class="status-dropdown" id="status-dropdown-${task.task_id}">
                            ${createStatusDropdownItems(task.task_id, task.project_id, task.task_status)}
                        </div>
                    </div>
                    <button class="btn btn-xs btn-secondary" onclick="editTask(${task.task_id}, '${task.project_id}')" title="Edit task">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-xs" style="background: var(--danger-color); color: white;" onclick="deleteTask(${task.task_id}, '${task.project_id}')" title="Delete task">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `;
    }).join('');
}

// Create status dropdown items
function createStatusDropdownItems(taskId, projectId, currentStatus) {
    const statuses = [
        { value: 'Not Started', icon: 'fa-circle', class: 'not-started' },
        { value: 'In Progress', icon: 'fa-circle', class: 'in-progress' },
        { value: 'Review', icon: 'fa-circle', class: 'review' },
        { value: 'On Hold', icon: 'fa-circle', class: 'on-hold' },
        { value: 'Completed', icon: 'fa-circle', class: 'completed' },
        { value: 'Cancelled', icon: 'fa-circle', class: 'cancelled' }
    ];
    
    return statuses.map(status => {
        const isActive = status.value === currentStatus ? 'active' : '';
        const statusClass = `task-status-${formatTaskStatusClass(status.value)}`;
        
        return `
            <div class="status-dropdown-item ${isActive}" onclick="changeTaskStatus(event, ${taskId}, '${projectId}', '${status.value}')">
                <i class="fas ${status.icon}"></i>
                <span>${status.value}</span>
            </div>
        `;
    }).join('');
}

// Toggle status dropdown
function toggleStatusDropdown(event, taskId) {
    event.stopPropagation();
    
    // Close all other dropdowns
    document.querySelectorAll('.status-dropdown').forEach(dropdown => {
        if (dropdown.id !== `status-dropdown-${taskId}`) {
            dropdown.classList.remove('show');
        }
    });
    
    // Toggle current dropdown
    const dropdown = document.getElementById(`status-dropdown-${taskId}`);
    dropdown.classList.toggle('show');
}

// Change task status
function changeTaskStatus(event, taskId, projectId, newStatus) {
    event.stopPropagation();
    
    // Close dropdown
    const dropdown = document.getElementById(`status-dropdown-${taskId}`);
    dropdown.classList.remove('show');
    
    // Prepare form data
    const formData = new FormData();
    formData.append('action', 'update_task_status');
    formData.append('taskId', taskId);
    formData.append('taskStatus', newStatus);
    
    // Send update request
    fetch('../../Models/php/save_task.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(`Task status updated to "${newStatus}"`, 'success');
            
            // Reload tasks for the project
            loadTasksForProject(projectId).then(tasks => {
                const tasksListElement = document.getElementById(`tasks-list-${projectId}`);
                if (tasksListElement) {
                    tasksListElement.innerHTML = createTasksHTML(tasks);
                }
                
                // Update task summary
                const taskSummaryElement = document.getElementById(`task-summary-${projectId}`);
                if (taskSummaryElement) {
                    const totalTasks = tasks.length;
                    const completedTasks = tasks.filter(t => t.task_status === 'Completed').length;
                    taskSummaryElement.innerHTML = `
                        <i class="fas fa-tasks"></i> ${completedTasks}/${totalTasks} tasks
                    `;
                }
            });
        } else {
            showToast(data.message || 'Error updating task status', 'error');
        }
    })
    .catch(error => {
        console.error('Error updating task status:', error);
        showToast('Network error: Unable to update task status', 'error');
    });
}

// Close dropdowns when clicking outside
document.addEventListener('click', function(event) {
    if (!event.target.closest('.task-status-wrapper')) {
        document.querySelectorAll('.status-dropdown').forEach(dropdown => {
            dropdown.classList.remove('show');
        });
    }
});
    </script>
</body>
</html>