<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Map - Survey Project Manager</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <link rel="stylesheet" href="../../Models/css/survey_projects_notes.css">
    <!-- Leaflet -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <!-- MarkerCluster -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" />
    <script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
    <!-- Esri Leaflet -->
    <script src="https://unpkg.com/esri-leaflet@3.0.12/dist/esri-leaflet.js"></script>

    <style>
        html, body { margin: 0; padding: 0; height: 100%; overflow: hidden; }

        /* Make the map fill the full right area */
        .main-content { display: flex; flex-direction: column; height: 100vh; padding: 0; }
        #map { flex: 1; z-index: 1; }

        /* Floating project list panel */
        #projectPanel {
            position: absolute;
            top: 0;
            right: 0;
            width: 320px;
            height: 100%;
            background: white;
            box-shadow: -3px 0 12px rgba(0,0,0,0.12);
            z-index: 500;
            display: flex;
            flex-direction: column;
            transform: translateX(100%);
            transition: transform 0.3s ease;
        }
        #projectPanel.open { transform: translateX(0); }

        .panel-header {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: white;
            padding: 1rem 1.25rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-shrink: 0;
        }
        .panel-header h3 { margin: 0; font-size: 0.95rem; font-weight: 600; }
        .panel-close {
            background: none; border: none; color: white;
            font-size: 1.1rem; cursor: pointer; opacity: 0.8; padding: 0;
        }
        .panel-close:hover { opacity: 1; }

        .panel-search {
            padding: 0.75rem;
            border-bottom: 1px solid var(--gray-200);
            flex-shrink: 0;
        }
        .panel-search input {
            width: 100%;
            box-sizing: border-box;
            padding: 0.45rem 0.75rem;
            border: 1px solid var(--gray-300);
            border-radius: 6px;
            font-size: 0.85rem;
        }
        .panel-search input:focus { outline: none; border-color: var(--primary-color); }

        .panel-body {
            flex: 1;
            overflow-y: auto;
            padding: 0.5rem 0;
        }

        .project-list-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.65rem 1.25rem;
            cursor: pointer;
            border-bottom: 1px solid var(--gray-100);
            transition: background 0.15s;
        }
        .project-list-item:hover { background: var(--gray-50); }
        .project-list-item.active { background: #eff6ff; }
        .project-list-item.no-code { opacity: 0.45; cursor: default; }

        .project-dot {
            width: 10px; height: 10px;
            border-radius: 50%;
            flex-shrink: 0;
        }
        .project-list-info { flex: 1; min-width: 0; }
        .project-list-name {
            font-size: 0.83rem;
            font-weight: 600;
            color: var(--gray-800);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .project-list-meta {
            font-size: 0.72rem;
            color: var(--gray-500);
            margin-top: 1px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .no-code-badge {
            font-size: 0.68rem;
            background: var(--gray-100);
            color: var(--gray-400);
            padding: 0.1rem 0.4rem;
            border-radius: 3px;
            flex-shrink: 0;
        }

        /* Toggle button floating on map */
        #togglePanelBtn {
            position: absolute;
            top: 1rem;
            right: 1rem;
            z-index: 600;
            background: white;
            border: 1px solid var(--gray-200);
            border-radius: 8px;
            padding: 0.5rem 0.9rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.12);
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--gray-700);
            display: flex;
            align-items: center;
            gap: 0.45rem;
            transition: all 0.15s;
        }
        #togglePanelBtn:hover { background: var(--gray-50); border-color: var(--primary-color); color: var(--primary-color); }

        /* Layer toggle button */
        #toggleControlPtsBtn {
            position: absolute;
            top: 1rem;
            right: 8.5rem;
            z-index: 600;
            background: white;
            border: 1px solid var(--gray-200);
            border-radius: 8px;
            padding: 0.5rem 0.9rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.12);
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--gray-700);
            display: flex;
            align-items: center;
            gap: 0.45rem;
            transition: all 0.15s;
        }
        #toggleControlPtsBtn:hover  { background: var(--gray-50); border-color: #f59e0b; color: #f59e0b; }
        #toggleControlPtsBtn.active { background: #fffbeb; border-color: #f59e0b; color: #b45309; }

        /* Control point popup */
        .cp-popup { min-width: 200px; font-size: 0.82rem; }
        .cp-popup-title { font-weight: 700; font-size: 0.9rem; color: var(--gray-900); margin-bottom: 0.4rem; }
        .cp-popup-row { color: var(--gray-600); display: flex; gap: 0.4rem; margin-top: 0.2rem; align-items: flex-start; }
        .cp-popup-row i { margin-top: 2px; flex-shrink: 0; color: #f59e0b; }

        /* ArcGIS login modal */
        #arcgisLoginOverlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.45);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        #arcgisLoginOverlay.open { display: flex; }
        #arcgisLoginBox {
            background: white;
            border-radius: 12px;
            padding: 1.75rem 2rem;
            width: 320px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.2);
        }
        #arcgisLoginBox h3 {
            margin: 0 0 0.25rem;
            font-size: 1rem;
            color: var(--gray-900);
        }
        #arcgisLoginBox p {
            margin: 0 0 1.25rem;
            font-size: 0.8rem;
            color: var(--gray-500);
        }
        #arcgisLoginBox label {
            display: block;
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--gray-700);
            margin-bottom: 0.25rem;
        }
        #arcgisLoginBox input {
            width: 100%;
            box-sizing: border-box;
            padding: 0.5rem 0.75rem;
            border: 1px solid var(--gray-300);
            border-radius: 6px;
            font-size: 0.875rem;
            margin-bottom: 1rem;
        }
        #arcgisLoginBox input:focus { outline: none; border-color: #f59e0b; }
        #arcgisLoginError {
            font-size: 0.8rem;
            color: var(--danger-color);
            margin-bottom: 0.75rem;
            display: none;
        }
        .arcgis-login-actions { display: flex; gap: 0.75rem; justify-content: flex-end; }
        .arcgis-login-actions button {
            padding: 0.45rem 1rem;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            border: 1px solid var(--gray-300);
            background: white;
            color: var(--gray-700);
        }
        .arcgis-login-actions button.primary {
            background: #f59e0b;
            border-color: #f59e0b;
            color: white;
        }
        .arcgis-login-actions button.primary:hover { background: #d97706; }
        .arcgis-login-actions button.primary:disabled { opacity: 0.6; cursor: default; }

        /* Loading overlay */
        #mapLoading {
            position: absolute;
            bottom: 1rem;
            left: 50%;
            transform: translateX(-50%);
            z-index: 600;
            background: white;
            border-radius: 20px;
            padding: 0.45rem 1rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            font-size: 0.8rem;
            color: var(--gray-600);
            display: none;
            align-items: center;
            gap: 0.5rem;
        }

        /* Permanent marker labels */
        .marker-label {
            background: white;
            border: 1px solid rgba(0,0,0,0.15);
            border-radius: 5px;
            padding: 2px 6px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.15);
            pointer-events: none;
            white-space: nowrap;
            line-height: 1.3;
        }
        .marker-label-name {
            font-size: 0.72rem;
            font-weight: 600;
            color: var(--gray-800);
            display: block;
        }
        .marker-label-id {
            font-size: 0.65rem;
            color: var(--gray-400);
            font-family: monospace;
            display: block;
        }
        /* Hide labels when inside a cluster */
        .leaflet-marker-icon .marker-label { display: none; }

        /* Stacked marker (multiple projects at the same coordinate) */
        .stack-marker-icon {
            border-radius: 50%;
            background: #7c3aed;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            border: 3px solid white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.25);
        }
        .stack-popup { min-width: 230px; max-width: 280px; }
        .stack-popup-header {
            font-weight: 700;
            font-size: 0.85rem;
            color: var(--gray-900);
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }
        .stack-popup-item { padding: 0.3rem 0; }
        .stack-popup-divider { border: none; border-top: 1px solid var(--gray-200); margin: 0.5rem 0; }

        /* Strip default Leaflet tooltip chrome from marker labels */
        .marker-label-tooltip {
            background: transparent !important;
            border: none !important;
            box-shadow: none !important;
            padding: 0 !important;
        }
        .marker-label-tooltip::before { display: none !important; }

        /* Custom marker popup */
        .leaflet-popup-content-wrapper { border-radius: 10px; }
        .map-popup { min-width: 220px; }
        .map-popup-id {
            font-size: 0.72rem;
            color: var(--gray-400);
            margin-bottom: 0.2rem;
            font-family: monospace;
        }
        .map-popup-name {
            font-weight: 700;
            font-size: 0.95rem;
            color: var(--gray-900);
            margin-bottom: 0.4rem;
        }
        .map-popup-row {
            font-size: 0.8rem;
            color: var(--gray-600);
            display: flex;
            align-items: center;
            gap: 0.4rem;
            margin-top: 0.2rem;
        }
        .map-popup-status {
            display: inline-block;
            padding: 0.15rem 0.5rem;
            border-radius: 20px;
            font-size: 0.72rem;
            font-weight: 600;
        }
        .map-popup-link {
            display: inline-block;
            margin-top: 0.6rem;
            font-size: 0.8rem;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
        }
        .map-popup-link:hover { text-decoration: underline; }
    </style>
