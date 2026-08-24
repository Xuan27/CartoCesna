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
        .qc-chip.qc-chip-warn {
            background: #fef2f2;
            color: #b91c1c;
        }
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
        .qc-path-row i.fa-cloud { color: #0369a1; flex-shrink: 0; }
        .qc-path-row a {
            color: #1d4ed8;
            text-decoration: none;
        }
        .qc-path-row a:hover { text-decoration: underline; }
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

        /* Point-ranges editor (moved from the retired Tools page) */
        .point-ranges-container {
            border: 1px solid var(--gray-200, #e5e7eb);
            border-radius: 8px;
            padding: 1rem;
            background: var(--gray-50, #f9fafb);
        }
        .point-range-entry {
            background: white;
            border: 1px solid var(--gray-200, #e5e7eb);
            border-radius: 6px;
            padding: 1rem;
            margin-bottom: 0.75rem;
            position: relative;
        }
        .point-range-entry:last-child { margin-bottom: 0; }
        .point-range-entry.has-range-conflict { border-left: 4px solid #dc3545; }
        .point-range-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.75rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid var(--gray-100, #f3f4f6);
        }
        .point-range-header span {
            font-weight: 600;
            color: var(--gray-700, #374151);
            font-size: 0.875rem;
        }
        .point-range-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.75rem;
        }
        .point-range-grid .form-group { margin-bottom: 0; }
        .point-range-grid .form-group.full-width { grid-column: 1 / -1; }
        .point-range-grid label {
            font-size: 0.75rem;
            color: var(--gray-600, #4b5563);
            margin-bottom: 0.25rem;
            display: block;
        }
        .point-range-grid input,
        .point-range-grid select {
            padding: 0.4rem 0.6rem;
            font-size: 0.8rem;
        }
        .add-point-range-btn {
            width: 100%;
            padding: 0.75rem;
            border: 2px dashed var(--gray-300, #d1d5db);
            border-radius: 6px;
            background: transparent;
            color: var(--gray-500);
            cursor: pointer;
            font-size: 0.875rem;
            transition: all 0.2s;
            margin-top: 0.75rem;
        }
        .add-point-range-btn:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
        }
        .remove-entry-btn {
            background: var(--danger-color, #dc3545);
            color: white;
            border: none;
            border-radius: 4px;
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
            cursor: pointer;
        }
        .remove-entry-btn:hover { background: #c82333; }
        .checkbox-group {
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        .checkbox-group label {
            display: flex;
            align-items: center;
            gap: 0.35rem;
            font-size: 0.8rem;
            cursor: pointer;
        }
        .checkbox-group input[type="checkbox"] {
            width: 16px;
            height: 16px;
        }
        .pr-ranges-container {
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
        }
        .range-row {
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }
        .range-row .form-input {
            flex: 1;
            min-width: 0;
        }
        .range-separator {
            color: var(--gray-500);
            font-size: 0.8rem;
            white-space: nowrap;
            padding: 0 0.1rem;
        }
        .remove-range-btn {
            background: var(--danger-color, #dc3545);
            color: white;
            border: none;
            border-radius: 4px;
            padding: 0.25rem 0.4rem;
            font-size: 0.7rem;
            cursor: pointer;
            flex-shrink: 0;
        }
        .remove-range-btn:hover { background: #c82333; }
        .add-range-btn {
            margin-top: 0.4rem;
            padding: 0.3rem 0.6rem;
            border: 1px dashed var(--gray-300, #d1d5db);
            border-radius: 4px;
            background: transparent;
            color: var(--gray-500);
            cursor: pointer;
            font-size: 0.75rem;
            transition: all 0.2s;
            display: block;
            width: 100%;
            text-align: left;
        }
        .add-range-btn:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
        }
        .range-overlap-warning {
            background: #fff3cd;
            border: 1px solid #ffc107;
            color: #856404;
            padding: 0.65rem 0.9rem;
            border-radius: 6px;
            font-size: 0.8rem;
            margin-bottom: 0.75rem;
            line-height: 1.5;
        }
        .range-overlap-warning i { margin-right: 0.35rem; }
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
                        <i class="fas fa-circle-info"></i> Job files &amp; point numbers used are stored per task and edited
                        in the session's "Point Ranges Used" section below the findings log.
                    </div>
                    <div class="form-row-2">
                        <div class="form-group">
                            <label class="form-label">Collection Date</label>
                            <input type="date" class="form-input" id="sessionDate">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Field Crew</label>
                            <div class="path-input-wrap">
                                <select class="form-select" id="sessionCrew"></select>
                                <button type="button" class="btn btn-secondary btn-sm" onclick="openCrewModal()" title="Add or remove crews">
                                    <i class="fas fa-users"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Raw Data Folder</label>
                        <div class="path-input-wrap">
                            <input type="text" class="form-input" id="sessionRawPath" placeholder="https://[company].sharepoint.com/... (OneDrive folder link) or N:\0012345.00\05 Service Groups\Survey\Downloads" spellcheck="false">
                            <button type="button" class="btn btn-secondary btn-sm" onclick="openRawPath(document.getElementById('sessionRawPath').value)" title="Open folder link">
                                <i class="fas fa-up-right-from-square"></i>
                            </button>
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
                        <div id="projectSfHint" style="font-size:0.75rem; color:var(--gray-400, #9ca3af); margin-top:0.3rem; display:none;"></div>
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

    <!-- Crew Management Modal -->
    <div class="checklist-modal" id="crewModal">
        <div class="checklist-modal-content" style="max-width: 460px; max-height: 85vh;">
            <div class="checklist-modal-header" style="background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);">
                <div class="checklist-modal-header-top">
                    <h3><i class="fas fa-users"></i> Field Crews</h3>
                    <button class="checklist-modal-close" onclick="closeCrewModal()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            <div class="checklist-modal-body" style="padding: 1.5rem; overflow-y: auto;">
                <div style="font-size:0.78rem; color:var(--gray-500); margin-bottom: 0.75rem;">
                    Crew initials are used to build job file names: <code>[yyyymmdd][initials]</code>, e.g. <code>20260731JM</code>.
                </div>
                <div id="crewList" style="margin-bottom: 1rem;"></div>
                <div class="form-row-2" style="align-items: end;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Initials</label>
                        <input type="text" class="form-input" id="newCrewInitials" placeholder="e.g., JM" maxlength="5"
                               style="text-transform: uppercase;" spellcheck="false">
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Name (optional)</label>
                        <input type="text" class="form-input" id="newCrewName" placeholder="e.g., Juan Martinez">
                    </div>
                </div>
                <button type="button" class="btn btn-primary btn-sm" style="margin-top: 0.75rem; width: 100%;" onclick="addCrew()">
                    <i class="fas fa-plus"></i> Add Crew
                </button>
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
        let findingsCache = {};      // session_id -> findings[]
        let prEditSessionId = null;  // session whose point ranges are being edited
        let projectTasksCache = {};  // project_id -> tasks[] (for the per-entry task select)
        let allCrews = [];           // [{initials, name}] from Private/crews.json via the API

        document.addEventListener('DOMContentLoaded', function() {
            setupSidebar();
            setupGeoSelects();
            setupPathAutoSuggest();
            init();
        });

        async function init() {
            await Promise.all([loadProjects(), loadSessions(), loadCrews()]);
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

        // ── Crews ────────────────────────────────────────────────────────────

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

        // Fill the session-form crew dropdown; keep a stored value selectable
        // even if that crew has since been removed from the list
        function populateCrewSelect(selectedValue = '') {
            const sel = document.getElementById('sessionCrew');
            sel.innerHTML = '<option value="">— Not set —</option>' +
                allCrews.map(c => `<option value="${escapeHtml(crewLabel(c))}">${escapeHtml(crewLabel(c))}</option>`).join('');
            if (selectedValue && ![...sel.options].some(o => o.value === selectedValue)) {
                sel.insertAdjacentHTML('beforeend',
                    `<option value="${escapeHtml(selectedValue)}">${escapeHtml(selectedValue)}</option>`);
            }
            sel.value = selectedValue;
        }

        function openCrewModal() {
            renderCrewList();
            document.getElementById('newCrewInitials').value = '';
            document.getElementById('newCrewName').value = '';
            document.getElementById('crewModal').classList.add('active');
        }

        function closeCrewModal() {
            document.getElementById('crewModal').classList.remove('active');
            // Refresh the dropdown in case crews changed while the modal was open
            populateCrewSelect(document.getElementById('sessionCrew').value);
        }

        function renderCrewList() {
            const list = document.getElementById('crewList');
            if (!allCrews.length) {
                list.innerHTML = '<div style="font-size:0.83rem; color:var(--gray-400); padding:0.5rem;">No crews yet — add one below.</div>';
                return;
            }
            list.innerHTML = allCrews.map(c => `
                <div style="display:flex; align-items:center; gap:0.6rem; padding:0.45rem 0.6rem; border:1px solid var(--gray-200, #e5e7eb); border-radius:6px; margin-bottom:0.4rem;">
                    <span style="font-family:monospace; font-weight:700;">${escapeHtml(c.initials)}</span>
                    <span style="font-size:0.83rem; color:var(--gray-600, #4b5563); flex:1;">${escapeHtml(c.name || '')}</span>
                    <button class="btn btn-sm btn-danger-outline" onclick="removeCrew(${jsAttr(c.initials)})" title="Remove crew">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>`).join('');
        }

        async function addCrew() {
            const initials = document.getElementById('newCrewInitials').value.trim().toUpperCase();
            const name = document.getElementById('newCrewName').value.trim();
            if (!initials) {
                showToast('Initials are required', 'error');
                return;
            }
            try {
                const formData = new FormData();
                formData.append('action', 'add_crew');
                formData.append('initials', initials);
                formData.append('name', name);
                const response = await fetch(QC_API, { method: 'POST', body: formData });
                const data = await response.json();
                if (data.success) {
                    allCrews = data.crews || [];
                    renderCrewList();
                    document.getElementById('newCrewInitials').value = '';
                    document.getElementById('newCrewName').value = '';
                    showToast(`Crew ${initials} added`);
                } else {
                    showToast(data.message || 'Error adding crew', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('Network error adding crew', 'error');
            }
        }

        async function removeCrew(initials) {
            if (!confirm(`Remove crew "${initials}"? Existing sessions and job files keep their values.`)) return;
            try {
                const formData = new FormData();
                formData.append('action', 'remove_crew');
                formData.append('initials', initials);
                const response = await fetch(QC_API, { method: 'POST', body: formData });
                const data = await response.json();
                if (data.success) {
                    allCrews = data.crews || [];
                    renderCrewList();
                    showToast(`Crew ${initials} removed`);
                } else {
                    showToast(data.message || 'Error removing crew', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('Network error removing crew', 'error');
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
            if (session.scale_factor) {
                const mismatch = scaleFactorMismatch(session);
                chips.push(`<span class="qc-chip ${mismatch ? 'qc-chip-warn' : ''}"
                    ${mismatch ? `title="Differs from project scale factor (${escapeHtml(getProjectScaleFactor(session.project_id))})"` : ''}>
                    <i class="fas ${mismatch ? 'fa-triangle-exclamation' : 'fa-compress-arrows-alt'}"></i>SF ${escapeHtml(trimScaleFactor(session.scale_factor))}</span>`);
            }

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

            const pathIsLink = isLikelyUrl(session.raw_data_path);
            const pathBlock = session.raw_data_path ? `
                <div class="qc-path-row">
                    <i class="fas ${pathIsLink ? 'fa-cloud' : 'fa-folder-open'}"></i>
                    ${pathIsLink
                        ? `<a href="${escapeHtml(session.raw_data_path)}" target="_blank" rel="noopener noreferrer" onclick="event.stopPropagation();" title="Open raw data folder">${escapeHtml(session.raw_data_path)}</a>`
                        : `<span>${escapeHtml(session.raw_data_path)}</span>`}
                    <button class="copy-btn" onclick="event.stopPropagation(); copyPath(${jsAttr(session.raw_data_path)})" title="Copy raw data path">
                        <i class="fas fa-copy"></i>
                    </button>
                </div>` : '';

            const projectSf = getProjectScaleFactor(session.project_id);
            let sfDisplay = null;
            if (session.scale_factor) {
                sfDisplay = trimScaleFactor(session.scale_factor);
            } else if (projectSf) {
                sfDisplay = `${projectSf} (from project)`;
            }
            const sfWarning = scaleFactorMismatch(session)
                ? `<div class="range-overlap-warning" style="margin-top:0.6rem; margin-bottom:0;">
                       <i class="fas fa-triangle-exclamation"></i>
                       Session scale factor <strong>${escapeHtml(trimScaleFactor(session.scale_factor))}</strong> differs from the
                       project's <strong>${escapeHtml(projectSf)}</strong> — confirm which is correct.
                   </div>`
                : '';

            const geoRows = [
                ['Coordinate System', session.coordinate_system],
                ['Datum / Epoch', session.datum_epoch],
                ['Geoid Model', session.geoid_model],
                ['Vertical Datum', session.vertical_datum],
                ['Combined Scale Factor', sfDisplay],
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
                        ${sfWarning}
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
                    <h4><i class="fas fa-list-ol"></i> Point Ranges Used</h4>
                    ${prEditSessionId === session.session_id ? '' : `
                    <button class="btn btn-secondary btn-sm" onclick="openPointRangesEditor(${session.session_id})">
                        <i class="fas fa-edit"></i> Edit Point Ranges
                    </button>`}
                </div>
                ${prEditSessionId === session.session_id ? createPointRangesEditor(session) : createPointRangesTable(session)}
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
                return '<div style="font-size:0.83rem; color:var(--gray-400); padding:0.5rem;">No point range entries recorded yet — click "Edit Point Ranges" to log job files and point numbers used.</div>';
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

        // ── Point-ranges editor (moved here from the retired Tools page) ──────
        // Entries are stored per task in tasks.point_ranges. Task-linked sessions
        // edit that task's entries; project-wide sessions edit entries across all
        // of the project's tasks, choosing the task per entry.

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

        async function openPointRangesEditor(sessionId) {
            const session = allSessions.find(s => s.session_id === sessionId);
            if (!session) return;
            if (!session.task_id) {
                if (!projectTasksCache[session.project_id]) {
                    projectTasksCache[session.project_id] = await fetchProjectTasks(session.project_id);
                }
                if (projectTasksCache[session.project_id].length === 0) {
                    showToast('This project has no tasks yet — point ranges are stored per task. Add a task first.', 'error');
                    return;
                }
            }
            prEditSessionId = sessionId;
            expandedSessionId = sessionId;
            renderSessions();
            checkPointRangeOverlaps();
        }

        function cancelPointRangesEdit() {
            prEditSessionId = null;
            renderSessions();
        }

        function createPointRangesEditor(session) {
            const entries = session.point_range_entries || [];
            const entriesHtml = entries.map((e, i) => buildPointRangeEntryHtml(session, e, i)).join('');
            return `
                <div class="point-ranges-container" id="pointRangesContainer">
                    <div id="pointRangesWarning" style="display: none;"></div>
                    <div id="pointRangesEntries">
                        ${entriesHtml || '<p style="color: var(--gray-500); text-align: center; padding: 1rem;">No point range entries yet. Click the button below to add one.</p>'}
                    </div>
                    <button type="button" class="add-point-range-btn" onclick="addPointRangeEntry()">
                        <i class="fas fa-plus"></i> Add Job File Entry
                    </button>
                    <div class="qc-card-actions" style="border-top: none; padding-top: 0; margin-top: 0.75rem;">
                        <button class="btn btn-secondary btn-sm" onclick="cancelPointRangesEdit()">Cancel</button>
                        <button class="btn btn-primary btn-sm" onclick="savePointRanges(${session.session_id})">
                            <i class="fas fa-save"></i> Save Point Ranges
                        </button>
                    </div>
                </div>`;
        }

        function buildPointRangeEntryHtml(session, entry = {}, index) {
            const existingRanges = parseRangeString(entry.point_number_used || '');
            const rangeRowsHtml = existingRanges.length > 0
                ? existingRanges.map(r => buildRangeRowHtml(r.from, r.to)).join('')
                : buildRangeRowHtml('', '');

            let taskSelectHtml = '';
            if (!session.task_id) {
                const tasks = projectTasksCache[session.project_id] || [];
                const options = tasks.map(t =>
                    `<option value="${t.task_id}" ${Number(entry.task_id) === Number(t.task_id) ? 'selected' : ''}>${escapeHtml(t.task_name)}</option>`).join('');
                taskSelectHtml = `
                    <div class="form-group full-width">
                        <label>Task</label>
                        <select class="form-select pr-task">${options}</select>
                    </div>`;
            }

            return `
                <div class="point-range-entry" data-index="${index}">
                    <div class="point-range-header">
                        <span><i class="fas fa-file-alt"></i> Job File #${index + 1}</span>
                        <button type="button" class="remove-entry-btn" onclick="removePointRangeEntry(${index})">
                            <i class="fas fa-trash"></i> Remove
                        </button>
                    </div>
                    <div class="point-range-grid">
                        ${taskSelectHtml}
                        <div class="form-group">
                            ${buildJobFileControls(entry)}
                        </div>
                        <div class="form-group">
                            <label>Point Numbers Used</label>
                            <div class="pr-ranges-container">
                                ${rangeRowsHtml}
                            </div>
                            <button type="button" class="add-range-btn" onclick="addRangeRow(this)">
                                <i class="fas fa-plus"></i> Add Range
                            </button>
                        </div>
                        <div class="form-group full-width">
                            <label>Status</label>
                            <div class="checkbox-group">
                                <label>
                                    <input type="checkbox" class="pr-converted" ${entry.converted === 'yes' ? 'checked' : ''}>
                                    Converted
                                </label>
                                <label>
                                    <input type="checkbox" class="pr-imported" ${entry.imported === 'yes' ? 'checked' : ''}>
                                    Imported
                                </label>
                                <label>
                                    <input type="checkbox" class="pr-checked" ${entry.checked === 'yes' ? 'checked' : ''}>
                                    Checked
                                </label>
                            </div>
                        </div>
                        <div class="form-group full-width">
                            <label>Notes</label>
                            <input type="text" class="form-input pr-notes" value="${escapeHtml(entry.notes || '')}" placeholder="e.g., Control, Boundary, Topo">
                        </div>
                    </div>
                </div>`;
        }

        // Job file names follow [yyyymmdd][crew initials], e.g. 20260731JM,
        // composed from a date picker and the crew list. Names saved before this
        // convention (e.g. 01232026JM) stay editable as plain text.
        function buildJobFileControls(entry) {
            const raw = entry.job_file_name || '';
            const m = raw.match(/^(\d{4})(\d{2})(\d{2})([A-Za-z]{1,5})$/);

            if (raw && !m) {
                return `
                    <label>Job File Name <span style="color:var(--gray-400);">(legacy format)</span></label>
                    <input type="text" class="form-input pr-job-file" value="${escapeHtml(raw)}" oninput="checkPointRangeOverlaps()">`;
            }

            const dateVal = m ? `${m[1]}-${m[2]}-${m[3]}` : '';
            const crewVal = m ? m[4].toUpperCase() : '';
            let crewOptions = '<option value="">Crew…</option>' +
                allCrews.map(c => `<option value="${escapeHtml(c.initials)}" ${c.initials === crewVal ? 'selected' : ''}>${escapeHtml(c.initials)}</option>`).join('');
            if (crewVal && !allCrews.some(c => c.initials === crewVal)) {
                crewOptions += `<option value="${escapeHtml(crewVal)}" selected>${escapeHtml(crewVal)}</option>`;
            }

            return `
                <label>Job File Name
                    <span class="pr-job-preview" style="color:var(--gray-400); font-family:monospace; margin-left:0.35rem;">${escapeHtml(raw)}</span>
                </label>
                <div style="display:flex; gap:0.4rem;">
                    <input type="date" class="form-input pr-job-date" value="${dateVal}" onchange="syncJobFileName(this)">
                    <select class="form-select pr-job-crew" style="max-width:7rem;" onchange="syncJobFileName(this)">${crewOptions}</select>
                </div>
                <input type="hidden" class="pr-job-file" value="${escapeHtml(raw)}">`;
        }

        function syncJobFileName(el) {
            const group = el.closest('.form-group');
            const date = group.querySelector('.pr-job-date')?.value || '';
            const crew = group.querySelector('.pr-job-crew')?.value || '';
            const composed = (date && crew) ? date.replace(/-/g, '') + crew : '';
            const hidden = group.querySelector('input.pr-job-file');
            if (hidden) hidden.value = composed;
            const preview = group.querySelector('.pr-job-preview');
            if (preview) preview.textContent = composed;
            checkPointRangeOverlaps();
        }

        function buildRangeRowHtml(from, to) {
            return `
                <div class="range-row">
                    <input type="number" class="form-input pr-range-from" value="${from}" placeholder="From" min="1" oninput="checkPointRangeOverlaps()">
                    <span class="range-separator">to</span>
                    <input type="number" class="form-input pr-range-to" value="${to}" placeholder="To" min="1" oninput="checkPointRangeOverlaps()">
                    <button type="button" class="remove-range-btn" onclick="removeRangeRow(this)" title="Remove range">
                        <i class="fas fa-times"></i>
                    </button>
                </div>`;
        }

        function parseRangeString(rangeStr) {
            if (!rangeStr) return [];
            const ranges = [];
            for (const part of rangeStr.split(',')) {
                const trimmed = part.trim();
                if (!trimmed) continue;
                const dashMatch = trimmed.match(/^(\d+)\s*-\s*(\d+)$/);
                const singleMatch = trimmed.match(/^(\d+)$/);
                if (dashMatch) {
                    ranges.push({ from: parseInt(dashMatch[1]), to: parseInt(dashMatch[2]) });
                } else if (singleMatch) {
                    const n = parseInt(singleMatch[1]);
                    ranges.push({ from: n, to: n });
                }
            }
            return ranges;
        }

        function addRangeRow(btn) {
            const container = btn.previousElementSibling;
            container.insertAdjacentHTML('beforeend', buildRangeRowHtml('', ''));
            checkPointRangeOverlaps();
        }

        function removeRangeRow(btn) {
            const row = btn.closest('.range-row');
            const container = row.closest('.pr-ranges-container');
            row.remove();
            if (container.querySelectorAll('.range-row').length === 0) {
                container.insertAdjacentHTML('beforeend', buildRangeRowHtml('', ''));
            }
            checkPointRangeOverlaps();
        }

        function serializeRangesFromEntry(entry) {
            const rangesContainer = entry.querySelector('.pr-ranges-container');
            if (!rangesContainer) return '';
            const parts = [];
            rangesContainer.querySelectorAll('.range-row').forEach(row => {
                const from = row.querySelector('.pr-range-from')?.value;
                const to = row.querySelector('.pr-range-to')?.value;
                if (from && to) {
                    parts.push(from === to ? from : `${from}-${to}`);
                }
            });
            return parts.join(', ');
        }

        function checkPointRangeOverlaps() {
            const entriesContainer = document.getElementById('pointRangesEntries');
            const warningEl = document.getElementById('pointRangesWarning');
            if (!entriesContainer || !warningEl) return;

            const entries = entriesContainer.querySelectorAll('.point-range-entry');
            const jobFileRanges = [];

            entries.forEach((entry, idx) => {
                const nameEl = entry.querySelector('.pr-job-file');
                const label = (nameEl && nameEl.value.trim()) ? nameEl.value.trim() : `Job File #${idx + 1}`;
                const ranges = [];
                entry.querySelectorAll('.range-row').forEach(row => {
                    const from = parseInt(row.querySelector('.pr-range-from')?.value);
                    const to = parseInt(row.querySelector('.pr-range-to')?.value);
                    if (!isNaN(from) && !isNaN(to) && from > 0 && to > 0) {
                        ranges.push({ from: Math.min(from, to), to: Math.max(from, to) });
                    }
                });
                jobFileRanges.push({ label, ranges, entry });
            });

            const overlaps = [];
            const conflictingEntries = new Set();
            for (let i = 0; i < jobFileRanges.length; i++) {
                for (let j = i + 1; j < jobFileRanges.length; j++) {
                    const a = jobFileRanges[i];
                    const b = jobFileRanges[j];
                    a.ranges.forEach(r1 => {
                        b.ranges.forEach(r2 => {
                            const start = Math.max(r1.from, r2.from);
                            const end = Math.min(r1.to, r2.to);
                            if (start <= end) {
                                const rangeStr = start === end ? String(start) : `${start}-${end}`;
                                overlaps.push({ rangeStr, label1: a.label, label2: b.label });
                                conflictingEntries.add(a.entry);
                                conflictingEntries.add(b.entry);
                            }
                        });
                    });
                }
            }

            entries.forEach(entry => {
                entry.classList.toggle('has-range-conflict', conflictingEntries.has(entry));
            });

            if (overlaps.length > 0) {
                const msgs = overlaps
                    .map(o => `Points <strong>${o.rangeStr}</strong> overlap between <em>${escapeHtml(o.label1)}</em> and <em>${escapeHtml(o.label2)}</em>`)
                    .join('<br>');
                warningEl.innerHTML = `<div class="range-overlap-warning"><i class="fas fa-exclamation-triangle"></i> <strong>Point range conflicts detected:</strong><br>${msgs}</div>`;
                warningEl.style.display = 'block';
            } else {
                warningEl.style.display = 'none';
            }
        }

        function addPointRangeEntry() {
            const session = allSessions.find(s => s.session_id === prEditSessionId);
            const container = document.getElementById('pointRangesEntries');
            if (!session || !container) return;

            const noEntriesMsg = container.querySelector('p');
            if (noEntriesMsg) {
                noEntriesMsg.remove();
            }

            const newIndex = container.querySelectorAll('.point-range-entry').length;
            container.insertAdjacentHTML('beforeend', buildPointRangeEntryHtml(session, {}, newIndex));
            checkPointRangeOverlaps();
        }

        function removePointRangeEntry(index) {
            const container = document.getElementById('pointRangesEntries');
            const entry = container.querySelector(`.point-range-entry[data-index="${index}"]`);
            if (entry) {
                entry.remove();

                const entries = container.querySelectorAll('.point-range-entry');
                entries.forEach((e, i) => {
                    e.dataset.index = i;
                    e.querySelector('.point-range-header span').innerHTML = `<i class="fas fa-file-alt"></i> Job File #${i + 1}`;
                    e.querySelector('.remove-entry-btn').setAttribute('onclick', `removePointRangeEntry(${i})`);
                });

                if (entries.length === 0) {
                    container.innerHTML = '<p style="color: var(--gray-500); text-align: center; padding: 1rem;">No point range entries yet. Click the button below to add one.</p>';
                }

                checkPointRangeOverlaps();
            }
        }

        async function savePointRanges(sessionId) {
            const session = allSessions.find(s => s.session_id === sessionId);
            const container = document.getElementById('pointRangesEntries');
            if (!session || !container) return;

            // A composed job file name needs both its parts
            const halfFilled = Array.from(container.querySelectorAll('.point-range-entry')).some(entryEl => {
                const date = entryEl.querySelector('.pr-job-date')?.value || '';
                const crew = entryEl.querySelector('.pr-job-crew')?.value || '';
                return (date && !crew) || (!date && crew);
            });
            if (halfFilled) {
                showToast('Each job file name needs both a date and a crew', 'error');
                return;
            }

            const collected = Array.from(container.querySelectorAll('.point-range-entry')).map(entryEl => ({
                task_id: session.task_id || parseInt(entryEl.querySelector('.pr-task')?.value, 10) || 0,
                job_file_name: entryEl.querySelector('.pr-job-file')?.value.trim() || '',
                point_number_used: serializeRangesFromEntry(entryEl),
                converted: entryEl.querySelector('.pr-converted')?.checked ? 'yes' : 'no',
                imported: entryEl.querySelector('.pr-imported')?.checked ? 'yes' : 'no',
                checked: entryEl.querySelector('.pr-checked')?.checked ? 'yes' : 'no',
                notes: entryEl.querySelector('.pr-notes')?.value.trim() || ''
            })).filter(e => e.job_file_name || e.point_number_used || e.notes);

            if (collected.some(e => !e.task_id)) {
                showToast('Select a task for every entry', 'error');
                return;
            }

            // Save per task: every task that had entries before or has them now
            const grouped = {};
            collected.forEach(e => {
                (grouped[e.task_id] = grouped[e.task_id] || []).push({
                    job_file_name: e.job_file_name,
                    point_number_used: e.point_number_used,
                    converted: e.converted,
                    imported: e.imported,
                    checked: e.checked,
                    notes: e.notes
                });
            });
            const affectedTaskIds = new Set(Object.keys(grouped).map(Number));
            (session.point_range_entries || []).forEach(e => {
                if (e.task_id) affectedTaskIds.add(Number(e.task_id));
            });

            try {
                for (const taskId of affectedTaskIds) {
                    const formData = new FormData();
                    formData.append('action', 'save_task_point_ranges');
                    formData.append('task_id', taskId);
                    formData.append('point_ranges', JSON.stringify({ entries: grouped[taskId] || [] }));
                    const response = await fetch(QC_API, { method: 'POST', body: formData });
                    const data = await response.json();
                    if (!data.success) {
                        showToast(data.message || 'Error saving point ranges', 'error');
                        return;
                    }
                }
                prEditSessionId = null;
                showToast('Point ranges saved');
                await loadSessions();
                renderSessions();
            } catch (error) {
                console.error('Error:', error);
                showToast('Network error saving point ranges', 'error');
            }
        }

        async function toggleSessionCard(sessionId) {
            if (prEditSessionId !== null) {
                prEditSessionId = null; // collapsing or switching cards discards an open editor
            }
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
            const sfInput = document.getElementById('sessionScaleFactor');
            sfInput.addEventListener('input', () => sfInput.classList.remove('auto-filled'));
        }

        function getProjectScaleFactor(projectId) {
            const project = allProjects.find(p => p.projectId === projectId);
            const sf = project ? String(project.scale_factor ?? '').trim() : '';
            return sf && !isNaN(parseFloat(sf)) ? sf : '';
        }

        // True when the session and its project both have a scale factor and they disagree
        function scaleFactorMismatch(session) {
            const projSf = parseFloat(getProjectScaleFactor(session.project_id));
            const sessSf = parseFloat(session.scale_factor);
            if (isNaN(projSf) || isNaN(sessSf)) return false;
            return Math.abs(projSf - sessSf) > 1e-9;
        }

        function updateProjectSfHint(projectId) {
            const hint = document.getElementById('projectSfHint');
            const projectSf = getProjectScaleFactor(projectId);
            if (projectSf) {
                hint.textContent = `Project scale factor: ${projectSf}`;
                hint.style.display = 'block';
            } else {
                hint.style.display = 'none';
            }
        }

        async function onProjectChange() {
            const projectId = document.getElementById('sessionProject').value;
            const pathInput = document.getElementById('sessionRawPath');

            // Suggest the conventional downloads path unless the user typed their own
            if (projectId && (pathInput.value === '' || pathInput.classList.contains('auto-filled'))) {
                pathInput.value = `N:\\${projectId}\\05 Service Groups\\Survey\\Downloads`;
                pathInput.classList.add('auto-filled');
            }

            // Default the session's scale factor from the project unless the user typed their own
            const sfInput = document.getElementById('sessionScaleFactor');
            const projectSf = getProjectScaleFactor(projectId);
            if (projectSf && (sfInput.value === '' || sfInput.classList.contains('auto-filled'))) {
                sfInput.value = projectSf;
                sfInput.classList.add('auto-filled');
            }
            updateProjectSfHint(projectId);

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
            document.getElementById('sessionScaleFactor').classList.remove('auto-filled');
            document.getElementById('projectSfHint').style.display = 'none';

            if (sessionId) {
                const s = allSessions.find(x => x.session_id === sessionId);
                if (!s) return;
                document.getElementById('sessionProject').value = s.project_id;
                document.getElementById('sessionDate').value = s.collection_date || '';
                document.getElementById('sessionRawPath').value = s.raw_data_path || '';
                populateCrewSelect(s.field_crew || '');
                document.getElementById('sessionInstrument').value = s.instrument || '';
                document.getElementById('sessionScaleFactor').value = s.scale_factor ? trimScaleFactor(s.scale_factor) : '';
                document.getElementById('sessionNotes').value = s.general_notes || '';
                setGeoValue('sessionCoordSystem', s.coordinate_system);
                setGeoValue('sessionDatum', s.datum_epoch);
                setGeoValue('sessionGeoid', s.geoid_model);
                setGeoValue('sessionVDatum', s.vertical_datum);
                setGeoValue('sessionUnits', s.units);
                updateProjectSfHint(s.project_id);
                await populateTaskSelect(s.project_id, s.task_id || '');
            } else {
                populateCrewSelect('');
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

        // Raw data paths are usually cloud folder links (OneDrive/SharePoint) rather
        // than local file paths — detect that so we can open them directly instead
        // of making people copy/paste into a browser.
        function isLikelyUrl(path) {
            return /^https?:\/\//i.test((path || '').trim());
        }

        function openRawPath(path) {
            path = (path || '').trim();
            if (!path) {
                showToast('No path to open', 'error');
                return;
            }
            if (!isLikelyUrl(path)) {
                showToast('Not a folder link — this looks like a local/network path', 'error');
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
