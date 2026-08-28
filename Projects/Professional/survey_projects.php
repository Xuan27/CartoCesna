<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$currentUsername = $_SESSION['username'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Survey Project Manager - Professional Dashboard</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <link rel="stylesheet" href="../../Models/css/survey_projects_notes.css?v=<?php echo filemtime(__DIR__ . '/../../Models/css/survey_projects_notes.css'); ?>">
</head>
<body>

    <header>
        <div id="header-container">
            <div class="loading">Loading header...</div>
        </div>
    </header>

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
                <a href="#" class="nav-item active" id="navDashboard" onclick="switchProjectsView('all'); return false;">
                    <i class="fas fa-th-large"></i>
                    Dashboard
                </a>
                <a href="#" class="nav-item" id="navMyTodo" onclick="switchProjectsView('todo'); return false;">
                    <i class="fas fa-star"></i>
                    My To-Do
                </a>
                <a href="./all_tasks.php" class="nav-item">
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
                <a href="#" class="nav-item" onclick="openTimesheetModal(); return false;" data-tooltip="Timesheet">
                    <i class="fas fa-clock"></i>
                    Timesheet
                </a>
                <a href="#" class="nav-item" data-tooltip="Settings">
                    <i class="fas fa-cog"></i>
                    Settings
                </a>
            </nav>
        </div>
    </div>

    <!-- Active Timer Banner -->
    <div id="activeTimerBanner" class="active-timer-banner" style="display:none;">
        <i class="fas fa-circle timer-pulse"></i>
        <span class="timer-label-text" id="activeTimerLabel">Recording...</span>
        <span id="timerStartTimeDisplay" onclick="editTimerStartTime()" title="Click to edit start time" style="cursor:pointer;font-size:0.78rem;color:rgba(255,255,255,0.7);margin:0 0.25rem;white-space:nowrap;"><i class="fas fa-edit" style="font-size:0.68rem;margin-right:2px;"></i><span id="timerStartTimeText">--:--</span></span>
        <span id="activeTimerDisplay" class="timer-count">0:00:00</span>
        <button onclick="openStopTimerModal()" class="btn timer-stop-btn">
            <i class="fas fa-stop"></i> Stop
        </button>
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
                    <h2 id="pageTitle">Survey Projects</h2>
                    <p id="pageSubtitle">Manage and organize your surveying projects</p>
                </div>
            </div>
            <div class="top-bar-right">
                <div style="position:relative;display:inline-block;">
                    <button id="logTimeBtnToggle" class="btn btn-secondary" onclick="toggleLogTimeDropdown()">
                        <i class="fas fa-clock"></i> Log Time <i class="fas fa-caret-down" style="margin-left:2px;font-size:0.75rem;"></i>
                    </button>
                    <div id="logTimeDropdown" style="display:none;position:absolute;right:0;top:calc(100% + 4px);background:#fff;border:1px solid #e2e8f0;border-radius:8px;box-shadow:0 4px 16px rgba(0,0,0,0.12);min-width:168px;z-index:200;overflow:hidden;">
                        <button onclick="startCategoryTimer('Admin Time','ADMIN')" style="width:100%;padding:0.6rem 1rem;border:none;background:none;text-align:left;cursor:pointer;font-size:0.875rem;color:#374151;display:flex;align-items:center;gap:0.5rem;" onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background='none'">
                            <i class="fas fa-briefcase" style="color:#6366f1;width:14px;"></i> Admin Time
                        </button>
                        <button onclick="startCategoryTimer('Training Time','TRAINING')" style="width:100%;padding:0.6rem 1rem;border:none;background:none;text-align:left;cursor:pointer;font-size:0.875rem;color:#374151;display:flex;align-items:center;gap:0.5rem;" onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background='none'">
                            <i class="fas fa-graduation-cap" style="color:#10b981;width:14px;"></i> Training Time
                        </button>
                    </div>
                </div>
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
                    <select class="filter-select" id="todoSortSelect" style="display:none;" onchange="searchProjects()" title="Sort My To-Do list">
                        <option value="priority-desc">Sort: Priority (High → Low)</option>
                        <option value="priority-asc">Sort: Priority (Low → High)</option>
                        <option value="recent">Sort: Recently Added</option>
                        <option value="name">Sort: Project Name (A-Z)</option>
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
                    <i class="fas fa-folder-open" id="emptyStateIcon"></i>
                    <h3 id="emptyStateTitle">No Projects Found</h3>
                    <p id="emptyStateText">Create your first survey project to get started</p>
                    <button class="btn btn-primary" onclick="openCreateModal()" style="margin-top: 1rem;" id="emptyStateAction">
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
                            <label class="form-label" for="plus_code">
                                Google Plus Code
                                <a href="https://maps.google.com/pluscodes/" target="_blank"
                                   style="font-size:0.75rem;font-weight:400;color:var(--primary-color);margin-left:0.4rem;">
                                    What's this?
                                </a>
                            </label>
                            <div style="display:flex; gap:0.5rem;">
                                <input type="text" class="form-input" id="plus_code" name="plus_code"
                                       placeholder="e.g. 87G8Q2PV+W3 or 2PVH+W3 Austin, Texas"
                                       style="font-family:monospace;text-transform:uppercase;flex:1;min-width:0;"
                                       oninput="this.value=this.value.toUpperCase()">
                                <button type="button" id="plusCodeLookupBtn" class="btn btn-secondary" style="flex-shrink:0;"
                                        onclick="lookupLocationFromPlusCode()" title="Fill City/County/State from this Plus Code">
                                    <i class="fas fa-location-crosshairs"></i>
                                </button>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="scale_factor">Scale Factor</label>
                            <input type="text" class="form-input" id="scale_factor" name="scale_factor"
                                   placeholder="1.0000000">
                        </div>
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label style="display:flex; align-items:center; gap:0.6rem; cursor:pointer; user-select:none;">
                                <input type="checkbox" id="needs_monuments" name="needs_monuments"
                                       style="width:18px; height:18px; accent-color:var(--primary-color); cursor:pointer; flex-shrink:0;">
                                <span>
                                    <strong style="font-size:0.9rem;">Needs Monument Setting</strong>
                                    <span style="display:block; font-size:0.78rem; color:var(--gray-500); margin-top:1px;">
                                        Check if this project requires physical survey monuments to be set in the field.
                                    </span>
                                </span>
                            </label>
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
                            <small style="color: #6b7280; font-size: 0.75rem;">Auto-fills as [Project ID][Task Type acronym] (e.g., SURV2024-001EX) once a task type is picked - edit freely for special cases</small>
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
                            <label class="form-label" for="coordinateType">Coordinate Type</label>
                            <select class="form-select" id="coordinateType" name="coordinateType">
                                <option value="">— Not specified —</option>
                                <option value="Grid">Grid</option>
                                <option value="Surface">Surface</option>
                                <option value="Calibration">Calibration</option>
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
                            <small style="color: #6b7280; font-size: 0.75rem;">Auto-fills as [Drawing Folder Link]\[Task Name].dwg - edit freely for special cases</small>
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

    <!-- Point Ranges Modal -->
    <div id="pointRangesModal" class="modal">
        <div class="modal-content" style="max-width: 650px;">
            <div class="modal-header">
                <h2><i class="fas fa-map-marker-alt" style="color: var(--primary-color); margin-right: 0.5rem;"></i> <span id="pointRangesTaskName">Point Ranges</span></h2>
                <button class="close-button" onclick="closePointRangesModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div id="pointRangesQcLink" style="margin-bottom: 1rem;"></div>
                <div id="pointRangesModalBody">
                    <!-- Content will be inserted here -->
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

    <script src="../../Models/js/survey_projects/point-ranges.js"></script>

    <script>
// Logged-in user (from PHP session)
const CURRENT_USER = <?php echo json_encode($currentUsername); ?>;

// Initialize all variables at the top
let allProjects = [];
let filteredProjects = [];
let currentPage = 1;
let itemsPerPage = 10;
let currentEditingProject = null;

// Priority levels available for My To-Do items, highest first, with the
// colors used for the badge/select shown on each todo row.
const TODO_PRIORITY_LEVELS = ['Urgent', 'High', 'Medium', 'Low'];
const TODO_PRIORITY_RANK = { Urgent: 4, High: 3, Medium: 2, Low: 1 };
const TODO_PRIORITY_COLORS = {
    Urgent: { bg: '#fee2e2', color: '#b91c1c', border: '#fca5a5' },
    High:   { bg: '#ffedd5', color: '#c2410c', border: '#fdba74' },
    Medium: { bg: '#fef9c3', color: '#a16207', border: '#fde68a' },
    Low:    { bg: '#e0f2fe', color: '#0369a1', border: '#7dd3fc' },
};

// Which sidebar tab is active: 'all' (Dashboard) or 'todo' (My To-Do)
let currentProjectsView = 'all';

// Tasks for every project, keyed by project_id. Warmed by one batched
// request instead of firing a separate load_tasks.php call per rendered
// project row (that fan-out is what made the task lists slow to appear).
let taskCache = {};
let taskCachePromise = null;

// DOM element references (will be set after DOM loads)
let projectIdInput, projectFolderLinkInput, projectSurveyFolderLinkInput,
    projectDrawingFolderLinkInput, projectContractLinkInput, projectQAQCLinkInput,
    projectResearchLinkInput, projectNameInput;

// Task type -> naming acronym used to build the task name / drawing convention.
// Edit this map to change the acronym convention; task name/folder fields can
// always be overridden by hand for special-circumstance tasks.
const TASK_TYPE_ACRONYMS = {
    'Easement':             'EX',
    'ALTA':                 'AS',
    'Plat':                 'PL',
    'Construction Staking': 'CS',
    'Boundary Survey':      'BD',
    'Topographic Survey':   'T',
    'As-Built Survey':      'AB',
    'Other':                'OT',
};

let taskNameInput, taskTypeInput, taskLinkInput, taskProjectIdInput;

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
    taskNameInput = document.getElementById('taskName');
    taskTypeInput = document.getElementById('taskType');
    taskLinkInput = document.getElementById('taskLink');
    taskProjectIdInput = document.getElementById('taskProjectId');

    // Set up auto-fill functionality
    setupAutoFill();
    setupTaskAutoFill();

    // Load projects and warm the task cache in parallel
    warmTaskCache();
    loadProjects();

    // Restore any active timer session
    checkActiveTimer();

    // Keep the session alive while this page stays open
    startSessionHeartbeat();
});
    </script>

    <script src="../../Models/js/survey_projects/project-data.js"></script>

    <script>