</head>
<body>

<div class="container">
    <button class="mobile-toggle" id="mobileToggle"><i class="fas fa-bars"></i></button>
    <div class="sidebar" id="sidebar">
        <button class="toggle-btn" id="toggleBtn"><i class="fas fa-chevron-left"></i></button>
        <div class="sidebar-header">
            <h1><i class="fas fa-map-marked-alt"></i> Survey Pro</h1>
            <p>Professional Project Management</p>
        </div>
        <nav class="sidebar-nav">
            <a href="survey_projects.php" class="nav-item">
                <i class="fas fa-th-large"></i> Dashboard
            </a>
            <a href="survey_projects.php?view=todo" class="nav-item">
                <i class="fas fa-star"></i> My To-Do
            </a>
            <a href="all_tasks.php" class="nav-item">
                <i class="fas fa-folder-open"></i> All Tasks
            </a>
            <a href="#" class="nav-item">
                <i class="fas fa-chart-bar"></i> Analytics
            </a>
            <a href="checklists.php" class="nav-item">
                <i class="fas fa-clipboard-check"></i> Checklists
            </a>
            <a href="map.php" class="nav-item active">
                <i class="fas fa-map"></i> Map
            </a>
            <a href="monuments.php" class="nav-item">
                <i class="fas fa-map-pin"></i> Monuments
            </a>
            <a href="field_data_qc.php" class="nav-item">
                <i class="fas fa-clipboard-list"></i> Field Data QC
            </a>
            <a href="control_points.php" class="nav-item">
                <i class="fas fa-crosshairs"></i> Control Points
            </a>
            <a href="#" class="nav-item">
                <i class="fas fa-cog"></i> Settings
            </a>
        </nav>
    </div>
