// Checklist integration for survey_projects.php: per-task checklist badges,
// the checklist side panel, and the template picker.
//
// Depends on globals defined by survey_projects.php's main script (loaded
// before this file): CURRENT_USER, showToast, taskCache, loadTasksForProject.
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
                    data-task-id="${task.task_id}"
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
                        data-task-id="${task.task_id}"
                        onclick="event.stopPropagation(); showTemplatePicker(${task.task_id}, '${(task.task_name || '').replace(/'/g, "\\'")}')"
                        title="Select a checklist template">
                    <i class="fas fa-clipboard-list"></i> Select Checklist
                </button>
            `;
        }
        return `
            <button class="checklist-btn checklist-not-started"
                    data-task-id="${task.task_id}"
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
        html += `<div class="checklist-category-header">${esc(category)}</div>`;
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
        ? `<div class="checklist-item-meta">${esc(item.completed_by)}${completedDateStr ? ' - ' + completedDateStr : ''}</div>`
        : '';
    const newStatusOnCheck = isCompleted ? 'unchecked' : 'completed';
    const newStatusOnNa   = isNa ? 'unchecked' : 'na';
    return `
        <label class="checklist-item ${doneClass} ${childClass}">
            <input type="checkbox" ${isCompleted ? 'checked' : ''}
                   onchange="setChecklistItemStatus(${currentChecklistTaskId}, ${item.item_id}, '${newStatusOnCheck}')">
            <div style="flex:1;">
                <div class="checklist-item-label">${esc(item.item_text)}${naLabel}</div>
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
                <span class="checklist-item-label">${esc(item.item_text)}</span>
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

// Finds a task object by ID across the warmed task cache, so a checklist
// button can be re-rendered without a fresh server round trip.
function findCachedTask(taskId) {
    for (const projectId in taskCache) {
        const found = (taskCache[projectId] || []).find(t => String(t.task_id) === String(taskId));
        if (found) return found;
    }
    return null;
}

// Swaps each task's checklist placeholder/button in the DOM for a freshly
// rendered one now that checklistSummaries has data for it.
function updateChecklistButtonsForTaskIds(taskIds) {
    taskIds.forEach(taskId => {
        const task = findCachedTask(taskId);
        if (!task) return;
        document.querySelectorAll(`.checklist-btn[data-task-id="${taskId}"], .checklist-btn-placeholder[data-task-id="${taskId}"]`)
            .forEach(el => { el.outerHTML = renderChecklistButton(task); });
    });
}

// Refresh checklist button displays after changes
async function refreshChecklistButtons() {
    const taskIds = Object.keys(checklistSummaries);
    if (taskIds.length > 0) {
        await loadChecklistSummaries(taskIds);
        updateChecklistButtonsForTaskIds(taskIds);
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
                <h4>${esc(t.template_name)}</h4>
                ${t.description ? `<p>${esc(t.description)}</p>` : ''}
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

// Hook into task loading to fetch checklist summaries
const _originalLoadTasksForProject = typeof loadTasksForProject === 'function' ? loadTasksForProject : null;

if (_originalLoadTasksForProject) {
    const origFn = loadTasksForProject;
    loadTasksForProject = async function(projectId) {
        const tasks = await origFn(projectId);
        if (tasks && tasks.length > 0) {
            const ids = tasks.map(t => t.task_id);
            // Don't hold up rendering the task list (which is already
            // loaded) just to wait on checklist badges - but once the
            // summaries do arrive, swap the placeholders for real buttons.
            loadChecklistSummaries(ids).then(() => updateChecklistButtonsForTaskIds(ids));
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
            updateChecklistButtonsForTaskIds(ids);
        }
    }, 2000);
});

// Close the template picker on outside click
// (the checklist side panel has no backdrop, so it's only closed via its own close button)
document.addEventListener('click', function(e) {
    if (e.target.id === 'templatePickerModal') {
        closeTemplatePicker();
    }
});
