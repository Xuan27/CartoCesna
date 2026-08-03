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
                    <h2>Survey Projects</h2>
                    <p>Manage and organize your surveying projects</p>
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

    <script>

// Point Ranges Functions
function parsePointRanges(value) {
    if (!value) return { entries: [] };
    if (typeof value === 'object' && value !== null) {
        // Already an object, ensure it has entries array
        return value.entries ? value : { entries: [] };
    }
    try {
        const parsed = JSON.parse(value);
        // Ensure it has entries array
        return parsed && parsed.entries ? parsed : { entries: [] };
    } catch (e) {
        console.log('Error parsing point_ranges:', e, 'Value:', value);
        return { entries: [] };
    }
}

function renderPointRangesIcon(task) {
    const pointRanges = parsePointRanges(task.point_ranges);
    const entryCount = pointRanges.entries ? pointRanges.entries.length : 0;
    const safeTaskName = btoa(encodeURIComponent(task.task_name || 'Task'));
    const safeData = entryCount > 0 ? btoa(encodeURIComponent(JSON.stringify(pointRanges))) : '';
    const projectId = task.project_id || '';
    const taskId = task.task_id || '';

    if (entryCount > 0) {
        return `
            <button class="btn btn-xs" style="background: var(--primary-color); color: white;" data-task-name="${safeTaskName}" data-point-ranges="${safeData}" data-project-id="${projectId}" data-task-id="${taskId}" onclick="event.stopPropagation(); handlePointRangesClick(this);" title="View point ranges (${entryCount} job file${entryCount > 1 ? 's' : ''})">
                <i class="fas fa-map-marker-alt"></i>
                <span style="margin-left: 0.25rem;">${entryCount}</span>
            </button>
        `;
    } else {
        return `
            <button class="btn btn-xs btn-secondary" data-task-name="${safeTaskName}" data-point-ranges="" data-project-id="${projectId}" data-task-id="${taskId}" onclick="event.stopPropagation(); handlePointRangesClick(this);" title="No point ranges data - click to view">
                <i class="fas fa-map-marker-alt"></i>
            </button>
        `;
    }
}

function handlePointRangesClick(button) {
    try {
        const taskName = decodeURIComponent(atob(button.dataset.taskName));
        const pointRangesData = button.dataset.pointRanges ? decodeURIComponent(atob(button.dataset.pointRanges)) : null;
        showPointRangesModal(taskName, pointRangesData, button.dataset.projectId || '', button.dataset.taskId || '');
    } catch (e) {
        console.error('Error handling point ranges click:', e);
        showPointRangesModal('Task', null, '', '');
    }
}

function showPointRangesModal(taskName, jsonData, projectId, taskId) {
    const modal = document.getElementById('pointRangesModal');
    const taskNameEl = document.getElementById('pointRangesTaskName');
    const bodyEl = document.getElementById('pointRangesModalBody');
    const qcLinkEl = document.getElementById('pointRangesQcLink');

    taskNameEl.textContent = taskName;

    if (qcLinkEl) {
        if (projectId) {
            const qcUrl = `field_data_qc.php?project_id=${encodeURIComponent(projectId)}`;
            qcLinkEl.innerHTML = `
                <a href="${qcUrl}" target="_blank" rel="noopener" class="btn btn-xs btn-secondary" style="text-decoration: none;">
                    <i class="fas fa-external-link-alt"></i> View Field Data QC
                </a>
            `;
        } else {
            qcLinkEl.innerHTML = '';
        }
    }

    if (!jsonData) {
        bodyEl.innerHTML = `
            <div style="text-align: center; padding: 2rem; color: var(--gray-500);">
                <i class="fas fa-map-marker-alt" style="font-size: 2.5rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                <p style="margin: 0;">No point range data available for this task.</p>
                <p style="font-size: 0.85rem; margin-top: 0.5rem; color: var(--gray-400);">Use the Field Data QC page to add point range information.</p>
            </div>
        `;
    } else {
        let data;
        try {
            data = typeof jsonData === 'string' ? JSON.parse(jsonData) : jsonData;
        } catch (e) {
            console.error('Error parsing point ranges data:', e);
            bodyEl.innerHTML = `
                <div style="text-align: center; padding: 2rem; color: var(--danger-color);">
                    <i class="fas fa-exclamation-triangle" style="font-size: 2.5rem; margin-bottom: 1rem; opacity: 0.7;"></i>
                    <p style="margin: 0;">Error loading point range data.</p>
                </div>
            `;
            modal.style.display = 'block';
            return;
        }

        if (!data.entries || data.entries.length === 0) {
            bodyEl.innerHTML = `
                <div style="text-align: center; padding: 2rem; color: var(--gray-500);">
                    <i class="fas fa-map-marker-alt" style="font-size: 2.5rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                    <p>No point range entries found.</p>
                </div>
            `;
        } else {
            bodyEl.innerHTML = data.entries.map((entry, index) => `
                <div style="background: var(--gray-50); border: 1px solid var(--gray-200); border-radius: var(--border-radius); padding: 1.25rem; margin-bottom: 1rem;">
                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem; padding-bottom: 0.75rem; border-bottom: 1px solid var(--gray-200);">
                        <i class="fas fa-file-alt" style="color: var(--primary-color);"></i>
                        <strong style="font-size: 1rem; color: var(--gray-800);">${entry.job_file_name || 'Job File #' + (index + 1)}</strong>
                    </div>
                    <div class="form-grid" style="gap: 1rem;">
                        <div class="form-group" style="grid-column: 1 / -1; margin-bottom: 0;">
                            <label class="form-label" style="margin-bottom: 0.25rem;">Point Numbers Used</label>
                            <div style="background: white; padding: 0.5rem 0.75rem; border: 1px solid var(--gray-200); border-radius: 6px; color: var(--gray-800);">
                                ${entry.point_number_used || '<span style="color: var(--gray-400);">Not specified</span>'}
                            </div>
                        </div>
                        <div class="form-group" style="grid-column: 1 / -1; margin-bottom: 0;">
                            <label class="form-label" style="margin-bottom: 0.5rem;">Status</label>
                            <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
                                <span style="display: inline-flex; align-items: center; gap: 0.35rem; padding: 0.35rem 0.75rem; border-radius: 6px; font-size: 0.85rem; font-weight: 500; ${entry.converted === 'yes' ? 'background: rgba(22, 163, 74, 0.1); color: var(--success-color);' : 'background: var(--gray-100); color: var(--gray-500);'}">
                                    <i class="fas fa-${entry.converted === 'yes' ? 'check-circle' : 'circle'}" style="font-size: 0.75rem;"></i>
                                    Converted
                                </span>
                                <span style="display: inline-flex; align-items: center; gap: 0.35rem; padding: 0.35rem 0.75rem; border-radius: 6px; font-size: 0.85rem; font-weight: 500; ${entry.imported === 'yes' ? 'background: rgba(22, 163, 74, 0.1); color: var(--success-color);' : 'background: var(--gray-100); color: var(--gray-500);'}">
                                    <i class="fas fa-${entry.imported === 'yes' ? 'check-circle' : 'circle'}" style="font-size: 0.75rem;"></i>
                                    Imported
                                </span>
                                <span style="display: inline-flex; align-items: center; gap: 0.35rem; padding: 0.35rem 0.75rem; border-radius: 6px; font-size: 0.85rem; font-weight: 500; ${entry.checked === 'yes' ? 'background: rgba(22, 163, 74, 0.1); color: var(--success-color);' : 'background: var(--gray-100); color: var(--gray-500);'}">
                                    <i class="fas fa-${entry.checked === 'yes' ? 'check-circle' : 'circle'}" style="font-size: 0.75rem;"></i>
                                    Checked
                                </span>
                            </div>
                        </div>
                        ${entry.notes ? `
                            <div class="form-group" style="grid-column: 1 / -1; margin-bottom: 0;">
                                <label class="form-label" style="margin-bottom: 0.25rem;">Notes</label>
                                <div style="background: white; padding: 0.5rem 0.75rem; border: 1px solid var(--gray-200); border-radius: 6px; color: var(--gray-800);">
                                    ${entry.notes}
                                </div>
                            </div>
                        ` : ''}
                    </div>
                </div>
            `).join('');
        }
    }

    modal.style.display = 'block';
}

