<?php
session_start();
$currentUsername = $_SESSION['username'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Control Points - Survey Project Manager</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <link rel="stylesheet" href="../../Models/css/survey_projects_notes.css">
    <style>
        .cp-empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--gray-500);
        }
        .cp-empty-state i {
            font-size: 4rem;
            color: var(--gray-300);
            margin-bottom: 1rem;
        }
        .cp-filters {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
            margin-bottom: 1.25rem;
        }
        .cp-filters .form-select,
        .cp-filters .form-input {
            max-width: 260px;
        }
        .cp-project-group {
            margin-bottom: 1.75rem;
        }
        .cp-project-group-header {
            display: flex;
            align-items: baseline;
            gap: 0.6rem;
            padding-bottom: 0.4rem;
            margin-bottom: 0.75rem;
            border-bottom: 2px solid var(--gray-200, #e5e7eb);
        }
        .cp-project-group-header h3 {
            font-size: 1.05rem;
            color: var(--gray-800, #1f2937);
        }
        .cp-project-group-header .cp-project-id {
            font-family: monospace;
            font-size: 0.85rem;
            color: var(--gray-500);
        }
        .cp-quick-link {
            margin-left: auto;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            font-size: 0.78rem;
            font-weight: 600;
            color: #7c3aed;
            text-decoration: none;
            white-space: nowrap;
        }
        .cp-quick-link:hover { text-decoration: underline; }
        .cp-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.83rem;
        }
        .cp-table th {
            text-align: left;
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--gray-500);
            padding: 0.4rem 0.6rem;
            border-bottom: 1px solid var(--gray-200, #e5e7eb);
            white-space: nowrap;
        }
        .cp-table td {
            padding: 0.5rem 0.6rem;
            border-bottom: 1px solid var(--gray-100, #f3f4f6);
            vertical-align: top;
        }
        .cp-badge {
            display: inline-flex;
            align-items: center;
            font-size: 0.72rem;
            font-weight: 600;
            padding: 0.15rem 0.55rem;
            border-radius: 999px;
            white-space: nowrap;
        }
        .cp-type-Control        { background: #eff6ff; color: #1d4ed8; }
        .cp-type-Benchmark      { background: #f5f3ff; color: #6d28d9; }
        .cp-type-BoundaryCorner { background: #fff7ed; color: #c2410c; }
        .cp-type-GPSBase        { background: #ecfeff; color: #0e7490; }
        .cp-type-Other          { background: var(--gray-100, #f3f4f6); color: var(--gray-600, #4b5563); }
        .cp-status-Proposed { background: var(--gray-100, #f3f4f6); color: var(--gray-600, #4b5563); }
        .cp-status-Set       { background: #eff6ff; color: #1d4ed8; }
        .cp-status-Verified  { background: #ecfdf5; color: #047857; }
        .cp-status-Destroyed { background: #fef2f2; color: #b91c1c; }
        .cp-status-Lost      { background: #fff7ed; color: #c2410c; }
        .cp-session-link {
            color: #1d4ed8;
            text-decoration: none;
            font-size: 0.78rem;
            white-space: nowrap;
        }
        .cp-session-link:hover { text-decoration: underline; }
        .cp-modal-section {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--gray-400, #9ca3af);
            margin: 1.1rem 0 0.6rem;
            padding-bottom: 0.25rem;
            border-bottom: 1px solid var(--gray-200, #e5e7eb);
        }
        .cp-modal-section:first-child { margin-top: 0; }
        .cp-other-input { margin-top: 0.4rem; display: none; }
        .cp-other-input.visible { display: block; }
        .cp-hint {
            font-size: 0.75rem;
            color: var(--gray-400, #9ca3af);
            margin: -0.25rem 0 0.75rem;
        }
        .path-input-wrap { display: flex; gap: 0.4rem; }
        .path-input-wrap .form-input { flex: 1; }
        .form-row-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
        }
        @media (max-width: 640px) {
            .form-row-2 { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

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
                <a href="./survey_projects.php" class="nav-item">
                    <i class="fas fa-th-large"></i>
                    Dashboard
                </a>
                <a href="./survey_projects.php?view=todo" class="nav-item">
                    <i class="fas fa-star"></i>
                    My To-Do
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
                <a href="./control_points.php" class="nav-item active">
                    <i class="fas fa-crosshairs"></i>
                    Control Points
                </a>
                <a href="#" class="nav-item">
                    <i class="fas fa-cog"></i>
                    Settings
                </a>
            </nav>
        </div>
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
                    <h2>Control Points & Benchmarks</h2>
                    <p>Registry of project control — coordinates, monument type & where it came from</p>
                </div>
            </div>
            <div class="top-bar-right">
                <button class="btn btn-primary" onclick="openPointModal()">
                    <i class="fas fa-plus"></i> Add Control Point
                </button>
            </div>
        </div>

        <!-- Content Area -->
        <div class="content-area">
            <div class="cp-filters">
                <select class="form-select" id="projectFilter" onchange="onProjectFilterChange()">
                    <option value="">All Projects</option>
                </select>
                <select class="form-select" id="taskFilter" onchange="renderPoints()">
                    <option value="">All Tasks</option>
                </select>
                <select class="form-select" id="typeFilter" onchange="renderPoints()">
                    <option value="">All Types</option>
                </select>
                <select class="form-select" id="statusFilter" onchange="renderPoints()">
                    <option value="">All Statuses</option>
                </select>
                <input type="text" class="form-input" id="searchInput" placeholder="Search point #, name, monument..." oninput="renderPoints()">
            </div>

            <div id="pointsContainer">
                <div class="cp-empty-state">
                    <i class="fas fa-crosshairs"></i>
                    <h3>Loading control points...</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Add/Edit Control Point Modal -->
    <div class="checklist-modal" id="pointModal">
        <div class="checklist-modal-content" style="max-width: 640px; max-height: 92vh;">
            <div class="checklist-modal-header" style="background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%);">
                <div class="checklist-modal-header-top">
                    <h3><i class="fas fa-crosshairs"></i> <span id="pointModalTitle">Add Control Point</span></h3>
                    <button class="checklist-modal-close" onclick="closePointModal()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            <div class="checklist-modal-body" style="padding: 1.5rem; overflow-y: auto;">
                <form id="pointForm" onsubmit="return false;">
                    <input type="hidden" id="editPointId" value="">

                    <div class="cp-modal-section">Point</div>
                    <div class="form-row-2">
                        <div class="form-group">
                            <label class="form-label">Project *</label>
                            <select class="form-select" id="pointProject" onchange="onModalProjectChange()" required></select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Task set during (optional)</label>
                            <select class="form-select" id="pointTask" onchange="onModalTaskChange()">
                                <option value="">— None / Project-wide —</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row-2">
                        <div class="form-group">
                            <label class="form-label">Point Number *</label>
                            <input type="text" class="form-input" id="pointNumber" placeholder="e.g., 1, CP-1, TBM1" spellcheck="false">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Point Name</label>
                            <input type="text" class="form-input" id="pointName" placeholder="e.g., TBM at NE corner of slab">
                        </div>
                    </div>
                    <div class="form-row-2">
                        <div class="form-group">
                            <label class="form-label">Point Type *</label>
                            <select class="form-select" id="pointType"></select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Status *</label>
                            <select class="form-select" id="pointStatus"></select>
                        </div>
                    </div>

                    <div class="cp-modal-section">Coordinates</div>
                    <div class="form-row-2">
                        <div class="form-group">
                            <label class="form-label">Northing</label>
                            <input type="number" class="form-input" id="pointNorthing" step="any" placeholder="e.g., 7123456.789">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Easting</label>
                            <input type="number" class="form-input" id="pointEasting" step="any" placeholder="e.g., 3123456.789">
                        </div>
                    </div>
                    <div class="form-row-2">
                        <div class="form-group">
                            <label class="form-label">Elevation</label>
                            <input type="number" class="form-input" id="pointElevation" step="any" placeholder="e.g., 512.34">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Units</label>
                            <select class="form-select cp-geo-select" id="pointUnits"></select>
                            <input type="text" class="form-input cp-other-input" id="pointUnitsOther" placeholder="Custom units...">
                        </div>
                    </div>
                    <div class="form-row-2">
                        <div class="form-group">
                            <label class="form-label">Latitude</label>
                            <input type="number" class="form-input" id="pointLatitude" step="any" placeholder="optional">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Longitude</label>
                            <input type="number" class="form-input" id="pointLongitude" step="any" placeholder="optional">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Coordinate System</label>
                        <select class="form-select cp-geo-select" id="pointCoordSystem"></select>
                        <input type="text" class="form-input cp-other-input" id="pointCoordSystemOther" placeholder="Custom coordinate system...">
                    </div>
                    <div class="form-row-2">
                        <div class="form-group">
                            <label class="form-label">Datum / Epoch</label>
                            <select class="form-select cp-geo-select" id="pointDatum"></select>
                            <input type="text" class="form-input cp-other-input" id="pointDatumOther" placeholder="Custom datum/epoch...">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Geoid Model</label>
                            <select class="form-select cp-geo-select" id="pointGeoid"></select>
                            <input type="text" class="form-input cp-other-input" id="pointGeoidOther" placeholder="Custom geoid model...">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Vertical Datum</label>
                        <select class="form-select cp-geo-select" id="pointVDatum"></select>
                        <input type="text" class="form-input cp-other-input" id="pointVDatumOther" placeholder="Custom vertical datum...">
                    </div>

                    <div class="cp-modal-section">Monument</div>
                    <div class="form-row-2">
                        <div class="form-group">
                            <label class="form-label">Monument Type</label>
                            <input type="text" class="form-input" id="pointMonumentType" list="monumentTypeList" placeholder="e.g., 5/8&quot; Rebar w/ Cap">
                            <datalist id="monumentTypeList"></datalist>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Order / Class</label>
                            <input type="text" class="form-input" id="pointOrderClass" list="orderClassList" placeholder="e.g., Boundary Corner, 2nd Order">
                            <datalist id="orderClassList"></datalist>
                        </div>
                    </div>
                    <div class="form-row-2">
                        <div class="form-group">
                            <label class="form-label">Date Established</label>
                            <input type="date" class="form-input" id="pointDateEstablished">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Established By (crew)</label>
                            <select class="form-select" id="pointEstablishedBy"></select>
                        </div>
                    </div>

                    <div class="cp-modal-section">Source</div>
                    <div class="cp-hint">
                        <i class="fas fa-circle-info"></i> Link the QC session where this control was set or verified in the field —
                        the list narrows to the selected task's sessions once one is picked above.
                    </div>
                    <div class="form-group">
                        <label class="form-label">Source QC Session (optional)</label>
                        <select class="form-select" id="pointSession">
                            <option value="">— None —</option>
                        </select>
                    </div>

                    <div class="cp-modal-section">Reference</div>
                    <div class="form-group">
                        <label class="form-label">Photo / Reference Link</label>
                        <div class="path-input-wrap">
                            <input type="text" class="form-input" id="pointPhotoLink" placeholder="Photo, sketch, or OneDrive folder link" spellcheck="false">
                            <button type="button" class="btn btn-secondary btn-sm" onclick="openIfLink(document.getElementById('pointPhotoLink').value)" title="Open link">
                                <i class="fas fa-up-right-from-square"></i>
                            </button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Notes</label>
                        <textarea class="form-textarea" id="pointNotes" rows="3" placeholder="Anything future-you should know about this point..."></textarea>
                    </div>

                    <div class="form-actions" style="margin-top: 1rem; padding-top: 1rem;">
                        <button type="button" class="btn btn-secondary" onclick="closePointModal()">Cancel</button>
                        <button type="button" class="btn btn-primary" onclick="savePoint()">
                            <i class="fas fa-save"></i> Save Control Point
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
        const CP_API = '../../Models/php/control_points_api.php';
        const QC_API = '../../Models/php/field_data_qc_api.php';
        const PROJECTS_API = '../../Models/php/load_survey_project_notes.php';
        const TASKS_API = '../../Models/php/load_tasks.php';
        const OTHER_VALUE = '__other__';

        // Same curated geodetic pick lists as Field Data QC, kept in sync by hand —
        // this app has no shared JS module, each page owns its own copy.
        const COORD_SYSTEMS = [
            'NAD83(2011) TX State Plane North (4201)',
            'NAD83(2011) TX State Plane North Central (4202)',
            'NAD83(2011) TX State Plane Central (4203)',
            'NAD83(2011) TX State Plane South Central (4204)',
            'NAD83(2011) TX State Plane South (4205)',
            'NAD83(2011) UTM Zone 13N',
            'NAD83(2011) UTM Zone 14N',
            'NAD83(2011) UTM Zone 15N',
            'WGS84 (G2139)'
        ];
        const DATUM_EPOCHS = ['NAD83(2011) epoch 2010.00', 'WGS84 (G2139)'];
        const GEOID_MODELS = ['GEOID18', 'GEOID12B'];
        const VERTICAL_DATUMS = ['NAVD88'];
        const UNIT_OPTIONS = ['US Survey Foot', 'International Foot', 'Meter'];
        const MONUMENT_TYPE_SUGGESTIONS = [
            '5/8" Iron Rebar w/ Cap', '1/2" Iron Rebar w/ Cap', 'Iron Pipe',
            'PK Nail', 'Mag Nail', 'Chiseled Square', 'Chiseled X',
            'Concrete Monument', 'Nail & Shiner', 'GPS Base Station'
        ];
        const ORDER_CLASS_SUGGESTIONS = [
            'Boundary Corner', 'Primary Control', 'Secondary Control',
            '2nd Order', '3rd Order', 'Construction Control'
        ];

        let allProjects = [];
        let allPoints = [];
        let allCrews = [];
        let cpTypes = [];
        let cpStatuses = [];
        let projectTasksCache = {};    // project_id -> tasks[]
        let projectSessionsCache = {}; // project_id -> QC sessions[]

        document.addEventListener('DOMContentLoaded', function() {
            setupSidebar();
            setupGeoSelects();
            setupSuggestionLists();
            init();
        });

        async function init() {
            await Promise.all([loadProjects(), loadPoints(), loadCrews()]);
            populateCrewSelect('');

            const params = new URLSearchParams(location.search);
            const projectId = params.get('project_id') || '';
            const taskId = params.get('task_id') || '';
            const sessionId = params.get('session_id') || '';

            if (projectId) {
                document.getElementById('projectFilter').value = projectId;
                await onProjectFilterChange();
                if (taskId) document.getElementById('taskFilter').value = taskId;
            }
            renderPoints();

            if (params.get('action') === 'new' && projectId) {
                await openPointModal(null, { projectId, taskId, sessionId });
            }
        }

        function setupSidebar() {
            const sidebar = document.getElementById('sidebar');
            const toggleButton = document.getElementById('toggleBtn');
            const mainContent = document.querySelector('.main-content');
            const mobileToggle = document.getElementById('mobileToggle');

            toggleButton.addEventListener('click', () => {
                sidebar.classList.toggle('collapsed');
                mainContent.classList.toggle('collapsed');
            });

            mobileToggle.addEventListener('click', () => {
                sidebar.classList.toggle('collapsed');
            });
        }

        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('open');
        }

        function setupSuggestionLists() {
            document.getElementById('monumentTypeList').innerHTML =
                MONUMENT_TYPE_SUGGESTIONS.map(v => `<option value="${escapeHtml(v)}">`).join('');
            document.getElementById('orderClassList').innerHTML =
                ORDER_CLASS_SUGGESTIONS.map(v => `<option value="${escapeHtml(v)}">`).join('');
        }

        // ── Data loading ─────────────────────────────────────────────────────

        async function loadProjects() {
            try {
                const formData = new FormData();
                formData.append('action', 'load_project');
                const response = await fetch(PROJECTS_API, { method: 'POST', body: formData });
                const data = await response.json();
                if (data.success) {
                    allProjects = data.projects || [];
                    populateProjectSelects();
                }
            } catch (error) {
                console.error('Error loading projects:', error);
                showToast('Network error loading projects', 'error');
            }
        }

        async function loadPoints() {
            try {
                const formData = new FormData();
                formData.append('action', 'get_points');
                const response = await fetch(CP_API, { method: 'POST', body: formData });
                const data = await response.json();
                if (data.success) {
                    allPoints = data.points || [];
                    cpTypes = data.types || [];
                    cpStatuses = data.statuses || [];
                    populateTypeStatusSelects();
                } else {
                    showToast(data.message || 'Error loading control points', 'error');
                }
            } catch (error) {
                console.error('Error loading control points:', error);
                showToast('Network error loading control points', 'error');
            }
        }

        async function loadCrews() {
            try {
                const formData = new FormData();
                formData.append('action', 'get_crews');
                const response = await fetch(QC_API, { method: 'POST', body: formData });
                const data = await response.json();
                if (data.success) {
                    allCrews = data.crews || [];
                }
            } catch (error) {
                console.error('Error loading crews:', error);
            }
        }

        function crewLabel(crew) {
            return crew.name ? `${crew.initials} — ${crew.name}` : crew.initials;
        }

        function populateCrewSelect(selectedValue = '') {
            const sel = document.getElementById('pointEstablishedBy');
            sel.innerHTML = '<option value="">— Not set —</option>' +
                allCrews.map(c => `<option value="${escapeHtml(crewLabel(c))}">${escapeHtml(crewLabel(c))}</option>`).join('');
            if (selectedValue && ![...sel.options].some(o => o.value === selectedValue)) {
                sel.insertAdjacentHTML('beforeend',
                    `<option value="${escapeHtml(selectedValue)}">${escapeHtml(selectedValue)}</option>`);
            }
            sel.value = selectedValue;
        }

        function populateProjectSelects() {
            const filter = document.getElementById('projectFilter');
            const modal = document.getElementById('pointProject');
            const filterValue = filter.value;

            filter.innerHTML = '<option value="">All Projects</option>';
            modal.innerHTML = '<option value="">— Select Project —</option>';
            allProjects.forEach(p => {
                const label = `${p.projectId} — ${p.projectName || ''}`;
                filter.insertAdjacentHTML('beforeend',
                    `<option value="${escapeHtml(p.projectId)}">${escapeHtml(label)}</option>`);
                modal.insertAdjacentHTML('beforeend',
                    `<option value="${escapeHtml(p.projectId)}">${escapeHtml(label)}</option>`);
            });
            filter.value = filterValue;
        }

        function populateTypeStatusSelects() {
            const typeFilter = document.getElementById('typeFilter');
            const statusFilter = document.getElementById('statusFilter');
            const modalType = document.getElementById('pointType');
            const modalStatus = document.getElementById('pointStatus');

            typeFilter.innerHTML = '<option value="">All Types</option>' +
                cpTypes.map(t => `<option value="${escapeHtml(t)}">${escapeHtml(t)}</option>`).join('');
            statusFilter.innerHTML = '<option value="">All Statuses</option>' +
                cpStatuses.map(s => `<option value="${escapeHtml(s)}">${escapeHtml(s)}</option>`).join('');
            modalType.innerHTML = cpTypes.map(t => `<option value="${escapeHtml(t)}">${escapeHtml(t)}</option>`).join('');
            modalStatus.innerHTML = cpStatuses.map(s => `<option value="${escapeHtml(s)}">${escapeHtml(s)}</option>`).join('');
        }

        // ── Filters / Tasks ──────────────────────────────────────────────────

        async function fetchProjectTasks(projectId) {
            try {
                const response = await fetch(TASKS_API, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=load_tasks&project_id=${encodeURIComponent(projectId)}`
                });
                const data = await response.json();
                return data.success ? (data.tasks || []) : [];
            } catch (error) {
                console.error('Error loading tasks:', error);
                return [];
            }
        }

        async function onProjectFilterChange() {
            const projectId = document.getElementById('projectFilter').value;
            const taskFilter = document.getElementById('taskFilter');
            taskFilter.innerHTML = '<option value="">All Tasks</option>';
            if (projectId) {
                if (!projectTasksCache[projectId]) {
                    projectTasksCache[projectId] = await fetchProjectTasks(projectId);
                }
                projectTasksCache[projectId].forEach(t => {
                    taskFilter.insertAdjacentHTML('beforeend',
                        `<option value="${t.task_id}">${escapeHtml(t.task_name)}</option>`);
                });
            }
            renderPoints();
        }

        // ── Rendering ────────────────────────────────────────────────────────

        function renderPoints() {
            const container = document.getElementById('pointsContainer');
            const projectFilter = document.getElementById('projectFilter').value;
            const taskFilter = document.getElementById('taskFilter').value;
            const typeFilter = document.getElementById('typeFilter').value;
            const statusFilter = document.getElementById('statusFilter').value;
            const search = document.getElementById('searchInput').value.trim().toLowerCase();

            let points = allPoints;
            if (projectFilter) points = points.filter(p => p.project_id === projectFilter);
            if (taskFilter) points = points.filter(p => String(p.task_id || '') === taskFilter);
            if (typeFilter) points = points.filter(p => p.point_type === typeFilter);
            if (statusFilter) points = points.filter(p => p.status === statusFilter);
            if (search) {
                points = points.filter(p =>
                    [p.point_number, p.point_name, p.monument_type, p.notes, p.project_name, p.project_id]
                        .some(v => (v || '').toLowerCase().includes(search)));
            }

            if (points.length === 0) {
                container.innerHTML = `
                    <div class="cp-empty-state">
                        <i class="fas fa-crosshairs"></i>
                        <h3>No control points${projectFilter || taskFilter || typeFilter || statusFilter || search ? ' match your filters' : ' yet'}</h3>
                        <p>Log control set or verified in the field so it's on record for the next crew</p>
                    </div>`;
                return;
            }

            const groups = new Map();
            points.forEach(p => {
                if (!groups.has(p.project_id)) groups.set(p.project_id, []);
                groups.get(p.project_id).push(p);
            });

            let html = '';
            groups.forEach((groupPoints, projectId) => {
                const projectName = groupPoints[0].project_name || '';
                html += `
                    <div class="cp-project-group">
                        <div class="cp-project-group-header">
                            <h3>${escapeHtml(projectName)}</h3>
                            <span class="cp-project-id">${escapeHtml(projectId)}</span>
                            <a href="./field_data_qc.php?project_id=${encodeURIComponent(projectId)}" class="cp-quick-link"
                               title="Open this project's Field Data QC sessions">
                                <i class="fas fa-clipboard-list"></i> Field Data QC
                            </a>
                        </div>
                        ${createPointsTable(groupPoints)}
                    </div>`;
            });
            container.innerHTML = html;
        }

        function createPointsTable(points) {
            const rows = points.map(p => `
                <tr>
                    <td style="font-family:monospace; font-weight:700; white-space:nowrap;">${escapeHtml(p.point_number)}</td>
                    <td>${escapeHtml(p.point_name || '—')}</td>
                    <td><span class="cp-badge cp-type-${(p.point_type || 'Other').replace(/\s+/g, '')}">${escapeHtml(p.point_type)}</span></td>
                    <td><span class="cp-badge cp-status-${p.status}">${escapeHtml(p.status)}</span></td>
                    <td style="font-family:monospace; white-space:nowrap;">${fmtCoord(p.northing)}</td>
                    <td style="font-family:monospace; white-space:nowrap;">${fmtCoord(p.easting)}</td>
                    <td style="font-family:monospace; white-space:nowrap;">${fmtCoord(p.elevation)}</td>
                    <td>${escapeHtml(p.monument_type || '—')}</td>
                    <td>${escapeHtml(p.task_name || '—')}</td>
                    <td>${sourceSessionCell(p)}</td>
                    <td style="white-space:nowrap; text-align:right;">
                        <button class="btn btn-secondary btn-sm" onclick="openPointModal(${p.control_point_id})" title="Edit control point">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-danger-outline" onclick="deletePoint(${p.control_point_id})" title="Delete control point">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>`).join('');
            return `
                <div style="overflow-x:auto;">
                    <table class="cp-table">
                        <thead>
                            <tr>
                                <th>Point</th><th>Name</th><th>Type</th><th>Status</th>
                                <th>Northing</th><th>Easting</th><th>Elevation</th>
                                <th>Monument</th><th>Task</th><th>Source Session</th><th></th>
                            </tr>
                        </thead>
                        <tbody>${rows}</tbody>
                    </table>
                </div>`;
        }

        function fmtCoord(v) {
            return (v === null || v === undefined || v === '') ? '—' : Number(v).toString();
        }

        function sourceSessionCell(p) {
            if (!p.source_session_id) return '<span style="color:var(--gray-300, #d1d5db);">—</span>';
            const label = p.session_date ? p.session_date.substring(0, 10) : 'View session';
            return `<a class="cp-session-link" href="./field_data_qc.php?project_id=${encodeURIComponent(p.project_id)}&session_id=${p.source_session_id}"
                       title="Open this control point's source QC session">
                        <i class="fas fa-arrow-up-right-from-square" style="font-size:0.68rem;"></i> ${escapeHtml(label)}
                    </a>`;
        }

        // ── Add/Edit modal ───────────────────────────────────────────────────

        async function fetchProjectSessions(projectId) {
            try {
                const formData = new FormData();
                formData.append('action', 'get_sessions');
                formData.append('project_id', projectId);
                const response = await fetch(QC_API, { method: 'POST', body: formData });
                const data = await response.json();
                return data.success ? (data.sessions || []) : [];
            } catch (error) {
                console.error('Error loading QC sessions:', error);
                return [];
            }
        }

        async function populateSessionSelect(projectId, taskId, selectedSessionId = '') {
            const sel = document.getElementById('pointSession');
            sel.innerHTML = '<option value="">— None —</option>';
            if (!projectId) return;
            if (!projectSessionsCache[projectId]) {
                projectSessionsCache[projectId] = await fetchProjectSessions(projectId);
            }
            let sessions = projectSessionsCache[projectId];
            if (taskId) sessions = sessions.filter(s => String(s.task_id || '') === String(taskId));
            sessions.forEach(s => {
                const label = `${s.collection_date || 'No date'} — ${s.field_crew || 'Unknown crew'}${s.task_name ? ' — ' + s.task_name : ''}`;
                sel.insertAdjacentHTML('beforeend', `<option value="${s.session_id}">${escapeHtml(label)}</option>`);
            });
            if (selectedSessionId) sel.value = String(selectedSessionId);
        }

        async function populateModalTaskSelect(projectId, selectedTaskId = '') {
            const sel = document.getElementById('pointTask');
            sel.innerHTML = '<option value="">— None / Project-wide —</option>';
            if (!projectId) return;
            if (!projectTasksCache[projectId]) {
                projectTasksCache[projectId] = await fetchProjectTasks(projectId);
            }
            projectTasksCache[projectId].forEach(t => {
                sel.insertAdjacentHTML('beforeend', `<option value="${t.task_id}">${escapeHtml(t.task_name)}</option>`);
            });
            if (selectedTaskId) sel.value = String(selectedTaskId);
        }

        async function onModalProjectChange(selectedTaskId = '') {
            const projectId = document.getElementById('pointProject').value;
            await populateModalTaskSelect(projectId, selectedTaskId);
            await populateSessionSelect(projectId, selectedTaskId, '');
        }

        async function onModalTaskChange() {
            const projectId = document.getElementById('pointProject').value;
            const taskId = document.getElementById('pointTask').value;
            await populateSessionSelect(projectId, taskId, '');
        }

        async function openPointModal(pointId = null, prefill = {}) {
            const form = document.getElementById('pointForm');
            form.reset();
            document.querySelectorAll('#pointModal .cp-other-input').forEach(i => i.classList.remove('visible'));
            document.getElementById('editPointId').value = pointId || '';
            document.getElementById('pointModalTitle').textContent = pointId ? 'Edit Control Point' : 'Add Control Point';

            if (pointId) {
                const p = allPoints.find(x => x.control_point_id === pointId);
                if (!p) return;
                document.getElementById('pointProject').value = p.project_id;
                await onModalProjectChange(p.task_id || '');
                document.getElementById('pointNumber').value = p.point_number || '';
                document.getElementById('pointName').value = p.point_name || '';
                document.getElementById('pointType').value = p.point_type || 'Control';
                document.getElementById('pointStatus').value = p.status || 'Set';
                document.getElementById('pointNorthing').value = p.northing ?? '';
                document.getElementById('pointEasting').value = p.easting ?? '';
                document.getElementById('pointElevation').value = p.elevation ?? '';
                document.getElementById('pointLatitude').value = p.latitude ?? '';
                document.getElementById('pointLongitude').value = p.longitude ?? '';
                setGeoValue('pointCoordSystem', p.coordinate_system);
                setGeoValue('pointDatum', p.datum_epoch);
                setGeoValue('pointGeoid', p.geoid_model);
                setGeoValue('pointVDatum', p.vertical_datum);
                setGeoValue('pointUnits', p.units);
                document.getElementById('pointMonumentType').value = p.monument_type || '';
                document.getElementById('pointOrderClass').value = p.order_class || '';
                document.getElementById('pointDateEstablished').value = p.date_established || '';
                populateCrewSelect(p.established_by || '');
                document.getElementById('pointPhotoLink').value = p.photo_link || '';
                document.getElementById('pointNotes').value = p.notes || '';
                await populateSessionSelect(p.project_id, p.task_id || '', p.source_session_id || '');
            } else {
                populateCrewSelect('');
                const projectId = prefill.projectId || document.getElementById('projectFilter').value;
                if (projectId) {
                    document.getElementById('pointProject').value = projectId;
                    await onModalProjectChange(prefill.taskId || '');
                }
                if (prefill.sessionId) {
                    await populateSessionSelect(projectId, prefill.taskId || '', prefill.sessionId);
                    document.getElementById('pointStatus').value = 'Set';
                    document.getElementById('pointDateEstablished').value = new Date().toISOString().slice(0, 10);
                }
            }
            document.getElementById('pointModal').classList.add('active');
        }

        function closePointModal() {
            document.getElementById('pointModal').classList.remove('active');
        }

        async function savePoint() {
            const pointId = document.getElementById('editPointId').value;
            const projectId = document.getElementById('pointProject').value;
            const pointNumber = document.getElementById('pointNumber').value.trim();

            if (!projectId) {
                showToast('Project is required', 'error');
                return;
            }
            if (!pointNumber) {
                showToast('Point number is required', 'error');
                return;
            }

            const formData = new FormData();
            formData.append('action', pointId ? 'update_point' : 'add_point');
            if (pointId) formData.append('control_point_id', pointId);
            formData.append('project_id', projectId);
            formData.append('task_id', document.getElementById('pointTask').value || '0');
            formData.append('source_session_id', document.getElementById('pointSession').value || '0');
            formData.append('point_number', pointNumber);
            formData.append('point_name', document.getElementById('pointName').value.trim());
            formData.append('point_type', document.getElementById('pointType').value);
            formData.append('status', document.getElementById('pointStatus').value);
            formData.append('northing', document.getElementById('pointNorthing').value.trim());
            formData.append('easting', document.getElementById('pointEasting').value.trim());
            formData.append('elevation', document.getElementById('pointElevation').value.trim());
            formData.append('latitude', document.getElementById('pointLatitude').value.trim());
            formData.append('longitude', document.getElementById('pointLongitude').value.trim());
            formData.append('coordinate_system', getGeoValue('pointCoordSystem'));
            formData.append('datum_epoch', getGeoValue('pointDatum'));
            formData.append('geoid_model', getGeoValue('pointGeoid'));
            formData.append('vertical_datum', getGeoValue('pointVDatum'));
            formData.append('units', getGeoValue('pointUnits'));
            formData.append('monument_type', document.getElementById('pointMonumentType').value.trim());
            formData.append('order_class', document.getElementById('pointOrderClass').value.trim());
            formData.append('date_established', document.getElementById('pointDateEstablished').value);
            formData.append('established_by', document.getElementById('pointEstablishedBy').value);
            formData.append('photo_link', document.getElementById('pointPhotoLink').value.trim());
            formData.append('notes', document.getElementById('pointNotes').value.trim());

            try {
                const response = await fetch(CP_API, { method: 'POST', body: formData });
                const data = await response.json();
                if (data.success) {
                    closePointModal();
                    await loadPoints();
                    renderPoints();
                    showToast(pointId ? 'Control point updated' : 'Control point added');
                } else {
                    showToast(data.message || 'Error saving control point', 'error');
                }
            } catch (error) {
                console.error('Error saving control point:', error);
                showToast('Network error saving control point', 'error');
            }
        }

        async function deletePoint(controlPointId) {
            if (!confirm('Delete this control point? This cannot be undone.')) return;
            try {
                const formData = new FormData();
                formData.append('action', 'delete_point');
                formData.append('control_point_id', controlPointId);
                const response = await fetch(CP_API, { method: 'POST', body: formData });
                const data = await response.json();
                if (data.success) {
                    await loadPoints();
                    renderPoints();
                    showToast('Control point deleted');
                } else {
                    showToast(data.message || 'Error deleting control point', 'error');
                }
            } catch (error) {
                console.error('Error deleting control point:', error);
                showToast('Network error deleting control point', 'error');
            }
        }

        // ── Geodetic "Other…" select helpers (same pattern as Field Data QC) ───

        function setupGeoSelects() {
            fillGeoSelect('pointCoordSystem', COORD_SYSTEMS);
            fillGeoSelect('pointDatum', DATUM_EPOCHS);
            fillGeoSelect('pointGeoid', GEOID_MODELS);
            fillGeoSelect('pointVDatum', VERTICAL_DATUMS);
            fillGeoSelect('pointUnits', UNIT_OPTIONS);

            document.querySelectorAll('.cp-geo-select').forEach(sel => {
                sel.addEventListener('change', () => handleOtherSelect(sel));
            });
        }

        function fillGeoSelect(selectId, options) {
            const sel = document.getElementById(selectId);
            sel.innerHTML = '<option value="">— Not set —</option>' +
                options.map(o => `<option value="${escapeHtml(o)}">${escapeHtml(o)}</option>`).join('') +
                `<option value="${OTHER_VALUE}">Other…</option>`;
        }

        function handleOtherSelect(sel) {
            const otherInput = document.getElementById(sel.id + 'Other');
            if (!otherInput) return;
            otherInput.classList.toggle('visible', sel.value === OTHER_VALUE);
            if (sel.value !== OTHER_VALUE) otherInput.value = '';
        }

        function setGeoValue(selectId, value) {
            const sel = document.getElementById(selectId);
            const otherInput = document.getElementById(selectId + 'Other');
            value = value || '';
            const inList = [...sel.options].some(o => o.value === value && o.value !== OTHER_VALUE);
            if (value === '' || inList) {
                sel.value = value;
                otherInput.value = '';
                otherInput.classList.remove('visible');
            } else {
                sel.value = OTHER_VALUE;
                otherInput.value = value;
                otherInput.classList.add('visible');
            }
        }

        function getGeoValue(selectId) {
            const sel = document.getElementById(selectId);
            if (sel.value === OTHER_VALUE) {
                return document.getElementById(selectId + 'Other').value.trim();
            }
            return sel.value;
        }

        // ── Utilities ────────────────────────────────────────────────────────

        function escapeHtml(text) {
            if (text === null || text === undefined) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function isLikelyUrl(path) {
            return /^https?:\/\//i.test((path || '').trim());
        }

        function openIfLink(path) {
            path = (path || '').trim();
            if (!path) {
                showToast('No link to open', 'error');
                return;
            }
            if (!isLikelyUrl(path)) {
                showToast('Not a link — this looks like a local file path', 'error');
                return;
            }
            window.open(path, '_blank', 'noopener,noreferrer');
        }

        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            const toastMessage = document.getElementById('toastMessage');
            const toastIcon = document.querySelector('.toast-icon');

            toastMessage.textContent = message;

            if (type === 'error') {
                toastIcon.className = 'fas fa-exclamation-circle toast-icon';
                toast.style.borderLeftColor = 'var(--danger-color)';
                toastIcon.style.color = 'var(--danger-color)';
            } else {
                toastIcon.className = 'fas fa-check-circle toast-icon';
                toast.style.borderLeftColor = 'var(--success-color)';
                toastIcon.style.color = 'var(--success-color)';
            }

            toast.classList.add('show');
            setTimeout(() => toast.classList.remove('show'), 3000);
        }
    </script>
</body>
</html>
