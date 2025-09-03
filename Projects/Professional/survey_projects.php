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
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h1><i class="fas fa-map-marked-alt"></i> Survey Pro</h1>
            <p>Professional Project Management</p>
        </div>
        <nav class="sidebar-nav">
            <a href="#" class="nav-item active">
                <i class="fas fa-th-large"></i>
                Dashboard
            </a>
            <a href="#" class="nav-item">
                <i class="fas fa-folder-open"></i>
                All Projects
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
        const row = createProjectRow(project);
        tbody.appendChild(row);
    });
    
    // Update pagination
    updatePagination(totalProjects);
}

// Create a project row
function createProjectRow(project) {
    const row = document.createElement('tr');
    
    // Format date
    const createdDate = new Date(project.createdDate).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
    
    // Status badge class
    const statusClass = `status-${project.projectStatus.toLowerCase().replace(' ', '-')}`;
    
    // Create links
    const links = createProjectLinks(project);
    
    row.innerHTML = `
        <td>
            <div class="project-id">${project.projectId}</div>
            <div class="project-name">${project.projectName}</div>
            <div class="project-description">Created by ${project.createdBy}</div>
            <div class="project-location"> Location: ${project.location}</div>
            <div class="project-scale-factor"> Scale factor: ${project.scale_factor}</div>
        </td>
        <td>
            <span class="status-badge ${statusClass}">
                <i class="fas fa-circle"></i>
                ${project.projectStatus}
            </span>
        </td>
        <td>${createdDate}</td>
        <td>
            <div class="links-grid">
                ${links}
            </div>
        </td>
        <td>
            <div style="display: flex; gap: 0.5rem;">
                <button class="btn btn-sm btn-secondary" onclick="editProject('${project.projectId}')">
                    <i class="fas fa-edit"></i> Edit
                </button>
                <button class="btn btn-sm btn-secondary" onclick="copyProjectId('${project.projectId}')">
                    <i class="fas fa-copy"></i>
                </button>
                <button class="btn btn-sm" style="background: var(--danger-color); color: white;" onclick="deleteProject('${project.projectId}')">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </td>
    `;
    
    return row;
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
    
    return linkTypes
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

// Toggle sidebar (mobile)
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
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
    const modal = document.getElementById('projectModal');
    const sidebar = document.getElementById('sidebar');
    const mobileMenuButton = document.querySelector('.mobile-menu-button');
    
    // Close modal if clicking outside
    if (event.target === modal) {
        closeModal();
    }
    
    // Close sidebar if clicking outside (mobile)
    if (sidebar && mobileMenuButton && 
        !sidebar.contains(event.target) && !mobileMenuButton.contains(event.target)) {
        sidebar.classList.remove('open');
    }
});
    </script>
</body>
</html>