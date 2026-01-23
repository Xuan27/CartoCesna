<?php
session_start();

// Determine root path for URLs
$root_page = '/CartoCesna/';

// Determine file system path for includes
$base_path = dirname(__DIR__, 3); // Go up 3 levels: 00_002 -> Professional -> Projects -> CartoCesna

// Check if user is logged in for edit permissions
$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'];
$canEdit = $isLoggedIn; // Only logged-in users can edit
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Texas Survey Legal Library - CartoCesna</title>
    <link rel="stylesheet" href="<?php echo $root_page; ?>Models/css/style.css">
    <link rel="stylesheet" href="<?php echo $root_page; ?>Models/css/header_tabs.css">
    <style>
        body {
            background-color: #f5f5f7;
            color: #333;
            line-height: 1.6;
        }

        .library-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .library-header {
            background: #fff;
            border-radius: 8px;
            padding: 32px;
            margin-bottom: 24px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
        }

        .library-header h1 {
            font-size: 2rem;
            color: #333;
            margin: 0;
        }

        .library-header p {
            color: #666;
            margin: 8px 0 0 0;
            font-size: 1rem;
        }

        .header-actions {
            display: flex;
            gap: 12px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
            cursor: pointer;
            border: none;
            transition: all 0.3s;
        }

        .btn-primary {
            background-color: #0066cc;
            color: #fff;
        }

        .btn-primary:hover {
            background-color: #0055aa;
        }

        .btn-secondary {
            background-color: #f0f0f0;
            color: #333;
        }

        .btn-secondary:hover {
            background-color: #e0e0e0;
        }

        .btn-danger {
            background-color: #dc3545;
            color: #fff;
        }

        .btn-danger:hover {
            background-color: #c82333;
        }

        .library-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 24px;
            margin-bottom: 24px;
        }

        .library-section {
            background: #fff;
            border-radius: 8px;
            padding: 24px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 2px solid #0066cc;
        }

        .section-header h2 {
            color: #0066cc;
            font-size: 1.2rem;
            margin: 0;
        }

        .section-actions {
            display: flex;
            gap: 8px;
        }

        .btn-icon {
            width: 32px;
            height: 32px;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
            font-size: 14px;
        }

        .library-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .library-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #eee;
        }

        .library-item:last-child {
            border-bottom: none;
        }

        .item-content {
            flex: 1;
        }

        .item-content a {
            color: #333;
            text-decoration: none;
            transition: color 0.3s;
        }

        .item-content a:hover {
            color: #0066cc;
        }

        .item-actions {
            display: flex;
            gap: 4px;
            opacity: 0;
            transition: opacity 0.2s;
        }

        .library-item:hover .item-actions {
            opacity: 1;
        }

        .resource-type {
            font-size: 0.7rem;
            padding: 3px 8px;
            border-radius: 4px;
            margin-left: 8px;
            text-transform: uppercase;
            font-weight: 600;
        }

        .type-statute {
            background: rgba(46, 204, 113, 0.15);
            color: #27ae60;
        }

        .type-case {
            background: rgba(155, 89, 182, 0.15);
            color: #8e44ad;
        }

        .type-guide {
            background: rgba(241, 196, 15, 0.15);
            color: #f39c12;
        }

        .type-reference {
            background: rgba(52, 152, 219, 0.15);
            color: #2980b9;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 2000;
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: #fff;
            border-radius: 8px;
            padding: 24px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .modal-header h3 {
            margin: 0;
            color: #333;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #666;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: 500;
            color: #333;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #0066cc;
        }

        .modal-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            margin-top: 20px;
        }

        .empty-section {
            text-align: center;
            padding: 24px;
            color: #999;
        }

        footer {
            text-align: center;
            padding: 24px;
            color: #666;
            font-size: 0.9rem;
        }

        .edit-mode .item-actions {
            opacity: 1;
        }

        .add-section-btn {
            width: 100%;
            padding: 40px;
            border: 2px dashed #ccc;
            border-radius: 8px;
            background: transparent;
            color: #999;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .add-section-btn:hover {
            border-color: #0066cc;
            color: #0066cc;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #666;
            text-decoration: none;
            margin-bottom: 16px;
        }

        .back-link:hover {
            color: #0066cc;
        }
    </style>