function closePointRangesModal() {
    document.getElementById('pointRangesModal').style.display = 'none';
}

// Close point ranges modal on outside click
document.getElementById('pointRangesModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closePointRangesModal();
    }
});

// Logged-in user (from PHP session)
const CURRENT_USER = <?php echo json_encode($currentUsername); ?>;

// Initialize all variables at the top
let allProjects = [];
let filteredProjects = [];
let currentPage = 1;
let itemsPerPage = 10;
let currentEditingProject = null;

// ── Timer state ──────────────────────────────────────────────────────────────
let timerState = {
    isRunning:   false,
    entryId:     null,
    taskId:      null,
    projectId:   null,
    taskName:    null,
    projectName: null,
    intervalId:  null,
    startTime:   null,
};

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

    // Load projects from server
    loadProjects();

    // Restore any active timer session
    checkActiveTimer();

    // Keep the session alive while this page stays open
    startSessionHeartbeat();
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
        if (data.success) {
            allProjects = data.projects || [];
            searchProjects(); // Update display
            showToast('Projects loaded successfully!', 'success');
            handleDeepLink();
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

// Handle deep-link from All Tasks page (?project=X&task=Y)
function handleDeepLink() {
    const params = new URLSearchParams(window.location.search);
    const projectId = params.get('project');
    if (!projectId) return;

    const project = allProjects.find(p => p.projectId === projectId);
    if (!project) return;

    // Show only this project
    filteredProjects = [project];
    currentPage = 1;
    updateProjectsDisplay();

    // Expand the row and scroll to it
    const projectRow = document.querySelector(`tr.project-row[data-project-id="${projectId}"]`);
    if (projectRow) {
        projectRow.click();
        projectRow.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
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
        const dueDate = task.due_date ? new Date(task.due_date + 'T00:00:00').toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : 'No due date';
        const uncPath = "westwoodps.local\\\\Global Projects";
        
        return `
            <div class="task-item" data-task-type="${task.task_type}">
                <div class="task-info">
                    <div class="task-header">
                        <span class="task-type-badge ${taskTypeClass}"><i class="fas ${getTaskTypeIcon(task.task_type)}"></i>${task.task_type}</span>
                        <span class="task-name">${task.task_name}</span>
                    </div>
                    <div class="task-meta">
                        ${task.phase_number ? `<span><i class="fas fa-layer-group"></i> Phase ${task.phase_number}</span>` : ''}
                        <span><i class="fas fa-calendar"></i> ${dueDate}</span>
                        ${task.assigned_to ? `<span><i class="fas fa-user"></i> ${task.assigned_to}</span>` : ''}
                        ${renderPointRangesIcon(task)}
                        ${renderChecklistButton(task)}
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
                        <div class="project-name">${project.projectName}</div>
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

// ═══════════════════════════════════════════════════════════════════════════
// TIME TRACKER — core logic
// ═══════════════════════════════════════════════════════════════════════════
const TIME_API = '../../Models/php/time_tracking_api.php';
const HEARTBEAT_API = '../../Models/php/session_heartbeat.php';
const HEARTBEAT_INTERVAL_MS = 5 * 60 * 1000; // 5 min — well under PHP's session GC window

// Periodically ping the server so the PHP session doesn't expire while this
// tab is left open (e.g. mid-survey with no clicks for 20-30+ minutes).
function startSessionHeartbeat() {
    setInterval(async () => {
        try {
            const resp = await fetch(HEARTBEAT_API, { method: 'POST' });
            const data = await resp.json();
            if (!data.loggedIn) {
                showToast('Your session has expired. Please log in again.', 'error');
            }
        } catch (err) {
            console.error('Session heartbeat error:', err);
        }
    }, HEARTBEAT_INTERVAL_MS);
}

// Parse start_time from API — handles both Unix timestamp (integer) and
// MySQL datetime string (which Hostinger returns as UTC but without 'Z').
function parseApiTime(t) {
    if (!t) return null;
    if (typeof t === 'number' || /^\d{9,}$/.test(String(t))) return Number(t) * 1000;
    return new Date(String(t).replace(' ', 'T') + 'Z').getTime();
}

// Check for an active timer on page load and restore UI
async function checkActiveTimer() {
    try {
        const fd = new FormData();
        fd.append('action', 'get_active_timer');
        const resp = await fetch(TIME_API, { method: 'POST', body: fd });
        const data = await resp.json();
        if (data.success && data.entry) {
            const e = data.entry;
            timerState.isRunning   = true;
            timerState.entryId     = e.entry_id;
            timerState.taskId      = parseInt(e.task_id);
            timerState.projectId   = e.project_id;
            timerState.taskName    = e.task_name;
            timerState.projectName = e.project_name;
            timerState.startTime   = parseApiTime(e.start_time);
            startTimerTick();
            showTimerBanner(e.task_name, e.project_name);
        }
    } catch (err) {
        console.error('checkActiveTimer error:', err);
    }
}

// Handle play/stop click on a task button
function handleTimerClick(taskId, projectId, taskName, projectName) {
    if (timerState.isRunning && timerState.taskId === taskId) {
        stopActiveTimer();
    } else {
        startTimer(taskId, projectId, taskName, projectName);
    }
}

// Start a timer for a task
async function startTimer(taskId, projectId, taskName, projectName) {
    try {
        const fd = new FormData();
        fd.append('action',       'start_timer');
        fd.append('task_id',      taskId);
        fd.append('project_id',   projectId);
        fd.append('task_name',    taskName);
        fd.append('project_name', projectName);

        const resp = await fetch(TIME_API, { method: 'POST', body: fd });
        const data = await resp.json();

        if (!data.success) {
            showToast(data.message || 'Could not start timer', 'error');
            return;
        }

        timerState.isRunning   = true;
        timerState.entryId     = data.entry_id;
        timerState.taskId      = taskId;
        timerState.projectId   = projectId;
        timerState.taskName    = taskName;
        timerState.projectName = projectName;
        timerState.startTime   = parseApiTime(data.start_time) || Date.now();

        startTimerTick();
        showTimerBanner(taskName, projectName);
        refreshTimerButtons();
        showToast(`Timer started: ${taskName}`, 'success');
    } catch (err) {
        console.error('startTimer error:', err);
        showToast('Network error starting timer', 'error');
    }
}

// Open stop-timer modal (timer keeps running until confirmed)
function stopActiveTimer() {
    openStopTimerModal();
}

async function openStopTimerModal() {
    if (!timerState.isRunning) return;
    document.getElementById('stopTimerModal').style.display = 'block';

    // Fetch recent notes and populate dropdown
    try {
        const fd = new FormData();
        fd.append('action',    'get_recent_notes');
        fd.append('task_id',   timerState.taskId   ?? 0);
        fd.append('task_name', timerState.taskName ?? '');
        const resp = await fetch(TIME_API, { method: 'POST', body: fd });
        const data = await resp.json();
        if (data.success && data.notes.length > 0) {
            const select = document.getElementById('recentNotesSelect');
            select.innerHTML = '<option value="">— Select a recent note —</option>';
            data.notes.forEach(n => {
                const opt = document.createElement('option');
                opt.value = n;
                opt.textContent = n.length > 70 ? n.slice(0, 70) + '…' : n;
                select.appendChild(opt);
            });
            document.getElementById('recentNotesRow').style.display = 'block';
        }
    } catch (err) { /* silently skip — dropdown just won't show */ }

    setTimeout(() => document.getElementById('stopTimerNotes').focus(), 80);
}

function applyRecentNote() {
    const select = document.getElementById('recentNotesSelect');
    if (select.value) {
        document.getElementById('stopTimerNotes').value = select.value;
        select.value = '';
    }
}

function cancelStopTimer() {
    document.getElementById('stopTimerModal').style.display = 'none';
    document.getElementById('stopTimerNotes').value = '';
    document.getElementById('recentNotesRow').style.display = 'none';
    document.getElementById('recentNotesSelect').innerHTML = '<option value="">— Select a recent note —</option>';
}

async function confirmStopTimer() {
    const notes = document.getElementById('stopTimerNotes').value.trim();
    document.getElementById('stopTimerModal').style.display = 'none';
    document.getElementById('stopTimerNotes').value = '';
    document.getElementById('recentNotesRow').style.display = 'none';
    document.getElementById('recentNotesSelect').innerHTML = '<option value="">— Select a recent note —</option>';
    await _doStopTimer(notes);
}

// Perform the actual stop API call with optional notes
async function _doStopTimer(notes) {
    if (!timerState.isRunning) return;
    const entryId = timerState.entryId;
    const taskId  = timerState.taskId;

    // Optimistically reset state
    clearInterval(timerState.intervalId);
    timerState.isRunning  = false;
    timerState.intervalId = null;
    hideTimerBanner();
    refreshTimerButtons();

    try {
        const fd = new FormData();
        fd.append('action',   'stop_timer');
        fd.append('entry_id', entryId);
        if (notes) fd.append('notes', notes);

        const resp = await fetch(TIME_API, { method: 'POST', body: fd });
        const data = await resp.json();

        if (data.success) {
            const dur = formatDurationShort(data.duration_seconds);
            showToast(`Timer stopped — logged ${dur}`, 'success');

            // Update actual hours display in any visible task row
            if (taskId) {
                const hoursEl = document.getElementById(`actual-hours-${taskId}`);
                if (hoursEl) {
                    hoursEl.textContent = parseFloat(data.actual_hours).toFixed(1) + 'h';
                }
            }
        } else {
            showToast(data.message || 'Could not stop timer', 'error');
        }
    } catch (err) {
        console.error('_doStopTimer error:', err);
        showToast('Network error stopping timer', 'error');
    } finally {
        timerState.entryId      = null;
        timerState.taskId       = null;
        timerState.projectId    = null;
        timerState.taskName     = null;
        timerState.projectName  = null;
        timerState.startTime    = null;
    }
}

// ── Admin / Training category timer ──────────────────────────────────────────

function toggleLogTimeDropdown() {
    const dd = document.getElementById('logTimeDropdown');
    dd.style.display = dd.style.display === 'none' ? 'block' : 'none';
}

// Close Log Time dropdown on outside click
document.addEventListener('click', e => {
    const dd  = document.getElementById('logTimeDropdown');
    const btn = document.getElementById('logTimeBtnToggle');
    if (dd && btn && !btn.contains(e.target) && !dd.contains(e.target)) {
        dd.style.display = 'none';
    }
});

async function startCategoryTimer(categoryName, categoryId) {
    document.getElementById('logTimeDropdown').style.display = 'none';
    await startTimer(0, categoryId, categoryName, '');
}

// ── Edit start time (inline in banner) ───────────────────────────────────────

function updateStartTimeDisplay() {
    const el = document.getElementById('timerStartTimeText');
    if (!el || !timerState.startTime) return;
    const d = new Date(timerState.startTime);
    el.textContent = d.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
}

function editTimerStartTime() {
    if (!timerState.startTime) return;
    const d = new Date(timerState.startTime);
    const timeStr = `${String(d.getHours()).padStart(2,'0')}:${String(d.getMinutes()).padStart(2,'0')}`;
    const display = document.getElementById('timerStartTimeDisplay');
    display.onclick = null; // disable outer click while editing
    display.innerHTML = `<input type="time" id="startTimeEditInput" value="${timeStr}"
        style="width:92px;font-size:0.78rem;padding:1px 4px;border:1px solid rgba(255,255,255,0.5);border-radius:4px;background:rgba(0,0,0,0.35);color:#fff;vertical-align:middle;">
        <button onclick="saveTimerStartTime()" style="background:none;border:none;color:#fff;cursor:pointer;padding:0 3px;font-size:0.78rem;" title="Save"><i class="fas fa-check"></i></button>
        <button onclick="cancelEditStartTime()" style="background:none;border:none;color:rgba(255,255,255,0.65);cursor:pointer;padding:0 3px;font-size:0.78rem;" title="Cancel"><i class="fas fa-times"></i></button>`;
}

function cancelEditStartTime() {
    const display = document.getElementById('timerStartTimeDisplay');
    if (!display) return;
    display.innerHTML = `<i class="fas fa-edit" style="font-size:0.68rem;margin-right:2px;"></i><span id="timerStartTimeText"></span>`;
    display.onclick = editTimerStartTime;
    updateStartTimeDisplay();
}

async function saveTimerStartTime() {
    const input = document.getElementById('startTimeEditInput');
    if (!input || !input.value) { cancelEditStartTime(); return; }

    const [hours, minutes] = input.value.split(':').map(Number);
    const newStart = new Date();
    newStart.setHours(hours, minutes, 0, 0);

    // If the resulting time is in the future, assume it's from the previous day
    if (newStart.getTime() > Date.now()) {
        newStart.setDate(newStart.getDate() - 1);
    }

    try {
        const fd = new FormData();
        fd.append('action',     'update_entry_time');
        fd.append('entry_id',   timerState.entryId);
        fd.append('start_time', Math.floor(newStart.getTime() / 1000));

        const resp = await fetch(TIME_API, { method: 'POST', body: fd });
        const data = await resp.json();

        if (data.success) {
            timerState.startTime = newStart.getTime();
            showToast('Start time updated', 'success');
        } else {
            showToast(data.message || 'Could not update start time', 'error');
        }
    } catch (err) {
        showToast('Network error updating start time', 'error');
    }
    cancelEditStartTime();
}

// Start the 1-second interval tick
function startTimerTick() {
    if (timerState.intervalId) clearInterval(timerState.intervalId);
    updateTimerDisplay(); // immediate update
    timerState.intervalId = setInterval(updateTimerDisplay, 1000);
}

// Update banner clock + active task button
function updateTimerDisplay() {
    if (!timerState.startTime) return;
    const elapsed = Math.floor((Date.now() - timerState.startTime) / 1000);
    const formatted = formatDurationClock(elapsed);

    const display = document.getElementById('activeTimerDisplay');
    if (display) display.textContent = formatted;

    // Update elapsed on the task button if it's visible
    const btnElapsed = document.getElementById(`timer-btn-elapsed-${timerState.taskId}`);
    if (btnElapsed) btnElapsed.textContent = ' ' + formatted;
}

// Show the banner with task + project info
function showTimerBanner(taskName, projectName) {
    const banner = document.getElementById('activeTimerBanner');
    const label  = document.getElementById('activeTimerLabel');
    if (banner) {
        label.textContent = `${taskName}${projectName ? '  ·  ' + projectName : ''}`;
        banner.style.display = 'flex';
    }
    updateStartTimeDisplay();
}

function hideTimerBanner() {
    const banner = document.getElementById('activeTimerBanner');
    if (banner) banner.style.display = 'none';
}

// Re-render all visible timer buttons to reflect current timerState
function refreshTimerButtons() {
    document.querySelectorAll('[id^="timer-btn-"]').forEach(btn => {
        const taskId = parseInt(btn.id.replace('timer-btn-', ''));
        const isActive = timerState.isRunning && timerState.taskId === taskId;
        btn.className = 'btn btn-xs ' + (isActive ? 'timer-play-btn timer-active' : 'timer-play-btn');
        btn.title = isActive ? 'Stop timer' : 'Start timer';
        const icon = btn.querySelector('i');
        if (icon) icon.className = 'fas ' + (isActive ? 'fa-stop' : 'fa-play');

        // Add/remove elapsed span
        let elSpan = btn.querySelector('.timer-btn-elapsed');
        if (isActive && !elSpan) {
            elSpan = document.createElement('span');
            elSpan.className = 'timer-btn-elapsed';
            elSpan.id = `timer-btn-elapsed-${taskId}`;
            btn.appendChild(elSpan);
        } else if (!isActive && elSpan) {
            elSpan.remove();
        }
    });
}

// Format seconds as H:MM:SS (live clock)
function formatDurationClock(seconds) {
    const h = Math.floor(seconds / 3600);
    const m = Math.floor((seconds % 3600) / 60);
    const s = seconds % 60;
    return `${h}:${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;
}

// Format seconds as "Xh Ym" (short label)
function formatDurationShort(seconds) {
    if (!seconds) return '0m';
    const h = Math.floor(seconds / 3600);
    const m = Math.floor((seconds % 3600) / 60);
    if (h > 0) return `${h}h ${m}m`;
    return `${m}m`;
}

// Format decimal hours for timesheet cells
function formatDecimalHours(seconds) {
    if (!seconds || seconds <= 0) return null;
    return roundToHalf(seconds / 3600);
}

// ═══════════════════════════════════════════════════════════════════════════
// TIMESHEET — modal and rendering
// ═══════════════════════════════════════════════════════════════════════════

let timesheetCurrentWeekStart = null;

function getWeekStart(date) {
    const d = new Date(date);
    const day = d.getDay(); // 0=Sun
    const diff = day === 0 ? -6 : 1 - day; // shift so Mon = day 0
    d.setDate(d.getDate() + diff);
    d.setHours(0, 0, 0, 0);
    return d;
}

function openTimesheetModal() {
    timesheetCurrentWeekStart = getWeekStart(new Date());
    document.getElementById('timesheetModal').style.display = 'block';
    loadTimesheet();
}

function closeTimesheetModal() {
    document.getElementById('timesheetModal').style.display = 'none';
}

function navigateWeek(direction) {
    timesheetCurrentWeekStart.setDate(timesheetCurrentWeekStart.getDate() + direction * 7);
    loadTimesheet();
}

async function loadTimesheet() {
    const weekStartStr = timesheetCurrentWeekStart.toISOString().split('T')[0];
    const weekEnd = new Date(timesheetCurrentWeekStart);
    weekEnd.setDate(weekEnd.getDate() + 6);

    // Update week label
    const opts = { month: 'short', day: 'numeric', year: 'numeric' };
    document.getElementById('timesheetWeekLabel').textContent =
        `${timesheetCurrentWeekStart.toLocaleDateString('en-US', opts)}  –  ${weekEnd.toLocaleDateString('en-US', opts)}`;

    document.getElementById('timesheetContent').innerHTML =
        '<p style="text-align:center;padding:2rem;color:var(--gray-400);"><i class="fas fa-spinner fa-spin"></i> Loading...</p>';

    try {
        const fd = new FormData();
        fd.append('action',     'get_timesheet');
        fd.append('week_start', weekStartStr);
        const resp = await fetch(TIME_API, { method: 'POST', body: fd });
        const data = await resp.json();

        if (!data.success) {
            document.getElementById('timesheetContent').innerHTML =
                `<p style="color:var(--danger-color);text-align:center;">${data.message || 'Error loading timesheet'}</p>`;
            return;
        }

        renderTimesheetTable(data.entries, timesheetCurrentWeekStart);
    } catch (err) {
        console.error('loadTimesheet error:', err);
        document.getElementById('timesheetContent').innerHTML =
            '<p style="color:var(--danger-color);text-align:center;">Network error loading timesheet</p>';
    }
}

// ── Timesheet helpers ─────────────────────────────────────────────────────────

// HTML-escape for safe embedding in attributes and text
function esc(s) {
    return String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// Copy text to clipboard with toast feedback
function tsClipboard(text) {
    if (!text) return;
    navigator.clipboard.writeText(text).then(() => {
        showToast('Copied to clipboard', 'success');
    }).catch(() => {
        showToast('Could not copy to clipboard', 'error');
    });
}

// Replace phase cell content with an inline editor
function tsEditPhase(td) {
    const taskId = td.dataset.taskId;
    const current = td.dataset.phase || '';
    td.innerHTML = `
        <div style="display:flex;align-items:center;gap:0.3rem;">
            <input type="text" id="phase-input-${taskId}" value="${esc(current)}"
                style="width:70px;font-size:0.82rem;padding:2px 5px;border:1px solid var(--primary-color);border-radius:4px;outline:none;"
                onkeydown="if(event.key==='Enter')tsSavePhase(this.parentElement.parentElement);if(event.key==='Escape')tsCancelPhase(this.parentElement.parentElement);">
            <button onclick="tsSavePhase(this.closest('td'))" title="Save" style="background:none;border:none;cursor:pointer;color:var(--success-color);font-size:0.8rem;padding:0 2px;"><i class="fas fa-check"></i></button>
            <button onclick="tsCancelPhase(this.closest('td'))" title="Cancel" style="background:none;border:none;cursor:pointer;color:var(--gray-400);font-size:0.8rem;padding:0 2px;"><i class="fas fa-times"></i></button>
        </div>`;
    td.querySelector('input').focus();
}

async function tsSavePhase(td) {
    const taskId = td.dataset.taskId;
    const input  = td.querySelector('input');
    if (!input) return;
    const newPhase = input.value.trim();

    try {
        const fd = new FormData();
        fd.append('action',       'update_phase_number');
        fd.append('task_id',      taskId);
        fd.append('phase_number', newPhase);
        const resp = await fetch(TIME_API, { method: 'POST', body: fd });
        const data = await resp.json();
        if (!data.success) { showToast(data.message || 'Could not save phase', 'error'); return; }
        td.dataset.phase = newPhase;
        showToast('Phase number saved', 'success');
    } catch (err) {
        showToast('Network error saving phase', 'error');
        return;
    }
    tsCancelPhase(td); // restore view with updated value
}

function tsCancelPhase(td) {
    const phase = td.dataset.phase || '';
    td.innerHTML = `
        <span class="ts-phase-view" onclick="tsEditPhase(this.parentElement)" style="cursor:pointer;display:flex;align-items:center;gap:0.3rem;">
            <span class="ts-phase-text">${esc(phase) || '—'}</span>
            <i class="fas fa-edit" style="font-size:0.65rem;color:var(--gray-400);opacity:0.6;"></i>
        </span>`;
}

// ── Project inline editing ─────────────────────────────────────────────────

function tsEditProject(td) {
    const current = td.dataset.projectId || '';
    td.innerHTML = `
        <div style="display:flex;align-items:center;gap:0.3rem;">
            <input type="text" value="${esc(current)}" placeholder="Project ID"
                style="width:90px;font-size:0.82rem;padding:2px 5px;border:1px solid var(--primary-color);border-radius:4px;outline:none;font-family:monospace;"
                onkeydown="if(event.key==='Enter')tsSaveProject(this.closest('td'));if(event.key==='Escape')tsCancelProject(this.closest('td'));">
            <button onclick="tsSaveProject(this.closest('td'))" title="Save" style="background:none;border:none;cursor:pointer;color:var(--success-color);font-size:0.8rem;padding:0 2px;"><i class="fas fa-check"></i></button>
            <button onclick="tsCancelProject(this.closest('td'))" title="Cancel" style="background:none;border:none;cursor:pointer;color:var(--gray-400);font-size:0.8rem;padding:0 2px;"><i class="fas fa-times"></i></button>
        </div>`;
    const inp = td.querySelector('input');
    inp.focus();
    inp.select();
}

async function tsSaveProject(td) {
    const taskId      = td.dataset.taskId    || '0';
    const taskName    = td.dataset.taskName  || '';
    const weekStart   = td.dataset.weekStart || '';
    const weekEnd     = td.dataset.weekEnd   || '';
    const input       = td.querySelector('input');
    if (!input) return;
    const newProjectId = input.value.trim();
    if (!newProjectId) { showToast('Project ID cannot be empty', 'error'); return; }

    try {
        const fd = new FormData();
        fd.append('action',         'update_entry_project');
        fd.append('task_id',        taskId);
        fd.append('task_name',      taskName);
        fd.append('week_start',     weekStart);
        fd.append('week_end',       weekEnd);
        fd.append('new_project_id', newProjectId);
        const resp = await fetch(TIME_API, { method: 'POST', body: fd });
        const data = await resp.json();
        if (!data.success) { showToast(data.message || 'Could not update project', 'error'); return; }
        td.dataset.projectId   = newProjectId;
        td.dataset.projectName = data.project_name || '';
        showToast('Project updated', 'success');
    } catch (err) {
        showToast('Network error updating project', 'error');
        return;
    }
    tsCancelProject(td);
}

function tsCancelProject(td) {
    const projectId   = td.dataset.projectId   || '';
    const projectName = td.dataset.projectName || '';
    td.innerHTML = `
        <div class="ts-project-view" ondblclick="tsEditProject(this.parentElement)" title="Double-click to edit project">
            <span style="font-family:monospace;font-size:0.8rem;color:var(--primary-color);font-weight:600;">${esc(projectId)}</span>
            <button onclick="event.stopPropagation();tsClipboard('${esc(projectId)}')" title="Copy project number"
                style="background:none;border:none;cursor:pointer;padding:1px 3px;color:var(--gray-400);font-size:0.7rem;opacity:0.6;"
                onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.6'"><i class="fas fa-copy"></i></button>
            <i class="fas fa-pencil-alt ts-project-edit-icon"></i>
        </div>
        ${projectName ? `<span style="font-size:0.75rem;color:var(--gray-500);font-weight:400;">${esc(projectName)}</span>` : ''}`;
}

// ── Day-note inline editing ────────────────────────────────────────────────

function tsEditDayNote(td) {
    const note  = td.dataset.note  || '';
    const hours = td.dataset.hours || '';
    td.innerHTML = `
        <div class="ts-day-edit">
            <div class="ts-hours-block">${esc(hours)}</div>
            <div class="ts-note-edit-row">
                <input type="text" class="ts-note-input" value="${esc(note)}" placeholder="Add note..."
                    onkeydown="if(event.key==='Enter')tsSaveDayNote(this.closest('td'));if(event.key==='Escape')tsCancelDayNote(this.closest('td'));">
                <div class="ts-note-actions">
                    <button onclick="tsSaveDayNote(this.closest('td'))" title="Save"><i class="fas fa-check"></i></button>
                    <button onclick="tsCancelDayNote(this.closest('td'))" title="Cancel"><i class="fas fa-times"></i></button>
                </div>
            </div>
        </div>`;
    const input = td.querySelector('input');
    input.focus();
    input.select();
}

async function tsSaveDayNote(td) {
    const taskId    = td.dataset.taskId;
    const taskName  = td.dataset.taskName;
    const entryDate = td.dataset.entryDate;
    const input = td.querySelector('input');
    if (!input) return;
    const newNote = input.value.trim();

    try {
        const fd = new FormData();
        fd.append('action',     'update_day_note');
        fd.append('task_id',    taskId);
        fd.append('task_name',  taskName);
        fd.append('entry_date', entryDate);
        fd.append('note',       newNote);
        const resp = await fetch(TIME_API, { method: 'POST', body: fd });
        const data = await resp.json();
        if (!data.success) { showToast(data.message || 'Could not save note', 'error'); return; }
        td.dataset.note = newNote;
        showToast('Note saved', 'success');
    } catch (err) {
        showToast('Network error saving note', 'error');
        return;
    }
    tsCancelDayNote(td);
}

function tsCancelDayNote(td) {
    const note  = td.dataset.note  || '';
    const hours = td.dataset.hours || '';
    const safeNote = esc(note);
    td.innerHTML = `
        <div class="ts-day-content">
            <div class="ts-hours-block" ondblclick="tsEditDayHours(this.closest('td'))" title="Double-click to edit hours">${esc(hours)}</div>
            <div class="ts-note-block">
                ${note
                    ? `<span class="ts-day-note" ondblclick="tsEditDayNote(this.closest('td'))" title="Double-click to edit">${safeNote}</span>
                       <button class="ts-note-copy" onclick="tsClipboard(this.closest('td').dataset.note)" title="Copy note"><i class="fas fa-copy"></i></button>`
                    : `<span class="ts-day-note ts-day-note-empty" ondblclick="tsEditDayNote(this.closest('td'))" title="Double-click to add note">+ note</span>`
                }
            </div>
        </div>`;
}

// ── Day-hours inline editing ───────────────────────────────────────────────

function tsEditDayHours(td) {
    const hours = td.dataset.hours || '';
    const note  = td.dataset.note  || '';
    const safeNote = esc(note);
    td.innerHTML = `
        <div class="ts-day-content">
            <div class="ts-hours-edit-row">
                <input type="number" class="ts-hours-input" value="${esc(hours)}" min="0" step="0.5" placeholder="0"
                    onkeydown="if(event.key==='Enter')tsSaveDayHours(this.closest('td'));if(event.key==='Escape')tsCancelDayNote(this.closest('td'));">
                <div class="ts-note-actions">
                    <button onclick="tsSaveDayHours(this.closest('td'))" title="Save"><i class="fas fa-check"></i></button>
                    <button onclick="tsCancelDayNote(this.closest('td'))" title="Cancel"><i class="fas fa-times"></i></button>
                </div>
            </div>
            <div class="ts-note-block">
                ${note
                    ? `<span class="ts-day-note">${safeNote}</span>`
                    : `<span class="ts-day-note ts-day-note-empty">+ note</span>`
                }
            </div>
        </div>`;
    const input = td.querySelector('.ts-hours-input');
    input.focus();
    input.select();
}

async function tsSaveDayHours(td) {
    const taskId    = td.dataset.taskId;
    const taskName  = td.dataset.taskName;
    const entryDate = td.dataset.entryDate;
    const input = td.querySelector('.ts-hours-input');
    if (!input) return;
    const newHours = parseFloat(input.value);

    if (isNaN(newHours) || newHours < 0) {
        showToast('Enter a valid number of hours', 'error');
        return;
    }

    try {
        const fd = new FormData();
        fd.append('action',     'update_day_hours');
        fd.append('task_id',    taskId);
        fd.append('task_name',  taskName);
        fd.append('entry_date', entryDate);
        fd.append('hours',      newHours);
        const resp = await fetch(TIME_API, { method: 'POST', body: fd });
        const data = await resp.json();
        if (!data.success) { showToast(data.message || 'Could not save hours', 'error'); return; }
        td.dataset.hours = String(roundToHalf(newHours));
        showToast('Hours updated', 'success');
    } catch (err) {
        showToast('Network error saving hours', 'error');
        return;
    }
    // Reload so daily totals row stays accurate
    loadTimesheet();
}

function renderTimesheetTable(entries, weekStart) {
    const days = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
    const today = new Date();
    today.setHours(0,0,0,0);

    // Build date objects for each day column
    const dates = days.map((_, i) => {
        const d = new Date(weekStart);
        d.setDate(d.getDate() + i);
        return d;
    });
    const weekStartStr = dates[0].toISOString().split('T')[0];
    const weekEndStr   = dates[6].toISOString().split('T')[0];

    // Group entries by task (composite key handles admin/training where task_id=0)
    const taskMap = {};
    entries.forEach(e => {
        const key = `${e.task_id}_${e.task_name}`;
        if (!taskMap[key]) {
            taskMap[key] = {
                task_id:      e.task_id,
                task_name:    e.task_name,
                project_id:   e.project_id,
                project_name: e.project_name,
                phase_number: e.phase_number || '',
                task_type:    e.task_type || e.task_name,
                days:  {},
                notes: {}
            };
        }
        taskMap[key].days[e.entry_date] = parseInt(e.total_seconds) || 0;
        if (e.notes) {
            const unique = [...new Set(e.notes.split(' | ').filter(Boolean))];
            taskMap[key].notes[e.entry_date] = unique.join(' | ');
        }
    });

    const tasks = Object.values(taskMap);

    if (tasks.length === 0) {
        document.getElementById('timesheetContent').innerHTML =
            `<div style="text-align:center;padding:3rem;color:var(--gray-400);">
                <i class="fas fa-clock" style="font-size:2.5rem;margin-bottom:1rem;opacity:0.4;display:block;"></i>
                No time entries for this week.
             </div>`;
        return;
    }

    // Daily totals
    const dailyTotals = dates.map((d, _) => {
        const ds = d.toISOString().split('T')[0];
        return tasks.reduce((sum, t) => sum + (t.days[ds] || 0), 0);
    });
    const grandTotal = dailyTotals.reduce((a, b) => a + b, 0);

    // Build header
    let headerHtml = `<tr>
        <th>Project</th>
        <th>Phase</th>
        <th>Task</th>`;
    dates.forEach((d, i) => {
        const isToday = d.getTime() === today.getTime();
        headerHtml += `<th class="${isToday ? 'today-header' : ''}">${days[i]}<br><small style="font-weight:400;font-size:0.7rem;">${d.toLocaleDateString('en-US',{month:'short',day:'numeric'})}</small></th>`;
    });
    headerHtml += `<th>Total</th><th>Notes</th></tr>`;

    // Build body rows
    let bodyHtml = '';
    tasks.forEach(t => {
        let rowTotal = 0;
        const safePhase   = esc(t.phase_number);
        const allNotes    = [...new Set(
            Object.values(t.notes)
                .filter(Boolean)
                .flatMap(n => n.split(' | '))
        )].join(' | ');
        const safeNotes   = esc(allNotes);
        const canEditPhase = t.task_id > 0;

        // Project cell: project ID with copy + double-click to edit
        const safeProjectId   = esc(t.project_id);
        const safeProjectName = esc(t.project_name || '');
        const projectCell = `<td class="ts-project-cell"
            data-task-id="${t.task_id}"
            data-task-name="${esc(t.task_name)}"
            data-project-id="${safeProjectId}"
            data-project-name="${safeProjectName}"
            data-week-start="${weekStartStr}"
            data-week-end="${weekEndStr}">
            <div class="ts-project-view" ondblclick="tsEditProject(this.parentElement)" title="Double-click to edit project">
                <span style="font-family:monospace;font-size:0.8rem;color:var(--primary-color);font-weight:600;">${safeProjectId}</span>
                <button onclick="event.stopPropagation();tsClipboard('${safeProjectId}')" title="Copy project number" style="background:none;border:none;cursor:pointer;padding:1px 3px;color:var(--gray-400);font-size:0.7rem;opacity:0.6;" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.6'"><i class="fas fa-copy"></i></button>
                <i class="fas fa-pencil-alt ts-project-edit-icon"></i>
            </div>
            ${t.project_name ? `<span style="font-size:0.75rem;color:var(--gray-500);font-weight:400;">${safeProjectName}</span>` : ''}
        </td>`;

        // Phase cell: editable for real tasks
        const phaseCell = canEditPhase
            ? `<td class="ts-phase-cell" data-task-id="${t.task_id}" data-phase="${safePhase}">
                <span class="ts-phase-view" onclick="tsEditPhase(this.parentElement)" style="cursor:pointer;display:flex;align-items:center;gap:0.3rem;">
                    <span class="ts-phase-text">${safePhase || '—'}</span>
                    <i class="fas fa-edit" style="font-size:0.65rem;color:var(--gray-400);opacity:0.6;"></i>
                </span>
               </td>`
            : `<td>—</td>`;

        // Task cell
        const taskCell = `<td>
            <span style="font-weight:600;color:var(--gray-800);">${esc(t.task_name)}</span>
            ${t.task_type && t.task_type !== t.task_name ? `<br><span style="font-size:0.7rem;color:var(--gray-500);">${esc(t.task_type)}</span>` : ''}
        </td>`;

        let rowHtml = `<tr>${projectCell}${phaseCell}${taskCell}`;

        dates.forEach(d => {
            const ds = d.toISOString().split('T')[0];
            const isToday = d.getTime() === today.getTime();
            const secs = t.days[ds] || 0;
            rowTotal += secs;
            const display = secs > 0 ? roundToHalf(secs / 3600) : null;
            const dayNote = t.notes[ds] || '';
            if (display !== null) {
                const safeNote    = esc(dayNote);
                const hoursDisplay = String(display);
                rowHtml += `<td class="${isToday ? 'today-cell' : ''} ts-day-cell"
                                data-task-id="${t.task_id}"
                                data-task-name="${esc(t.task_name)}"
                                data-entry-date="${ds}"
                                data-note="${safeNote}"
                                data-hours="${hoursDisplay}">
                    <div class="ts-day-content">
                        <div class="ts-hours-block" ondblclick="tsEditDayHours(this.closest('td'))" title="Double-click to edit hours">${hoursDisplay}</div>
                        <div class="ts-note-block">
                            ${dayNote
                                ? `<span class="ts-day-note" ondblclick="tsEditDayNote(this.closest('td'))" title="Double-click to edit">${safeNote}</span>
                                   <button class="ts-note-copy" onclick="tsClipboard(this.closest('td').dataset.note)" title="Copy note"><i class="fas fa-copy"></i></button>`
                                : `<span class="ts-day-note ts-day-note-empty" ondblclick="tsEditDayNote(this.closest('td'))" title="Double-click to add note">+ note</span>`
                            }
                        </div>
                    </div>
                </td>`;
            } else {
                rowHtml += `<td class="${isToday ? 'today-cell' : ''}"><span class="timesheet-empty-cell">—</span></td>`;
            }
        });

        const rowTotalHours = roundToHalf(rowTotal / 3600);
        rowHtml += `<td style="font-weight:600;">${rowTotalHours > 0 ? rowTotalHours : '—'}</td>`;

        // Notes cell: text + copy button
        const notesCell = allNotes
            ? `<td style="font-size:0.78rem;color:var(--gray-600);max-width:180px;white-space:normal;word-break:break-word;overflow-wrap:break-word;vertical-align:top;" data-notes="${safeNotes}">
                <span style="line-height:1.3;">${safeNotes}</span>
                <button onclick="tsClipboard(this.closest('td').dataset.notes)" title="Copy notes" style="background:none;border:none;cursor:pointer;padding:1px 3px;color:var(--gray-400);font-size:0.7rem;opacity:0.6;vertical-align:top;" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.6'"><i class="fas fa-copy"></i></button>
               </td>`
            : `<td><span class="timesheet-empty-cell">—</span></td>`;

        rowHtml += notesCell + '</tr>';
        bodyHtml += rowHtml;
    });

    // Total row
    let totalRowHtml = '<tr class="timesheet-total-row"><td colspan="3">Daily Total</td>';
    dailyTotals.forEach((secs, i) => {
        const d = dates[i];
        const isToday = d.getTime() === today.getTime();
        const h = roundToHalf(secs / 3600);
        totalRowHtml += `<td class="${isToday ? 'today-cell' : ''}">${h > 0 ? h : '—'}</td>`;
    });
    const grandH = roundToHalf(grandTotal / 3600);
    totalRowHtml += `<td>${grandH > 0 ? grandH : '—'}</td><td></td></tr>`;

    const tableHtml = `
        <div class="timesheet-table-wrapper">
            <table class="timesheet-table">
                <thead>${headerHtml}</thead>
                <tbody>${bodyHtml}${totalRowHtml}</tbody>
            </table>
        </div>
        <div class="timesheet-footer">
            <span class="timesheet-grand-total">
                <i class="fas fa-clock"></i> Week Total: <strong>${grandH}h</strong>
            </span>
            <button class="btn-vantagepoint" onclick="copyTimesheetForVantagepoint()" title="Copy tab-separated for Deltek Vantagepoint">
                <i class="fas fa-copy"></i> Copy for Vantagepoint
            </button>
        </div>`;

    document.getElementById('timesheetContent').innerHTML = tableHtml;

    // Stash data for clipboard copy
    window._timesheetData = { tasks, dates, days, dailyTotals, grandTotal };
}

function copyTimesheetForVantagepoint() {
    const d = window._timesheetData;
    if (!d) return;

    const header = ['Project', 'Project Name', 'Phase', 'Task', 'Labor Code', ...d.days, 'Total'].join('\t');
    const rows = d.tasks.map(t => {
        let rowTotal = 0;
        const dayCells = d.dates.map(date => {
            const ds = date.toISOString().split('T')[0];
            const secs = t.days[ds] || 0;
            rowTotal += secs;
            return secs > 0 ? roundToHalf(secs / 3600) : '';
        });
        const totalH = roundToHalf(rowTotal / 3600);
        return [t.project_id, t.project_name || '', t.phase_number || '', t.task_name, t.task_type || '', ...dayCells, totalH > 0 ? totalH : ''].join('\t');
    });

    const totalRow = ['Daily Total', '', '', '', '', ...d.dailyTotals.map(s => s > 0 ? roundToHalf(s / 3600) : ''), roundToHalf(d.grandTotal / 3600)].join('\t');
    const tsv = [header, ...rows, totalRow].join('\n');

    navigator.clipboard.writeText(tsv).then(() => {
        showToast('Timesheet copied to clipboard — paste into Vantagepoint!', 'success');
    }).catch(() => {
        showToast('Could not copy to clipboard', 'error');
    });
}
    </script>

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

    <script>
    // ========== CHECKLIST INTEGRATION ==========
    const CHECKLIST_API = '../../Models/php/checklist_api.php';
    let checklistSummaries = {};
    let currentChecklistTaskId = null;
    let currentChecklistTaskName = null;

    // Load checklist summaries for a batch of task IDs
    async function loadChecklistSummaries(taskIds) {
        if (!taskIds || taskIds.length === 0) return;
        try {
            const response = await fetch(`${CHECKLIST_API}?action=get_checklist_summaries&task_ids=${taskIds.join(',')}`);
            const data = await response.json();
            if (data.success && data.summaries) {
                Object.assign(checklistSummaries, data.summaries);
            }
        } catch (error) {
            console.error('Error loading checklist summaries:', error);
        }
    }

    // Render a checklist button for a task
    function renderChecklistButton(task) {
        const summary = checklistSummaries[task.task_id];

        if (!summary) {
            // No data loaded yet - will appear after summaries load
            return `<span class="checklist-btn-placeholder" data-task-id="${task.task_id}"></span>`;
        }

        if (summary.total > 0) {
            // Has progress records
            const pct = Math.round((summary.completed / summary.total) * 100);
            const isComplete = summary.completed === summary.total;
            const btnClass = isComplete ? 'checklist-complete' : 'checklist-in-progress';
            return `
                <button class="checklist-btn ${btnClass}"
                        onclick="event.stopPropagation(); showChecklistModal(${task.task_id}, '${(task.task_name || '').replace(/'/g, "\\'")}', '${(task.assigned_to || '').replace(/'/g, "\\'")}')"
                        title="Checklist: ${summary.completed}/${summary.total}">
                    <i class="fas ${isComplete ? 'fa-check-circle' : 'fa-clipboard-check'}"></i>
                    ${summary.completed}/${summary.total}
                    <span class="checklist-progress-mini"><span class="checklist-progress-mini-fill" style="width:${pct}%"></span></span>
                </button>
            `;
        } else if (summary.has_template) {
            // Template exists but not yet initialized
            if (summary.template_count > 1) {
                return `
                    <button class="checklist-btn checklist-pick"
                            onclick="event.stopPropagation(); showTemplatePicker(${task.task_id}, '${(task.task_name || '').replace(/'/g, "\\'")}')"
                            title="Select a checklist template">
                        <i class="fas fa-clipboard-list"></i> Select Checklist
                    </button>
                `;
            }
            return `
                <button class="checklist-btn checklist-not-started"
                        onclick="event.stopPropagation(); showChecklistModal(${task.task_id}, '${(task.task_name || '').replace(/'/g, "\\'")}', '${(task.assigned_to || '').replace(/'/g, "\\'")}')"
                        title="Open checklist">
                    <i class="fas fa-clipboard-check"></i> Checklist
                </button>
            `;
        }

        // No template for this task type
        return '';
    }

    // Show checklist modal
    async function showChecklistModal(taskId, taskName, assignedTo) {
        currentChecklistTaskId = taskId;
        currentChecklistTaskName = taskName || null;
        const changeBtn = document.getElementById('changeChecklistBtn');
        if (changeBtn) changeBtn.style.display = 'none';
        document.getElementById('checklistModalTitle').textContent = taskName || 'Checklist';
        document.getElementById('checklistModalBody').innerHTML = `
            <div style="text-align: center; padding: 2rem; color: var(--gray-400);">
                <i class="fas fa-spinner fa-spin" style="font-size: 2rem;"></i>
                <p>Loading checklist...</p>
            </div>
        `;
        document.getElementById('checklistProgressText').textContent = '';
        document.getElementById('checklistProgressFill').style.width = '0%';
        document.getElementById('checklistModal').classList.add('active');

        try {
            const response = await fetch(`${CHECKLIST_API}?action=get_task_checklist&task_id=${taskId}`);
            const data = await response.json();

            if (!data.success) {
                document.getElementById('checklistModalBody').innerHTML = `
                    <div style="text-align: center; padding: 2rem; color: var(--gray-500);">
                        <i class="fas fa-exclamation-circle" style="font-size: 2rem; margin-bottom: 0.5rem;"></i>
                        <p>${data.message || 'Error loading checklist'}</p>
                    </div>
                `;
                return;
            }

            if (data.pick_template) {
                // Need to pick a template first
                closeChecklistModal();
                showTemplatePicker(taskId, taskName, data.templates);
                return;
            }

            if (!data.checklist) {
                document.getElementById('checklistModalBody').innerHTML = `
                    <div style="text-align: center; padding: 2rem; color: var(--gray-500);">
                        <i class="fas fa-clipboard" style="font-size: 2rem; margin-bottom: 0.5rem; opacity: 0.5;"></i>
                        <p>No checklist template exists for this task type.</p>
                    </div>
                `;
                return;
            }

            renderChecklistItems(data.checklist);

        } catch (error) {
            console.error('Error:', error);
            document.getElementById('checklistModalBody').innerHTML = `
                <div style="text-align: center; padding: 2rem; color: var(--danger-color);">
                    <p>Network error loading checklist</p>
                </div>
            `;
        }
    }

    // Render checklist items in modal
    function renderChecklistItems(checklist) {
        const { items } = checklist;

        // Build lookup maps
        const childrenOf = {};
        items.forEach(item => {
            if (item.parent_item_id) {
                if (!childrenOf[item.parent_item_id]) childrenOf[item.parent_item_id] = [];
                childrenOf[item.parent_item_id].push(item);
            }
        });

        // Compute accurate progress considering conditional visibility
        const { total, completed } = computeChecklistProgress(items, childrenOf);
        const pct = total > 0 ? Math.round((completed / total) * 100) : 0;
        document.getElementById('checklistProgressText').textContent = `${completed} / ${total} completed (${pct}%)`;
        document.getElementById('checklistProgressFill').style.width = `${pct}%`;

        // Show "Change" button only when nothing has been checked yet
        const changeBtn = document.getElementById('changeChecklistBtn');
        if (changeBtn) changeBtn.style.display = (completed === 0) ? '' : 'none';

        // Group root items by category
        const grouped = {};
        items.filter(i => !i.parent_item_id).forEach(item => {
            const cat = item.category || 'General';
            if (!grouped[cat]) grouped[cat] = [];
            grouped[cat].push(item);
        });

        let html = '';
        for (const [category, catItems] of Object.entries(grouped)) {
            html += `<div class="checklist-category-header">${escapeHtmlChecklist(category)}</div>`;
            catItems.forEach(item => {
                html += item.item_type === 'conditional'
                    ? renderConditionalChecklistItem(item, childrenOf[item.item_id] || [])
                    : renderStandardChecklistItem(item);
            });
        }

        document.getElementById('checklistModalBody').innerHTML = html;
    }

    function computeChecklistProgress(items, childrenOf) {
        let total = 0, completed = 0;
        const done = s => ['completed', 'yes', 'no', 'na'].includes(s);
        items.filter(i => !i.parent_item_id).forEach(item => {
            total++;
            if (done(item.item_status)) completed++;
            // Count visible branch children for conditionals
            if (item.item_type === 'conditional' && (item.item_status === 'yes' || item.item_status === 'no')) {
                (childrenOf[item.item_id] || []).filter(c => c.branch === item.item_status).forEach(child => {
                    total++;
                    if (done(child.item_status)) completed++;
                });
            }
        });
        return { total, completed };
    }

    function renderStandardChecklistItem(item, isChild = false) {
        const status = item.item_status || 'unchecked';
        const isCompleted = status === 'completed';
        const isNa = status === 'na';
        const doneClass = (isCompleted || isNa) ? 'completed' : '';
        const childClass = isChild ? 'checklist-child-item' : '';
        const naLabel = isNa ? ` <span class="na-badge">N/A</span>` : '';
        const completedDateStr = item.completed_date
            ? new Date(item.completed_date.replace(' ', 'T')).toLocaleDateString('en-US', { month: '2-digit', day: '2-digit', year: 'numeric' })
            : '';
        const meta = isCompleted && item.completed_by
            ? `<div class="checklist-item-meta">${escapeHtmlChecklist(item.completed_by)}${completedDateStr ? ' - ' + completedDateStr : ''}</div>`
            : '';
        const newStatusOnCheck = isCompleted ? 'unchecked' : 'completed';
        const newStatusOnNa   = isNa ? 'unchecked' : 'na';
        return `
            <label class="checklist-item ${doneClass} ${childClass}">
                <input type="checkbox" ${isCompleted ? 'checked' : ''}
                       onchange="setChecklistItemStatus(${currentChecklistTaskId}, ${item.item_id}, '${newStatusOnCheck}')">
                <div style="flex:1;">
                    <div class="checklist-item-label">${escapeHtmlChecklist(item.item_text)}${naLabel}</div>
                    ${meta}
                </div>
                <button class="btn-na ${isNa ? 'active' : ''}" title="Not Applicable"
                        onclick="event.preventDefault();event.stopPropagation();setChecklistItemStatus(${currentChecklistTaskId}, ${item.item_id}, '${newStatusOnNa}')">N/A</button>
            </label>`;
    }

    function renderConditionalChecklistItem(item, children) {
        const status = item.item_status || 'unchecked';
        const yesActive = status === 'yes' ? 'active' : '';
        const noActive  = status === 'no'  ? 'active' : '';
        const naActive  = status === 'na'  ? 'active' : '';
        const answeredClass = status !== 'unchecked' ? 'answered' : '';
        // Toggle: clicking same button deselects
        const newYes = status === 'yes' ? 'unchecked' : 'yes';
        const newNo  = status === 'no'  ? 'unchecked' : 'no';
        const newNa  = status === 'na'  ? 'unchecked' : 'na';

        const yesChildren = children.filter(c => c.branch === 'yes');
        const noChildren  = children.filter(c => c.branch === 'no');
        let branchHtml = '';
        if (status === 'yes' && yesChildren.length > 0) {
            branchHtml = `<div class="branch-children">${yesChildren.map(c => renderStandardChecklistItem(c, true)).join('')}</div>`;
        } else if (status === 'no' && noChildren.length > 0) {
            branchHtml = `<div class="branch-children">${noChildren.map(c => renderStandardChecklistItem(c, true)).join('')}</div>`;
        }

        return `
            <div class="checklist-conditional-item ${answeredClass}">
                <div class="conditional-question">
                    <i class="fas fa-code-branch" style="color:var(--primary-color);flex-shrink:0;"></i>
                    <span class="checklist-item-label">${escapeHtmlChecklist(item.item_text)}</span>
                    <div class="conditional-buttons">
                        <button class="btn-branch yes-btn ${yesActive}"
                                onclick="setChecklistItemStatus(${currentChecklistTaskId}, ${item.item_id}, '${newYes}')">
                            <i class="fas fa-check"></i> Yes
                        </button>
                        <button class="btn-branch no-btn ${noActive}"
                                onclick="setChecklistItemStatus(${currentChecklistTaskId}, ${item.item_id}, '${newNo}')">
                            <i class="fas fa-times"></i> No
                        </button>
                        <button class="btn-branch na-btn ${naActive}"
                                onclick="setChecklistItemStatus(${currentChecklistTaskId}, ${item.item_id}, '${newNa}')">
                            N/A
                        </button>
                    </div>
                </div>
                ${branchHtml}
            </div>`;
    }

    // Set a checklist item to a specific status and re-render
    async function setChecklistItemStatus(taskId, itemId, status) {
        try {
            const res = await fetch(CHECKLIST_API, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'set_item_status', task_id: taskId, item_id: itemId, status, completed_by: CURRENT_USER })
            });
            const data = await res.json();
            if (data.success) {
                await refreshChecklistView(taskId);
            } else {
                showToast(data.message || 'Error updating item', 'error');
            }
        } catch (err) {
            console.error('Error:', err);
        }
    }

    async function refreshChecklistView(taskId) {
        const res = await fetch(`${CHECKLIST_API}?action=get_task_checklist&task_id=${taskId}`);
        const data = await res.json();
        if (data.success && data.checklist) {
            renderChecklistItems(data.checklist);
            // Update cached summary
            const items = data.checklist.items;
            const childrenOf = {};
            items.forEach(i => { if (i.parent_item_id) { if (!childrenOf[i.parent_item_id]) childrenOf[i.parent_item_id] = []; childrenOf[i.parent_item_id].push(i); } });
            const { total, completed } = computeChecklistProgress(items, childrenOf);
            checklistSummaries[taskId] = { total, completed, template_id: data.checklist.template_id };
        }
    }

    // Legacy toggle (kept for any external callers)
    async function toggleChecklistItem(taskId, itemId, checkbox) {
        const newStatus = checkbox.checked ? 'completed' : 'unchecked';
        await setChecklistItemStatus(taskId, itemId, newStatus);
    }

    function closeChecklistModal() {
        document.getElementById('checklistModal').classList.remove('active');
        // Refresh the task display to update buttons
        if (currentChecklistTaskId) {
            refreshChecklistButtons();
        }
        currentChecklistTaskId = null;
        currentChecklistTaskName = null;
    }

    // Refresh checklist button displays after changes
    async function refreshChecklistButtons() {
        const taskIds = Object.keys(checklistSummaries);
        if (taskIds.length > 0) {
            await loadChecklistSummaries(taskIds);
            // Update placeholder buttons
            document.querySelectorAll('.checklist-btn, .checklist-btn-placeholder').forEach(el => {
                const taskId = el.dataset?.taskId || el.getAttribute('onclick')?.match(/\d+/)?.[0];
                if (taskId && checklistSummaries[taskId]) {
                    // Re-render by reloading the project tasks
                }
            });
        }
    }

    // Template picker
    async function showTemplatePicker(taskId, taskName, templates) {
        currentChecklistTaskId = taskId;

        if (!templates) {
            try {
                const response = await fetch(`${CHECKLIST_API}?action=get_templates_for_task&task_id=${taskId}`);
                const data = await response.json();
                if (data.success) templates = data.templates;
            } catch (error) {
                console.error('Error:', error);
                return;
            }
        }

        if (!templates || templates.length === 0) {
            showToast('No templates available for this task type', 'error');
            return;
        }

        const html = templates.map(t => `
            <div class="template-picker-item" onclick="assignTemplate(${taskId}, ${t.template_id}, '${(taskName || '').replace(/'/g, "\\'")}')">
                <i class="fas fa-clipboard-check"></i>
                <div class="template-picker-info">
                    <h4>${escapeHtmlChecklist(t.template_name)}</h4>
                    ${t.description ? `<p>${escapeHtmlChecklist(t.description)}</p>` : ''}
                </div>
            </div>
        `).join('');

        document.getElementById('templatePickerBody').innerHTML = html;
        document.getElementById('templatePickerModal').classList.add('active');
    }

    function closeTemplatePicker() {
        document.getElementById('templatePickerModal').classList.remove('active');
    }

    // Assign template to task
    async function assignTemplate(taskId, templateId, taskName) {
        try {
            const response = await fetch(CHECKLIST_API, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'assign_template',
                    task_id: taskId,
                    template_id: templateId
                })
            });
            const data = await response.json();

            if (data.success) {
                closeTemplatePicker();
                showChecklistModal(taskId, taskName);
            } else {
                showToast(data.message || 'Error assigning template', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showToast('Network error', 'error');
        }
    }

    function escapeHtmlChecklist(text) {
        if (text === null || text === undefined) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Hook into task loading to fetch checklist summaries
    const _originalLoadTasksForProject = typeof loadTasksForProject === 'function' ? loadTasksForProject : null;

    if (_originalLoadTasksForProject) {
        const origFn = loadTasksForProject;
        loadTasksForProject = async function(projectId) {
            const tasks = await origFn(projectId);
            if (tasks && tasks.length > 0) {
                const taskIds = tasks.map(t => t.task_id);
                await loadChecklistSummaries(taskIds);
            }
            return tasks;
        };
    }

    // Load summaries on initial page load for visible tasks
    document.addEventListener('DOMContentLoaded', async function() {
        // Wait for projects to load, then scan for task IDs
        setTimeout(async () => {
            const taskElements = document.querySelectorAll('[data-task-id]');
            const ids = [...new Set(Array.from(taskElements).map(el => el.dataset.taskId).filter(Boolean))];
            if (ids.length > 0) {
                await loadChecklistSummaries(ids);
            }
        }, 2000);
    });

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

    // Close timesheet / template-picker modals on outside click
    // (the checklist side panel has no backdrop, so it's only closed via its own close button)
    document.addEventListener('click', function(e) {
        if (e.target.id === 'templatePickerModal') {
            closeTemplatePicker();
        }
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
