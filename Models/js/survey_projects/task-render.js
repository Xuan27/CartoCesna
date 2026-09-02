// Task-card rendering for survey_projects.php: the per-task HTML
// (badges, timer/status controls, folder links), the status dropdown,
// and small task-type/status formatting helpers.
//
// Depends on globals defined by survey_projects.php's main script and by
// point-ranges.js/timer.js/checklist.js (all loaded before this file):
// allProjects, timerState, renderPointRangesIcon, renderChecklistButton,
// handleTimerClick, editTask, deleteTask, editTaskNotes, showToast.

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

// Copy task folder path
function copyTaskPath(taskPath, taskName) {
    navigator.clipboard.writeText(taskPath).then(() => {
        showToast(`Task folder path for "${taskName}" copied to clipboard!`);
    }).catch(() => {
        showToast(`Failed to copy task folder path`, 'error');
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

