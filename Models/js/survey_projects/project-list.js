// Project list rendering for survey_projects.php: search/filter/sort,
// pagination, the My To-Do view toggle, and building each project row
// (including its folder links) for the projects table.
//
// Depends on globals defined by survey_projects.php's main script (loaded
// before this file): allProjects, filteredProjects, currentPage,
// itemsPerPage, currentProjectsView, TODO_PRIORITY_LEVELS/RANK/COLORS,
// showToast, loadTasksForProject, createTasksHTML.

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

