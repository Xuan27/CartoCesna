// Project/task data loading for survey_projects.php: fetches the
// project list and the per-project task cache from the server, and
// resolves deep links (?project=, ?view=todo) once data has loaded.
//
// Depends on globals defined by survey_projects.php's main script
// (loaded before this file): allProjects, filteredProjects, currentPage,
// taskCache, taskCachePromise, showToast, searchProjects,
// updateProjectsDisplay, switchProjectsView.

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

// Handle deep-link from All Tasks page (?project=X&task=Y) and from other
// pages' sidebar (?view=todo) linking straight into the My To-Do view.
function handleDeepLink() {
    const params = new URLSearchParams(window.location.search);

    if (params.get('view') === 'todo') {
        switchProjectsView('todo');
    }

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

// Warms taskCache with every project's tasks in a single request. Kicked off
// once at page load (in parallel with loadProjects) so that rendering the
// project rows doesn't have to wait on one load_tasks.php round trip per row.
function warmTaskCache() {
    taskCachePromise = fetch('../../Models/php/load_tasks.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=load_all_tasks'
    })
    .then(response => response.json())
    .then(data => {
        taskCache = (data.success && data.tasksByProject) ? data.tasksByProject : {};
        return taskCache;
    })
    .catch(error => {
        console.error('Error warming task cache:', error);
        taskCache = {};
        return taskCache;
    });
    return taskCachePromise;
}

// Fetches tasks for a single project directly from the server, bypassing
// the cache, and updates the cache with the result. Use this after a
// mutation (add/edit/delete/status change) so stale cached data isn't shown.
function refreshTasksForProject(projectId) {
    return fetch('../../Models/php/load_tasks.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=load_tasks&project_id=${encodeURIComponent(projectId)}`
    })
    .then(response => response.json())
    .then(data => {
        const tasks = data.success ? (data.tasks || []) : [];
        taskCache[projectId] = tasks;
        return tasks;
    })
    .catch(error => {
        console.error('Error loading tasks:', error);
        return [];
    });
}

// Loads the tasks for a project, preferring the warmed cache so rendering
// many project rows doesn't fire one request per row.
function loadTasksForProject(projectId) {
    if (taskCache[projectId]) {
        return Promise.resolve(taskCache[projectId]);
    }
    if (taskCachePromise) {
        return taskCachePromise.then(() => taskCache[projectId] || []);
    }
    return refreshTasksForProject(projectId);
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