// Round hours to the nearest 0.5 increment using threshold rules:
//   fraction ≤ 0.01  → keep whole hours (e.g. 1.005 → 1.0)
//   fraction > 0.01  → round up to next half  (e.g. 1.03 → 1.5)
//   fraction > 0.51  → round up to next whole (e.g. 1.52 → 2.0)
function roundToHalf(value) {
    if (!value || value <= 0) return 0;
    const whole    = Math.floor(value);
    const fraction = value - whole;
    if (fraction > 0.51) return whole + 1;
    if (fraction > 0.01) return whole + 0.5;
    return whole;
}

// Helper function to format task type for CSS class
function formatTaskTypeClass(taskType) {
    return taskType.toLowerCase().replace(/\s+/g, '-');
}

// Returns a FontAwesome icon class for each task type
function getTaskTypeIcon(taskType) {
    const icons = {
        'Easement':             'fa-file-contract',
        'ALTA':                 'fa-drafting-compass',
        'Plat':                 'fa-map',
        'Construction Staking': 'fa-hard-hat',
        'Boundary Survey':      'fa-draw-polygon',
        'Topographic Survey':   'fa-mountain',
        'As-Built Survey':      'fa-ruler-combined',
        'Other':                'fa-ellipsis-h',
    };
    return icons[taskType] || 'fa-tasks';
}