</div>

<!-- Main content: full-height map -->
<div class="main-content" style="position: relative;">
    <div id="map"></div>

    <!-- Toggle panel button -->
    <button id="togglePanelBtn" onclick="togglePanel()">
        <i class="fas fa-list"></i> Projects
    </button>

    <!-- Control points layer toggle -->
    <button id="toggleControlPtsBtn" onclick="toggleControlPoints()" title="Toggle Survey Control Points">
        <i class="fas fa-crosshairs"></i> Control Pts
    </button>

    <!-- Loading indicator -->
    <div id="mapLoading">
        <i class="fas fa-spinner fa-spin"></i>
        <span id="loadingText">Loading projects…</span>
    </div>

    <!-- Floating project list panel -->
    <div id="projectPanel">
        <div class="panel-header">
            <h3><i class="fas fa-map-marker-alt" style="margin-right:0.5rem;"></i>Projects on Map</h3>
            <button class="panel-close" onclick="togglePanel()"><i class="fas fa-times"></i></button>
        </div>
        <div class="panel-search">
            <input type="text" id="panelSearch" placeholder="Search projects…" oninput="filterPanel(this.value)">
        </div>
        <div class="panel-body" id="panelBody">
            <div style="padding:2rem; text-align:center; color:var(--gray-400);">
                <i class="fas fa-spinner fa-spin" style="font-size:1.5rem; margin-bottom:0.5rem;"></i>
                <p>Loading projects…</p>
            </div>
        </div>
    </div>

    <!-- ArcGIS Login Modal -->
    <div id="arcgisLoginOverlay">
        <div id="arcgisLoginBox">
            <h3><i class="fas fa-crosshairs" style="color:#f59e0b;margin-right:6px;"></i>ArcGIS Sign In</h3>
            <p>Sign in with your Westwood ArcGIS account to load survey control points.</p>
            <label for="arcgisUsername">Username</label>
            <input type="text" id="arcgisUsername" placeholder="ArcGIS username" autocomplete="username">
            <label for="arcgisPassword">Password</label>
            <input type="password" id="arcgisPassword" placeholder="ArcGIS password" autocomplete="current-password">
            <div id="arcgisLoginError"></div>
            <div class="arcgis-login-actions">
                <button onclick="closeArcGISLogin()">Cancel</button>
                <button class="primary" id="arcgisLoginBtn" onclick="submitArcGISLogin()">
                    <i class="fas fa-sign-in-alt"></i> Sign In
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// ── Status colours ─────────────────────────────────────────────────────────
const STATUS_COLORS = {
    'Active':    '#2563eb', 'Completed': '#16a34a',
    'On Hold':   '#d97706', 'Cancelled': '#6b7280', 'Inactive': '#6b7280',
};
const STATUS_BG = {
    'Active':'#dbeafe','Completed':'#dcfce7','On Hold':'#fef3c7',
    'Cancelled':'#f3f4f6','Inactive':'#f3f4f6',
};
function statusColor(s) { return STATUS_COLORS[s] || '#6b7280'; }
function statusBg(s)    { return STATUS_BG[s]    || '#f3f4f6'; }

