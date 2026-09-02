// Weekly timesheet modal for survey_projects.php: week navigation,
// inline editing of phase/project/notes/hours per cell, and the
// Vantagepoint clipboard export.
//
// Depends on globals defined by survey_projects.php's main script and by
// timer.js (both loaded before this file): showToast, esc, roundToHalf,
// TIME_API.

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

// Close timesheet modal on outside click
document.addEventListener('click', function(e) {
    if (e.target.id === 'timesheetModal') {
        closeTimesheetModal();
    }
});