// Helper function to format task status for CSS class
function formatTaskStatusClass(status) {
    return status.toLowerCase().replace(/\s+/g, '-');
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

        const matchesView = currentProjectsView !== 'todo' || project.isTodo;

        return matchesSearch && matchesStatus && matchesView;
    });

    if (currentProjectsView === 'todo') {
        sortTodoProjects(filteredProjects);
    }

    currentPage = 1;
    updateProjectsDisplay();
}

// Sorts the (already filtered) My To-Do project list in place according to
// the #todoSortSelect control. Priority order defaults to highest first so
// the most urgent projects surface at the top of the dashboard.
function sortTodoProjects(list) {
    const sortSelect = document.getElementById('todoSortSelect');
    const mode = sortSelect ? sortSelect.value : 'priority-desc';

    list.sort((a, b) => {
        switch (mode) {
            case 'priority-asc':
                return (TODO_PRIORITY_RANK[a.todoPriority] || 0) - (TODO_PRIORITY_RANK[b.todoPriority] || 0);
            case 'name':
                return a.projectName.localeCompare(b.projectName);
            case 'recent':
                return new Date(b.todoAddedAt || 0) - new Date(a.todoAddedAt || 0);
            case 'priority-desc':
            default:
                return (TODO_PRIORITY_RANK[b.todoPriority] || 0) - (TODO_PRIORITY_RANK[a.todoPriority] || 0);
        }
    });

    return list;
}

// Switches between the full Dashboard view and the My To-Do view (projects
// the user has starred). Both views share the same table/search/pagination -
// only the underlying project set and some header text change.
function switchProjectsView(view) {
    currentProjectsView = view;

    document.getElementById('navDashboard').classList.toggle('active', view === 'all');
    document.getElementById('navMyTodo').classList.toggle('active', view === 'todo');

    const pageTitle = document.getElementById('pageTitle');
    const pageSubtitle = document.getElementById('pageSubtitle');
    const todoSortSelect = document.getElementById('todoSortSelect');
    if (view === 'todo') {
        pageTitle.textContent = 'My To-Do';
        pageSubtitle.textContent = 'Projects you\'ve added to your personal to-do list';
        todoSortSelect.style.display = '';
    } else {
        pageTitle.textContent = 'Survey Projects';
        pageSubtitle.textContent = 'Manage and organize your surveying projects';
        todoSortSelect.style.display = 'none';
    }

    searchProjects();
}

// Adds or removes a project from the current user's My To-Do list and
// persists the change server-side, then refreshes whichever view is active.
function toggleProjectTodo(projectId) {
    const project = allProjects.find(p => p.projectId === projectId);
    if (!project) return;

    const addingTodo = !project.isTodo;
    const action = addingTodo ? 'add_todo' : 'remove_todo';

    const formData = new FormData();
    formData.append('action', action);
    formData.append('projectId', projectId);

    fetch('../../Models/php/load_survey_project_notes.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            throw new Error(data.message || 'Unable to update My To-Do list');
        }
        project.isTodo = addingTodo;
        showToast(addingTodo ? 'Added to My To-Do' : 'Removed from My To-Do', 'success');
        searchProjects();
    })
    .catch(error => {
        console.error('Error updating My To-Do list:', error);
        showToast('Failed to update My To-Do list', 'error');
    });
}

// Updates the priority of a project already on the current user's My To-Do
// list and re-sorts the visible list so the change is reflected immediately.
function updateTodoPriority(projectId, priority) {
    const project = allProjects.find(p => p.projectId === projectId);
    if (!project) return;

    const previousPriority = project.todoPriority;
    project.todoPriority = priority; // optimistic update

    const formData = new FormData();
    formData.append('action', 'update_todo_priority');
    formData.append('projectId', projectId);
    formData.append('priority', priority);

    fetch('../../Models/php/load_survey_project_notes.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            throw new Error(data.message || 'Unable to update priority');
        }
        showToast(`Priority set to ${priority}`, 'success');
        if (currentProjectsView === 'todo') {
            searchProjects();
        }
    })
    .catch(error => {
        console.error('Error updating todo priority:', error);
        project.todoPriority = previousPriority; // roll back
        showToast('Failed to update priority', 'error');
        if (currentProjectsView === 'todo') {
            searchProjects();
        }
    });
}