// ── Leaflet map ────────────────────────────────────────────────────────────
const map = L.map('map', { zoomControl: false }).setView([31.0, -98.0], 6);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    maxZoom: 19
}).addTo(map);
L.control.zoom({ position: 'bottomright' }).addTo(map);

// ── ArcGIS Survey Control Points layer ─────────────────────────────────────
const CONTROL_PTS_QUERY = 'https://dservices.arcgis.com/FqQ2BQIKVGpUWqAa/arcgis/services/SurveyControl/WFSServer';
const ARCGIS_TOKEN_API  = '../../Models/php/arcgis_token.php';
let controlPtsLayer  = null;
let controlPtsVisible = false;
let arcgisToken      = null;

// ── Login modal ─────────────────────────────────────────────────────────────
function openArcGISLogin() {
    document.getElementById('arcgisLoginOverlay').classList.add('open');
    document.getElementById('arcgisLoginError').style.display = 'none';
    document.getElementById('arcgisUsername').focus();
}
function closeArcGISLogin() {
    document.getElementById('arcgisLoginOverlay').classList.remove('open');
    document.getElementById('arcgisLoginBtn').disabled = false;
    document.getElementById('arcgisLoginBtn').innerHTML = '<i class="fas fa-sign-in-alt"></i> Sign In';
    // If user cancelled and layer wasn't loaded, reset button
    if (!arcgisToken) {
        document.getElementById('toggleControlPtsBtn').classList.remove('active');
        controlPtsVisible = false;
    }
}

document.addEventListener('keydown', e => {
    if (e.key === 'Enter' && document.getElementById('arcgisLoginOverlay').classList.contains('open')) {
        submitArcGISLogin();
    }
});

