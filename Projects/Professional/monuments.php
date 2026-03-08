<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monument Setting - Survey Project Manager</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <link rel="stylesheet" href="../../Models/css/survey_projects_notes.css">
    <style>
        .monument-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 1rem;
        }
        .monument-card {
            background: white;
            border: 1px solid var(--gray-200);
            border-radius: 10px;
            padding: 1.25rem;
            transition: box-shadow 0.2s, border-color 0.2s;
            position: relative;
        }
        .monument-card:hover {
            box-shadow: 0 4px 16px rgba(37,99,235,0.1);
            border-color: var(--primary-color);
        }
        .monument-card-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 0.75rem;
            margin-bottom: 0.75rem;
        }
        .monument-card-id {
            font-size: 0.72rem;
            font-family: monospace;
            color: var(--primary-color);
            font-weight: 600;
            background: #eff6ff;
            padding: 0.15rem 0.45rem;
            border-radius: 4px;
        }
        .monument-card-name {
            font-size: 1rem;
            font-weight: 700;
            color: var(--gray-900);
            margin-top: 0.25rem;
            line-height: 1.3;
        }
        .monument-badge {
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #fcd34d;
            border-radius: 6px;
            padding: 0.25rem 0.6rem;
            font-size: 0.75rem;
            font-weight: 700;
            white-space: nowrap;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }
        .monument-card-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 0.6rem;
        }
        .monument-meta-item {
            display: flex;
            align-items: center;
            gap: 0.3rem;
            font-size: 0.78rem;
            color: var(--gray-500);
        }
        .monument-meta-item i {
            width: 14px;
            text-align: center;
        }
        .monument-card-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
            padding-top: 0.75rem;
            border-top: 1px solid var(--gray-100);
        }
        .monument-empty {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--gray-400);
        }
        .monument-empty i {
            font-size: 3.5rem;
            margin-bottom: 1rem;
            color: var(--gray-300);
        }
        .monument-count-bar {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.25rem;
        }
        .monument-stat {
            background: white;
            border: 1px solid var(--gray-200);
            border-radius: 8px;
            padding: 0.6rem 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.85rem;
        }
        .monument-stat strong { font-size: 1.2rem; color: var(--gray-900); }
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
            <a href="map.php" class="nav-item">
                <i class="fas fa-map"></i> Map
            </a>
            <a href="monuments.php" class="nav-item active">
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

<!-- Main Content -->
<div class="main-content">
    <div class="top-bar">
        <div class="top-bar-left">
            <button class="mobile-menu-button" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
            <div>
                <h2>Monument Setting</h2>
                <p>Projects that require physical survey monuments to be set in the field</p>
            </div>
        </div>
        <div class="top-bar-right">
            <input type="text" class="form-input" id="searchInput" placeholder="Search projects…"
                   style="width:220px;" oninput="filterCards(this.value)">
        </div>
    </div>

    <div class="content-area">
        <!-- Stats bar -->
        <div class="monument-count-bar" id="statsBar" style="display:none;">
            <div class="monument-stat">
                <i class="fas fa-map-pin" style="color:#d97706;"></i>
                <span>Needing monuments:</span>
                <strong id="statTotal">—</strong>
            </div>
            <div class="monument-stat">
                <i class="fas fa-circle" style="color:#2563eb;font-size:0.6rem;"></i>
                <span>Active:</span>
                <strong id="statActive">—</strong>
            </div>
            <div class="monument-stat">
                <i class="fas fa-circle" style="color:#16a34a;font-size:0.6rem;"></i>
                <span>Completed:</span>
                <strong id="statCompleted">—</strong>
            </div>
        </div>

        <div id="monumentsContainer">
            <div style="text-align:center;padding:3rem;color:var(--gray-400);">
                <i class="fas fa-spinner fa-spin" style="font-size:2rem;margin-bottom:0.5rem;"></i>
                <p>Loading projects…</p>
            </div>
        </div>
    </div>
</div>

<!-- Toast -->
<div id="toast" class="toast">
    <div class="toast-content">
        <i class="fas fa-check-circle toast-icon"></i>
        <span id="toastMessage">Done</span>
    </div>
</div>

<script>
let allMonumentProjects = [];

document.addEventListener('DOMContentLoaded', () => {
    setupSidebar();
    loadProjects();
});

function setupSidebar() {
    const sidebar     = document.getElementById('sidebar');
    const mainContent = document.querySelector('.main-content');
    document.getElementById('toggleBtn').addEventListener('click', () => {
        sidebar.classList.toggle('collapsed');
        mainContent.classList.toggle('collapsed');
    });
    document.getElementById('mobileToggle').addEventListener('click', () => {
        sidebar.classList.toggle('open');
    });
}
function toggleSidebar() { document.getElementById('sidebar').classList.toggle('open'); }

