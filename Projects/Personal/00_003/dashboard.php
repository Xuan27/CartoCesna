<?php
require_once '../../../classes/Security.php';
Security::secureSession();
if (empty($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: ../../../login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scan Tours - 3D Property Tour Manager</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <link rel="stylesheet" href="../../../Models/css/survey_projects_notes.css">
    <link rel="stylesheet" href="../../../Models/css/scan_tours.css">
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
                <h1><i class="fas fa-cube"></i> Scan Tours</h1>
                <p>3D Property Tour Manager</p>
            </div>

            <nav class="sidebar-nav">
                <a href="#" class="nav-item active" onclick="switchTab('properties', this); return false;" data-tab="properties">
                    <i class="fas fa-building"></i>
                    Properties
                </a>
                <a href="#" class="nav-item" onclick="switchTab('scans', this); return false;" data-tab="scans">
                    <i class="fas fa-camera"></i>
                    Scans
                </a>
                <a href="#" class="nav-item" onclick="switchTab('scenes', this); return false;" data-tab="scenes">
                    <i class="fas fa-images"></i>
                    Scenes
                </a>
                <a href="#" class="nav-item" onclick="switchTab('config', this); return false;" data-tab="config">
                    <i class="fas fa-sliders-h"></i>
                    Tour Config
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
                    <h2 id="tabTitle">Properties</h2>
                    <p id="tabSubtitle">Manage your 3D scan properties</p>
                </div>
            </div>
            <div class="top-bar-right" id="topBarActions">
                <button class="btn btn-primary" onclick="openPropertyModal()">
                    <i class="fas fa-plus"></i> Add Property
                </button>
            </div>
        </div>

        <!-- Content Area -->
        <div class="content-area">

            <!-- Properties Tab -->
            <div class="tab-panel active" id="tab-properties">
                <div id="propertiesContainer">
                    <div class="scan-empty-state">
                        <i class="fas fa-building"></i>
                        <h3>No properties yet</h3>
                        <p>Add your first property to get started</p>
                    </div>
                </div>
            </div>

            <!-- Scans Tab -->
            <div class="tab-panel" id="tab-scans">
                <div class="scan-filter-bar">
                    <select id="scanPropertyFilter" onchange="loadScans()">
                        <option value="">All Properties</option>
                    </select>
                </div>
                <div id="scansContainer">
                    <div class="scan-empty-state">
                        <i class="fas fa-camera"></i>
                        <h3>No scans yet</h3>
                        <p>Add a scan to get started</p>
                    </div>
                </div>
            </div>

            <!-- Scenes Tab -->
            <div class="tab-panel" id="tab-scenes">
                <div class="scan-filter-bar">
                    <select id="scenesScanFilter" onchange="loadScenes()">
                        <option value="">Select a scan...</option>
                    </select>
                </div>
                <div id="scenesContainer">
                    <div class="scan-empty-state">
                        <i class="fas fa-images"></i>
                        <h3>Select a scan to view scenes</h3>
                        <p>Choose a scan from the dropdown above</p>
                    </div>
                </div>
            </div>

            <!-- Tour Config Tab -->
            <div class="tab-panel" id="tab-config">
                <div class="scan-filter-bar">
                    <select id="configScanFilter" onchange="loadTourConfig()">
                        <option value="">Select a scan...</option>
                    </select>
                </div>
                <div id="configContainer">
                    <div class="scan-empty-state">
                        <i class="fas fa-sliders-h"></i>
                        <h3>Select a scan to configure its tour</h3>
                        <p>Choose a published or review scan above</p>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- ============================================================
         PROPERTY MODAL
    ============================================================ -->
    <div class="checklist-modal" id="propertyModal">
        <div class="checklist-modal-content" style="max-width: 640px; max-height: 90vh;">
            <div class="checklist-modal-header" style="background: linear-gradient(135deg, #0f766e 0%, #0d9488 100%);">
                <div class="checklist-modal-header-top">
                    <h3><i class="fas fa-building"></i> <span id="propertyModalTitle">Add Property</span></h3>
                    <button class="checklist-modal-close" onclick="closeModal('propertyModal')">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            <div class="checklist-modal-body" style="padding: 1.5rem;">
                <form id="propertyForm" onsubmit="return false;">
                    <input type="hidden" id="editPropertyId" value="">

                    <div class="form-group" style="margin-bottom: 1rem;">
                        <label class="form-label">Address <span style="color: var(--danger-color);">*</span></label>
                        <input type="text" class="form-input" id="propertyAddress" placeholder="123 Main Street" required>
                    </div>

                    <div class="form-grid" style="grid-template-columns: 1fr 1fr; margin-bottom: 1rem;">
                        <div class="form-group">
                            <label class="form-label">City</label>
                            <input type="text" class="form-input" id="propertyCity" placeholder="City">
                        </div>
                        <div class="form-group">
                            <label class="form-label">State</label>
                            <input type="text" class="form-input" id="propertyState" placeholder="TX">
                        </div>
                    </div>

                    <div class="form-grid" style="grid-template-columns: 1fr 1fr; margin-bottom: 1rem;">
                        <div class="form-group">
                            <label class="form-label">ZIP</label>
                            <input type="text" class="form-input" id="propertyZip" placeholder="78201">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Property Type</label>
                            <select class="form-select" id="propertyType">
                                <option value="residential">Residential</option>
                                <option value="commercial">Commercial</option>
                                <option value="industrial">Industrial</option>
                                <option value="land">Land</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-grid" style="grid-template-columns: 1fr 1fr; margin-bottom: 1rem;">
                        <div class="form-group">
                            <label class="form-label">Sq Ft</label>
                            <input type="number" class="form-input" id="propertySqft" placeholder="2400" min="0">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Floors</label>
                            <input type="number" class="form-input" id="propertyFloors" placeholder="1" min="1" value="1">
                        </div>
                    </div>

                    <div class="form-grid" style="grid-template-columns: 1fr 1fr; margin-bottom: 1rem;">
                        <div class="form-group">
                            <label class="form-label">Latitude</label>
                            <input type="number" class="form-input" id="propertyLat" placeholder="29.4241" step="any">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Longitude</label>
                            <input type="number" class="form-input" id="propertyLng" placeholder="-98.4936" step="any">
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 1rem;">
                        <label class="form-label">Notes</label>
                        <textarea class="form-textarea" id="propertyNotes" rows="2" placeholder="Optional notes..."></textarea>
                    </div>

                    <div class="form-actions" style="margin-top: 1rem; padding-top: 1rem;">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('propertyModal')">Cancel</button>
                        <button type="button" class="btn btn-primary" onclick="saveProperty()">
                            <i class="fas fa-save"></i> Save Property
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ============================================================
         SCAN MODAL
    ============================================================ -->
    <div class="checklist-modal" id="scanModal">
        <div class="checklist-modal-content" style="max-width: 640px; max-height: 90vh;">
            <div class="checklist-modal-header" style="background: linear-gradient(135deg, #1e40af 0%, #2563eb 100%);">
                <div class="checklist-modal-header-top">
                    <h3><i class="fas fa-camera"></i> <span id="scanModalTitle">Add Scan</span></h3>
                    <button class="checklist-modal-close" onclick="closeModal('scanModal')">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            <div class="checklist-modal-body" style="padding: 1.5rem;">
                <form id="scanForm" onsubmit="return false;">
                    <input type="hidden" id="editScanId" value="">

                    <div class="form-group" style="margin-bottom: 1rem;">
                        <label class="form-label">Scan Name <span style="color: var(--danger-color);">*</span></label>
                        <input type="text" class="form-input" id="scanName" placeholder="e.g., Living Room Full Scan - March 2026" required>
                    </div>

                    <div class="form-group" style="margin-bottom: 1rem;">
                        <label class="form-label">Property <span style="color: var(--danger-color);">*</span></label>
                        <select class="form-select" id="scanPropertyId">
                            <option value="">Select property...</option>
                        </select>
                    </div>

                    <div class="form-grid" style="grid-template-columns: 1fr 1fr; margin-bottom: 1rem;">
                        <div class="form-group">
                            <label class="form-label">Status</label>
                            <select class="form-select" id="scanStatus">
                                <option value="draft">Draft</option>
                                <option value="processing">Processing</option>
                                <option value="review">Review</option>
                                <option value="published">Published</option>
                                <option value="archived">Archived</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Scan Date</label>
                            <input type="date" class="form-input" id="scanDate">
                        </div>
                    </div>

                    <div class="form-grid" style="grid-template-columns: 1fr 1fr; margin-bottom: 1rem;">
                        <div class="form-group">
                            <label class="form-label">Scanner Type</label>
                            <input type="text" class="form-input" id="scannerType" placeholder="e.g., Matterport Pro3">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Software</label>
                            <input type="text" class="form-input" id="softwareUsed" placeholder="e.g., ReCap, Matterport">
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 1rem;">
                        <label class="form-label">File Format</label>
                        <input type="text" class="form-input" id="fileFormat" placeholder="e.g., .e57, .pts, .las, .obj">
                    </div>

                    <div class="form-group" style="margin-bottom: 1rem;">
                        <label class="form-label">Raw Data Path</label>
                        <input type="text" class="form-input" id="rawDataPath" placeholder="/scans/property-id/raw/">
                    </div>

                    <div class="form-group" style="margin-bottom: 1rem;">
                        <label class="form-label">Notes</label>
                        <textarea class="form-textarea" id="scanNotes" rows="2" placeholder="Optional notes..."></textarea>
                    </div>

                    <div class="form-actions" style="margin-top: 1rem; padding-top: 1rem;">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('scanModal')">Cancel</button>
                        <button type="button" class="btn btn-primary" onclick="saveScan()">
                            <i class="fas fa-save"></i> Save Scan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ============================================================
         SCENE MODAL
    ============================================================ -->
    <div class="checklist-modal" id="sceneModal">
        <div class="checklist-modal-content" style="max-width: 600px; max-height: 90vh;">
            <div class="checklist-modal-header" style="background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%);">
                <div class="checklist-modal-header-top">
                    <h3><i class="fas fa-image"></i> <span id="sceneModalTitle">Add Scene</span></h3>
                    <button class="checklist-modal-close" onclick="closeModal('sceneModal')">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            <div class="checklist-modal-body" style="padding: 1.5rem;">
                <form id="sceneForm" onsubmit="return false;">
                    <input type="hidden" id="editSceneId" value="">
                    <input type="hidden" id="sceneParentScanId" value="">

                    <div class="form-group" style="margin-bottom: 1rem;">
                        <label class="form-label">Scene Name <span style="color: var(--danger-color);">*</span></label>
                        <input type="text" class="form-input" id="sceneName" placeholder="e.g., Living Room, Master Bedroom" required>
                    </div>

                    <div style="margin-bottom: 1rem;">
                        <p class="config-section-title">Position</p>
                        <div class="form-grid" style="grid-template-columns: 1fr 1fr 1fr;">
                            <div class="form-group">
                                <label class="form-label">X</label>
                                <input type="number" class="form-input" id="scenePosX" value="0" step="any">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Y</label>
                                <input type="number" class="form-input" id="scenePosY" value="0" step="any">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Z</label>
                                <input type="number" class="form-input" id="scenePosZ" value="0" step="any">
                            </div>
                        </div>
                    </div>

                    <div style="margin-bottom: 1rem;">
                        <p class="config-section-title">Rotation</p>
                        <div class="form-grid" style="grid-template-columns: 1fr 1fr 1fr;">
                            <div class="form-group">
                                <label class="form-label">X</label>
                                <input type="number" class="form-input" id="sceneRotX" value="0" step="any">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Y</label>
                                <input type="number" class="form-input" id="sceneRotY" value="0" step="any">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Z</label>
                                <input type="number" class="form-input" id="sceneRotZ" value="0" step="any">
                            </div>
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 1rem;">
                        <label class="form-label">Panorama Path</label>
                        <input type="text" class="form-input" id="scenePanoPath" placeholder="/scans/scene-id/panorama.jpg">
                    </div>

                    <div class="form-group" style="margin-bottom: 1rem;">
                        <label class="form-label">Thumbnail Path</label>
                        <input type="text" class="form-input" id="sceneThumbPath" placeholder="/scans/scene-id/thumb.jpg">
                    </div>

                    <div class="form-group" style="margin-bottom: 1rem;">
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                            <input type="checkbox" id="sceneIsStart">
                            <span class="form-label" style="margin: 0;">Set as starting scene</span>
                        </label>
                    </div>

                    <div class="form-actions" style="margin-top: 1rem; padding-top: 1rem;">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('sceneModal')">Cancel</button>
                        <button type="button" class="btn btn-primary" onclick="saveScene()">
                            <i class="fas fa-save"></i> Save Scene
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
        const API_URL = '../../../Models/php/scan_tours_api.php';
        let allProperties = [];
        let allScans = [];
        let draggedSceneId = null;

        // ============================================================
        // LIFECYCLE
        // ============================================================
        document.addEventListener('DOMContentLoaded', function () {
            setupSidebar();
            switchTab('properties', document.querySelector('[data-tab="properties"]'));
            loadProperties();
            loadAllScansForDropdowns();
        });

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

        // ============================================================
        // TAB SWITCHING
        // ============================================================
        const tabMeta = {
            properties: {
                title: 'Properties',
                subtitle: 'Manage your 3D scan properties',
                actions: `<button class="btn btn-primary" onclick="openPropertyModal()"><i class="fas fa-plus"></i> Add Property</button>`
            },
            scans: {
                title: 'Scans',
                subtitle: 'Manage 3D scan sessions',
                actions: `<button class="btn btn-primary" onclick="openScanModal()"><i class="fas fa-plus"></i> Add Scan</button>`
            },
            scenes: {
                title: 'Scenes',
                subtitle: 'Manage panorama scenes — drag to reorder',
                actions: `<button class="btn btn-primary" onclick="openSceneModal()"><i class="fas fa-plus"></i> Add Scene</button>`
            },
            config: {
                title: 'Tour Config',
                subtitle: 'Configure renderer and embed settings',
                actions: ''
            }
        };

        function switchTab(tab, el) {
            document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
            document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));

            const panel = document.getElementById('tab-' + tab);
            if (panel) panel.classList.add('active');
            if (el) el.classList.add('active');

            const meta = tabMeta[tab] || {};
            document.getElementById('tabTitle').textContent = meta.title || '';
            document.getElementById('tabSubtitle').textContent = meta.subtitle || '';
            document.getElementById('topBarActions').innerHTML = meta.actions || '';
        }

        // ============================================================
        // PROPERTIES
        // ============================================================
        async function loadProperties() {
            try {
                const res = await fetch(`${API_URL}?action=get_properties`);
                const data = await res.json();
                if (data.success) {
                    allProperties = data.properties || [];
                    renderProperties();
                    populatePropertyDropdowns();
                } else {
                    showToast(data.message || 'Error loading properties', 'error');
                }
            } catch (e) {
                console.error(e);
                showToast('Network error loading properties', 'error');
            }
        }

        function renderProperties() {
            const container = document.getElementById('propertiesContainer');
            if (allProperties.length === 0) {
                container.innerHTML = `
                    <div class="scan-empty-state">
                        <i class="fas fa-building"></i>
                        <h3>No properties yet</h3>
                        <p>Add your first property to get started</p>
                    </div>`;
                return;
            }

            const cards = allProperties.map(p => `
                <div class="scan-card">
                    <div class="scan-card-header">
                        <span class="scan-card-name">${escapeHtml(p.address)}</span>
                        <div class="scan-card-actions">
                            <button class="btn btn-xs btn-secondary" onclick="editProperty(${p.property_id})" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-xs" style="background: var(--danger-color); color: white;" onclick="deleteProperty(${p.property_id})" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    <div class="scan-card-body">
                        <span class="scan-type-badge">${escapeHtml(p.property_type || 'residential')}</span>
                        <div style="margin-top: 0.5rem; display: flex; flex-wrap: wrap; gap: 0.75rem;">
                            ${p.city || p.state ? `<span class="scan-stat"><i class="fas fa-map-marker-alt"></i> ${escapeHtml([p.city, p.state].filter(Boolean).join(', '))}</span>` : ''}
                            ${p.sqft ? `<span class="scan-stat"><i class="fas fa-ruler-combined"></i> ${Number(p.sqft).toLocaleString()} sqft</span>` : ''}
                            ${p.floors ? `<span class="scan-stat"><i class="fas fa-layer-group"></i> ${p.floors} floor${p.floors > 1 ? 's' : ''}</span>` : ''}
                        </div>
                    </div>
                    <div class="scan-card-footer">
                        <span class="scan-stat"><i class="fas fa-camera"></i> ${p.scan_count || 0} scan${p.scan_count != 1 ? 's' : ''}</span>
                        <button class="btn btn-xs btn-secondary" onclick="switchToScansForProperty(${p.property_id})">
                            <i class="fas fa-arrow-right"></i> View Scans
                        </button>
                    </div>
                </div>
            `).join('');

            container.innerHTML = `<div class="scan-grid">${cards}</div>`;
        }

        function populatePropertyDropdowns() {
            const opts = allProperties.map(p =>
                `<option value="${p.property_id}">${escapeHtml(p.address)}</option>`
            ).join('');
            document.getElementById('scanPropertyFilter').innerHTML = '<option value="">All Properties</option>' + opts;
            document.getElementById('scanPropertyId').innerHTML = '<option value="">Select property...</option>' + opts;
        }

        function switchToScansForProperty(propertyId) {
            const filterEl = document.getElementById('scanPropertyFilter');
            filterEl.value = propertyId;
            switchTab('scans', document.querySelector('[data-tab="scans"]'));
            loadScans();
        }

        function openPropertyModal() {
            document.getElementById('editPropertyId').value = '';
            document.getElementById('propertyModalTitle').textContent = 'Add Property';
            document.getElementById('propertyAddress').value = '';
            document.getElementById('propertyCity').value = '';
            document.getElementById('propertyState').value = '';
            document.getElementById('propertyZip').value = '';
            document.getElementById('propertyType').value = 'residential';
            document.getElementById('propertySqft').value = '';
            document.getElementById('propertyFloors').value = '1';
            document.getElementById('propertyLat').value = '';
            document.getElementById('propertyLng').value = '';
            document.getElementById('propertyNotes').value = '';
            openModal('propertyModal');
        }

        async function editProperty(id) {
            try {
                const res = await fetch(`${API_URL}?action=get_property&property_id=${id}`);
                const data = await res.json();
                if (!data.success) { showToast(data.message || 'Error loading property', 'error'); return; }
                const p = data.property;
                document.getElementById('editPropertyId').value = p.property_id;
                document.getElementById('propertyModalTitle').textContent = 'Edit Property';
                document.getElementById('propertyAddress').value = p.address || '';
                document.getElementById('propertyCity').value = p.city || '';
                document.getElementById('propertyState').value = p.state || '';
                document.getElementById('propertyZip').value = p.zip || '';
                document.getElementById('propertyType').value = p.property_type || 'residential';
                document.getElementById('propertySqft').value = p.sqft || '';
                document.getElementById('propertyFloors').value = p.floors || 1;
                document.getElementById('propertyLat').value = p.latitude || '';
                document.getElementById('propertyLng').value = p.longitude || '';
                document.getElementById('propertyNotes').value = p.notes || '';
                openModal('propertyModal');
            } catch (e) {
                console.error(e);
                showToast('Network error', 'error');
            }
        }

        async function saveProperty() {
            const id = document.getElementById('editPropertyId').value;
            const address = document.getElementById('propertyAddress').value.trim();
            if (!address) { showToast('Address is required', 'error'); return; }

            const payload = {
                action: id ? 'update_property' : 'add_property',
                address,
                city:          document.getElementById('propertyCity').value.trim(),
                state:         document.getElementById('propertyState').value.trim(),
                zip:           document.getElementById('propertyZip').value.trim(),
                property_type: document.getElementById('propertyType').value,
                sqft:          document.getElementById('propertySqft').value || null,
                floors:        document.getElementById('propertyFloors').value || 1,
                latitude:      document.getElementById('propertyLat').value || null,
                longitude:     document.getElementById('propertyLng').value || null,
                notes:         document.getElementById('propertyNotes').value.trim()
            };
            if (id) payload.property_id = id;

            try {
                const res = await fetch(API_URL, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
                const data = await res.json();
                if (data.success) {
                    showToast(id ? 'Property updated' : 'Property added', 'success');
                    closeModal('propertyModal');
                    loadProperties();
                } else {
                    showToast(data.message || 'Error saving property', 'error');
                }
            } catch (e) {
                console.error(e);
                showToast('Network error', 'error');
            }
        }

        async function deleteProperty(id) {
            if (!confirm('Delete this property? All scans and scenes will also be deleted.')) return;
            try {
                const res = await fetch(API_URL, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ action: 'delete_property', property_id: id }) });
                const data = await res.json();
                if (data.success) { showToast('Property deleted', 'success'); loadProperties(); }
                else showToast(data.message || 'Error deleting property', 'error');
            } catch (e) { console.error(e); showToast('Network error', 'error'); }
        }

        // ============================================================
        // SCANS
        // ============================================================
        async function loadAllScansForDropdowns() {
            try {
                const res = await fetch(`${API_URL}?action=get_scans`);
                const data = await res.json();
                if (data.success) {
                    allScans = data.scans || [];
                    populateScanDropdowns();
                }
            } catch (e) { console.error(e); }
        }

        function populateScanDropdowns() {
            const opts = allScans.map(s =>
                `<option value="${s.scan_id}">${escapeHtml(s.scan_name)}</option>`
            ).join('');
            document.getElementById('scenesScanFilter').innerHTML = '<option value="">Select a scan...</option>' + opts;
            document.getElementById('configScanFilter').innerHTML  = '<option value="">Select a scan...</option>' + opts;
        }

        async function loadScans() {
            const propertyId = document.getElementById('scanPropertyFilter').value;
            const url = propertyId ? `${API_URL}?action=get_scans&property_id=${propertyId}` : `${API_URL}?action=get_scans`;
            try {
                const res = await fetch(url);
                const data = await res.json();
                if (data.success) renderScans(data.scans || []);
                else showToast(data.message || 'Error loading scans', 'error');
            } catch (e) { console.error(e); showToast('Network error', 'error'); }
        }

        const statusColors = {
            draft:      'scan-status-draft',
            processing: 'scan-status-processing',
            review:     'scan-status-review',
            published:  'scan-status-published',
            archived:   'scan-status-archived'
        };

        function renderScans(scans) {
            const container = document.getElementById('scansContainer');
            if (scans.length === 0) {
                container.innerHTML = `
                    <div class="scan-empty-state">
                        <i class="fas fa-camera"></i>
                        <h3>No scans found</h3>
                        <p>Add a scan or select a different property</p>
                    </div>`;
                return;
            }

            const cards = scans.map(s => {
                const badgeClass = statusColors[s.status] || 'scan-status-draft';
                const publishBtn = s.status !== 'published'
                    ? `<button class="btn btn-xs" style="background:#16a34a;color:white;" onclick="publishScan(${s.scan_id})"><i class="fas fa-globe"></i> Publish</button>`
                    : '';
                return `
                <div class="scan-card">
                    <div class="scan-card-header">
                        <span class="scan-card-name">${escapeHtml(s.scan_name)}</span>
                        <div class="scan-card-actions">
                            <button class="btn btn-xs btn-secondary" onclick="editScan(${s.scan_id})" title="Edit"><i class="fas fa-edit"></i></button>
                            <button class="btn btn-xs" style="background:var(--danger-color);color:white;" onclick="deleteScan(${s.scan_id})" title="Delete"><i class="fas fa-trash"></i></button>
                        </div>
                    </div>
                    <div class="scan-card-body">
                        <div style="display:flex;gap:0.5rem;align-items:center;flex-wrap:wrap;margin-bottom:0.5rem;">
                            <span class="scan-status-badge ${badgeClass}">${s.status}</span>
                            ${s.file_format ? `<span class="scan-type-badge">${escapeHtml(s.file_format)}</span>` : ''}
                        </div>
                        <div style="display:flex;flex-wrap:wrap;gap:0.75rem;">
                            ${s.address ? `<span class="scan-stat"><i class="fas fa-building"></i> ${escapeHtml(s.address)}</span>` : ''}
                            ${s.scanner_type ? `<span class="scan-stat"><i class="fas fa-camera-retro"></i> ${escapeHtml(s.scanner_type)}</span>` : ''}
                            <span class="scan-stat"><i class="fas fa-images"></i> ${s.scene_count || 0} scene${s.scene_count != 1 ? 's' : ''}</span>
                            ${s.scan_date ? `<span class="scan-stat"><i class="fas fa-calendar"></i> ${s.scan_date}</span>` : ''}
                        </div>
                    </div>
                    <div class="scan-card-footer">
                        ${publishBtn}
                        <button class="btn btn-xs btn-secondary" onclick="triggerModelUpload(${s.scan_id})" title="Upload .glb or .gltf model file">
                            <i class="fas fa-upload"></i> Upload GLB
                        </button>
                        ${s.raw_data_path
                            ? `<button class="btn btn-xs" style="background:#0d9488;color:white;" onclick="openTour(${s.scan_id})" title="Open 3D viewer"><i class="fas fa-cube"></i> View Tour</button>`
                            : `<span class="scan-stat" style="color:#f87171;font-size:11px;"><i class="fas fa-exclamation-circle"></i> No model</span>`}
                        <button class="btn btn-xs btn-secondary" onclick="switchToScenesForScan(${s.scan_id})">
                            <i class="fas fa-images"></i> Scenes
                        </button>
                    </div>
                </div>`;
            }).join('');

            container.innerHTML = `<div class="scan-grid">${cards}</div>`;
        }

        async function publishScan(id) {
            if (!confirm('Publish this scan? Its status will be set to Published and an embed token will be generated.')) return;
            try {
                const res = await fetch(API_URL, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ action: 'publish_scan', scan_id: id }) });
                const data = await res.json();
                if (data.success) {
                    showToast(`Published! Token: ${data.embed_token}`, 'success');
                    loadScans();
                    loadAllScansForDropdowns();
                } else {
                    showToast(data.message || 'Error publishing scan', 'error');
                }
            } catch (e) { console.error(e); showToast('Network error', 'error'); }
        }

        function switchToScenesForScan(scanId) {
            document.getElementById('scenesScanFilter').value = scanId;
            switchTab('scenes', document.querySelector('[data-tab="scenes"]'));
            loadScenes();
        }

        function openScanModal() {
            document.getElementById('editScanId').value = '';
            document.getElementById('scanModalTitle').textContent = 'Add Scan';
            document.getElementById('scanName').value = '';
            document.getElementById('scanPropertyId').value = '';
            document.getElementById('scanStatus').value = 'draft';
            document.getElementById('scanDate').value = '';
            document.getElementById('scannerType').value = '';
            document.getElementById('softwareUsed').value = '';
            document.getElementById('fileFormat').value = '';
            document.getElementById('rawDataPath').value = '';
            document.getElementById('scanNotes').value = '';
            openModal('scanModal');
        }

        async function editScan(id) {
            try {
                const res = await fetch(`${API_URL}?action=get_scan&scan_id=${id}`);
                const data = await res.json();
                if (!data.success) { showToast(data.message || 'Error loading scan', 'error'); return; }
                const s = data.scan;
                document.getElementById('editScanId').value = s.scan_id;
                document.getElementById('scanModalTitle').textContent = 'Edit Scan';
                document.getElementById('scanName').value = s.scan_name || '';
                document.getElementById('scanPropertyId').value = s.property_id || '';
                document.getElementById('scanStatus').value = s.status || 'draft';
                document.getElementById('scanDate').value = s.scan_date || '';
                document.getElementById('scannerType').value = s.scanner_type || '';
                document.getElementById('softwareUsed').value = s.software_used || '';
                document.getElementById('fileFormat').value = s.file_format || '';
                document.getElementById('rawDataPath').value = s.raw_data_path || '';
                document.getElementById('scanNotes').value = s.notes || '';
                openModal('scanModal');
            } catch (e) { console.error(e); showToast('Network error', 'error'); }
        }

        async function saveScan() {
            const id = document.getElementById('editScanId').value;
            const name = document.getElementById('scanName').value.trim();
            const propertyId = document.getElementById('scanPropertyId').value;
            if (!name) { showToast('Scan name is required', 'error'); return; }
            if (!propertyId) { showToast('Property is required', 'error'); return; }

            const payload = {
                action: id ? 'update_scan' : 'add_scan',
                scan_name:     name,
                property_id:   propertyId,
                status:        document.getElementById('scanStatus').value,
                scan_date:     document.getElementById('scanDate').value || null,
                scanner_type:  document.getElementById('scannerType').value.trim() || null,
                software_used: document.getElementById('softwareUsed').value.trim() || null,
                file_format:   document.getElementById('fileFormat').value.trim() || null,
                raw_data_path: document.getElementById('rawDataPath').value.trim() || null,
                notes:         document.getElementById('scanNotes').value.trim() || null
            };
            if (id) payload.scan_id = id;

            try {
                const res = await fetch(API_URL, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
                const data = await res.json();
                if (data.success) {
                    showToast(id ? 'Scan updated' : 'Scan added', 'success');
                    closeModal('scanModal');
                    loadScans();
                    loadAllScansForDropdowns();
                } else {
                    showToast(data.message || 'Error saving scan', 'error');
                }
            } catch (e) { console.error(e); showToast('Network error', 'error'); }
        }

        async function deleteScan(id) {
            if (!confirm('Delete this scan? All scenes, assets, and analytics will be deleted.')) return;
            try {
                const res = await fetch(API_URL, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ action: 'delete_scan', scan_id: id }) });
                const data = await res.json();
                if (data.success) { showToast('Scan deleted', 'success'); loadScans(); loadAllScansForDropdowns(); }
                else showToast(data.message || 'Error deleting scan', 'error');
            } catch (e) { console.error(e); showToast('Network error', 'error'); }
        }

        // ============================================================
        // SCENES
        // ============================================================
        async function loadScenes() {
            const scanId = document.getElementById('scenesScanFilter').value;
            if (!scanId) {
                document.getElementById('scenesContainer').innerHTML = `
                    <div class="scan-empty-state">
                        <i class="fas fa-images"></i>
                        <h3>Select a scan to view scenes</h3>
                        <p>Choose a scan from the dropdown above</p>
                    </div>`;
                return;
            }
            try {
                const res = await fetch(`${API_URL}?action=get_scenes&scan_id=${scanId}`);
                const data = await res.json();
                if (data.success) renderScenes(data.scenes || [], scanId);
                else showToast(data.message || 'Error loading scenes', 'error');
            } catch (e) { console.error(e); showToast('Network error', 'error'); }
        }

        function renderScenes(scenes, scanId) {
            const container = document.getElementById('scenesContainer');
            if (scenes.length === 0) {
                container.innerHTML = `
                    <div class="scan-empty-state">
                        <i class="fas fa-images"></i>
                        <h3>No scenes yet</h3>
                        <p>Add scenes to this scan to build the tour</p>
                    </div>`;
                return;
            }

            const cards = scenes.map(sc => {
                const thumb = sc.thumbnail_path
                    ? `<img src="${escapeHtml(sc.thumbnail_path)}" alt="${escapeHtml(sc.scene_name)}" onerror="this.parentElement.innerHTML='<div class=\\'scene-thumb-placeholder\\'><i class=\\'fas fa-panorama\\'></i><span>No thumbnail</span></div>'">`
                    : `<div class="scene-thumb-placeholder"><i class="fas fa-panorama"></i><span>No thumbnail</span></div>`;
                const startBadge = sc.is_start_scene == 1 ? `<span class="scene-start-badge">Start</span>` : '';
                return `
                <div class="scene-card" id="scene-card-${sc.scene_id}"
                     draggable="true"
                     ondragstart="dragStart(event, ${sc.scene_id})"
                     ondragover="dragOver(event)"
                     ondrop="dropScene(event, ${sc.scene_id}, ${scanId})">
                    <div class="scene-thumb">
                        ${thumb}
                        ${startBadge}
                    </div>
                    <div class="scene-card-body">
                        <div class="scene-card-name" title="${escapeHtml(sc.scene_name)}">${escapeHtml(sc.scene_name)}</div>
                        <div class="scene-card-actions">
                            <button class="btn btn-xs btn-secondary" onclick="editScene(${sc.scene_id})" title="Edit"><i class="fas fa-edit"></i></button>
                            <button class="btn btn-xs" style="background:var(--danger-color);color:white;" onclick="deleteScene(${sc.scene_id}, ${scanId})" title="Delete"><i class="fas fa-trash"></i></button>
                        </div>
                    </div>
                </div>`;
            }).join('');

            container.innerHTML = `<div class="scenes-grid">${cards}</div>`;
        }

        function dragStart(event, sceneId) {
            draggedSceneId = sceneId;
            event.dataTransfer.effectAllowed = 'move';
            document.getElementById(`scene-card-${sceneId}`).classList.add('dragging');
        }

        function dragOver(event) {
            event.preventDefault();
            event.dataTransfer.dropEffect = 'move';
            const card = event.currentTarget;
            card.classList.add('drag-over');
        }

        async function dropScene(event, targetId, scanId) {
            event.preventDefault();
            document.querySelectorAll('.scene-card').forEach(c => c.classList.remove('drag-over', 'dragging'));

            if (!draggedSceneId || draggedSceneId === targetId) { draggedSceneId = null; return; }

            // Build new order from DOM, swapping dragged and target
            const grid = document.querySelector('.scenes-grid');
            const cards = Array.from(grid.querySelectorAll('.scene-card'));
            const order = cards.map(c => parseInt(c.id.replace('scene-card-', '')));

            const fromIdx = order.indexOf(draggedSceneId);
            const toIdx   = order.indexOf(targetId);
            order.splice(fromIdx, 1);
            order.splice(toIdx, 0, draggedSceneId);

            draggedSceneId = null;

            try {
                const res = await fetch(API_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'reorder_scenes', order })
                });
                const data = await res.json();
                if (data.success) loadScenes();
                else showToast(data.message || 'Error reordering', 'error');
            } catch (e) { console.error(e); showToast('Network error', 'error'); }
        }

        function openSceneModal() {
            const scanId = document.getElementById('scenesScanFilter').value;
            if (!scanId) { showToast('Select a scan first', 'error'); return; }
            document.getElementById('editSceneId').value = '';
            document.getElementById('sceneParentScanId').value = scanId;
            document.getElementById('sceneModalTitle').textContent = 'Add Scene';
            document.getElementById('sceneName').value = '';
            document.getElementById('scenePosX').value = '0';
            document.getElementById('scenePosY').value = '0';
            document.getElementById('scenePosZ').value = '0';
            document.getElementById('sceneRotX').value = '0';
            document.getElementById('sceneRotY').value = '0';
            document.getElementById('sceneRotZ').value = '0';
            document.getElementById('scenePanoPath').value = '';
            document.getElementById('sceneThumbPath').value = '';
            document.getElementById('sceneIsStart').checked = false;
            openModal('sceneModal');
        }

        async function editScene(id) {
            try {
                const res = await fetch(`${API_URL}?action=get_scene&scene_id=${id}`);
                const data = await res.json();
                if (!data.success) { showToast(data.message || 'Error loading scene', 'error'); return; }
                const sc = data.scene;
                document.getElementById('editSceneId').value = sc.scene_id;
                document.getElementById('sceneParentScanId').value = sc.scan_id;
                document.getElementById('sceneModalTitle').textContent = 'Edit Scene';
                document.getElementById('sceneName').value = sc.scene_name || '';
                document.getElementById('scenePosX').value = sc.pos_x || 0;
                document.getElementById('scenePosY').value = sc.pos_y || 0;
                document.getElementById('scenePosZ').value = sc.pos_z || 0;
                document.getElementById('sceneRotX').value = sc.rot_x || 0;
                document.getElementById('sceneRotY').value = sc.rot_y || 0;
                document.getElementById('sceneRotZ').value = sc.rot_z || 0;
                document.getElementById('scenePanoPath').value = sc.panorama_path || '';
                document.getElementById('sceneThumbPath').value = sc.thumbnail_path || '';
                document.getElementById('sceneIsStart').checked = sc.is_start_scene == 1;
                openModal('sceneModal');
            } catch (e) { console.error(e); showToast('Network error', 'error'); }
        }

        async function saveScene() {
            const id = document.getElementById('editSceneId').value;
            const scanId = document.getElementById('sceneParentScanId').value;
            const name = document.getElementById('sceneName').value.trim();
            if (!name) { showToast('Scene name is required', 'error'); return; }

            const payload = {
                action: id ? 'update_scene' : 'add_scene',
                scene_name:     name,
                scan_id:        scanId,
                pos_x:          document.getElementById('scenePosX').value,
                pos_y:          document.getElementById('scenePosY').value,
                pos_z:          document.getElementById('scenePosZ').value,
                rot_x:          document.getElementById('sceneRotX').value,
                rot_y:          document.getElementById('sceneRotY').value,
                rot_z:          document.getElementById('sceneRotZ').value,
                panorama_path:  document.getElementById('scenePanoPath').value.trim() || null,
                thumbnail_path: document.getElementById('sceneThumbPath').value.trim() || null,
                is_start_scene: document.getElementById('sceneIsStart').checked ? 1 : 0
            };
            if (id) payload.scene_id = id;

            try {
                const res = await fetch(API_URL, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
                const data = await res.json();
                if (data.success) {
                    showToast(id ? 'Scene updated' : 'Scene added', 'success');
                    closeModal('sceneModal');
                    loadScenes();
                } else {
                    showToast(data.message || 'Error saving scene', 'error');
                }
            } catch (e) { console.error(e); showToast('Network error', 'error'); }
        }

        async function deleteScene(id, scanId) {
            if (!confirm('Delete this scene? Hotspots and measurements linked to it will also be removed.')) return;
            try {
                const res = await fetch(API_URL, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ action: 'delete_scene', scene_id: id }) });
                const data = await res.json();
                if (data.success) { showToast('Scene deleted', 'success'); loadScenes(); }
                else showToast(data.message || 'Error deleting scene', 'error');
            } catch (e) { console.error(e); showToast('Network error', 'error'); }
        }

        // ============================================================
        // TOUR CONFIG
        // ============================================================
        async function loadTourConfig() {
            const scanId = document.getElementById('configScanFilter').value;
            if (!scanId) {
                document.getElementById('configContainer').innerHTML = `
                    <div class="scan-empty-state">
                        <i class="fas fa-sliders-h"></i>
                        <h3>Select a scan to configure its tour</h3>
                        <p>Choose a scan above</p>
                    </div>`;
                return;
            }
            try {
                const res = await fetch(`${API_URL}?action=get_tour_config&scan_id=${scanId}`);
                const data = await res.json();
                if (data.success) renderTourConfig(data.config, scanId);
                else showToast(data.message || 'Error loading config', 'error');
            } catch (e) { console.error(e); showToast('Network error', 'error'); }
        }

        function renderTourConfig(cfg, scanId) {
            const c = cfg || {};
            const tokenSection = c.embed_token ? `
                <div class="embed-section">
                    <h4><i class="fas fa-code"></i> Embed Token</h4>
                    <div class="embed-token-display">
                        <code>${escapeHtml(c.embed_token)}</code>
                        <button class="btn btn-xs btn-secondary" onclick="copyEmbedToken('${escapeHtml(c.embed_token)}')">
                            <i class="fas fa-copy"></i> Copy
                        </button>
                    </div>
                </div>` : `<p style="color:var(--gray-400);font-size:0.875rem;margin-top:1rem;">Publish the scan to generate an embed token.</p>`;

            document.getElementById('configContainer').innerHTML = `
                <div class="config-panel">
                    <p class="config-section-title">Navigation &amp; Display</p>

                    <div class="form-grid" style="grid-template-columns: 1fr 1fr; margin-bottom: 1rem;">
                        <div class="form-group">
                            <label class="form-label">Navigation Style</label>
                            <select class="form-select" id="cfgNavStyle">
                                <option value="arrows" ${c.nav_style === 'arrows' ? 'selected' : ''}>Arrows</option>
                                <option value="minimap" ${c.nav_style === 'minimap' ? 'selected' : ''}>Minimap</option>
                                <option value="list" ${c.nav_style === 'list' ? 'selected' : ''}>List</option>
                                <option value="both" ${c.nav_style === 'both' ? 'selected' : ''}>Both</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Primary Color</label>
                            <input type="color" class="form-input" id="cfgPrimaryColor" value="${escapeHtml(c.primary_color || '#2563eb')}" style="height:2.5rem;padding:0.25rem;">
                        </div>
                    </div>

                    <div class="form-grid" style="grid-template-columns: 1fr 1fr 1fr; margin-bottom: 1rem;">
                        <div class="form-group">
                            <label style="display:flex;align-items:center;gap:0.5rem;cursor:pointer;">
                                <input type="checkbox" id="cfgShowMeasurements" ${c.show_measurements != 0 ? 'checked' : ''}>
                                <span class="form-label" style="margin:0;">Show Measurements</span>
                            </label>
                        </div>
                        <div class="form-group">
                            <label style="display:flex;align-items:center;gap:0.5rem;cursor:pointer;">
                                <input type="checkbox" id="cfgShowMinimap" ${c.show_minimap != 0 ? 'checked' : ''}>
                                <span class="form-label" style="margin:0;">Show Minimap</span>
                            </label>
                        </div>
                        <div class="form-group">
                            <label style="display:flex;align-items:center;gap:0.5rem;cursor:pointer;">
                                <input type="checkbox" id="cfgAutoplay" ${c.autoplay == 1 ? 'checked' : ''}>
                                <span class="form-label" style="margin:0;">Autoplay</span>
                            </label>
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 1.5rem;">
                        <label class="form-label">Autoplay Delay (seconds)</label>
                        <input type="number" class="form-input" id="cfgAutoplayDelay" value="${c.autoplay_delay || 5}" min="1" max="60" style="max-width:120px;">
                    </div>

                    <button class="btn btn-primary" onclick="saveTourConfig(${scanId})">
                        <i class="fas fa-save"></i> Save Configuration
                    </button>

                    ${tokenSection}
                </div>`;
        }

        async function saveTourConfig(scanId) {
            const payload = {
                action:            'save_tour_config',
                scan_id:           scanId,
                nav_style:         document.getElementById('cfgNavStyle').value,
                primary_color:     document.getElementById('cfgPrimaryColor').value,
                show_measurements: document.getElementById('cfgShowMeasurements').checked ? 1 : 0,
                show_minimap:      document.getElementById('cfgShowMinimap').checked ? 1 : 0,
                autoplay:          document.getElementById('cfgAutoplay').checked ? 1 : 0,
                autoplay_delay:    document.getElementById('cfgAutoplayDelay').value
            };
            try {
                const res = await fetch(API_URL, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
                const data = await res.json();
                if (data.success) showToast('Configuration saved', 'success');
                else showToast(data.message || 'Error saving config', 'error');
            } catch (e) { console.error(e); showToast('Network error', 'error'); }
        }

        function copyEmbedToken(token) {
            const url = window.location.origin + '/CartoCesna/tour_viewer.php?token=' + token;
            navigator.clipboard.writeText(url).then(() => {
                showToast('Tour URL copied to clipboard!', 'success');
            }).catch(() => {
                showToast('Copy failed — token: ' + token, 'error');
            });
        }

        // ============================================================
        // UTILITIES
        // ============================================================
        function openModal(id) {
            document.getElementById(id).classList.add('active');
        }

        function closeModal(id) {
            document.getElementById(id).classList.remove('active');
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
            setTimeout(() => toast.classList.remove('show'), 3500);
        }

        function escapeHtml(text) {
            if (text === null || text === undefined) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Close modal on outside click
        document.addEventListener('click', function (e) {
            if (e.target.classList.contains('checklist-modal')) {
                e.target.classList.remove('active');
            }
        });

        // ============================================================
        // GLB UPLOAD
        // ============================================================
        const fileInput = document.createElement('input');
        fileInput.type    = 'file';
        fileInput.accept  = '.glb,.gltf';
        fileInput.style.display = 'none';
        document.body.appendChild(fileInput);

        let uploadTargetScanId = null;

        function triggerModelUpload(scanId) {
            uploadTargetScanId = scanId;
            fileInput.value = '';
            fileInput.click();
        }

        function openTour(scanId) {
            window.open(`/CartoCesna/uploads/scans/${scanId}/index.html`, '_blank');
        }

        fileInput.addEventListener('change', async function () {
            const file = fileInput.files[0];
            if (!file || !uploadTargetScanId) return;

            const sizeMB = (file.size / (1024 * 1024)).toFixed(1);
            showToast(`Uploading ${file.name} (${sizeMB} MB) — please wait...`, 'success');

            const formData = new FormData();
            formData.append('scan_id',    uploadTargetScanId);
            formData.append('model_file', file);

            try {
                const res  = await fetch('../../../Models/php/upload_model.php', {
                    method: 'POST',
                    body:   formData
                });
                const data = await res.json();

                if (data.success) {
                    showToast(`✓ Uploaded ${data.size_mb} MB — project folder created!`, 'success');
                    loadScans();
                    // Offer to open the tour immediately
                    setTimeout(() => {
                        if (confirm(`Model uploaded successfully!\n\nOpen the 3D tour viewer now?`)) {
                            window.open(data.tour_url, '_blank');
                        }
                    }, 500);
                } else {
                    showToast('Upload failed: ' + data.message, 'error');
                }
            } catch (e) {
                console.error(e);
                showToast('Network error during upload.', 'error');
            }
        });
    </script>
</body>
</html>