async function submitArcGISLogin() {
    const username = document.getElementById('arcgisUsername').value.trim();
    const password = document.getElementById('arcgisPassword').value;
    const errEl    = document.getElementById('arcgisLoginError');
    const btn      = document.getElementById('arcgisLoginBtn');

    if (!username || !password) {
        errEl.textContent = 'Please enter username and password.';
        errEl.style.display = 'block';
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Signing in…';
    errEl.style.display = 'none';

    try {
        const res  = await fetch(ARCGIS_TOKEN_API, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ username, password })
        });
        const data = await res.json();

        if (!data.success) {
            errEl.textContent = data.message || 'Sign in failed.';
            errEl.style.display = 'block';
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-sign-in-alt"></i> Sign In';
            return;
        }

        arcgisToken = data.token;
        document.getElementById('arcgisPassword').value = '';
        closeArcGISLogin();
        loadControlPoints();

    } catch (err) {
        errEl.textContent = 'Network error. Please try again.';
        errEl.style.display = 'block';
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-sign-in-alt"></i> Sign In';
    }
}

// ── Toggle & load control points ────────────────────────────────────────────
function toggleControlPoints() {
    const btn = document.getElementById('toggleControlPtsBtn');
    if (controlPtsVisible) {
        if (controlPtsLayer) map.removeLayer(controlPtsLayer);
        controlPtsVisible = false;
        btn.classList.remove('active');
        return;
    }
    controlPtsVisible = true;
    btn.classList.add('active');

    if (controlPtsLayer) { controlPtsLayer.addTo(map); return; }
    // Load control points from local database (no ArcGIS login required)
    loadControlPoints();
}

async function loadControlPoints() {
    showLoading('Loading control points…');
    try {
        if (controlPtsLayer) map.removeLayer(controlPtsLayer);

        // Load locally-imported control points from database
        const res = await fetch('../../Models/php/control_points_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=get_points'
        });
        const data = await res.json();

        console.log('Control points API response:', data);

        if (data.success && data.points && data.points.length > 0) {
            console.log(`Loading ${data.points.length} control points from database`);
            const featureGroup = L.featureGroup();
            let pointsWithCoords = 0;

            data.points.forEach(point => {
                // Only add points with latitude/longitude
                if (point.latitude && point.longitude) {
                    pointsWithCoords++;
                    console.log(`Adding point ${point.point_number} at [${point.latitude}, ${point.longitude}]`);

                    const marker = L.circleMarker([parseFloat(point.latitude), parseFloat(point.longitude)], {
                        radius: 5,
                        fillColor: '#3b82f6',
                        color: '#1d4ed8',
                        weight: 1.5,
                        fillOpacity: 0.85
                    });

                    marker.bindPopup(`
                        <div class="cp-popup">
                            <div class="cp-popup-title">${esc(point.point_number)}</div>
                            ${point.point_name ? `<div class="cp-popup-row"><i class="fas fa-circle-dot"></i><strong>Name:</strong> ${esc(point.point_name)}</div>` : ''}
                            <div class="cp-popup-row"><i class="fas fa-circle-dot"></i><strong>Type:</strong> ${esc(point.point_type || 'Control')}</div>
                            <div class="cp-popup-row"><i class="fas fa-circle-dot"></i><strong>Status:</strong> ${esc(point.status || 'Unknown')}</div>
                            ${point.latitude ? `<div class="cp-popup-row"><i class="fas fa-circle-dot"></i><strong>Lat:</strong> ${parseFloat(point.latitude).toFixed(7)}</div>` : ''}
                            ${point.longitude ? `<div class="cp-popup-row"><i class="fas fa-circle-dot"></i><strong>Lon:</strong> ${parseFloat(point.longitude).toFixed(7)}</div>` : ''}
                            ${point.elevation ? `<div class="cp-popup-row"><i class="fas fa-circle-dot"></i><strong>Elev:</strong> ${parseFloat(point.elevation).toFixed(2)}</div>` : ''}
                        </div>
                    `, { maxWidth: 300 });

                    featureGroup.addLayer(marker);
                }
            });

            console.log(`Added ${pointsWithCoords} points with coordinates to map`);

            if (pointsWithCoords > 0) {
                controlPtsLayer = featureGroup.addTo(map);
            } else {
                console.warn('No control points have latitude/longitude values - check if CSV transform succeeded');
                showToast('No points with coordinates found', 'error');
            }
        } else {
            console.log('No control points found in database');
            showToast('No control points in database', 'info');
        }

    } catch (err) {
        console.error('Control points error:', err);
        showToast('Error loading control points', 'error');
        document.getElementById('toggleControlPtsBtn').classList.remove('active');
        controlPtsVisible = false;
    }
    showLoading(null);
}