async function loadProjects() {
    try {
        const fd = new FormData();
        fd.append('action', 'load_project');
        const res  = await fetch('../../Models/php/load_survey_project_notes.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (!data.success) throw new Error(data.message);
        allMonumentProjects = (data.projects || []).filter(p => parseInt(p.needs_monuments) === 1);
        renderCards(allMonumentProjects);
    } catch (err) {
        document.getElementById('monumentsContainer').innerHTML =
            `<div style="text-align:center;padding:2rem;color:var(--danger-color);">${escHtml(err.message)}</div>`;
    }
}

function renderCards(projects) {
    const container = document.getElementById('monumentsContainer');

    // Stats
    document.getElementById('statTotal').textContent     = projects.length;
    document.getElementById('statActive').textContent    = projects.filter(p => p.projectStatus === 'Active').length;
    document.getElementById('statCompleted').textContent = projects.filter(p => p.projectStatus === 'Completed').length;
    document.getElementById('statsBar').style.display    = projects.length ? 'flex' : 'none';

    if (!projects.length) {
        container.innerHTML = `
            <div class="monument-empty">
                <i class="fas fa-map-pin"></i>
                <h3>No projects need monuments</h3>
                <p style="margin-top:0.5rem;">Projects with "Needs Monument Setting" checked will appear here.</p>
                <a href="survey_projects.php" class="btn btn-primary" style="display:inline-flex;margin-top:1rem;">
                    <i class="fas fa-th-large"></i> Go to Dashboard
                </a>
            </div>`;
        return;
    }

    container.innerHTML = `<div class="monument-grid">${projects.map(buildCard).join('')}</div>`;
}

function buildCard(p) {
    const statusColors = { 'Active':'#2563eb','Completed':'#16a34a','On Hold':'#d97706','Cancelled':'#6b7280' };
    const statusBgs    = { 'Active':'#dbeafe','Completed':'#dcfce7','On Hold':'#fef3c7','Cancelled':'#f3f4f6' };
    const color = statusColors[p.projectStatus] || '#6b7280';
    const bg    = statusBgs[p.projectStatus]    || '#f3f4f6';

    const location  = p.location  ? `<div class="monument-meta-item"><i class="fas fa-location-dot"></i>${escHtml(p.location)}</div>` : '';
    const plusCode  = p.plus_code ? `<div class="monument-meta-item"><i class="fas fa-qrcode"></i><span style="font-family:monospace;">${escHtml(p.plus_code)}</span></div>` : '';
    const createdBy = p.createdBy ? `<div class="monument-meta-item"><i class="fas fa-user"></i>${escHtml(p.createdBy)}</div>` : '';

    return `
        <div class="monument-card" id="card-${escHtml(p.projectId)}">
            <div class="monument-card-header">
                <div>
                    <div class="monument-card-id">${escHtml(p.projectId)}</div>
                    <div class="monument-card-name">${escHtml(p.projectName)}</div>
                </div>
                <div class="monument-badge"><i class="fas fa-map-pin"></i> Monuments</div>
            </div>

            <span class="status-badge" style="background:${bg};color:${color};border:none;font-size:0.75rem;padding:0.2rem 0.6rem;">
                <i class="fas fa-circle" style="font-size:0.5rem;"></i> ${escHtml(p.projectStatus)}
            </span>

            <div class="monument-card-meta">
                ${location}${plusCode}${createdBy}
            </div>

            <div class="monument-card-actions">
                <a href="survey_projects.php?project=${encodeURIComponent(p.projectId)}"
                   class="btn btn-sm btn-secondary" style="flex:1;justify-content:center;">
                    <i class="fas fa-external-link-alt"></i> Open in Dashboard
                </a>
                <button class="btn btn-sm" onclick="markMonumentsSet('${escHtml(p.projectId)}')"
                        style="background:#16a34a;color:white;flex:1;"
                        title="Mark monuments as set — removes this project from the list">
                    <i class="fas fa-check"></i> Monuments Set
                </button>
            </div>
        </div>`;
}

async function markMonumentsSet(projectId) {
    if (!confirm('Mark monuments as set for this project? It will be removed from this list.')) return;

    const fd = new FormData();
    fd.append('action', 'update_project');
    fd.append('projectId', projectId);
    fd.append('needs_monuments', '0');

    try {
        const res  = await fetch('../../Models/php/load_survey_project_notes.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (!data.success) throw new Error(data.message || 'Update failed');

        // Remove from local list and re-render
        allMonumentProjects = allMonumentProjects.filter(p => p.projectId !== projectId);
        renderCards(allMonumentProjects);
        showToast('Monuments marked as set', 'success');
    } catch (err) {
        showToast(err.message, 'error');
    }
}

function filterCards(query) {
    const q = query.toLowerCase();
    const filtered = allMonumentProjects.filter(p =>
        p.projectName.toLowerCase().includes(q) ||
        p.projectId.toLowerCase().includes(q) ||
        (p.location || '').toLowerCase().includes(q)
    );
    renderCards(filtered);
}

function escHtml(str) {
    if (!str) return '';
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function showToast(msg, type = 'success') {
    const toast = document.getElementById('toast');
    document.getElementById('toastMessage').textContent = msg;
    const icon = toast.querySelector('.toast-icon');
    icon.className = type === 'error' ? 'fas fa-exclamation-circle toast-icon' : 'fas fa-check-circle toast-icon';
    toast.style.borderLeftColor = type === 'error' ? 'var(--danger-color)' : 'var(--success-color)';
    icon.style.color = type === 'error' ? 'var(--danger-color)' : 'var(--success-color)';
    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 3000);
}
</script>
</body>
</html>
