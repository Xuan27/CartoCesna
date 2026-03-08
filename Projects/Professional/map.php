<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Map - Survey Project Manager</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <link rel="stylesheet" href="../../Models/css/survey_projects_notes.css">
    <!-- Leaflet -->
    <link  rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

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
            <a href="tools.php" class="nav-item">
                <i class="fas fa-tools"></i> Tools
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

// ── State ──────────────────────────────────────────────────────────────────
let allProjects  = [];
let markers      = {};   // projectId → L.circleMarker
let panelOpen    = false;
const markerGroup = L.featureGroup().addTo(map);

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

// ── Load projects (server decodes plus codes) ──────────────────────────────
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

// ── Plot markers ───────────────────────────────────────────────────────────
function plotProjects(projects) {
    projects.forEach(p => {
        if (p.lat !== null && p.lon !== null) addMarker(p);
    });
    if (markerGroup.getLayers().length) {
        map.fitBounds(markerGroup.getBounds().pad(0.25));
    }
}

function addMarker(p) {
    const color  = statusColor(p.projectStatus);
    const marker = L.circleMarker([p.lat, p.lon], {
        radius: 9, fillColor: color,
        color: 'white', weight: 2, fillOpacity: 0.9,
    });
    marker.bindPopup(buildPopup(p), { maxWidth: 280 });
    marker.on('click', () => highlightPanelItem(p.projectId));
    marker.addTo(markerGroup);
    markers[p.projectId] = marker;
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
    map.flyTo(marker.getLatLng(), 16, { duration: 1 });
    marker.openPopup();
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