function buildControlPopup(feature) {
    const p    = feature.properties || {};
    const skip = new Set(['FID', 'OBJECTID', 'Shape', 'GlobalID', 'SHAPE']);
    let rows   = '';
    for (const [key, val] of Object.entries(p)) {
        if (skip.has(key) || val === null || val === '' || val === undefined) continue;
        rows += `<div class="cp-popup-row"><i class="fas fa-circle-dot"></i><span><strong>${key}:</strong> ${esc(String(val))}</span></div>`;
    }
    const title = p.Name || p.NAME || p.PointName || p.POINT_NAME || p.StationName || p.STATION || 'Control Point';
    return `<div class="cp-popup">
        <div class="cp-popup-title"><i class="fas fa-crosshairs" style="color:#f59e0b;margin-right:4px;"></i>${esc(title)}</div>
        ${rows || '<div class="cp-popup-row">No attributes available.</div>'}
    </div>`;
}

// ── State ──────────────────────────────────────────────────────────────────
let allProjects = [];
let markers     = {};    // projectId → marker
let panelOpen   = false;

// MarkerCluster group — handles grouping + performance for large datasets
const clusterGroup = L.markerClusterGroup({
    maxClusterRadius: 60,
    spiderfyOnMaxZoom: true,
    showCoverageOnHover: false,
    zoomToBoundsOnClick: true,
    iconCreateFunction(cluster) {
        const count = cluster.getChildCount();
        const size  = count < 10 ? 36 : count < 100 ? 44 : 52;
        return L.divIcon({
            html: `<div style="width:${size}px;height:${size}px;border-radius:50%;
                        background:var(--primary-color);color:white;
                        display:flex;align-items:center;justify-content:center;
                        font-weight:700;font-size:${size < 44 ? 13 : 15}px;
                        border:3px solid white;box-shadow:0 2px 8px rgba(0,0,0,0.25);">
                        ${count}
                   </div>`,
            className: '',
            iconSize: [size, size],
            iconAnchor: [size / 2, size / 2],
        });
    }
}).addTo(map);

// ── Init ───────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    const sidebar     = document.getElementById('sidebar');
    const mainContent = document.querySelector('.main-content');

    document.getElementById('toggleBtn').addEventListener('click', () => {
        sidebar.classList.toggle('collapsed');
        mainContent.classList.toggle('collapsed');
        setTimeout(() => map.invalidateSize(), 320);
    });
    document.getElementById('mobileToggle').addEventListener('click', () => {
        sidebar.classList.toggle('open');
    });

    loadProjects();
});

// ── Load projects — coordinates come pre-decoded from server ───────────────
async function loadProjects() {
    showLoading('Loading projects…');
    try {
        const res  = await fetch('../../Models/php/map_api.php');
        const data = await res.json();
        if (!data.success) throw new Error(data.message || 'API error');
        allProjects = data.projects || [];
        renderPanel(allProjects);
        plotProjects(allProjects);
    } catch (err) {
        console.error('Map load error:', err);
        document.getElementById('panelBody').innerHTML =
            `<div style="padding:2rem;text-align:center;color:var(--danger-color);">${esc(err.message)}</div>`;
    }
    showLoading(null);
}

// ── Plot markers (no geocoding — lat/lon already stored in DB) ─────────────
function plotProjects(projects) {
    clusterGroup.clearLayers();
    markers = {};
    const groups = groupByCoordinate(projects.filter(p => p.lat !== null && p.lon !== null));
    groups.forEach(group => {
        if (group.length === 1) addMarker(group[0]);
        else addStackMarker(group);
    });
    if (clusterGroup.getLayers().length) {
        map.fitBounds(clusterGroup.getBounds().pad(0.25));
    }
}

