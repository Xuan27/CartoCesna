// Point-range viewer for survey_projects.php: the per-task point-range
// badge and its read-only detail modal (job file / point numbers / QC
// status). Self-contained - no dependency on other page modules.

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

