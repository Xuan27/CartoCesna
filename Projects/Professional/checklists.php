<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checklists - Survey Project Manager</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <link rel="stylesheet" href="../../Models/css/survey_projects_notes.css">
    <style>
        .checklist-empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--gray-500);
        }
        .checklist-empty-state i {
            font-size: 4rem;
            color: var(--gray-300);
            margin-bottom: 1rem;
        }
        .template-item-row.dragging {
            opacity: 0.35;
            border: 2px dashed var(--primary-color) !important;
        }
        .template-item-row.drag-over {
            border-color: var(--primary-color) !important;
            background: #eff6ff !important;
            box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.25);
        }
        .template-item-row .drag-handle {
            cursor: grab;
        }
        .template-item-row .drag-handle:active {
            cursor: grabbing;
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
                <a href="./checklists.php" class="nav-item active">
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
                <a href="./control_points.php" class="nav-item">
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
                    <h2>Checklist Templates</h2>
                    <p>Manage standardized checklists for survey task types</p>
                </div>
            </div>
            <div class="top-bar-right">
                <button class="btn btn-primary" onclick="openTemplateModal()">
                    <i class="fas fa-plus"></i> Create Template
                </button>
            </div>
        </div>

        <!-- Content Area -->
        <div class="content-area">
            <div id="templatesContainer">
                <div class="checklist-empty-state" id="emptyState">
                    <i class="fas fa-clipboard-check"></i>
                    <h3>No checklist templates yet</h3>
                    <p>Create your first template to get started</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Template Create/Edit Modal -->
    <div class="checklist-modal" id="templateModal">
        <div class="checklist-modal-content" style="max-width: 750px; max-height: 90vh;">
            <div class="checklist-modal-header" style="background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);">
                <div class="checklist-modal-header-top">
                    <h3><i class="fas fa-clipboard-check"></i> <span id="templateModalTitle">Create Template</span></h3>
                    <button class="checklist-modal-close" onclick="closeTemplateModal()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            <div class="checklist-modal-body" style="padding: 1.5rem;">
                <form id="templateForm" onsubmit="return false;">
                    <input type="hidden" id="editTemplateId" value="">

                    <div class="form-group" style="margin-bottom: 1rem;">
                        <label class="form-label">Template Name</label>
                        <input type="text" class="form-input" id="templateName" placeholder="e.g., ALTA/Topo Standard Checklist" required>
                    </div>

                    <div class="form-group" style="margin-bottom: 1rem;">
                        <label class="form-label">Task Types</label>
                        <div class="task-type-checkboxes" id="taskTypeCheckboxes">
                            <label><input type="checkbox" value="Easement"> Easement</label>
                            <label><input type="checkbox" value="ALTA"> ALTA</label>
                            <label><input type="checkbox" value="Plat"> Plat</label>
                            <label><input type="checkbox" value="Construction Staking"> Construction Staking</label>
                            <label><input type="checkbox" value="Boundary Survey"> Boundary Survey</label>
                            <label><input type="checkbox" value="Topographic Survey"> Topographic Survey</label>
                            <label><input type="checkbox" value="As-Built Survey"> As-Built Survey</label>
                            <label><input type="checkbox" value="Other"> Other</label>
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 1rem;">
                        <label class="form-label">Description</label>
                        <textarea class="form-textarea" id="templateDescription" rows="2" placeholder="Optional description..."></textarea>
                    </div>

                    <div class="form-group" style="margin-bottom: 1rem;">
                        <label class="form-label">Checklist Items</label>
                        <div class="template-items-list" id="templateItemsList">
                            <!-- Items added dynamically -->
                        </div>
                        <div style="display:flex; gap:0.5rem; margin-top:0.5rem;">
                            <button type="button" class="add-item-btn" style="flex:1;" onclick="addItemRow()">
                                <i class="fas fa-plus"></i> Add Item
                            </button>
                            <button type="button" class="add-item-btn" style="flex:1; border-color:#bfdbfe; color:var(--primary-color);" onclick="addConditionalRow()">
                                <i class="fas fa-code-branch"></i> Add Conditional
                            </button>
                        </div>
                    </div>

                    <div class="form-actions" style="margin-top: 1rem; padding-top: 1rem;">
                        <button type="button" class="btn btn-secondary" onclick="closeTemplateModal()">Cancel</button>
                        <button type="button" class="btn btn-primary" onclick="saveTemplate()">
                            <i class="fas fa-save"></i> Save Template
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
        const API_URL = '../../Models/php/checklist_api.php';
        let allTemplates = [];

        document.addEventListener('DOMContentLoaded', function() {
            setupSidebar();
            loadTemplates();
            initDragAndDrop();
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

        // Load all templates
        async function loadTemplates() {
            try {
                const response = await fetch(`${API_URL}?action=get_templates`);
                const data = await response.json();
                if (data.success) {
                    allTemplates = data.templates || [];
                    renderTemplates();
                } else {
                    showToast(data.message || 'Error loading templates', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('Network error loading templates', 'error');
            }
        }

        function renderTemplates() {
            const container = document.getElementById('templatesContainer');

            if (allTemplates.length === 0) {
                container.innerHTML = `
                    <div class="checklist-empty-state">
                        <i class="fas fa-clipboard-check"></i>
                        <h3>No checklist templates yet</h3>
                        <p>Create your first template to get started</p>
                    </div>
                `;
                return;
            }

            const cardsHtml = allTemplates.map(t => {
                const typeBadges = (t.task_types || []).map(type =>
                    `<span class="template-type-badge">${type}</span>`
                ).join('');

                return `
                    <div class="template-card" onclick="editTemplate(${t.template_id})">
                        <div class="template-card-header">
                            <span class="template-card-name">${escapeHtml(t.template_name)}</span>
                            <div class="template-card-actions" onclick="event.stopPropagation();">
                                <button class="btn btn-xs btn-secondary" onclick="editTemplate(${t.template_id})" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-xs" style="background: var(--danger-color); color: white;" onclick="deleteTemplate(${t.template_id})" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        <div class="template-card-badges">${typeBadges || '<span style="color: var(--gray-400); font-size: 0.8rem;">No task types assigned</span>'}</div>
                        ${t.description ? `<div class="template-card-desc">${escapeHtml(t.description)}</div>` : ''}
                        <div class="template-card-meta">
                            <span><i class="fas fa-list-check"></i> ${t.item_count || 0} items</span>
                            <span><i class="fas fa-clock"></i> ${new Date(t.created_date).toLocaleDateString()}</span>
                        </div>
                    </div>
                `;
            }).join('');

            container.innerHTML = `<div class="templates-grid">${cardsHtml}</div>`;
        }

        // Open modal for new template
        function openTemplateModal() {
            document.getElementById('editTemplateId').value = '';
            document.getElementById('templateModalTitle').textContent = 'Create Template';
            document.getElementById('templateName').value = '';
            document.getElementById('templateDescription').value = '';
            document.querySelectorAll('#taskTypeCheckboxes input').forEach(cb => cb.checked = false);
            document.getElementById('templateItemsList').innerHTML = '';
            // Add 3 empty rows to start
            addItemRow();
            addItemRow();
            addItemRow();
            document.getElementById('templateModal').classList.add('active');
        }

        // Close modal
        function closeTemplateModal() {
            document.getElementById('templateModal').classList.remove('active');
        }

        // Edit existing template
        async function editTemplate(templateId) {
            try {
                const response = await fetch(`${API_URL}?action=get_template&template_id=${templateId}`);
                const data = await response.json();
                if (!data.success) {
                    showToast(data.message || 'Error loading template', 'error');
                    return;
                }

                const t = data.template;
                document.getElementById('editTemplateId').value = t.template_id;
                document.getElementById('templateModalTitle').textContent = 'Edit Template';
                document.getElementById('templateName').value = t.template_name;
                document.getElementById('templateDescription').value = t.description || '';

                document.querySelectorAll('#taskTypeCheckboxes input').forEach(cb => {
                    cb.checked = (t.task_types || []).includes(cb.value);
                });

                const itemsList = document.getElementById('templateItemsList');
                itemsList.innerHTML = '';

                // Build child map: parent_item_id -> {yes: [], no: []}
                const childMap = {};
                (t.items || []).forEach(item => {
                    if (item.parent_item_id) {
                        if (!childMap[item.parent_item_id]) childMap[item.parent_item_id] = { yes: [], no: [] };
                        childMap[item.parent_item_id][item.branch || 'yes'].push(item);
                    }
                });

                // Render root items only
                const rootItems = (t.items || []).filter(item => !item.parent_item_id);
                if (rootItems.length === 0) {
                    addItemRow();
                } else {
                    rootItems.forEach(item => {
                        if (item.item_type === 'conditional') {
                            addConditionalRow(
                                item.item_id, item.item_text, item.category,
                                childMap[item.item_id]?.yes || [],
                                childMap[item.item_id]?.no  || []
                            );
                        } else {
                            addItemRow(item.item_id, item.item_text, item.category);
                        }
                    });
                }

                document.getElementById('templateModal').classList.add('active');
            } catch (error) {
                console.error('Error:', error);
                showToast('Network error', 'error');
            }
        }

        // Add a standard item row
        function addItemRow(itemId = '', text = '', category = '') {
            const list = document.getElementById('templateItemsList');
            const row = document.createElement('div');
            row.className = 'template-item-row';
            row.dataset.itemId = itemId || '';
            row.dataset.itemType = 'standard';
            row.innerHTML = `
                <span class="drag-handle"><i class="fas fa-grip-vertical"></i></span>
                <input type="text" class="item-text-input" placeholder="Checklist item text..." value="${escapeHtml(text)}">
                <input type="text" class="category-input" placeholder="Category" value="${escapeHtml(category)}">
                <button type="button" class="remove-item-btn" onclick="this.closest('.template-item-row').remove()">
                    <i class="fas fa-times"></i>
                </button>
            `;
            list.appendChild(row);
        }

        // Add a conditional item row (with yes/no branches)
        function addConditionalRow(itemId = '', text = '', category = '', yesChildren = [], noChildren = []) {
            const list = document.getElementById('templateItemsList');
            const tempId = 'cond_' + Date.now() + '_' + Math.random().toString(36).slice(2, 7);
            const row = document.createElement('div');
            row.className = 'template-item-row conditional-item-row';
            row.dataset.itemId  = itemId || '';
            row.dataset.itemType = 'conditional';
            row.dataset.tempId  = tempId;
            row.innerHTML = `
                <div class="conditional-header">
                    <span class="drag-handle"><i class="fas fa-grip-vertical"></i></span>
                    <span class="conditional-badge"><i class="fas fa-code-branch"></i> Conditional</span>
                    <input type="text" class="item-text-input" placeholder="Question (e.g., Are there buildings?)" value="${escapeHtml(text)}">
                    <input type="text" class="category-input" placeholder="Category" value="${escapeHtml(category)}">
                    <button type="button" class="remove-item-btn" onclick="this.closest('.conditional-item-row').remove()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="conditional-branches-editor">
                    <div class="branch-editor yes-branch-editor">
                        <div class="branch-editor-label"><i class="fas fa-check" style="color:#16a34a;"></i> YES branch</div>
                        <div class="branch-items-list" data-branch="yes"></div>
                        <button type="button" class="add-branch-item-btn" onclick="addBranchItem(this)">
                            <i class="fas fa-plus"></i> Add Yes item
                        </button>
                    </div>
                    <div class="branch-editor no-branch-editor">
                        <div class="branch-editor-label"><i class="fas fa-times" style="color:#dc2626;"></i> NO branch</div>
                        <div class="branch-items-list" data-branch="no"></div>
                        <button type="button" class="add-branch-item-btn" onclick="addBranchItem(this)">
                            <i class="fas fa-plus"></i> Add No item
                        </button>
                    </div>
                </div>
            `;
            list.appendChild(row);

            // Populate existing children
            yesChildren.forEach(c => addBranchItemToList(row.querySelector('.branch-items-list[data-branch="yes"]'), c.item_id, c.item_text));
            noChildren.forEach(c  => addBranchItemToList(row.querySelector('.branch-items-list[data-branch="no"]'),  c.item_id, c.item_text));
        }

        function addBranchItem(btn) {
            const container = btn.closest('.branch-editor').querySelector('.branch-items-list');
            addBranchItemToList(container, '', '');
        }

        function addBranchItemToList(container, itemId = '', text = '') {
            const row = document.createElement('div');
            row.className = 'branch-item-row';
            row.dataset.itemId = itemId || '';
            row.innerHTML = `
                <input type="text" class="branch-item-text" placeholder="Sub-item text..." value="${escapeHtml(text)}">
                <button type="button" class="remove-item-btn" onclick="this.closest('.branch-item-row').remove()">
                    <i class="fas fa-times"></i>
                </button>
            `;
            container.appendChild(row);
        }

        // Save template (create or update)
        async function saveTemplate() {
            const templateId = document.getElementById('editTemplateId').value;
            const name = document.getElementById('templateName').value.trim();
            const description = document.getElementById('templateDescription').value.trim();

            if (!name) {
                showToast('Template name is required', 'error');
                return;
            }

            const taskTypes = [];
            document.querySelectorAll('#taskTypeCheckboxes input:checked').forEach(cb => taskTypes.push(cb.value));

            const items = [];
            let sortOrder = 0;

            document.querySelectorAll('#templateItemsList > .template-item-row').forEach(row => {
                const itemType = row.dataset.itemType || 'standard';
                const text = row.querySelector('.item-text-input').value.trim();
                if (!text) return;

                const tempId = row.dataset.tempId || ('tmp_' + sortOrder + '_' + Math.random().toString(36).slice(2, 7));
                const existingId = row.dataset.itemId || null;

                items.push({
                    item_id:       existingId,
                    temp_id:       tempId,
                    item_type:     itemType,
                    item_text:     text,
                    category:      row.querySelector('.category-input').value.trim() || null,
                    sort_order:    sortOrder++,
                    parent_item_id:  null,
                    parent_temp_id:  null,
                    branch:          null
                });

                if (itemType === 'conditional') {
                    let childSort = 0;
                    // YES branch children
                    row.querySelectorAll('.branch-items-list[data-branch="yes"] .branch-item-row').forEach(childRow => {
                        const childText = childRow.querySelector('.branch-item-text').value.trim();
                        if (!childText) return;
                        items.push({
                            item_id:       childRow.dataset.itemId || null,
                            temp_id:       null,
                            item_type:     'standard',
                            item_text:     childText,
                            category:      null,
                            sort_order:    childSort++,
                            parent_item_id:  existingId ? parseInt(existingId) : null,
                            parent_temp_id:  existingId ? null : tempId,
                            branch:          'yes'
                        });
                    });
                    childSort = 0;
                    // NO branch children
                    row.querySelectorAll('.branch-items-list[data-branch="no"] .branch-item-row').forEach(childRow => {
                        const childText = childRow.querySelector('.branch-item-text').value.trim();
                        if (!childText) return;
                        items.push({
                            item_id:       childRow.dataset.itemId || null,
                            temp_id:       null,
                            item_type:     'standard',
                            item_text:     childText,
                            category:      null,
                            sort_order:    childSort++,
                            parent_item_id:  existingId ? parseInt(existingId) : null,
                            parent_temp_id:  existingId ? null : tempId,
                            branch:          'no'
                        });
                    });
                }
            });

            const payload = {
                action: templateId ? 'update_template' : 'add_template',
                template_name: name,
                description:   description,
                task_types:    taskTypes,
                items:         items
            };
            if (templateId) payload.template_id = templateId;

            try {
                const response = await fetch(API_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const data = await response.json();

                if (data.success) {
                    showToast(templateId ? 'Template updated' : 'Template created', 'success');
                    closeTemplateModal();
                    loadTemplates();
                } else {
                    showToast(data.message || 'Error saving template', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('Network error', 'error');
            }
        }

        // Delete template
        async function deleteTemplate(templateId) {
            if (!confirm('Are you sure you want to delete this template? This will also remove checklist progress for any tasks using it.')) {
                return;
            }

            try {
                const response = await fetch(API_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'delete_template', template_id: templateId })
                });
                const data = await response.json();

                if (data.success) {
                    showToast('Template deleted', 'success');
                    loadTemplates();
                } else {
                    showToast(data.message || 'Error deleting template', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('Network error', 'error');
            }
        }

        // Utility
        function escapeHtml(text) {
            if (text === null || text === undefined) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
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

        // Drag-and-drop reordering for top-level checklist items
        function initDragAndDrop() {
            const list = document.getElementById('templateItemsList');
            let dragSrcRow = null;

            // Only enable draggable when the user grabs the handle,
            // so text inputs inside the row remain normally clickable.
            list.addEventListener('mousedown', function(e) {
                const handle = e.target.closest('.drag-handle');
                if (handle) {
                    const row = handle.closest('.template-item-row');
                    if (row && row.parentElement === list) {
                        row.draggable = true;
                    }
                }
            });

            list.addEventListener('mouseup', function() {
                list.querySelectorAll(':scope > .template-item-row[draggable]').forEach(row => {
                    row.draggable = false;
                });
            });

            list.addEventListener('dragstart', function(e) {
                const row = e.target.closest('.template-item-row');
                if (!row || row.parentElement !== list) return;
                dragSrcRow = row;
                row.classList.add('dragging');
                e.dataTransfer.effectAllowed = 'move';
                e.dataTransfer.setData('text/plain', ''); // required for Firefox
            });

            list.addEventListener('dragend', function() {
                if (dragSrcRow) {
                    dragSrcRow.classList.remove('dragging');
                    dragSrcRow.draggable = false;
                    dragSrcRow = null;
                }
                list.querySelectorAll('.drag-over').forEach(el => el.classList.remove('drag-over'));
            });

            list.addEventListener('dragover', function(e) {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
                const row = e.target.closest('.template-item-row');
                if (!row || row.parentElement !== list || row === dragSrcRow) return;
                list.querySelectorAll('.drag-over').forEach(el => el.classList.remove('drag-over'));
                row.classList.add('drag-over');
            });

            list.addEventListener('dragleave', function(e) {
                const row = e.target.closest('.template-item-row');
                if (row && !row.contains(e.relatedTarget)) {
                    row.classList.remove('drag-over');
                }
            });

            list.addEventListener('drop', function(e) {
                e.preventDefault();
                const targetRow = e.target.closest('.template-item-row');
                if (!targetRow || !dragSrcRow || targetRow === dragSrcRow) return;
                if (targetRow.parentElement !== list || dragSrcRow.parentElement !== list) return;

                const rect = targetRow.getBoundingClientRect();
                if (e.clientY < rect.top + rect.height / 2) {
                    list.insertBefore(dragSrcRow, targetRow);
                } else {
                    list.insertBefore(dragSrcRow, targetRow.nextSibling);
                }

                targetRow.classList.remove('drag-over');
            });
        }

        // Close modal on outside click
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('checklist-modal')) {
                e.target.classList.remove('active');
            }
        });
    </script>
</body>
</html>