</head>
<body>
    <?php include $base_path . '/Models/php/header_tabs.php'; ?>

    <div class="library-container">
        <a href="<?php echo $root_page; ?>dashboard.php" class="back-link">
            <span>←</span> Back to Dashboard
        </a>

        <div class="library-header">
            <div>
                <h1>Texas Survey Legal Library</h1>
                <p>Boundary Law, State Statutes, and Professional Practices</p>
            </div>
            <?php if ($canEdit): ?>
            <div class="header-actions">
                <button class="btn btn-secondary" id="toggleEditMode">
                    <span>Edit Mode</span>
                </button>
                <button class="btn btn-primary" id="addSectionBtn" style="display: none;">
                    <span>+ Add Section</span>
                </button>
            </div>
            <?php endif; ?>
        </div>

        <div class="library-grid" id="libraryGrid">
            <!-- Sections will be loaded here -->
        </div>

        <?php if ($canEdit): ?>
        <button class="add-section-btn" id="addSectionBtnAlt" style="display: none;">
            + Add New Section
        </button>
        <?php endif; ?>

        <footer>
            <p>Texas Survey Legal Library - Project ID: 00_002</p>
            <p>CartoCesna Professional Projects</p>
        </footer>
    </div>

    <!-- Section Modal -->
    <div class="modal" id="sectionModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="sectionModalTitle">Add Section</h3>
                <button class="modal-close" onclick="closeSectionModal()">&times;</button>
            </div>
            <form id="sectionForm">
                <input type="hidden" id="sectionId" name="id">
                <div class="form-group">
                    <label for="sectionName">Section Name</label>
                    <input type="text" id="sectionName" name="name" required placeholder="e.g., Texas Property Code">
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeSectionModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Section</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Item Modal -->
    <div class="modal" id="itemModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="itemModalTitle">Add Item</h3>
                <button class="modal-close" onclick="closeItemModal()">&times;</button>
            </div>
            <form id="itemForm">
                <input type="hidden" id="itemSectionId" name="section_id">
                <input type="hidden" id="itemId" name="id">
                <div class="form-group">
                    <label for="itemTitle">Title</label>
                    <input type="text" id="itemTitle" name="title" required placeholder="e.g., Title 2 - Conveyances">
                </div>
                <div class="form-group">
                    <label for="itemUrl">URL (optional)</label>
                    <input type="url" id="itemUrl" name="url" placeholder="https://...">
                </div>
                <div class="form-group">
                    <label for="itemType">Resource Type</label>
                    <select id="itemType" name="type">
                        <option value="statute">Statute</option>
                        <option value="case">Case Law</option>
                        <option value="guide">Guide</option>
                        <option value="reference">Reference</option>
                    </select>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeItemModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Item</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const API_URL = 'api.php';
        const canEdit = <?php echo $canEdit ? 'true' : 'false'; ?>;
        let editMode = false;
        let libraryData = { sections: [] };

        // Load library data
        async function loadLibrary() {
            try {
                const response = await fetch(API_URL + '?action=get');
                const data = await response.json();
                if (data.success) {
                    libraryData = data.data;
                    renderLibrary();
                }
            } catch (error) {
                console.error('Error loading library:', error);
            }
        }

        // Render library
        function renderLibrary() {
            const grid = document.getElementById('libraryGrid');

            if (libraryData.sections.length === 0) {
                grid.innerHTML = '<div class="empty-section">No sections yet. Click "Add Section" to get started.</div>';
                return;
            }

            grid.innerHTML = libraryData.sections.map(section => `
                <div class="library-section" data-section-id="${section.id}">
                    <div class="section-header">
                        <h2>${escapeHtml(section.name)}</h2>
                        ${canEdit ? `
                        <div class="section-actions" style="display: ${editMode ? 'flex' : 'none'}">
                            <button class="btn btn-icon btn-secondary" onclick="openItemModal('${section.id}')" title="Add Item">+</button>
                            <button class="btn btn-icon btn-secondary" onclick="editSection('${section.id}')" title="Edit Section">✎</button>
                            <button class="btn btn-icon btn-danger" onclick="deleteSection('${section.id}')" title="Delete Section">×</button>
                        </div>
                        ` : ''}
                    </div>
                    <ul class="library-list">
                        ${section.items.length === 0
                            ? '<li class="empty-section">No items yet</li>'
                            : section.items.map(item => `
                                <li class="library-item" data-item-id="${item.id}">
                                    <div class="item-content">
                                        ${item.url
                                            ? `<a href="${escapeHtml(item.url)}" target="_blank">${escapeHtml(item.title)}</a>`
                                            : `<span>${escapeHtml(item.title)}</span>`
                                        }
                                        <span class="resource-type type-${item.type}">${item.type}</span>
                                    </div>
                                    ${canEdit ? `
                                    <div class="item-actions" style="display: ${editMode ? 'flex' : 'none'}">
                                        <button class="btn btn-icon btn-secondary" onclick="editItem('${section.id}', '${item.id}')" title="Edit">✎</button>
                                        <button class="btn btn-icon btn-danger" onclick="deleteItem('${section.id}', '${item.id}')" title="Delete">×</button>
                                    </div>
                                    ` : ''}
                                </li>
                            `).join('')
                        }
                    </ul>
                </div>
            `).join('');
        }

        // Toggle edit mode
        function toggleEditMode() {
            editMode = !editMode;
            const btn = document.getElementById('toggleEditMode');
            const addBtn = document.getElementById('addSectionBtn');
            const addBtnAlt = document.getElementById('addSectionBtnAlt');

            if (editMode) {
                btn.innerHTML = '<span>Done Editing</span>';
                btn.classList.remove('btn-secondary');
                btn.classList.add('btn-primary');
                if (addBtn) addBtn.style.display = 'inline-flex';
                if (addBtnAlt) addBtnAlt.style.display = 'block';
                document.body.classList.add('edit-mode');
            } else {
                btn.innerHTML = '<span>Edit Mode</span>';
                btn.classList.remove('btn-primary');
                btn.classList.add('btn-secondary');
                if (addBtn) addBtn.style.display = 'none';
                if (addBtnAlt) addBtnAlt.style.display = 'none';
                document.body.classList.remove('edit-mode');
            }

            // Toggle visibility of edit buttons
            document.querySelectorAll('.section-actions, .item-actions').forEach(el => {
                el.style.display = editMode ? 'flex' : 'none';
            });
        }

        // Section functions
        function openSectionModal(sectionId = null) {
            const modal = document.getElementById('sectionModal');
            const title = document.getElementById('sectionModalTitle');
            const form = document.getElementById('sectionForm');

            form.reset();

            if (sectionId) {
                const section = libraryData.sections.find(s => s.id === sectionId);
                if (section) {
                    title.textContent = 'Edit Section';
                    document.getElementById('sectionId').value = section.id;
                    document.getElementById('sectionName').value = section.name;
                }
            } else {
                title.textContent = 'Add Section';
                document.getElementById('sectionId').value = '';
            }

            modal.classList.add('active');
        }

        function closeSectionModal() {
            document.getElementById('sectionModal').classList.remove('active');
        }

        function editSection(sectionId) {
            openSectionModal(sectionId);
        }

        async function deleteSection(sectionId) {
            if (!confirm('Are you sure you want to delete this section and all its items?')) return;

            try {
                const response = await fetch(API_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'delete_section', section_id: sectionId })
                });
                const data = await response.json();
                if (data.success) {
                    loadLibrary();
                } else {
                    alert('Error: ' + data.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error deleting section');
            }
        }

        // Item functions
        function openItemModal(sectionId, itemId = null) {
            const modal = document.getElementById('itemModal');
            const title = document.getElementById('itemModalTitle');
            const form = document.getElementById('itemForm');

            form.reset();
            document.getElementById('itemSectionId').value = sectionId;

            if (itemId) {
                const section = libraryData.sections.find(s => s.id === sectionId);
                const item = section?.items.find(i => i.id === itemId);
                if (item) {
                    title.textContent = 'Edit Item';
                    document.getElementById('itemId').value = item.id;
                    document.getElementById('itemTitle').value = item.title;
                    document.getElementById('itemUrl').value = item.url || '';
                    document.getElementById('itemType').value = item.type;
                }
            } else {
                title.textContent = 'Add Item';
                document.getElementById('itemId').value = '';
            }

            modal.classList.add('active');
        }

        function closeItemModal() {
            document.getElementById('itemModal').classList.remove('active');
        }

        function editItem(sectionId, itemId) {
            openItemModal(sectionId, itemId);
        }

        async function deleteItem(sectionId, itemId) {
            if (!confirm('Are you sure you want to delete this item?')) return;

            try {
                const response = await fetch(API_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'delete_item', section_id: sectionId, item_id: itemId })
                });
                const data = await response.json();
                if (data.success) {
                    loadLibrary();
                } else {
                    alert('Error: ' + data.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error deleting item');
            }
        }

        // Form submissions
        document.getElementById('sectionForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const sectionId = formData.get('id');

            try {
                const response = await fetch(API_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: sectionId ? 'update_section' : 'add_section',
                        section_id: sectionId || undefined,
                        name: formData.get('name')
                    })
                });
                const data = await response.json();
                if (data.success) {
                    closeSectionModal();
                    loadLibrary();
                } else {
                    alert('Error: ' + data.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error saving section');
            }
        });

        document.getElementById('itemForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const itemId = formData.get('id');

            try {
                const response = await fetch(API_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: itemId ? 'update_item' : 'add_item',
                        section_id: formData.get('section_id'),
                        item_id: itemId || undefined,
                        title: formData.get('title'),
                        url: formData.get('url'),
                        type: formData.get('type')
                    })
                });
                const data = await response.json();
                if (data.success) {
                    closeItemModal();
                    loadLibrary();
                } else {
                    alert('Error: ' + data.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error saving item');
            }
        });

        // Utility
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Event listeners
        if (canEdit) {
            document.getElementById('toggleEditMode')?.addEventListener('click', toggleEditMode);
            document.getElementById('addSectionBtn')?.addEventListener('click', () => openSectionModal());
            document.getElementById('addSectionBtnAlt')?.addEventListener('click', () => openSectionModal());
        }

        // Close modals on outside click
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.classList.remove('active');
                }
            });
        });

        // Initialize
        loadLibrary();
    </script>
</body>
</html>