// Renders the priority badge/select shown on a My To-Do row so priority can
// be changed inline without opening the project.
function renderTodoPriorityControl(project) {
    const priority = TODO_PRIORITY_LEVELS.includes(project.todoPriority) ? project.todoPriority : 'Medium';
    const colors = TODO_PRIORITY_COLORS[priority];
    const options = TODO_PRIORITY_LEVELS.map(level =>
        `<option value="${level}" ${level === priority ? 'selected' : ''}>${level}</option>`
    ).join('');

    return `
        <select class="todo-priority-select" data-project-id="${project.projectId}"
            style="font-size:0.72rem;font-weight:700;padding:0.15rem 1.4rem 0.15rem 0.5rem;border-radius:5px;border:1px solid ${colors.border};background-color:${colors.bg};color:${colors.color};cursor:pointer;-webkit-appearance:none;appearance:none;"
            title="Set priority"
            onclick="event.stopPropagation();"
            onchange="event.stopPropagation(); updateTodoPriority('${project.projectId}', this.value);">
            ${options}
        </select>
    `;
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
        const emptyIcon = document.getElementById('emptyStateIcon');
        const emptyTitle = document.getElementById('emptyStateTitle');
        const emptyText = document.getElementById('emptyStateText');
        const emptyAction = document.getElementById('emptyStateAction');
        if (currentProjectsView === 'todo') {
            emptyIcon.className = 'fas fa-star';
            emptyTitle.textContent = 'Your To-Do List Is Empty';
            emptyText.textContent = 'Click the star next to a project to add it to My To-Do';
            emptyAction.style.display = 'none';
        } else {
            emptyIcon.className = 'fas fa-folder-open';
            emptyTitle.textContent = 'No Projects Found';
            emptyText.textContent = 'Create your first survey project to get started';
            emptyAction.style.display = '';
        }
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
    
    // Format date (strip any time component before parsing to avoid invalid date strings)
    const createdDate = new Date(project.createdDate.split(' ')[0] + 'T00:00:00').toLocaleDateString('en-US', {
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
                    <div style="display:flex;align-items:center;gap:0.4rem;">
                        <button type="button" class="todo-star-btn ${project.isTodo ? 'active' : ''}" id="todo-star-${project.projectId}" onclick="event.stopPropagation(); toggleProjectTodo('${project.projectId}');" title="${project.isTodo ? 'Remove from My To-Do' : 'Add to My To-Do'}">
                            <i class="fa-star ${project.isTodo ? 'fas' : 'far'}"></i>
                        </button>
                        <div class="project-name">${project.projectName}</div>
                        ${currentProjectsView === 'todo' ? renderTodoPriorityControl(project) : ''}
                        ${project.needs_monuments ? `<span title="Needs monument setting" style="font-size:0.7rem;background:#fef3c7;color:#92400e;border:1px solid #fcd34d;border-radius:4px;padding:0.1rem 0.4rem;font-weight:600;white-space:nowrap;"><i class="fas fa-map-pin"></i> Monuments</span>` : ''}
                    </div>
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
                        <span class="detail-label">Plus Code</span>
                        <span class="detail-value">
                            ${project.plus_code
                                ? `<span style="font-family:monospace;font-size:0.85rem;">${project.plus_code}</span>
                                   <a href="https://www.google.com/maps?q=${encodeURIComponent(project.plus_code)}"
                                      target="_blank"
                                      class="plus-code-map-btn"
                                      title="Open in Google Maps"
                                      onclick="event.stopPropagation()">
                                       <i class="fas fa-map-marker-alt"></i> Maps
                                   </a>`
                                : '<span style="color:var(--gray-400);">Not set</span>'}
                        </span>
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
                    <button class="btn btn-sm btn-secondary" id="todo-action-btn-${project.projectId}" onclick="event.stopPropagation(); toggleProjectTodo('${project.projectId}');">
                        <i class="fa-star ${project.isTodo ? 'fas' : 'far'}"></i> ${project.isTodo ? 'Remove from My To-Do' : 'Add to My To-Do'}
                    </button>
                    ${project.isTodo ? renderTodoPriorityControl(project) : ''}
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

    // Deep link to the Field Data QA/QC page filtered to this project
    const qcLink = `
        <a href="./field_data_qc.php?project_id=${encodeURIComponent(project.projectId)}" class="link-button" style="border-color: #7c3aed;">
            <i class="fas fa-clipboard-list" style="color: #7c3aed;"></i>
            Field Data QC
        </a>
    `;

    return dbLinks + autoLinks + qcLink;
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
        if (!element) return;
        if (element.type === 'checkbox') {
            element.checked = !!parseInt(project[key]);
        } else {
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
        'fieldFolderLink', 'notes', 'modifiedBy', 'location', 'plus_code', 'scale_factor'
    ];
    // Checkbox fields (value is 0 or 1, not read via .value)
    formData.append('needs_monuments', document.getElementById('needs_monuments').checked ? '1' : '0');

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

// Copy project ID to clipboard
function copyProjectId(projectId) {
    navigator.clipboard.writeText(projectId).then(() => {
        showToast(`Project ID "${projectId}" copied to clipboard!`);
    }).catch(() => {
        showToast('Failed to copy Project ID', 'error');
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

// Edit task notes inline on double-click
function editTaskNotes(element) {
    event.stopPropagation();

    // Prevent multiple editors
    if (element.querySelector('textarea')) return;

    const taskId = element.dataset.taskId;
    const projectId = element.dataset.projectId;
    const contentSpan = element.querySelector('.task-notes-content');
    const currentNotes = contentSpan.textContent.trim();
    const isPlaceholder = contentSpan.querySelector('em') !== null;

    // Store original content
    const originalContent = isPlaceholder ? '' : currentNotes;

    // Replace with textarea
    element.innerHTML = `
        <i class="fas fa-sticky-note"></i>
        <div style="flex: 1; display: flex; flex-direction: column; gap: 0.5rem;">
            <textarea class="task-notes-editor" rows="3" style="width: 100%; padding: 0.5rem; border: 1px solid var(--primary-color); border-radius: 4px; font-size: 0.875rem; resize: vertical;">${originalContent}</textarea>
            <div style="display: flex; gap: 0.5rem; justify-content: flex-end;">
                <button class="btn btn-xs btn-secondary" onclick="event.stopPropagation(); cancelNotesEdit(this, '${originalContent.replace(/'/g, "\\'")}', ${taskId}, '${projectId}')">Cancel</button>
                <button class="btn btn-xs btn-primary" onclick="event.stopPropagation(); saveTaskNotes(${taskId}, '${projectId}')">Save</button>
            </div>
        </div>
    `;

    // Focus the textarea
    const textarea = element.querySelector('textarea');
    textarea.focus();
    textarea.setSelectionRange(textarea.value.length, textarea.value.length);
}

// Cancel notes editing
function cancelNotesEdit(button, originalContent, taskId, projectId) {
    const notesDiv = button.closest('.task-notes');
    const displayContent = originalContent ? originalContent.replace(/\n/g, '<br>') : '<em style="color: var(--gray-400);">Double-click to add notes...</em>';
    notesDiv.innerHTML = `
        <i class="fas fa-sticky-note"></i>
        <span class="task-notes-content">${displayContent}</span>
    `;
}

// Save task notes to database
function saveTaskNotes(taskId, projectId) {
    const notesDiv = document.querySelector(`.task-notes[data-task-id="${taskId}"]`);
    const textarea = notesDiv.querySelector('textarea');
    const newNotes = textarea.value.trim();

    // Show saving state
    const saveBtn = notesDiv.querySelector('.btn-primary');
    saveBtn.textContent = 'Saving...';
    saveBtn.disabled = true;

    const formData = new FormData();
    formData.append('action', 'update_task_notes');
    formData.append('taskId', taskId);
    formData.append('notes', newNotes);

    fetch('../../Models/php/save_task.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update display with line breaks preserved
            const displayContent = newNotes ? newNotes.replace(/\n/g, '<br>') : '<em style="color: var(--gray-400);">Double-click to add notes...</em>';
            notesDiv.innerHTML = `
                <i class="fas fa-sticky-note"></i>
                <span class="task-notes-content">${displayContent}</span>
            `;
            // Keep the cached task list in sync so a later re-render doesn't show stale notes
            const cachedTask = (taskCache[projectId] || []).find(t => t.task_id == taskId);
            if (cachedTask) cachedTask.notes = newNotes;
            showToast('Notes saved successfully!', 'success');
        } else {
            showToast(data.message || 'Failed to save notes', 'error');
            saveBtn.textContent = 'Save';
            saveBtn.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error saving notes:', error);
        showToast('Failed to save notes', 'error');
        saveBtn.textContent = 'Save';
        saveBtn.disabled = false;
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

// ── Google Plus Code (Open Location Code) decoding ─────────────────────────
// Pure client-side implementation of the OLC "full code" decode algorithm
// (https://github.com/google/open-location-code) - no API key required.
const OLC_ALPHABET = '23456789CFGHJMPQRVWX';
const OLC_SEPARATOR = '+';
const OLC_SEPARATOR_POSITION = 8;
const OLC_PAIR_RESOLUTIONS = [20.0, 1.0, 0.05, 0.0025, 0.000125];
const OLC_GRID_ROWS = 5;
const OLC_GRID_COLUMNS = 4;

// A "full" plus code (e.g. 87G8Q2PV+W3) can be decoded on its own. A "short"
// code (e.g. 2PVH+W3) is missing its leading digits and can only be resolved
// relative to a nearby reference location, so it isn't handled here.
function isFullPlusCode(code) {
    if (!code || code.indexOf(OLC_SEPARATOR) !== OLC_SEPARATOR_POSITION) return false;
    return code.replace(OLC_SEPARATOR, '').indexOf('0') === -1;
}

function decodePlusCode(code) {
    const clean = code.toUpperCase().replace(OLC_SEPARATOR, '');
    let latVal = 0, lngVal = 0;
    for (let i = 0; i * 2 < Math.min(clean.length, 10); i++) {
        latVal += OLC_ALPHABET.indexOf(clean[i * 2]) * OLC_PAIR_RESOLUTIONS[i];
    }
    for (let i = 0; i * 2 + 1 < Math.min(clean.length, 10); i++) {
        lngVal += OLC_ALPHABET.indexOf(clean[i * 2 + 1]) * OLC_PAIR_RESOLUTIONS[i];
    }
    let latResolution = OLC_PAIR_RESOLUTIONS[4];
    let lngResolution = OLC_PAIR_RESOLUTIONS[4];
    for (let i = 10; i < clean.length; i++) {
        const idx = OLC_ALPHABET.indexOf(clean[i]);
        if (idx < 0) break;
        const row = Math.floor(idx / OLC_GRID_COLUMNS);
        const col = idx % OLC_GRID_COLUMNS;
        latResolution /= OLC_GRID_ROWS;
        lngResolution /= OLC_GRID_COLUMNS;
        latVal += row * latResolution;
        lngVal += col * lngResolution;
    }
    return {
        lat: (latVal - 90) + latResolution / 2,
        lng: (lngVal - 180) + lngResolution / 2
    };
}

// Build a "City, County, State" string from a Nominatim address object
function formatCityCountyState(address) {
    const city = address.city || address.town || address.village || address.hamlet || '';
    const county = address.county || '';
    const state = address.state || '';
    return [city, county, state].filter(Boolean).join(', ');
}

// Fill the City/County/State field by looking up the entered Plus Code.
// Full codes are decoded locally then reverse-geocoded; short codes (which
// include a locality after the code, e.g. "2PVH+W3 Austin, Texas") skip the
// code math and just forward-geocode that locality text directly. Uses the
// free OpenStreetMap Nominatim API - no API key required.
async function lookupLocationFromPlusCode() {
    const plusCodeInput = document.getElementById('plus_code');
    const locationInput = document.getElementById('location');
    const raw = (plusCodeInput.value || '').trim();
    if (!raw) {
        showToast('Enter a Plus Code first', 'error');
        return;
    }

    const btn = document.getElementById('plusCodeLookupBtn');
    const originalHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

    try {
        const spaceIndex = raw.indexOf(' ');
        let address;

        if (spaceIndex === -1) {
            if (!isFullPlusCode(raw)) {
                throw new Error('Short Plus Codes need a locality after the code, e.g. "2PVH+W3 Austin, Texas"');
            }
            const { lat, lng } = decodePlusCode(raw);
            const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&addressdetails=1&lat=${lat}&lon=${lng}`);
            const data = await response.json();
            if (!data || !data.address) throw new Error('Could not resolve that Plus Code to a location');
            address = data.address;
        } else {
            const locality = raw.substring(spaceIndex + 1).trim();
            const response = await fetch(`https://nominatim.openstreetmap.org/search?format=jsonv2&limit=1&addressdetails=1&q=${encodeURIComponent(locality)}`);
            const results = await response.json();
            if (!results.length) throw new Error(`Could not find location "${locality}"`);
            address = results[0].address;
        }

        const formatted = formatCityCountyState(address);
        if (!formatted) throw new Error('Could not determine city/county/state for that Plus Code');
        locationInput.value = formatted;
        showToast('City/County/State filled from Plus Code', 'success');
    } catch (err) {
        console.error('Plus Code lookup failed:', err);
        showToast(err.message || 'Failed to look up location from Plus Code', 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalHtml;
    }
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
            projectContractLinkInput.value = `N:\\\\${projectId}\\\\01 Administration\\\\Contracts`;
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

// Look up the naming acronym for a task type (see TASK_TYPE_ACRONYMS above)
function getTaskTypeAcronym(taskType) {
    if (!taskType) return '';
    return TASK_TYPE_ACRONYMS[taskType] || taskType.replace(/[^A-Za-z0-9]/g, '').slice(0, 2).toUpperCase();
}

// Auto-fill the task name ([project_id][task_type acronym]) and the task
// folder link ([drawing_folder_link]\[task_name].dwg) based on the naming
// convention. Fields stay editable - once a user focuses a field it's
// treated as manually set and is no longer overwritten by the convention.
function setupTaskAutoFill() {
    function updateTaskDependentFields() {
        const projectId = (taskProjectIdInput.value || '').trim();
        const taskType = taskTypeInput.value;
        if (!projectId || !taskType) return;

        const acronym = getTaskTypeAcronym(taskType);
        const generatedTaskName = `${projectId}${acronym}`;

        if (!taskNameInput.value.trim() || taskNameInput.classList.contains('auto-filled')) {
            taskNameInput.value = generatedTaskName;
            taskNameInput.classList.add('auto-filled');
        }

        const project = allProjects.find(p => p.projectId === projectId);
        const drawingFolderLink = project ? project.drawingFolderLink : '';
        if (drawingFolderLink && (!taskLinkInput.value.trim() || taskLinkInput.classList.contains('auto-filled'))) {
            const taskName = taskNameInput.value.trim() || generatedTaskName;
            const separator = /[\\\/]$/.test(drawingFolderLink) ? '' : '\\';
            taskLinkInput.value = `${drawingFolderLink}${separator}${taskName}.dwg`;
            taskLinkInput.classList.add('auto-filled');
        }
    }

    taskTypeInput.addEventListener('change', updateTaskDependentFields);

    // Allow manual editing of auto-filled fields
    [taskNameInput, taskLinkInput].forEach(input => {
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
    // Reset auto-fill state so the naming convention kicks in fresh once a task type is picked
    taskNameInput.classList.remove('auto-filled');
    taskLinkInput.classList.remove('auto-filled');
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
        document.getElementById('coordinateType').value = task.coordinate_type || '';
        document.getElementById('taskStatus').value = task.task_status || 'Not Started';
        document.getElementById('taskPriority').value = task.task_priority || 'Medium';
        document.getElementById('phaseNumber').value = task.phase_number || '';
        document.getElementById('assignedTo').value = task.assigned_to || '';
        document.getElementById('startDate').value = task.start_date || '';
        document.getElementById('dueDate').value = task.due_date || '';
        document.getElementById('completionDate').value = task.completion_date || '';
        document.getElementById('estimatedHours').value = task.estimated_hours ? roundToHalf(parseFloat(task.estimated_hours)) : '';
        document.getElementById('actualHours').value = task.actual_hours ? roundToHalf(parseFloat(task.actual_hours)) : '';
        document.getElementById('taskLink').value = task.task_link || '';
        document.getElementById('taskNotes').value = task.notes || '';
        // Existing values are real data, not the auto-fill convention - don't overwrite them
        taskNameInput.classList.remove('auto-filled');
        taskLinkInput.classList.remove('auto-filled');

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
            refreshTasksForProject(projectId).then(tasks => {
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
            refreshTasksForProject(projectId).then(tasks => {
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

// Create tasks HTML with edit/delete buttons, clickable status, and timer
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
        const dueDate = task.due_date ? new Date(task.due_date + 'T00:00:00').toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : 'No due date';
        const uncPath = "westwoodps.local\\\\Global Projects";
        const safeTaskName = (task.task_name || '').replace(/'/g, "\\'");

        // Look up project name from allProjects
        const proj = allProjects.find(p => p.projectId === task.project_id);
        const projectName = (proj ? proj.projectName : task.project_id).replace(/'/g, "\\'");

        // Timer button state
        const isActive = timerState.isRunning && timerState.taskId === task.task_id;
        const timerBtnClass = isActive ? 'timer-play-btn timer-active' : 'timer-play-btn';
        const timerBtnTitle = isActive ? 'Stop timer' : 'Start timer';
        const timerBtnIcon = isActive ? 'fa-stop' : 'fa-play';
        const timerElapsed = isActive ? `<span class="timer-btn-elapsed" id="timer-btn-elapsed-${task.task_id}"></span>` : '';

        // Hours tracker
        const actual = parseFloat(task.actual_hours) || 0;
        const estimated = parseFloat(task.estimated_hours) || 0;
        let hoursClass = '';
        let timeLeftHtml = '';
        if (estimated > 0) {
            const ratio = actual / estimated;
            hoursClass = ratio >= 1 ? 'hours-over-budget' : (ratio >= 0.8 ? 'hours-near-budget' : 'hours-on-track');
            const left = estimated - actual;
            if (left > 0) {
                timeLeftHtml = `<span class="hours-time-left hours-on-track" title="Time remaining"><i class="fas fa-arrow-right"></i>${left.toFixed(1)}h left</span>`;
            } else if (left < 0) {
                timeLeftHtml = `<span class="hours-time-left hours-over-budget" title="Over budget"><i class="fas fa-exclamation-triangle"></i>${Math.abs(left).toFixed(1)}h over</span>`;
            } else {
                timeLeftHtml = `<span class="hours-time-left hours-near-budget" title="On budget"><i class="fas fa-check"></i>0h left</span>`;
            }
        }
        const hoursDisplay = `
            <span class="hours-tracker ${hoursClass}" title="Actual vs Estimated hours">
                <i class="fas fa-hourglass-half"></i>
                <span id="actual-hours-${task.task_id}">${actual.toFixed(1)}h</span>
                /
                <span>${estimated > 0 ? estimated + 'h' : '?h'}</span>
            </span>${timeLeftHtml}`;

        return `
            <div class="task-item" data-task-type="${task.task_type}">
                <div class="task-info">
                    <div class="task-header">
                        <span class="task-type-badge ${taskTypeClass}"><i class="fas ${getTaskTypeIcon(task.task_type)}"></i>${task.task_type}</span>
                        ${task.coordinate_type ? `<span class="coordinate-type-badge coordinate-type-${task.coordinate_type.toLowerCase()}">${task.coordinate_type}</span>` : ''}
                        <span class="task-name">${task.task_name}</span>
                    </div>
                    <div class="task-meta">
                        ${task.phase_number ? `<span><i class="fas fa-layer-group"></i> Phase ${task.phase_number}</span>` : ''}
                        <span><i class="fas fa-calendar"></i> ${dueDate}</span>
                        ${task.assigned_to ? `<span><i class="fas fa-user"></i> ${task.assigned_to}</span>` : ''}
                        ${renderPointRangesIcon(task)}
                        ${renderChecklistButton(task)}
                    </div>
                    <div class="task-folder-links">
                        ${generateTaskFolderLinks(task.project_id, task)}
                        ${task.task_link ? `
                            <span class="task-folder-link" style="border-color: #6366f1; cursor: default;" title="${task.task_link}">
                                <i class="fas fa-folder" style="color: #6366f1;"></i>
                                <span style="font-size: 0.75rem; color: var(--gray-600);">${task.task_link.split('\\').pop()}</span>
                            </span>
                            <button class="btn btn-xs btn-secondary" onclick="event.stopPropagation(); copyTaskPath('${task.task_link.replace(/\\/g, '\\\\')}', '${safeTaskName}');" title="Copy Task Folder Path">
                                <i class="fas fa-copy"></i>
                            </button>
                        ` : ''}
                    </div>
                    <div class="task-notes" data-task-id="${task.task_id}" data-project-id="${task.project_id}" ondblclick="editTaskNotes(this)" title="Double-click to edit">
                        <i class="fas fa-sticky-note"></i>
                        <span class="task-notes-content">${task.notes ? task.notes.replace(/\n/g, '<br>') : '<em style="color: var(--gray-400);">Double-click to add notes...</em>'}</span>
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; justify-content: flex-end;">
                    ${hoursDisplay}
                    <button class="btn btn-xs ${timerBtnClass}"
                            id="timer-btn-${task.task_id}"
                            onclick="event.stopPropagation(); handleTimerClick(${task.task_id}, '${task.project_id}', '${safeTaskName}', '${projectName}')"
                            title="${timerBtnTitle}">
                        <i class="fas ${timerBtnIcon}"></i>${timerElapsed}
                    </button>
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
            refreshTasksForProject(projectId).then(tasks => {
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

    <script src="../../Models/js/survey_projects/timer.js"></script>

    <script src="../../Models/js/survey_projects/timesheet.js"></script>


    <!-- Stop Timer Modal -->
    <div id="stopTimerModal" class="modal">
        <div class="modal-content" style="max-width:420px;">
            <div class="modal-header">
                <h2><i class="fas fa-stop-circle" style="color:var(--danger-color);margin-right:0.5rem;"></i> Stop Timer</h2>
                <button class="close-button" onclick="cancelStopTimer()"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <div id="recentNotesRow" style="display:none;margin-bottom:0.85rem;">
                    <label style="font-size:0.8rem;font-weight:600;color:var(--gray-500);display:block;margin-bottom:0.3rem;">Recent notes</label>
                    <select id="recentNotesSelect" onchange="applyRecentNote()" style="width:100%;padding:0.5rem 0.75rem;border:1px solid #e2e8f0;border-radius:6px;font-size:0.875rem;color:#374151;background:#fff;box-sizing:border-box;">
                        <option value="">— Select a recent note —</option>
                    </select>
                </div>
                <label style="font-size:0.8rem;font-weight:600;color:var(--gray-500);display:block;margin-bottom:0.3rem;">Note</label>
                <textarea id="stopTimerNotes" rows="3" style="width:100%;padding:0.6rem 0.75rem;border:1px solid #e2e8f0;border-radius:6px;font-size:0.875rem;resize:vertical;font-family:inherit;box-sizing:border-box;" placeholder="e.g. Completed boundary research, reviewed deeds..."></textarea>
            </div>
            <div class="modal-footer" style="display:flex;gap:0.75rem;justify-content:flex-end;padding:1rem 1.5rem;border-top:1px solid #f1f5f9;">
                <button class="btn btn-secondary" onclick="cancelStopTimer()">Cancel</button>
                <button class="btn btn-primary" onclick="confirmStopTimer()" style="background:var(--danger-color);border-color:var(--danger-color);">
                    <i class="fas fa-stop"></i> Stop &amp; Log
                </button>
            </div>
        </div>
    </div>

    <!-- Timesheet Modal -->
    <div id="timesheetModal" class="modal">
        <div class="modal-content timesheet-modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-clock" style="color:var(--primary-color);margin-right:0.5rem;"></i> Weekly Timesheet</h2>
                <button class="close-button" onclick="closeTimesheetModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="timesheet-week-nav">
                    <button onclick="navigateWeek(-1)"><i class="fas fa-chevron-left"></i> Prev</button>
                    <span id="timesheetWeekLabel">Week of ...</span>
                    <button onclick="navigateWeek(1)">Next <i class="fas fa-chevron-right"></i></button>
                </div>
                <div id="timesheetContent">
                    <!-- rendered table injected by JS -->
                </div>
            </div>
        </div>
    </div>

    <!-- Checklist Side Panel -->
    <div class="checklist-panel" id="checklistModal">
        <div class="checklist-panel-content">
            <div class="checklist-modal-header">
                <div class="checklist-modal-header-top">
                    <h3><i class="fas fa-clipboard-check"></i> <span id="checklistModalTitle">Checklist</span></h3>
                    <div style="display:flex; align-items:center; gap:0.5rem;">
                        <button id="changeChecklistBtn"
                                class="checklist-change-btn"
                                style="display:none"
                                onclick="showTemplatePicker(currentChecklistTaskId, currentChecklistTaskName, null)">
                            <i class="fas fa-exchange-alt"></i> Change
                        </button>
                        <button class="checklist-modal-close" onclick="closeChecklistModal()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                <span class="progress-text" id="checklistProgressText">0 / 0 completed</span>
                <div class="checklist-progress-bar">
                    <div class="checklist-progress-bar-fill" id="checklistProgressFill" style="width: 0%"></div>
                </div>
            </div>
            <div class="checklist-modal-body" id="checklistModalBody">
                <div style="text-align: center; padding: 2rem; color: var(--gray-400);">
                    <i class="fas fa-spinner fa-spin" style="font-size: 2rem;"></i>
                    <p>Loading checklist...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Template Picker Modal -->
    <div class="checklist-modal" id="templatePickerModal">
        <div class="checklist-modal-content" style="max-width: 500px;">
            <div class="checklist-modal-header">
                <div class="checklist-modal-header-top">
                    <h3><i class="fas fa-clipboard-list"></i> Select Checklist</h3>
                    <button class="checklist-modal-close" onclick="closeTemplatePicker()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            <div class="template-picker" id="templatePickerBody">
            </div>
        </div>
    </div>

    <script src="../../Models/js/survey_projects/checklist.js"></script>

    <script>
    // Keep content-header sticky offset in sync with top-bar height
    function syncTopBarHeight() {
        const h = document.querySelector('.top-bar')?.offsetHeight || 73;
        document.documentElement.style.setProperty('--top-bar-height', h + 'px');
    }
    syncTopBarHeight();
    window.addEventListener('resize', syncTopBarHeight);

    // Keep sidebar/top-bar offset in sync with the shared header tabs height.
    // The header scrolls with the page (not sticky here), so the offset tracks
    // only its still-visible portion — once it scrolls off, the sidebar and
    // sticky top-bar sit at the very top.
    function syncHeaderHeight() {
        const total = document.querySelector('.header-tabs-container')?.offsetHeight || 0;
        const visible = Math.max(0, total - window.scrollY);
        document.documentElement.style.setProperty('--header-height', visible + 'px');
    }
    document.addEventListener('headerLoaded', syncHeaderHeight);
    window.addEventListener('resize', syncHeaderHeight);
    window.addEventListener('scroll', syncHeaderHeight, { passive: true });

    // Close timesheet modal on outside click
    document.addEventListener('click', function(e) {
        if (e.target.id === 'timesheetModal') {
            closeTimesheetModal();
        }
    });
    </script>

    <!-- Shared header tabs (home, about, services/survey projects, articles, contact, dashboard) -->
    <script src="../../Models/js/header_loader.js"></script>
    <script src="../../Models/js/header_tabs.js"></script>
</body>
</html>
