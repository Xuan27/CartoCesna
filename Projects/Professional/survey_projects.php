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

    <script src="../../Models/js/survey_projects/task-render.js"></script>

    <script src="../../Models/js/survey_projects/project-list.js"></script>

    <script src="../../Models/js/survey_projects/project-crud.js"></script>

    <script src="../../Models/js/survey_projects/task-crud.js"></script>

    <script>
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