// Group projects that sit on (or effectively on) the same coordinate so we
// plot one marker per point instead of stacking overlapping labels.
function groupByCoordinate(projects) {
    const byKey = new Map();
    projects.forEach(p => {
        const key = `${Number(p.lat).toFixed(6)},${Number(p.lon).toFixed(6)}`;
        if (!byKey.has(key)) byKey.set(key, []);
        byKey.get(key).push(p);
    });
    return Array.from(byKey.values());
}

function addMarker(p) {
    const color  = statusColor(p.projectStatus);
    const marker = L.circleMarker([p.lat, p.lon], {
        radius: 9, fillColor: color,
        color: 'white', weight: 2, fillOpacity: 0.9,
    });
    marker.bindPopup(buildPopup(p), { maxWidth: 280 });
    marker.bindTooltip(
        `<div class="marker-label">
            <span class="marker-label-name">${esc(p.projectName)}</span>
            <span class="marker-label-id">${esc(p.projectId)}</span>
         </div>`,
        { permanent: true, direction: 'right', offset: [10, 0], className: 'marker-label-tooltip' }
    );
    marker.on('click', () => {
        map.flyTo([p.lat, p.lon], Math.max(map.getZoom(), 14), { duration: 0.6, animate: true });
        highlightPanelItem(p.projectId);
    });
    clusterGroup.addLayer(marker);
    markers[p.projectId] = marker;
}

// A single marker representing multiple projects that share one coordinate.
// Avoids plotting overlapping circle markers + overlapping name labels.
function addStackMarker(group) {
    const lat   = group[0].lat, lon = group[0].lon;
    const count = group.length;
    const size  = count < 10 ? 30 : 38;

    const marker = L.marker([lat, lon], {
        icon: L.divIcon({
            html: `<div class="stack-marker-icon" style="width:${size}px;height:${size}px;font-size:${size < 34 ? 13 : 15}px;">${count}</div>`,
            className: '',
            iconSize: [size, size],
            iconAnchor: [size / 2, size / 2],
        })
    });

    marker.bindPopup(buildStackPopup(group), { maxWidth: 300 });
    marker.bindTooltip(
        `<div class="marker-label">
            <span class="marker-label-name">${count} projects here</span>
         </div>`,
        { permanent: true, direction: 'right', offset: [size / 2 + 4, 0], className: 'marker-label-tooltip' }
    );
    marker.on('click', () => {
        map.flyTo([lat, lon], Math.max(map.getZoom(), 14), { duration: 0.6, animate: true });
    });

    clusterGroup.addLayer(marker);
    group.forEach(p => { markers[p.projectId] = marker; });
}

function buildStackPopup(group) {
    const items = group.map(p => {
        const color = statusColor(p.projectStatus);
        const bg    = statusBg(p.projectStatus);
        const loc   = p.location ? `<div class="map-popup-row"><i class="fas fa-location-dot"></i> ${esc(p.location)}</div>` : '';
        return `
            <div class="stack-popup-item">
                <div class="map-popup-id">${esc(p.projectId)}</div>
                <div class="map-popup-name">${esc(p.projectName)}</div>
                <span class="map-popup-status" style="background:${bg};color:${color};">${esc(p.projectStatus)}</span>
                ${loc}
                <a class="map-popup-link" href="survey_projects.php?project=${esc(p.projectId)}">
                    <i class="fas fa-external-link-alt"></i> View in Dashboard
                </a>
            </div>`;
    }).join('<hr class="stack-popup-divider">');

    return `
        <div class="map-popup stack-popup">
            <div class="stack-popup-header"><i class="fas fa-layer-group"></i> ${group.length} projects at this location</div>
            ${items}
        </div>`;
}

