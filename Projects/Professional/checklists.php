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
                <a href="./checklists.php" class="nav-item active">
                    <i class="fas fa-clipboard-check"></i>
                    Checklists
                </a>
                <a href="./tools.php" class="nav-item">
                    <i class="fas fa-tools"></i>
                    Tools
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
                        <button type="button" class="add-item-btn" onclick="addItemRow()">
                            <i class="fas fa-plus"></i> Add Item
                        </button>
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

                // Set task type checkboxes
                document.querySelectorAll('#taskTypeCheckboxes input').forEach(cb => {
                    cb.checked = (t.task_types || []).includes(cb.value);
                });

                // Populate items
                const itemsList = document.getElementById('templateItemsList');
                itemsList.innerHTML = '';
                (t.items || []).forEach(item => {
                    addItemRow(item.item_id, item.item_text, item.category);
                });

                if (!t.items || t.items.length === 0) {
                    addItemRow();
                }

                document.getElementById('templateModal').classList.add('active');
            } catch (error) {
                console.error('Error:', error);
                showToast('Network error', 'error');
            }
        }

        // Add an item row to the modal
        function addItemRow(itemId = '', text = '', category = '') {
            const list = document.getElementById('templateItemsList');
            const row = document.createElement('div');
            row.className = 'template-item-row';
            row.dataset.itemId = itemId || '';
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

        // Save template (create or update)
        async function saveTemplate() {
            const templateId = document.getElementById('editTemplateId').value;
            const name = document.getElementById('templateName').value.trim();
            const description = document.getElementById('templateDescription').value.trim();

            if (!name) {
                showToast('Template name is required', 'error');
                return;
            }

            // Collect task types
            const taskTypes = [];
            document.querySelectorAll('#taskTypeCheckboxes input:checked').forEach(cb => {
                taskTypes.push(cb.value);
            });

            // Collect items
            const items = [];
            document.querySelectorAll('#templateItemsList .template-item-row').forEach((row, i) => {
                const text = row.querySelector('.item-text-input').value.trim();
                if (text) {
                    items.push({
                        item_id: row.dataset.itemId || null,
                        item_text: text,
                        category: row.querySelector('.category-input').value.trim() || null,
                        sort_order: i
                    });
                }
            });

            const payload = {
                action: templateId ? 'update_template' : 'add_template',
                template_name: name,
                description: description,
                task_types: taskTypes,
                items: items
            };

            if (templateId) {
                payload.template_id = templateId;
            }

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

        // Close modal on outside click
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('checklist-modal')) {
                e.target.classList.remove('active');
            }
        });
    </script>
</body>
</html>
