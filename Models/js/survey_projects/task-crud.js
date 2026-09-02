// Task create/edit/delete modal for survey_projects.php: the task form
// (add/edit/delete/save), inline task-notes editing, and the task-name /
// task-folder-link auto-fill wiring based on TASK_TYPE_ACRONYMS.
//
// Depends on globals defined by survey_projects.php's main script (loaded
// before this file): TASK_TYPE_ACRONYMS, showToast, refreshTasksForProject,
// createTasksHTML, taskNameInput/taskTypeInput/taskLinkInput/
// taskProjectIdInput and the other task form DOM refs.

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

