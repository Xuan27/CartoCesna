// Timer / time-tracking core logic for survey_projects.php: the timer
// state object, active-timer persistence, the play/stop buttons on each
// task, the stop-timer modal, and the category (Admin/Training) timers.
//
// Depends on globals defined by survey_projects.php's main script (loaded
// before this file): showToast.
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

