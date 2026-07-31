<?php
session_start();
$currentUsername = $_SESSION['username'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Field Data QC - Survey Project Manager</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <link rel="stylesheet" href="../../Models/css/survey_projects_notes.css">
    <style>
        .qc-empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--gray-500);
        }
        .qc-empty-state i {
            font-size: 4rem;
            color: var(--gray-300);
            margin-bottom: 1rem;
        }
        .qc-filters {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
            margin-bottom: 1.25rem;
        }
        .qc-filters .form-select,
        .qc-filters .form-input {
            max-width: 320px;
        }
        .qc-project-group {
            margin-bottom: 1.75rem;
        }
        .qc-project-group-header {
            display: flex;
            align-items: baseline;
            gap: 0.6rem;
            padding-bottom: 0.4rem;
            margin-bottom: 0.75rem;
            border-bottom: 2px solid var(--gray-200, #e5e7eb);
        }
        .qc-project-group-header h3 {
            font-size: 1.05rem;
            color: var(--gray-800, #1f2937);
        }
        .qc-project-group-header .qc-project-id {
            font-family: monospace;
            font-size: 0.85rem;
            color: var(--gray-500);
        }
        .qc-card {
            background: white;
            border: 1px solid var(--gray-200, #e5e7eb);
            border-radius: 10px;
            margin-bottom: 0.75rem;
            overflow: hidden;
        }
        .qc-card-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            flex-wrap: wrap;
            padding: 0.85rem 1rem;
            cursor: pointer;
        }
        .qc-card-header:hover {
            background: var(--gray-50, #f9fafb);
        }
        .qc-jobfile {
            font-weight: 700;
            font-size: 1rem;
            color: var(--gray-800, #1f2937);
        }
        .qc-card-date {
            font-size: 0.8rem;
            color: var(--gray-500);
        }
        .qc-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            font-size: 0.72rem;
            padding: 0.15rem 0.5rem;
            border-radius: 999px;
            background: var(--gray-100, #f3f4f6);
            color: var(--gray-600, #4b5563);
            white-space: nowrap;
        }
        .qc-chip i { font-size: 0.65rem; }
        .qc-stage-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.2rem 0.6rem;
            border-radius: 999px;
            background: #eff6ff;
            color: #1d4ed8;
        }
        .qc-stage-pill.complete {
            background: #ecfdf5;
            color: #047857;
        }
        .qc-findings-badge {
            font-size: 0.72rem;
            font-weight: 600;
            padding: 0.15rem 0.55rem;
            border-radius: 999px;
        }
        .qc-card-spacer { flex: 1; }
        .qc-card-body {
            display: none;
            border-top: 1px solid var(--gray-200, #e5e7eb);
            padding: 1rem;
        }
        .qc-card.expanded .qc-card-body { display: block; }
        .qc-detail-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.25rem;
            margin-bottom: 1.25rem;
        }
        @media (max-width: 900px) {
            .qc-detail-grid { grid-template-columns: 1fr; }
        }
        .qc-panel {
            background: var(--gray-50, #f9fafb);
            border: 1px solid var(--gray-200, #e5e7eb);
            border-radius: 8px;
            padding: 0.85rem 1rem;
        }
        .qc-panel h4 {
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--gray-500);
            margin-bottom: 0.6rem;
        }
        .qc-stage-row {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            padding: 0.4rem 0.5rem;
            border-radius: 6px;
            cursor: pointer;
            user-select: none;
        }
        .qc-stage-row:hover { background: #eff6ff; }
        .qc-stage-row .qc-stage-check {
            width: 1.15rem;
            height: 1.15rem;
            border-radius: 50%;
            border: 2px solid var(--gray-300, #d1d5db);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.6rem;
            color: white;
            flex-shrink: 0;
        }
        .qc-stage-row.done .qc-stage-check {
            background: var(--success-color, #10b981);
            border-color: var(--success-color, #10b981);
        }
        .qc-stage-row.done .qc-stage-label {
            color: var(--gray-500);
            text-decoration: line-through;
        }
        .qc-stage-label { font-size: 0.85rem; }
        .qc-stage-meta {
            margin-left: auto;
            font-size: 0.7rem;
            color: var(--gray-400, #9ca3af);
            white-space: nowrap;
        }
        .qc-geo-rows { display: grid; gap: 0.35rem; }
        .qc-geo-row {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            font-size: 0.83rem;
        }
        .qc-geo-row .k { color: var(--gray-500); flex-shrink: 0; }
        .qc-geo-row .v { text-align: right; word-break: break-word; }
        .qc-path-row {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: #fffbeb;
            border: 1px solid #fde68a;
            border-radius: 8px;
            padding: 0.5rem 0.75rem;
            margin-bottom: 1.25rem;
            font-family: monospace;
            font-size: 0.8rem;
            word-break: break-all;
        }
        .qc-path-row i.fa-folder-open { color: #d97706; flex-shrink: 0; }
        .qc-path-row .copy-btn {
            margin-left: auto;
            border: none;
            background: none;
            color: #d97706;
            cursor: pointer;
            flex-shrink: 0;
        }
        .qc-findings-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.83rem;
        }
        .qc-findings-table th {
            text-align: left;
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--gray-500);
            padding: 0.4rem 0.6rem;
            border-bottom: 1px solid var(--gray-200, #e5e7eb);
        }
        .qc-findings-table td {
            padding: 0.5rem 0.6rem;
            border-bottom: 1px solid var(--gray-100, #f3f4f6);
            vertical-align: top;
        }
        .qc-sev {
            font-size: 0.7rem;
            font-weight: 700;
            padding: 0.1rem 0.5rem;
            border-radius: 999px;
            white-space: nowrap;
        }
        .qc-sev-Critical { background: #fef2f2; color: #b91c1c; }
        .qc-sev-Major    { background: #fff7ed; color: #c2410c; }
        .qc-sev-Minor    { background: #eff6ff; color: #1d4ed8; }
        .qc-sev-Info     { background: var(--gray-100, #f3f4f6); color: var(--gray-600, #4b5563); }
        .qc-status-Open     { color: #b91c1c; font-weight: 600; }
        .qc-status-Resolved { color: #047857; font-weight: 600; }
        .qc-status-Waived   { color: var(--gray-500); font-weight: 600; }
        .qc-notes-block {
            margin-top: 1.25rem;
            font-size: 0.85rem;
            color: var(--gray-600, #4b5563);
            white-space: pre-wrap;
        }
        .qc-section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin: 1rem 0 0.5rem;
        }
        .qc-section-header h4 {
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--gray-500);
        }
        .qc-card-actions {
            display: flex;
            gap: 0.5rem;
            justify-content: flex-end;
            margin-top: 1.25rem;
            padding-top: 0.75rem;
            border-top: 1px solid var(--gray-100, #f3f4f6);
        }
        .btn-danger-outline {
            background: white;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }
        .btn-danger-outline:hover { background: #fef2f2; }
        .form-row-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
        }
        @media (max-width: 640px) {
            .form-row-2 { grid-template-columns: 1fr; }
        }
        .qc-other-input { margin-top: 0.4rem; display: none; }
        .qc-other-input.visible { display: block; }
        .qc-modal-section {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--gray-400, #9ca3af);
            margin: 1.1rem 0 0.6rem;
            padding-bottom: 0.25rem;
            border-bottom: 1px solid var(--gray-200, #e5e7eb);
        }
        .auto-filled { background: #fefce8 !important; }
        .path-input-wrap { display: flex; gap: 0.4rem; }
        .path-input-wrap .form-input { flex: 1; }
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
                <a href="./tools.php" class="nav-item">
                    <i class="fas fa-tools"></i>
                    Tools
                </a>
                <a href="./field_data_qc.php" class="nav-item active">
                    <i class="fas fa-clipboard-list"></i>
                    Field Data QC
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
                    <h2>Field Data QA/QC</h2>
                    <p>Raw field data review, geodetic settings & error log per job file</p>
                </div>
            </div>
            <div class="top-bar-right">
                <button class="btn btn-primary" onclick="openSessionModal()">
                    <i class="fas fa-plus"></i> New QC Session
                </button>
            </div>
        </div>

        <!-- Content Area -->
        <div class="content-area">
            <div class="qc-filters">
                <select class="form-select" id="projectFilter" onchange="renderSessions()">
                    <option value="">All Projects</option>
                </select>
                <input type="text" class="form-input" id="searchInput" placeholder="Search job file, crew, instrument, notes..." oninput="renderSessions()">
            </div>

            <div id="sessionsContainer">
                <div class="qc-empty-state">
                    <i class="fas fa-clipboard-list"></i>
                    <h3>Loading QC sessions...</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Session Create/Edit Modal -->
    <div class="checklist-modal" id="sessionModal">
        <div class="checklist-modal-content" style="max-width: 720px; max-height: 92vh;">
            <div class="checklist-modal-header" style="background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);">
                <div class="checklist-modal-header-top">
                    <h3><i class="fas fa-clipboard-list"></i> <span id="sessionModalTitle">New QC Session</span></h3>
                    <button class="checklist-modal-close" onclick="closeSessionModal()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            <div class="checklist-modal-body" style="padding: 1.5rem; overflow-y: auto;">
                <form id="sessionForm" onsubmit="return false;">
                    <input type="hidden" id="editSessionId" value="">

                    <div class="qc-modal-section">Session</div>
                    <div class="form-row-2">
                        <div class="form-group">
                            <label class="form-label">Project *</label>
                            <select class="form-select" id="sessionProject" onchange="onProjectChange()" required></select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Task (optional)</label>
                            <select class="form-select" id="sessionTask">
                                <option value="">— None —</option>
                            </select>
                        </div>
                    </div>
                    <div style="font-size:0.75rem; color:var(--gray-400, #9ca3af); margin:-0.25rem 0 0.75rem;">
                        <i class="fas fa-circle-info"></i> Job files &amp; point numbers used are recorded per task on the
                        <a href="./tools.php" target="_blank">Tools</a> page and appear automatically in the session detail.
                    </div>
                    <div class="form-row-2">
                        <div class="form-group">
                            <label class="form-label">Collection Date</label>
                            <input type="date" class="form-input" id="sessionDate">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Field Crew</label>
                            <input type="text" class="form-input" id="sessionCrew" placeholder="e.g., J. Martinez / D. Smith">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Raw Data Path</label>
                        <div class="path-input-wrap">
                            <input type="text" class="form-input" id="sessionRawPath" placeholder="N:\0012345.00\05 Service Groups\Survey\Downloads" spellcheck="false">
                            <button type="button" class="btn btn-secondary btn-sm" onclick="copyPath(document.getElementById('sessionRawPath').value)" title="Copy path">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Instrument / Equipment</label>
                        <input type="text" class="form-input" id="sessionInstrument" placeholder="e.g., Trimble R12i + TSC7">
                    </div>

                    <div class="qc-modal-section">Geodetic Settings</div>
                    <div class="form-group">
                        <label class="form-label">Coordinate System</label>
                        <select class="form-select qc-geo-select" id="sessionCoordSystem"></select>
                        <input type="text" class="form-input qc-other-input" id="sessionCoordSystemOther" placeholder="Custom coordinate system...">
                    </div>
                    <div class="form-row-2">
                        <div class="form-group">
                            <label class="form-label">Datum / Epoch</label>
                            <select class="form-select qc-geo-select" id="sessionDatum"></select>
                            <input type="text" class="form-input qc-other-input" id="sessionDatumOther" placeholder="Custom datum/epoch...">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Geoid Model</label>
                            <select class="form-select qc-geo-select" id="sessionGeoid"></select>
                            <input type="text" class="form-input qc-other-input" id="sessionGeoidOther" placeholder="Custom geoid model...">
                        </div>
                    </div>
                    <div class="form-row-2">
                        <div class="form-group">
                            <label class="form-label">Vertical Datum</label>
                            <select class="form-select qc-geo-select" id="sessionVDatum"></select>
                            <input type="text" class="form-input qc-other-input" id="sessionVDatumOther" placeholder="Custom vertical datum...">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Units</label>
                            <select class="form-select qc-geo-select" id="sessionUnits"></select>
                            <input type="text" class="form-input qc-other-input" id="sessionUnitsOther" placeholder="Custom units...">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Combined Scale Factor</label>
                        <input type="number" class="form-input" id="sessionScaleFactor" step="any" placeholder="e.g., 0.9998675432">
                    </div>

                    <div class="qc-modal-section">Notes</div>
                    <div class="form-group">
                        <textarea class="form-textarea" id="sessionNotes" rows="3" placeholder="General notes about this data set (what was surveyed, control used, anything future-you should know)..."></textarea>
                    </div>

                    <div class="form-actions" style="margin-top: 1rem; padding-top: 1rem;">
                        <button type="button" class="btn btn-secondary" onclick="closeSessionModal()">Cancel</button>
                        <button type="button" class="btn btn-primary" onclick="saveSession()">
                            <i class="fas fa-save"></i> Save Session
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Finding Create/Edit Modal -->
    <div class="checklist-modal" id="findingModal">
        <div class="checklist-modal-content" style="max-width: 560px; max-height: 92vh;">
            <div class="checklist-modal-header" style="background: linear-gradient(135deg, #b91c1c 0%, #991b1b 100%);">
                <div class="checklist-modal-header-top">
                    <h3><i class="fas fa-triangle-exclamation"></i> <span id="findingModalTitle">Log Finding</span></h3>
                    <button class="checklist-modal-close" onclick="closeFindingModal()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            <div class="checklist-modal-body" style="padding: 1.5rem; overflow-y: auto;">
                <form id="findingForm" onsubmit="return false;">
                    <input type="hidden" id="editFindingId" value="">
                    <input type="hidden" id="findingSessionId" value="">

                    <div class="form-row-2">
                        <div class="form-group">
                            <label class="form-label">Point Number(s)</label>
                            <input type="text" class="form-input" id="findingPoints" placeholder="e.g., 101, 205-210">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Category *</label>
                            <select class="form-select" id="findingCategory"></select>
                            <input type="text" class="form-input qc-other-input" id="findingCategoryOther" placeholder="Custom category (max 50 chars)..." maxlength="50">
                        </div>
                    </div>
                    <div class="form-row-2">
                        <div class="form-group">
                            <label class="form-label">Severity *</label>
                            <select class="form-select" id="findingSeverity"></select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Status *</label>
                            <select class="form-select" id="findingStatus"></select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Description (what was wrong)</label>
                        <textarea class="form-textarea" id="findingDescription" rows="3" placeholder="e.g., Shots 205-210 taken with wrong rod height (2.000 m entered instead of 6.5617 ft)..."></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Resolution (what was done)</label>
                        <textarea class="form-textarea" id="findingResolution" rows="3" placeholder="e.g., Corrected HR in TBC, recomputed, elevations now check within 0.05 ft of control..."></textarea>
                    </div>

                    <div class="form-actions" style="margin-top: 1rem; padding-top: 1rem;">
                        <button type="button" class="btn btn-secondary" onclick="closeFindingModal()">Cancel</button>
                        <button type="button" class="btn btn-primary" onclick="saveFinding()">
                            <i class="fas fa-save"></i> Save Finding
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
        const QC_API = '../../Models/php/field_data_qc_api.php';
        const PROJECTS_API = '../../Models/php/load_survey_project_notes.php';
        const TASKS_API = '../../Models/php/load_tasks.php';
        const OTHER_VALUE = '__other__';

        // Curated geodetic pick lists — anything not covered goes through the "Other…" escape
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

        let allProjects = [];
        let allSessions = [];
        let qcStages = {};        // {stage_key: label} from the server
        let qcCategories = [];
        let qcSeverities = [];
        let qcStatuses = [];
        let expandedSessionId = null;
        let findingsCache = {};   // session_id -> findings[]

        document.addEventListener('DOMContentLoaded', function() {
            setupSidebar();
            setupGeoSelects();
            setupPathAutoSuggest();
            init();
        });

        async function init() {
            await Promise.all([loadProjects(), loadSessions()]);
            const deepLinkProject = new URLSearchParams(location.search).get('project_id');
            if (deepLinkProject) {
                document.getElementById('projectFilter').value = deepLinkProject;
            }
            renderSessions();
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

        async function loadSessions() {
            try {
                const formData = new FormData();
                formData.append('action', 'get_sessions');
                const response = await fetch(QC_API, { method: 'POST', body: formData });
                const data = await response.json();
                if (data.success) {
                    allSessions = data.sessions || [];
                    qcStages = data.stages || {};
                    qcCategories = data.categories || [];
                    qcSeverities = data.severities || [];
                    qcStatuses = data.statuses || [];
                } else {
                    showToast(data.message || 'Error loading QC sessions', 'error');
                }
            } catch (error) {
                console.error('Error loading sessions:', error);
                showToast('Network error loading QC sessions', 'error');
            }
        }

        async function loadFindings(sessionId) {
            try {
                const formData = new FormData();
                formData.append('action', 'get_findings');
                formData.append('session_id', sessionId);
                const response = await fetch(QC_API, { method: 'POST', body: formData });
                const data = await response.json();
                if (data.success) {
                    findingsCache[sessionId] = data.findings || [];
                }
            } catch (error) {
                console.error('Error loading findings:', error);
            }
        }

        function populateProjectSelects() {
            const filter = document.getElementById('projectFilter');
            const modal = document.getElementById('sessionProject');
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

        // ── Rendering ────────────────────────────────────────────────────────

        function renderSessions() {
            const container = document.getElementById('sessionsContainer');
            const projectFilter = document.getElementById('projectFilter').value;
            const search = document.getElementById('searchInput').value.trim().toLowerCase();

            let sessions = allSessions;
            if (projectFilter) {
                sessions = sessions.filter(s => s.project_id === projectFilter);
            }
            if (search) {
                sessions = sessions.filter(s => {
                    const jobFiles = (s.point_range_entries || []).map(e => e.job_file_name).join(' ');
                    return [jobFiles, s.task_name, s.field_crew, s.instrument, s.general_notes,
                            s.coordinate_system, s.project_name, s.project_id]
                        .some(v => (v || '').toLowerCase().includes(search));
                });
            }

            if (sessions.length === 0) {
                container.innerHTML = `
                    <div class="qc-empty-state">
                        <i class="fas fa-clipboard-list"></i>
                        <h3>No QC sessions${projectFilter || search ? ' match your filters' : ' yet'}</h3>
                        <p>Start a QC session for each field data set you bring back from the field</p>
                    </div>`;
                return;
            }

            // Group by project, keep server ordering within groups
            const groups = new Map();
            sessions.forEach(s => {
                if (!groups.has(s.project_id)) groups.set(s.project_id, []);
                groups.get(s.project_id).push(s);
            });

            let html = '';
            groups.forEach((groupSessions, projectId) => {
                const projectName = groupSessions[0].project_name || '';
                html += `
                    <div class="qc-project-group">
                        <div class="qc-project-group-header">
                            <h3>${escapeHtml(projectName)}</h3>
                            <span class="qc-project-id">${escapeHtml(projectId)}</span>
                        </div>
                        ${groupSessions.map(createSessionCard).join('')}
                    </div>`;
            });
            container.innerHTML = html;
        }

        function createSessionCard(session) {
            const stageKeys = Object.keys(qcStages);
            const doneCount = stageKeys.filter(k => session.stages && session.stages[k] && session.stages[k].done).length;
            const isExpanded = expandedSessionId === session.session_id;

            const openCount = parseInt(session.open_findings, 10) || 0;
            const totalCount = parseInt(session.total_findings, 10) || 0;
            let findingsBadge = '';
            if (openCount > 0) {
                const sev = session.worst_open_severity || 'Minor';
                findingsBadge = `<span class="qc-findings-badge qc-sev-${escapeHtml(sev)}">
                    <i class="fas fa-triangle-exclamation"></i> ${openCount} open</span>`;
            } else if (totalCount > 0) {
                findingsBadge = `<span class="qc-findings-badge qc-sev-Info">
                    <i class="fas fa-check"></i> ${totalCount} resolved</span>`;
            }

            const chips = [];
            if (session.coordinate_system) chips.push(`<span class="qc-chip"><i class="fas fa-globe"></i>${escapeHtml(session.coordinate_system)}</span>`);
            if (session.geoid_model) chips.push(`<span class="qc-chip"><i class="fas fa-layer-group"></i>${escapeHtml(session.geoid_model)}</span>`);
            if (session.units) chips.push(`<span class="qc-chip"><i class="fas fa-ruler"></i>${escapeHtml(session.units)}</span>`);
            if (session.scale_factor) chips.push(`<span class="qc-chip"><i class="fas fa-compress-arrows-alt"></i>SF ${escapeHtml(trimScaleFactor(session.scale_factor))}</span>`);

            const title = session.task_name || 'Project-wide QC';
            const jobFiles = [...new Set((session.point_range_entries || [])
                .map(e => e.job_file_name).filter(Boolean))];
            const jobFileChips = jobFiles.slice(0, 4).map(jf =>
                `<span class="qc-chip"><i class="fas fa-file-lines"></i>${escapeHtml(jf)}</span>`).join('') +
                (jobFiles.length > 4 ? `<span class="qc-chip">+${jobFiles.length - 4} more</span>` : '');

            return `
                <div class="qc-card ${isExpanded ? 'expanded' : ''}" id="qc-card-${session.session_id}">
                    <div class="qc-card-header" onclick="toggleSessionCard(${session.session_id})">
                        <span class="qc-jobfile"><i class="fas fa-clipboard-list" style="color: var(--gray-400, #9ca3af);"></i> ${escapeHtml(title)}</span>
                        ${session.collection_date ? `<span class="qc-card-date"><i class="fas fa-calendar"></i> ${escapeHtml(session.collection_date)}</span>` : ''}
                        ${jobFileChips}
                        <span class="qc-stage-pill ${doneCount === stageKeys.length && stageKeys.length > 0 ? 'complete' : ''}">
                            <i class="fas ${doneCount === stageKeys.length && stageKeys.length > 0 ? 'fa-circle-check' : 'fa-list-check'}"></i>
                            ${doneCount}/${stageKeys.length}
                        </span>
                        ${findingsBadge}
                        <span class="qc-card-spacer"></span>
                        ${chips.join('')}
                        <i class="fas fa-chevron-${isExpanded ? 'up' : 'down'}" style="color: var(--gray-400, #9ca3af);"></i>
                    </div>
                    <div class="qc-card-body" id="qc-body-${session.session_id}">
                        ${isExpanded ? createSessionDetail(session) : ''}
                    </div>
                </div>`;
        }

        function createSessionDetail(session) {
            const findings = findingsCache[session.session_id];

            const pathBlock = session.raw_data_path ? `
                <div class="qc-path-row">
                    <i class="fas fa-folder-open"></i>
                    <span>${escapeHtml(session.raw_data_path)}</span>
                    <button class="copy-btn" onclick="event.stopPropagation(); copyPath(${jsAttr(session.raw_data_path)})" title="Copy raw data path">
                        <i class="fas fa-copy"></i>
                    </button>
                </div>` : '';

            const geoRows = [
                ['Coordinate System', session.coordinate_system],
                ['Datum / Epoch', session.datum_epoch],
                ['Geoid Model', session.geoid_model],
                ['Vertical Datum', session.vertical_datum],
                ['Combined Scale Factor', session.scale_factor ? trimScaleFactor(session.scale_factor) : null],
                ['Units', session.units],
                ['Field Crew', session.field_crew],
                ['Instrument', session.instrument],
                ['Task', session.task_name],
            ].filter(r => r[1])
             .map(r => `<div class="qc-geo-row"><span class="k">${r[0]}</span><span class="v">${escapeHtml(r[1])}</span></div>`)
             .join('') || '<div style="font-size:0.83rem; color:var(--gray-400);">No geodetic settings recorded — edit the session to add them.</div>';

            const stageRows = Object.entries(qcStages).map(([key, label]) => {
                const info = session.stages && session.stages[key];
                const done = !!(info && info.done);
                const meta = done ? `${info.by} · ${(info.at || '').substring(0, 10)}` : '';
                return `
                    <div class="qc-stage-row ${done ? 'done' : ''}"
                         onclick="toggleStage(${session.session_id}, '${key}', ${done ? 0 : 1})"
                         title="${done ? escapeHtml('Completed by ' + info.by + ' on ' + info.at + ' — click to undo') : 'Click to mark complete'}">
                        <span class="qc-stage-check">${done ? '<i class="fas fa-check"></i>' : ''}</span>
                        <span class="qc-stage-label">${escapeHtml(label)}</span>
                        <span class="qc-stage-meta">${escapeHtml(meta)}</span>
                    </div>`;
            }).join('');

            const findingsBlock = findings === undefined
                ? '<div style="font-size:0.83rem; color:var(--gray-400); padding:0.5rem;">Loading findings...</div>'
                : createFindingsTable(session.session_id, findings);

            return `
                ${pathBlock}
                <div class="qc-detail-grid">
                    <div class="qc-panel">
                        <h4><i class="fas fa-list-check"></i> QC Workflow</h4>
                        ${stageRows}
                    </div>
                    <div class="qc-panel">
                        <h4><i class="fas fa-globe"></i> Geodetic Settings</h4>
                        <div class="qc-geo-rows">${geoRows}</div>
                    </div>
                </div>
                <div class="qc-section-header">
                    <h4><i class="fas fa-triangle-exclamation"></i> Findings / Error Log</h4>
                    <button class="btn btn-secondary btn-sm" onclick="openFindingModal(${session.session_id})">
                        <i class="fas fa-plus"></i> Log Finding
                    </button>
                </div>
                ${findingsBlock}
                <div class="qc-section-header">
                    <h4><i class="fas fa-list-ol"></i> Point Ranges Used (from Tools)</h4>
                    <a class="btn btn-secondary btn-sm" href="./tools.php" target="_blank">
                        <i class="fas fa-tools"></i> Edit in Tools
                    </a>
                </div>
                ${createPointRangesTable(session)}
                ${session.general_notes ? `<div class="qc-notes-block"><strong><i class="fas fa-sticky-note"></i> Notes:</strong> ${escapeHtml(session.general_notes)}</div>` : ''}
                <div class="qc-card-actions">
                    <button class="btn btn-secondary btn-sm" onclick="openSessionModal(${session.session_id})">
                        <i class="fas fa-edit"></i> Edit Session
                    </button>
                    <button class="btn btn-sm btn-danger-outline" onclick="deleteSession(${session.session_id})">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                </div>`;
        }

        function createFindingsTable(sessionId, findings) {
            if (!findings.length) {
                return '<div style="font-size:0.83rem; color:var(--gray-400); padding:0.5rem;">No findings logged — that\'s either a clean data set or an unchecked one.</div>';
            }
            const rows = findings.map(f => `
                <tr>
                    <td style="font-family:monospace; white-space:nowrap;">${escapeHtml(f.point_numbers || '—')}</td>
                    <td>${escapeHtml(f.category)}</td>
                    <td><span class="qc-sev qc-sev-${escapeHtml(f.severity)}">${escapeHtml(f.severity)}</span></td>
                    <td><span class="qc-status-${escapeHtml(f.status)}">${escapeHtml(f.status)}</span>
                        ${f.resolved_by ? `<div style="font-size:0.68rem; color:var(--gray-400);">${escapeHtml(f.resolved_by)} · ${escapeHtml((f.resolved_date || '').substring(0, 10))}</div>` : ''}
                    </td>
                    <td>${escapeHtml(f.description || '')}
                        ${f.resolution ? `<div style="color:#047857; margin-top:0.2rem;"><i class="fas fa-arrow-turn-up" style="transform:rotate(90deg); font-size:0.65rem;"></i> ${escapeHtml(f.resolution)}</div>` : ''}
                    </td>
                    <td style="white-space:nowrap; text-align:right;">
                        <button class="btn btn-secondary btn-sm" onclick="openFindingModal(${sessionId}, ${f.finding_id})" title="Edit finding">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-danger-outline" onclick="deleteFinding(${f.finding_id}, ${sessionId})" title="Delete finding">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>`).join('');
            return `
                <div style="overflow-x:auto;">
                    <table class="qc-findings-table">
                        <thead>
                            <tr><th>Points</th><th>Category</th><th>Severity</th><th>Status</th><th>Description / Resolution</th><th></th></tr>
                        </thead>
                        <tbody>${rows}</tbody>
                    </table>
                </div>`;
        }

        function createPointRangesTable(session) {
            const entries = session.point_range_entries || [];
            if (!entries.length) {
                return '<div style="font-size:0.83rem; color:var(--gray-400); padding:0.5rem;">No point range entries recorded yet — log job files and point numbers used per task on the Tools page.</div>';
            }
            // Show which task each entry came from when the session spans the whole project
            const showTask = !session.task_id;
            const flag = v => v === 'yes'
                ? '<i class="fas fa-check" style="color:#047857;"></i>'
                : '<i class="fas fa-minus" style="color:var(--gray-300, #d1d5db);"></i>';
            const rows = entries.map(e => `
                <tr>
                    <td style="font-family:monospace; white-space:nowrap;">${escapeHtml(e.job_file_name || '—')}</td>
                    <td style="font-family:monospace;">${escapeHtml(e.point_number_used || '—')}</td>
                    ${showTask ? `<td>${escapeHtml(e.task_name || '')}</td>` : ''}
                    <td style="text-align:center;">${flag(e.converted)}</td>
                    <td style="text-align:center;">${flag(e.imported)}</td>
                    <td style="text-align:center;">${flag(e.checked)}</td>
                    <td>${escapeHtml(e.notes || '')}</td>
                </tr>`).join('');
            return `
                <div style="overflow-x:auto;">
                    <table class="qc-findings-table">
                        <thead>
                            <tr><th>Job File</th><th>Points Used</th>${showTask ? '<th>Task</th>' : ''}<th style="text-align:center;">Converted</th><th style="text-align:center;">Imported</th><th style="text-align:center;">Checked</th><th>Notes</th></tr>
                        </thead>
                        <tbody>${rows}</tbody>
                    </table>
                </div>`;
        }

        async function toggleSessionCard(sessionId) {
            if (expandedSessionId === sessionId) {
                expandedSessionId = null;
                renderSessions();
                return;
            }
            expandedSessionId = sessionId;
            renderSessions();
            if (findingsCache[sessionId] === undefined) {
                await loadFindings(sessionId);
                renderSessions();
            }
        }

        // ── Stage toggling ───────────────────────────────────────────────────

        async function toggleStage(sessionId, stageKey, done) {
            try {
                const formData = new FormData();
                formData.append('action', 'update_stage');
                formData.append('session_id', sessionId);
                formData.append('stage_key', stageKey);
                formData.append('done', done);
                const response = await fetch(QC_API, { method: 'POST', body: formData });
                const data = await response.json();
                if (data.success) {
                    const session = allSessions.find(s => s.session_id === sessionId);
                    if (session) session.stages = data.stages || {};
                    renderSessions();
                } else {
                    showToast(data.message || 'Error updating stage', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('Network error updating stage', 'error');
            }
        }

        // ── Session modal ────────────────────────────────────────────────────

        function setupGeoSelects() {
            fillGeoSelect('sessionCoordSystem', COORD_SYSTEMS);
            fillGeoSelect('sessionDatum', DATUM_EPOCHS);
            fillGeoSelect('sessionGeoid', GEOID_MODELS);
            fillGeoSelect('sessionVDatum', VERTICAL_DATUMS);
            fillGeoSelect('sessionUnits', UNIT_OPTIONS);

            document.querySelectorAll('.qc-geo-select').forEach(sel => {
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

        function setupPathAutoSuggest() {
            const pathInput = document.getElementById('sessionRawPath');
            pathInput.addEventListener('input', () => pathInput.classList.remove('auto-filled'));
        }

        async function onProjectChange() {
            const projectId = document.getElementById('sessionProject').value;
            const pathInput = document.getElementById('sessionRawPath');

            // Suggest the conventional downloads path unless the user typed their own
            if (projectId && (pathInput.value === '' || pathInput.classList.contains('auto-filled'))) {
                pathInput.value = `N:\\${projectId}\\05 Service Groups\\Survey\\Downloads`;
                pathInput.classList.add('auto-filled');
            }
            await populateTaskSelect(projectId);
        }

        async function populateTaskSelect(projectId, selectedTaskId = '') {
            const taskSel = document.getElementById('sessionTask');
            taskSel.innerHTML = '<option value="">— None —</option>';
            if (!projectId) return;
            try {
                const response = await fetch(TASKS_API, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=load_tasks&project_id=${encodeURIComponent(projectId)}`
                });
                const data = await response.json();
                if (data.success) {
                    (data.tasks || []).forEach(t => {
                        taskSel.insertAdjacentHTML('beforeend',
                            `<option value="${t.task_id}">${escapeHtml(t.task_name)} (${escapeHtml(t.task_status || '')})</option>`);
                    });
                }
            } catch (error) {
                console.error('Error loading tasks:', error);
            }
            if (selectedTaskId) taskSel.value = String(selectedTaskId);
        }

        async function openSessionModal(sessionId = null) {
            const form = document.getElementById('sessionForm');
            form.reset();
            document.querySelectorAll('#sessionModal .qc-other-input').forEach(i => i.classList.remove('visible'));
            document.getElementById('editSessionId').value = sessionId || '';
            document.getElementById('sessionModalTitle').textContent = sessionId ? 'Edit QC Session' : 'New QC Session';
            document.getElementById('sessionRawPath').classList.remove('auto-filled');

            if (sessionId) {
                const s = allSessions.find(x => x.session_id === sessionId);
                if (!s) return;
                document.getElementById('sessionProject').value = s.project_id;
                document.getElementById('sessionDate').value = s.collection_date || '';
                document.getElementById('sessionRawPath').value = s.raw_data_path || '';
                document.getElementById('sessionCrew').value = s.field_crew || '';
                document.getElementById('sessionInstrument').value = s.instrument || '';
                document.getElementById('sessionScaleFactor').value = s.scale_factor ? trimScaleFactor(s.scale_factor) : '';
                document.getElementById('sessionNotes').value = s.general_notes || '';
                setGeoValue('sessionCoordSystem', s.coordinate_system);
                setGeoValue('sessionDatum', s.datum_epoch);
                setGeoValue('sessionGeoid', s.geoid_model);
                setGeoValue('sessionVDatum', s.vertical_datum);
                setGeoValue('sessionUnits', s.units);
                await populateTaskSelect(s.project_id, s.task_id || '');
            } else {
                // Preselect the filtered project for convenience
                const filtered = document.getElementById('projectFilter').value;
                if (filtered) {
                    document.getElementById('sessionProject').value = filtered;
                    await onProjectChange();
                }
            }
            document.getElementById('sessionModal').classList.add('active');
        }

        function closeSessionModal() {
            document.getElementById('sessionModal').classList.remove('active');
        }

        async function saveSession() {
            const sessionId = document.getElementById('editSessionId').value;
            const projectId = document.getElementById('sessionProject').value;

            if (!projectId) {
                showToast('Project is required', 'error');
                return;
            }

            const formData = new FormData();
            formData.append('action', sessionId ? 'update_session' : 'create_session');
            if (sessionId) formData.append('session_id', sessionId);
            formData.append('project_id', projectId);
            formData.append('task_id', document.getElementById('sessionTask').value || '0');
            formData.append('raw_data_path', document.getElementById('sessionRawPath').value.trim());
            formData.append('collection_date', document.getElementById('sessionDate').value);
            formData.append('field_crew', document.getElementById('sessionCrew').value.trim());
            formData.append('instrument', document.getElementById('sessionInstrument').value.trim());
            formData.append('coordinate_system', getGeoValue('sessionCoordSystem'));
            formData.append('datum_epoch', getGeoValue('sessionDatum'));
            formData.append('geoid_model', getGeoValue('sessionGeoid'));
            formData.append('vertical_datum', getGeoValue('sessionVDatum'));
            formData.append('units', getGeoValue('sessionUnits'));
            formData.append('scale_factor', document.getElementById('sessionScaleFactor').value.trim());
            formData.append('general_notes', document.getElementById('sessionNotes').value.trim());

            try {
                const response = await fetch(QC_API, { method: 'POST', body: formData });
                const data = await response.json();
                if (data.success) {
                    closeSessionModal();
                    showToast(sessionId ? 'QC session updated' : 'QC session created');
                    if (!sessionId && data.session_id) expandedSessionId = data.session_id;
                    await loadSessions();
                    renderSessions();
                } else {
                    showToast(data.message || 'Error saving session', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('Network error saving session', 'error');
            }
        }

        async function deleteSession(sessionId) {
            const session = allSessions.find(s => s.session_id === sessionId);
            const name = session ? (session.task_name || `${session.project_id} project-wide QC`) : sessionId;
            if (!confirm(`Delete the QC session "${name}" and all its findings? This cannot be undone.`)) return;

            try {
                const formData = new FormData();
                formData.append('action', 'delete_session');
                formData.append('session_id', sessionId);
                const response = await fetch(QC_API, { method: 'POST', body: formData });
                const data = await response.json();
                if (data.success) {
                    showToast('QC session deleted');
                    delete findingsCache[sessionId];
                    if (expandedSessionId === sessionId) expandedSessionId = null;
                    await loadSessions();
                    renderSessions();
                } else {
                    showToast(data.message || 'Error deleting session', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('Network error deleting session', 'error');
            }
        }

        // ── Finding modal ────────────────────────────────────────────────────

        function openFindingModal(sessionId, findingId = null) {
            const form = document.getElementById('findingForm');
            form.reset();
            document.getElementById('findingSessionId').value = sessionId;
            document.getElementById('editFindingId').value = findingId || '';
            document.getElementById('findingModalTitle').textContent = findingId ? 'Edit Finding' : 'Log Finding';

            // (Re)build selects from server-provided vocabularies
            const catSel = document.getElementById('findingCategory');
            catSel.innerHTML = qcCategories.map(c => `<option value="${escapeHtml(c)}">${escapeHtml(c)}</option>`).join('') +
                `<option value="${OTHER_VALUE}">Other (custom)…</option>`;
            catSel.onchange = () => {
                const other = document.getElementById('findingCategoryOther');
                other.classList.toggle('visible', catSel.value === OTHER_VALUE);
                if (catSel.value !== OTHER_VALUE) other.value = '';
            };
            document.getElementById('findingCategoryOther').classList.remove('visible');

            document.getElementById('findingSeverity').innerHTML =
                qcSeverities.map(s => `<option value="${escapeHtml(s)}">${escapeHtml(s)}</option>`).join('');
            document.getElementById('findingStatus').innerHTML =
                qcStatuses.map(s => `<option value="${escapeHtml(s)}">${escapeHtml(s)}</option>`).join('');
            document.getElementById('findingSeverity').value = 'Minor';
            document.getElementById('findingStatus').value = 'Open';

            if (findingId) {
                const f = (findingsCache[sessionId] || []).find(x => x.finding_id === findingId);
                if (f) {
                    document.getElementById('findingPoints').value = f.point_numbers || '';
                    document.getElementById('findingSeverity').value = f.severity;
                    document.getElementById('findingStatus').value = f.status;
                    document.getElementById('findingDescription').value = f.description || '';
                    document.getElementById('findingResolution').value = f.resolution || '';
                    if (qcCategories.includes(f.category)) {
                        catSel.value = f.category;
                    } else {
                        catSel.value = OTHER_VALUE;
                        const other = document.getElementById('findingCategoryOther');
                        other.value = f.category;
                        other.classList.add('visible');
                    }
                }
            }
            document.getElementById('findingModal').classList.add('active');
        }

        function closeFindingModal() {
            document.getElementById('findingModal').classList.remove('active');
        }

        async function saveFinding() {
            const sessionId = document.getElementById('findingSessionId').value;
            const findingId = document.getElementById('editFindingId').value;
            const catSel = document.getElementById('findingCategory');
            const category = catSel.value === OTHER_VALUE
                ? document.getElementById('findingCategoryOther').value.trim()
                : catSel.value;

            if (!category) {
                showToast('Category is required', 'error');
                return;
            }

            const formData = new FormData();
            formData.append('action', findingId ? 'update_finding' : 'add_finding');
            if (findingId) formData.append('finding_id', findingId);
            else formData.append('session_id', sessionId);
            formData.append('point_numbers', document.getElementById('findingPoints').value.trim());
            formData.append('category', category);
            formData.append('severity', document.getElementById('findingSeverity').value);
            formData.append('status', document.getElementById('findingStatus').value);
            formData.append('description', document.getElementById('findingDescription').value.trim());
            formData.append('resolution', document.getElementById('findingResolution').value.trim());

            try {
                const response = await fetch(QC_API, { method: 'POST', body: formData });
                const data = await response.json();
                if (data.success) {
                    closeFindingModal();
                    showToast(findingId ? 'Finding updated' : 'Finding logged');
                    await Promise.all([loadFindings(parseInt(sessionId, 10)), loadSessions()]);
                    renderSessions();
                } else {
                    showToast(data.message || 'Error saving finding', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('Network error saving finding', 'error');
            }
        }

        async function deleteFinding(findingId, sessionId) {
            if (!confirm('Delete this finding?')) return;
            try {
                const formData = new FormData();
                formData.append('action', 'delete_finding');
                formData.append('finding_id', findingId);
                const response = await fetch(QC_API, { method: 'POST', body: formData });
                const data = await response.json();
                if (data.success) {
                    showToast('Finding deleted');
                    await Promise.all([loadFindings(sessionId), loadSessions()]);
                    renderSessions();
                } else {
                    showToast(data.message || 'Error deleting finding', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('Network error deleting finding', 'error');
            }
        }

        // ── Utilities ────────────────────────────────────────────────────────

        function escapeHtml(text) {
            if (text === null || text === undefined) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Safely embed a string value inside an inline onclick attribute
        function jsAttr(value) {
            return escapeHtml(JSON.stringify(value || ''));
        }

        function trimScaleFactor(value) {
            // DECIMAL(12,10) comes back zero-padded; trim trailing zeros but keep precision
            return String(value).replace(/(\.\d*?)0+$/, '$1').replace(/\.$/, '');
        }

        function copyPath(path) {
            if (!path) {
                showToast('No path to copy', 'error');
                return;
            }
            navigator.clipboard.writeText(path).then(() => {
                showToast('Path copied to clipboard');
            }).catch(() => {
                showToast('Could not copy path', 'error');
            });
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