function buildPopup(p) {
    const color = statusColor(p.projectStatus);
    const bg    = statusBg(p.projectStatus);
    const loc   = p.location  ? `<div class="map-popup-row"><i class="fas fa-location-dot"></i> ${esc(p.location)}</div>`  : '';
    const code  = p.plus_code ? `<div class="map-popup-row"><i class="fas fa-qrcode"></i> ${esc(p.plus_code)}</div>` : '';
    return `
        <div class="map-popup">
            <div class="map-popup-id">${esc(p.projectId)}</div>
            <div class="map-popup-name">${esc(p.projectName)}</div>
            <span class="map-popup-status" style="background:${bg};color:${color};">${esc(p.projectStatus)}</span>
            ${loc}${code}
            <a class="map-popup-link" href="survey_projects.php?project=${esc(p.projectId)}">
                <i class="fas fa-external-link-alt"></i> View in Dashboard
            </a>
        </div>`;
}

// ── Project panel ──────────────────────────────────────────────────────────
function togglePanel() {
    panelOpen = !panelOpen;
    document.getElementById('projectPanel').classList.toggle('open', panelOpen);
    setTimeout(() => map.invalidateSize(), 320);
}

function renderPanel(projects) {
    const mapped    = projects.filter(p => p.lat !== null);
    const unmapped  = projects.filter(p => p.lat === null);

    let html = '';
    if (mapped.length) {
        html += sectionLabel(`${mapped.length} on map`);
        html += mapped.map(p => projectListItem(p, true)).join('');
    }
    if (unmapped.length) {
        html += sectionLabel(`${unmapped.length} without plus code`);
        html += unmapped.map(p => projectListItem(p, false)).join('');
    }
    if (!projects.length) {
        html = `<div style="padding:2rem;text-align:center;color:var(--gray-400);">No projects found.</div>`;
    }
    document.getElementById('panelBody').innerHTML = html;
}

function sectionLabel(text) {
    return `<div style="padding:0.5rem 1.25rem 0.2rem;font-size:0.72rem;font-weight:600;color:var(--gray-400);text-transform:uppercase;letter-spacing:0.05em;">${text}</div>`;
}

function projectListItem(p, hasMarker) {
    const color     = statusColor(p.projectStatus);
    const codeText  = p.plus_code ? esc(p.plus_code) : 'No plus code';
    const clickAttr = hasMarker ? `onclick="focusProject('${esc(p.projectId)}')"` : '';
    return `
        <div class="project-list-item${hasMarker ? '' : ' no-code'}" id="panel-item-${esc(p.projectId)}" ${clickAttr}>
            <div class="project-dot" style="background:${color};"></div>
            <div class="project-list-info">
                <div class="project-list-name" title="${esc(p.projectName)}">${esc(p.projectName)}</div>
                <div class="project-list-meta">${esc(p.projectId)} · ${codeText}</div>
            </div>
            ${!hasMarker ? '<span class="no-code-badge">No code</span>' : ''}
        </div>`;
}

function filterPanel(query) {
    const q = query.toLowerCase();
    const filtered = allProjects.filter(p =>
        p.projectName.toLowerCase().includes(q) ||
        p.projectId.toLowerCase().includes(q) ||
        (p.location   || '').toLowerCase().includes(q) ||
        (p.plus_code  || '').toLowerCase().includes(q)
    );
    renderPanel(filtered);
}

function focusProject(projectId) {
    const marker = markers[projectId];
    if (!marker) return;
    map.flyTo(marker.getLatLng(), 16, { duration: 1, animate: true });
    // Wait for fly animation then open popup (cluster may need to be expanded first)
    setTimeout(() => {
        clusterGroup.zoomToShowLayer(marker, () => marker.openPopup());
    }, 900);
    highlightPanelItem(projectId);
}

function highlightPanelItem(projectId) {
    document.querySelectorAll('.project-list-item.active').forEach(el => el.classList.remove('active'));
    const el = document.getElementById(`panel-item-${projectId}`);
    if (el) { el.classList.add('active'); el.scrollIntoView({ block: 'nearest', behavior: 'smooth' }); }
}

// ── Helpers ────────────────────────────────────────────────────────────────
function showLoading(msg) {
    const el = document.getElementById('mapLoading');
    if (!msg) { el.style.display = 'none'; return; }
    document.getElementById('loadingText').textContent = msg;
    el.style.display = 'flex';
}

function esc(str) {
    if (!str) return '';
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>
</body>
</html>
