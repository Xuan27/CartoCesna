<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Tools - Survey Project Manager</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <link rel="stylesheet" href="../../Models/css/survey_projects_notes.css">
    <style>
        /* Tools-specific styles */
        .tools-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 1.5rem;
            overflow: hidden;
        }

        .table-tabs {
            display: flex;
            border-bottom: 2px solid var(--gray-200);
            background: var(--gray-50);
        }

        .table-tab {
            padding: 1rem 2rem;
            border: none;
            background: transparent;
            cursor: pointer;
            font-size: 0.95rem;
            font-weight: 500;
            color: var(--gray-600);
            transition: all 0.2s;
            position: relative;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .table-tab:hover {
            color: var(--primary-color);
            background: rgba(102, 126, 234, 0.05);
        }

        .table-tab.active {
            color: var(--primary-color);
            background: white;
        }

        .table-tab.active::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            right: 0;
            height: 2px;
            background: var(--primary-color);
        }

        .table-tab .count-badge {
            background: var(--gray-200);
            color: var(--gray-600);
            padding: 0.15rem 0.5rem;
            border-radius: 10px;
            font-size: 0.75rem;
        }

        .table-tab.active .count-badge {
            background: var(--primary-color);
            color: white;
        }

        .tools-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--gray-200);
            flex-wrap: wrap;
            gap: 1rem;
        }

        .toolbar-left {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .toolbar-right {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .search-box {
            position: relative;
        }

        .search-box input {
            padding: 0.5rem 1rem 0.5rem 2.5rem;
            border: 1px solid var(--gray-300);
            border-radius: 6px;
            font-size: 0.875rem;
            width: 280px;
            transition: border-color 0.2s;
        }

        .search-box input:focus {
            outline: none;
            border-color: var(--primary-color);
        }

        .search-box i {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray-400);
        }

        .bulk-actions {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 0.75rem;
            background: var(--primary-color);
            border-radius: 6px;
            color: white;
            font-size: 0.875rem;
        }

        .bulk-actions.hidden {
            display: none;
        }

        .data-grid-wrapper {
            overflow-x: auto;
        }

        .data-grid {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.875rem;
        }

        .data-grid th {
            background: var(--gray-50);
            padding: 0.75rem 1rem;
            text-align: left;
            font-weight: 600;
            color: var(--gray-700);
            border-bottom: 2px solid var(--gray-200);
            white-space: nowrap;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .data-grid th.sortable {
            cursor: pointer;
            user-select: none;
        }

        .data-grid th.sortable:hover {
            background: var(--gray-100);
        }

        .data-grid th .sort-icon {
            margin-left: 0.5rem;
            opacity: 0.3;
        }

        .data-grid th.sorted .sort-icon {
            opacity: 1;
            color: var(--primary-color);
        }

        .data-grid td {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid var(--gray-100);
            vertical-align: middle;
        }

        .data-grid tr:hover {
            background: var(--gray-50);
        }

        .data-grid tr.selected {
            background: rgba(102, 126, 234, 0.1);
        }

        .data-grid .checkbox-cell {
            width: 40px;
            text-align: center;
        }

        .data-grid .actions-cell {
            width: 120px;
            text-align: right;
        }

        .data-grid input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: var(--primary-color);
        }

        .editable-cell {
            cursor: pointer;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            transition: background 0.2s;
            min-height: 1.5em;
        }

        .editable-cell:hover {
            background: var(--gray-100);
        }

        .editable-cell.editing {
            padding: 0;
        }

        .editable-cell input,
        .editable-cell select,
        .editable-cell textarea {
            width: 100%;
            padding: 0.35rem 0.5rem;
            border: 2px solid var(--primary-color);
            border-radius: 4px;
            font-size: 0.875rem;
            background: white;
        }

        .editable-cell input:focus,
        .editable-cell select:focus,
        .editable-cell textarea:focus {
            outline: none;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .status-badge i {
            font-size: 0.5rem;
        }

        .priority-badge {
            padding: 0.2rem 0.6rem;
            border-radius: 4px;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .priority-low { background: #e0f2fe; color: #0369a1; }
        .priority-medium { background: #fef3c7; color: #d97706; }
        .priority-high { background: #fee2e2; color: #dc2626; }
        .priority-urgent { background: #fce7f3; color: #db2777; }

        .row-actions {
            display: flex;
            gap: 0.25rem;
            justify-content: flex-end;
        }

        .row-actions .btn {
            padding: 0.35rem 0.5rem;
            font-size: 0.75rem;
        }

        .grid-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 1.5rem;
            background: var(--gray-50);
            border-top: 1px solid var(--gray-200);
        }

        .pagination-info {
            color: var(--gray-600);
            font-size: 0.875rem;
        }

        .pagination-controls {
            display: flex;
            gap: 0.25rem;
        }

        .pagination-controls button {
            padding: 0.5rem 0.75rem;
            border: 1px solid var(--gray-300);
            background: white;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.875rem;
            transition: all 0.2s;
        }

        .pagination-controls button:hover:not(:disabled) {
            background: var(--gray-100);
            border-color: var(--gray-400);
        }

        .pagination-controls button.active {
            background: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
        }

        .pagination-controls button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* Quick Edit Modal */
        .quick-edit-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .quick-edit-modal.active {
            display: flex;
        }

        .quick-edit-content {
            background: white;
            border-radius: 12px;
            width: 90%;
            max-width: 700px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .quick-edit-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--gray-200);
        }

        .quick-edit-header h3 {
            margin: 0;
            font-size: 1.1rem;
            color: var(--gray-800);
        }

        .quick-edit-body {
            padding: 1.5rem;
        }

        .quick-edit-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }

        .quick-edit-grid .form-group.full-width {
            grid-column: 1 / -1;
        }

        .quick-edit-footer {
            display: flex;
            justify-content: flex-end;
            gap: 0.75rem;
            padding: 1rem 1.5rem;
            border-top: 1px solid var(--gray-200);
            background: var(--gray-50);
        }

        .truncate {
            max-width: 200px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: var(--gray-500);
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        /* Column visibility dropdown */
        .columns-dropdown {
            position: relative;
        }

        .columns-menu {
            display: none;
            position: absolute;
            top: 100%;
            right: 0;
            background: white;
            border: 1px solid var(--gray-200);
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            z-index: 100;
            min-width: 200px;
            padding: 0.5rem 0;
            margin-top: 0.5rem;
        }

        .columns-menu.show {
            display: block;
        }

        .columns-menu-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            cursor: pointer;
            font-size: 0.875rem;
        }

        .columns-menu-item:hover {
            background: var(--gray-50);
        }

        .loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 50;
        }

        .loading-overlay.hidden {
            display: none;
        }

        .spinner {
            width: 40px;
            height: 40px;
            border: 3px solid var(--gray-200);
            border-top-color: var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Point Ranges Editor Styles */
        .point-ranges-container {
            border: 1px solid var(--gray-200);
            border-radius: 8px;
            padding: 1rem;
            background: var(--gray-50);
        }

        .point-range-entry {
            background: white;
            border: 1px solid var(--gray-200);
            border-radius: 6px;
            padding: 1rem;
            margin-bottom: 0.75rem;
            position: relative;
        }

        .point-range-entry:last-child {
            margin-bottom: 0;
        }

        .point-range-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.75rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid var(--gray-100);
        }

        .point-range-header span {
            font-weight: 600;
            color: var(--gray-700);
            font-size: 0.875rem;
        }

        .point-range-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.75rem;
        }

        .point-range-grid .form-group {
            margin-bottom: 0;
        }

        .point-range-grid .form-group.full-width {
            grid-column: 1 / -1;
        }

        .point-range-grid label {
            font-size: 0.75rem;
            color: var(--gray-600);
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
            border: 2px dashed var(--gray-300);
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
            background: var(--danger-color);
            color: white;
            border: none;
            border-radius: 4px;
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
            cursor: pointer;
        }

        .remove-entry-btn:hover {
            background: #c82333;
        }

        .point-ranges-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.2rem 0.5rem;
            background: rgba(102, 126, 234, 0.1);
            color: var(--primary-color);
            border-radius: 4px;
            font-size: 0.75rem;
            cursor: pointer;
        }

        .point-ranges-badge:hover {
            background: rgba(102, 126, 234, 0.2);
        }

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

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .toolbar-left, .toolbar-right {
                width: 100%;
            }

            .search-box input {
                width: 100%;
            }

            .quick-edit-grid {
                grid-template-columns: 1fr;
            }

            .point-range-grid {
                grid-template-columns: 1fr;
            }
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
                <a href="./tools.php" class="nav-item active">
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
                    <h2>Database Tools</h2>
                    <p>Manage and edit survey projects and tasks data</p>
                </div>
            </div>
            <div class="top-bar-right">
                <button class="btn btn-secondary" onclick="exportData()">
                    <i class="fas fa-download"></i> Export CSV
                </button>
                <button class="btn btn-primary" onclick="openAddModal()">
                    <i class="fas fa-plus"></i> Add Record
                </button>
            </div>
        </div>

        <!-- Content Area -->
        <div class="content-area">
            <div class="tools-container">
                <!-- Table Tabs -->
                <div class="table-tabs">
                    <button class="table-tab active" data-table="survey_projects" onclick="switchTable('survey_projects')">
                        <i class="fas fa-project-diagram"></i>
                        Survey Projects
                        <span class="count-badge" id="projectsCount">0</span>
                    </button>
                    <button class="table-tab" data-table="tasks" onclick="switchTable('tasks')">
                        <i class="fas fa-tasks"></i>
                        Tasks
                        <span class="count-badge" id="tasksCount">0</span>
                    </button>
                </div>

                <!-- Toolbar -->
                <div class="tools-toolbar">
                    <div class="toolbar-left">
                        <div class="search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" id="searchInput" placeholder="Search records..." onkeyup="searchRecords()">
                        </div>
                        <select class="filter-select" id="statusFilter" onchange="filterRecords()">
                            <option value="">All Status</option>
                            <option value="Active">Active</option>
                            <option value="Completed">Completed</option>
                            <option value="On Hold">On Hold</option>
                            <option value="In Progress">In Progress</option>
                            <option value="Not Started">Not Started</option>
                            <option value="Cancelled">Cancelled</option>
                        </select>
                        <div class="bulk-actions hidden" id="bulkActions">
                            <span id="selectedCount">0 selected</span>
                            <button class="btn btn-sm" style="background: white; color: var(--danger-color);" onclick="bulkDelete()">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                            <button class="btn btn-sm" style="background: white; color: var(--primary-color);" onclick="bulkUpdateStatus()">
                                <i class="fas fa-edit"></i> Update Status
                            </button>
                        </div>
                    </div>
                    <div class="toolbar-right">
                        <button class="btn btn-secondary btn-sm" onclick="refreshData()">
                            <i class="fas fa-sync-alt"></i> Refresh
                        </button>
                        <div class="columns-dropdown">
                            <button class="btn btn-secondary btn-sm" onclick="toggleColumnsMenu()">
                                <i class="fas fa-columns"></i> Columns
                            </button>
                            <div class="columns-menu" id="columnsMenu">
                                <!-- Column toggles will be inserted here -->
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Data Grid -->
                <div class="data-grid-wrapper" style="position: relative;">
                    <div class="loading-overlay" id="loadingOverlay">
                        <div class="spinner"></div>
                    </div>
                    <table class="data-grid" id="dataGrid">
                        <thead id="gridHeader">
                            <!-- Headers will be inserted dynamically -->
                        </thead>
                        <tbody id="gridBody">
                            <!-- Data rows will be inserted dynamically -->
                        </tbody>
                    </table>
                </div>

                <!-- Grid Footer -->
                <div class="grid-footer">
                    <div class="pagination-info" id="paginationInfo">
                        Showing 0 to 0 of 0 records
                    </div>
                    <div class="pagination-controls" id="paginationControls">
                        <!-- Pagination buttons will be inserted here -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Edit Modal -->
    <div class="quick-edit-modal" id="editModal">
        <div class="quick-edit-content">
            <div class="quick-edit-header">
                <h3 id="editModalTitle">Edit Record</h3>
                <button class="close-button" onclick="closeEditModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="quick-edit-body">
                <form id="editForm">
                    <div class="quick-edit-grid" id="editFormFields">
                        <!-- Form fields will be inserted dynamically -->
                    </div>
                </form>
            </div>
            <div class="quick-edit-footer">
                <button class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
                <button class="btn btn-primary" onclick="saveRecord()">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </div>
        </div>
    </div>

    <!-- Bulk Status Update Modal -->
    <div class="quick-edit-modal" id="bulkStatusModal">
        <div class="quick-edit-content" style="max-width: 400px;">
            <div class="quick-edit-header">
                <h3>Update Status</h3>
                <button class="close-button" onclick="closeBulkStatusModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="quick-edit-body">
                <div class="form-group">
                    <label class="form-label">New Status</label>
                    <select class="form-select" id="bulkStatusSelect">
                        <option value="Active">Active</option>
                        <option value="Completed">Completed</option>
                        <option value="On Hold">On Hold</option>
                        <option value="In Progress">In Progress</option>
                        <option value="Not Started">Not Started</option>
                        <option value="Cancelled">Cancelled</option>
                    </select>
                </div>
            </div>
            <div class="quick-edit-footer">
                <button class="btn btn-secondary" onclick="closeBulkStatusModal()">Cancel</button>
                <button class="btn btn-primary" onclick="applyBulkStatus()">
                    <i class="fas fa-check"></i> Apply
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
        // Configuration
        const API_URL = '../../Models/php/tools_api.php';

        // State
        let currentTable = 'survey_projects';
        let allRecords = [];
        let filteredRecords = [];
        let selectedIds = new Set();
        let currentPage = 1;
        let itemsPerPage = 25;
        let sortColumn = null;
        let sortDirection = 'asc';
        let editingRecord = null;
        let visibleColumns = {};

        // Table configurations
        const tableConfig = {
            survey_projects: {
                primaryKey: 'project_id',
                columns: [
                    { key: 'project_id', label: 'Project ID', editable: false, visible: true },
                    { key: 'project_name', label: 'Project Name', editable: true, visible: true },
                    { key: 'project_status', label: 'Status', editable: true, type: 'select', options: ['Active', 'Completed', 'On Hold'], visible: true },
                    { key: 'created_by', label: 'Created By', editable: true, visible: true },
                    { key: 'created_date', label: 'Created', editable: false, type: 'date', visible: true },
                    { key: 'project_folder_link', label: 'Project Folder', editable: true, visible: false },
                    { key: 'survey_folder_link', label: 'Survey Folder', editable: true, visible: false },
                    { key: 'drawing_folder_link', label: 'Drawing Folder', editable: true, visible: false },
                    { key: 'contract_link', label: 'Contract Link', editable: true, visible: false },
                    { key: 'qaQc_folder_link', label: 'QA/QC Folder', editable: true, visible: false },
                    { key: 'research_folder_link', label: 'Research Folder', editable: true, visible: false },
                    { key: 'location', label: 'Location', editable: true, visible: true },
                    { key: 'scale_factor', label: 'Scale Factor', editable: true, visible: false },
                    { key: 'notes', label: 'Notes', editable: true, type: 'textarea', visible: false }
                ]
            },
            tasks: {
                primaryKey: 'task_id',
                columns: [
                    { key: 'task_id', label: 'Task ID', editable: false, visible: true },
                    { key: 'project_id', label: 'Project ID', editable: true, visible: true },
                    { key: 'task_name', label: 'Task Name', editable: true, visible: true },
                    { key: 'task_type', label: 'Type', editable: true, type: 'select', options: ['Easement', 'ALTA', 'Plat', 'Construction Staking', 'Boundary Survey', 'Topographic Survey', 'As-Built Survey', 'Other'], visible: true },
                    { key: 'task_status', label: 'Status', editable: true, type: 'select', options: ['Not Started', 'In Progress', 'On Hold', 'Completed', 'Cancelled'], visible: true },
                    { key: 'task_priority', label: 'Priority', editable: true, type: 'select', options: ['Low', 'Medium', 'High', 'Urgent'], visible: true },
                    { key: 'phase_number', label: 'Phase', editable: true, visible: true },
                    { key: 'assigned_to', label: 'Assigned To', editable: true, visible: true },
                    { key: 'start_date', label: 'Start Date', editable: true, type: 'date', visible: false },
                    { key: 'due_date', label: 'Due Date', editable: true, type: 'date', visible: true },
                    { key: 'completion_date', label: 'Completed', editable: true, type: 'date', visible: false },
                    { key: 'estimated_hours', label: 'Est. Hours', editable: true, type: 'number', visible: false },
                    { key: 'actual_hours', label: 'Actual Hours', editable: true, type: 'number', visible: false },
                    { key: 'task_link', label: 'Task Folder', editable: true, visible: false },
                    { key: 'point_ranges', label: 'Point Ranges', editable: true, type: 'point_ranges', visible: true },
                    { key: 'notes', label: 'Notes', editable: true, type: 'textarea', visible: false }
                ]
            }
        };

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            initializeVisibleColumns();
            loadData();
            setupSidebar();
        });

        function initializeVisibleColumns() {
            Object.keys(tableConfig).forEach(table => {
                visibleColumns[table] = {};
                tableConfig[table].columns.forEach(col => {
                    visibleColumns[table][col.key] = col.visible;
                });
            });
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

        // Load data from API
        async function loadData() {
            showLoading(true);
            try {
                const response = await fetch(`${API_URL}?action=get_all&table=${currentTable}`);
                const data = await response.json();

                if (data.success) {
                    allRecords = data.records || [];
                    updateCounts(data.counts);
                    filterRecords();
                    showToast('Data loaded successfully', 'success');
                } else {
                    showToast(data.message || 'Error loading data', 'error');
                }
            } catch (error) {
                console.error('Error loading data:', error);
                showToast('Network error loading data', 'error');
            } finally {
                showLoading(false);
            }
        }

        function updateCounts(counts) {
            if (counts) {
                document.getElementById('projectsCount').textContent = counts.survey_projects || 0;
                document.getElementById('tasksCount').textContent = counts.tasks || 0;
            }
        }

        // Switch between tables
        function switchTable(table) {
            currentTable = table;
            selectedIds.clear();
            currentPage = 1;
            sortColumn = null;

            // Update tab styles
            document.querySelectorAll('.table-tab').forEach(tab => {
                tab.classList.toggle('active', tab.dataset.table === table);
            });

            // Clear search and filters
            document.getElementById('searchInput').value = '';
            document.getElementById('statusFilter').value = '';

            // Update columns menu
            updateColumnsMenu();

            // Load new data
            loadData();
        }

        // Search and filter
        function searchRecords() {
            filterRecords();
        }

        function filterRecords() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const statusFilter = document.getElementById('statusFilter').value;
            const config = tableConfig[currentTable];

            filteredRecords = allRecords.filter(record => {
                // Search across all visible columns
                const matchesSearch = !searchTerm || config.columns.some(col => {
                    const value = record[col.key];
                    return value && String(value).toLowerCase().includes(searchTerm);
                });

                // Status filter
                const statusKey = currentTable === 'survey_projects' ? 'project_status' : 'task_status';
                const matchesStatus = !statusFilter || record[statusKey] === statusFilter;

                return matchesSearch && matchesStatus;
            });

            // Apply sorting
            if (sortColumn) {
                filteredRecords.sort((a, b) => {
                    const aVal = a[sortColumn] || '';
                    const bVal = b[sortColumn] || '';
                    const comparison = String(aVal).localeCompare(String(bVal));
                    return sortDirection === 'asc' ? comparison : -comparison;
                });
            }

            currentPage = 1;
            renderGrid();
            updateBulkActions();
        }

        // Render data grid
        function renderGrid() {
            const config = tableConfig[currentTable];
            const visibleCols = config.columns.filter(col => visibleColumns[currentTable][col.key]);

            // Render header
            const headerHtml = `
                <tr>
                    <th class="checkbox-cell">
                        <input type="checkbox" onchange="toggleSelectAll(this)" id="selectAllCheckbox">
                    </th>
                    ${visibleCols.map(col => `
                        <th class="sortable ${sortColumn === col.key ? 'sorted' : ''}" onclick="sortBy('${col.key}')">
                            ${col.label}
                            <i class="fas fa-sort${sortColumn === col.key ? (sortDirection === 'asc' ? '-up' : '-down') : ''} sort-icon"></i>
                        </th>
                    `).join('')}
                    <th class="actions-cell">Actions</th>
                </tr>
            `;
            document.getElementById('gridHeader').innerHTML = headerHtml;

            // Paginate
            const startIndex = (currentPage - 1) * itemsPerPage;
            const endIndex = Math.min(startIndex + itemsPerPage, filteredRecords.length);
            const pageRecords = filteredRecords.slice(startIndex, endIndex);

            // Render body
            if (pageRecords.length === 0) {
                document.getElementById('gridBody').innerHTML = `
                    <tr>
                        <td colspan="${visibleCols.length + 2}">
                            <div class="empty-state">
                                <i class="fas fa-database"></i>
                                <h3>No records found</h3>
                                <p>Try adjusting your search or filters</p>
                            </div>
                        </td>
                    </tr>
                `;
            } else {
                const bodyHtml = pageRecords.map(record => {
                    const id = record[config.primaryKey];
                    const isSelected = selectedIds.has(id);

                    return `
                        <tr class="${isSelected ? 'selected' : ''}" data-id="${id}">
                            <td class="checkbox-cell">
                                <input type="checkbox" ${isSelected ? 'checked' : ''} onchange="toggleSelect('${id}', this)">
                            </td>
                            ${visibleCols.map(col => renderCell(record, col)).join('')}
                            <td class="actions-cell">
                                <div class="row-actions">
                                    <button class="btn btn-secondary btn-sm" onclick="editRecord('${id}')" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm" style="background: var(--danger-color); color: white;" onclick="deleteRecord('${id}')" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    `;
                }).join('');
                document.getElementById('gridBody').innerHTML = bodyHtml;
            }

            // Update pagination
            updatePagination();
        }

        function renderCell(record, col) {
            const value = record[col.key];
            let displayValue = value || '-';

            // Format based on type
            if (col.type === 'date' && value) {
                displayValue = new Date(value).toLocaleDateString();
            } else if (col.key.includes('status')) {
                const statusClass = getStatusClass(value);
                displayValue = `<span class="status-badge ${statusClass}"><i class="fas fa-circle"></i> ${value || 'N/A'}</span>`;
            } else if (col.key === 'task_priority') {
                displayValue = `<span class="priority-badge priority-${(value || 'medium').toLowerCase()}">${value || 'Medium'}</span>`;
            } else if (col.key.includes('link') || col.key.includes('folder')) {
                displayValue = value ? `<span class="truncate" title="${value}">${value}</span>` : '-';
            } else if (col.type === 'point_ranges') {
                // Parse and display point ranges
                const pointRanges = parsePointRanges(value);
                const entryCount = pointRanges.entries ? pointRanges.entries.length : 0;
                if (entryCount > 0) {
                    displayValue = `<span class="point-ranges-badge" title="Click Edit to view details"><i class="fas fa-map-marker-alt"></i> ${entryCount} job file${entryCount > 1 ? 's' : ''}</span>`;
                } else {
                    displayValue = '<span style="color: var(--gray-400);">No data</span>';
                }
            }

            return `<td>${displayValue}</td>`;
        }

        function parsePointRanges(value) {
            if (!value) return { entries: [] };
            if (typeof value === 'object') return value;
            try {
                return JSON.parse(value);
            } catch (e) {
                return { entries: [] };
            }
        }

        function getStatusClass(status) {
            const statusMap = {
                'Active': 'status-active',
                'Completed': 'status-completed',
                'On Hold': 'status-on-hold',
                'In Progress': 'status-in-progress',
                'Not Started': 'status-not-started',
                'Cancelled': 'status-cancelled'
            };
            return statusMap[status] || 'status-active';
        }

        // Sorting
        function sortBy(column) {
            if (sortColumn === column) {
                sortDirection = sortDirection === 'asc' ? 'desc' : 'asc';
            } else {
                sortColumn = column;
                sortDirection = 'asc';
            }
            filterRecords();
        }

        // Selection
        function toggleSelectAll(checkbox) {
            const config = tableConfig[currentTable];
            if (checkbox.checked) {
                filteredRecords.forEach(record => {
                    selectedIds.add(record[config.primaryKey]);
                });
            } else {
                selectedIds.clear();
            }
            renderGrid();
            updateBulkActions();
        }

        function toggleSelect(id, checkbox) {
            if (checkbox.checked) {
                selectedIds.add(id);
            } else {
                selectedIds.delete(id);
            }
            renderGrid();
            updateBulkActions();
        }

        function updateBulkActions() {
            const bulkActions = document.getElementById('bulkActions');
            const selectedCount = document.getElementById('selectedCount');

            if (selectedIds.size > 0) {
                bulkActions.classList.remove('hidden');
                selectedCount.textContent = `${selectedIds.size} selected`;
            } else {
                bulkActions.classList.add('hidden');
            }
        }

        // Pagination
        function updatePagination() {
            const totalRecords = filteredRecords.length;
            const totalPages = Math.ceil(totalRecords / itemsPerPage);
            const startIndex = (currentPage - 1) * itemsPerPage + 1;
            const endIndex = Math.min(currentPage * itemsPerPage, totalRecords);

            document.getElementById('paginationInfo').textContent =
                totalRecords > 0
                    ? `Showing ${startIndex} to ${endIndex} of ${totalRecords} records`
                    : 'No records';

            let paginationHtml = `
                <button ${currentPage === 1 ? 'disabled' : ''} onclick="goToPage(${currentPage - 1})">
                    <i class="fas fa-chevron-left"></i>
                </button>
            `;

            const maxVisible = 5;
            let startPage = Math.max(1, currentPage - Math.floor(maxVisible / 2));
            let endPage = Math.min(totalPages, startPage + maxVisible - 1);

            if (endPage - startPage + 1 < maxVisible) {
                startPage = Math.max(1, endPage - maxVisible + 1);
            }

            for (let i = startPage; i <= endPage; i++) {
                paginationHtml += `
                    <button class="${i === currentPage ? 'active' : ''}" onclick="goToPage(${i})">${i}</button>
                `;
            }

            paginationHtml += `
                <button ${currentPage === totalPages || totalPages === 0 ? 'disabled' : ''} onclick="goToPage(${currentPage + 1})">
                    <i class="fas fa-chevron-right"></i>
                </button>
            `;

            document.getElementById('paginationControls').innerHTML = paginationHtml;
        }

        function goToPage(page) {
            currentPage = page;
            renderGrid();
        }

        // Column visibility
        function updateColumnsMenu() {
            const config = tableConfig[currentTable];
            const menuHtml = config.columns.map(col => `
                <label class="columns-menu-item">
                    <input type="checkbox" ${visibleColumns[currentTable][col.key] ? 'checked' : ''}
                           onchange="toggleColumn('${col.key}', this.checked)">
                    ${col.label}
                </label>
            `).join('');
            document.getElementById('columnsMenu').innerHTML = menuHtml;
        }

        function toggleColumnsMenu() {
            document.getElementById('columnsMenu').classList.toggle('show');
        }

        function toggleColumn(key, visible) {
            visibleColumns[currentTable][key] = visible;
            renderGrid();
        }

        // Close columns menu when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.columns-dropdown')) {
                document.getElementById('columnsMenu').classList.remove('show');
            }
        });

        // Edit record
        function editRecord(id) {
            const config = tableConfig[currentTable];
            const record = allRecords.find(r => r[config.primaryKey] == id);

            if (!record) {
                showToast('Record not found', 'error');
                return;
            }

            editingRecord = record;
            document.getElementById('editModalTitle').textContent = `Edit ${currentTable === 'survey_projects' ? 'Project' : 'Task'}`;

            // Build form fields
            const fieldsHtml = config.columns.map(col => {
                const value = record[col.key] || '';
                let inputHtml = '';

                if (col.type === 'select') {
                    inputHtml = `
                        <select class="form-select" name="${col.key}" ${!col.editable ? 'disabled' : ''}>
                            ${col.options.map(opt => `
                                <option value="${opt}" ${value === opt ? 'selected' : ''}>${opt}</option>
                            `).join('')}
                        </select>
                    `;
                } else if (col.type === 'textarea') {
                    inputHtml = `<textarea class="form-textarea" name="${col.key}" rows="3" ${!col.editable ? 'disabled' : ''}>${value}</textarea>`;
                } else if (col.type === 'date') {
                    const dateValue = value ? value.split('T')[0] : '';
                    inputHtml = `<input type="date" class="form-input" name="${col.key}" value="${dateValue}" ${!col.editable ? 'disabled' : ''}>`;
                } else if (col.type === 'number') {
                    inputHtml = `<input type="number" class="form-input" name="${col.key}" value="${value}" step="0.5" ${!col.editable ? 'disabled' : ''}>`;
                } else if (col.type === 'point_ranges') {
                    inputHtml = buildPointRangesEditor(value);
                } else {
                    inputHtml = `<input type="text" class="form-input" name="${col.key}" value="${escapeHtml(value)}" ${!col.editable ? 'disabled' : ''}>`;
                }

                const isFullWidth = col.type === 'textarea' || col.key.includes('link') || col.key.includes('folder') || col.type === 'point_ranges';

                return `
                    <div class="form-group ${isFullWidth ? 'full-width' : ''}">
                        <label class="form-label">${col.label}</label>
                        ${inputHtml}
                    </div>
                `;
            }).join('');

            document.getElementById('editFormFields').innerHTML = fieldsHtml;
            document.getElementById('editModal').classList.add('active');
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.remove('active');
            editingRecord = null;
        }

        // Save record
        async function saveRecord() {
            if (!editingRecord) return;

            const config = tableConfig[currentTable];
            const form = document.getElementById('editForm');
            const formData = new FormData(form);

            const data = {
                action: 'update',
                table: currentTable,
                id: editingRecord[config.primaryKey]
            };

            config.columns.forEach(col => {
                if (col.editable) {
                    if (col.type === 'point_ranges') {
                        // Collect point ranges data from the editor
                        const pointRanges = collectPointRangesData();
                        data[col.key] = pointRanges ? JSON.stringify(pointRanges) : null;
                    } else {
                        data[col.key] = formData.get(col.key) || null;
                    }
                }
            });

            try {
                const response = await fetch(API_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (result.success) {
                    showToast('Record updated successfully', 'success');
                    closeEditModal();
                    loadData();
                } else {
                    showToast(result.message || 'Error updating record', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('Network error', 'error');
            }
        }

        // Delete record
        async function deleteRecord(id) {
            if (!confirm('Are you sure you want to delete this record? This action cannot be undone.')) {
                return;
            }

            try {
                const response = await fetch(API_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'delete',
                        table: currentTable,
                        id: id
                    })
                });

                const result = await response.json();

                if (result.success) {
                    showToast('Record deleted successfully', 'success');
                    selectedIds.delete(id);
                    loadData();
                } else {
                    showToast(result.message || 'Error deleting record', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('Network error', 'error');
            }
        }

        // Bulk operations
        async function bulkDelete() {
            if (selectedIds.size === 0) return;

            if (!confirm(`Are you sure you want to delete ${selectedIds.size} record(s)? This action cannot be undone.`)) {
                return;
            }

            try {
                const response = await fetch(API_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'bulk_delete',
                        table: currentTable,
                        ids: Array.from(selectedIds)
                    })
                });

                const result = await response.json();

                if (result.success) {
                    showToast(`${selectedIds.size} record(s) deleted successfully`, 'success');
                    selectedIds.clear();
                    loadData();
                } else {
                    showToast(result.message || 'Error deleting records', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('Network error', 'error');
            }
        }

        function bulkUpdateStatus() {
            if (selectedIds.size === 0) return;
            document.getElementById('bulkStatusModal').classList.add('active');
        }

        function closeBulkStatusModal() {
            document.getElementById('bulkStatusModal').classList.remove('active');
        }

        async function applyBulkStatus() {
            const newStatus = document.getElementById('bulkStatusSelect').value;
            const statusKey = currentTable === 'survey_projects' ? 'project_status' : 'task_status';

            try {
                const response = await fetch(API_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'bulk_update',
                        table: currentTable,
                        ids: Array.from(selectedIds),
                        field: statusKey,
                        value: newStatus
                    })
                });

                const result = await response.json();

                if (result.success) {
                    showToast(`${selectedIds.size} record(s) updated successfully`, 'success');
                    closeBulkStatusModal();
                    selectedIds.clear();
                    loadData();
                } else {
                    showToast(result.message || 'Error updating records', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('Network error', 'error');
            }
        }

        // Add new record
        function openAddModal() {
            editingRecord = {};
            const config = tableConfig[currentTable];

            document.getElementById('editModalTitle').textContent = `Add New ${currentTable === 'survey_projects' ? 'Project' : 'Task'}`;

            // Build empty form
            const fieldsHtml = config.columns.map(col => {
                let inputHtml = '';

                if (col.type === 'select') {
                    inputHtml = `
                        <select class="form-select" name="${col.key}">
                            ${col.options.map(opt => `<option value="${opt}">${opt}</option>`).join('')}
                        </select>
                    `;
                } else if (col.type === 'textarea') {
                    inputHtml = `<textarea class="form-textarea" name="${col.key}" rows="3"></textarea>`;
                } else if (col.type === 'date') {
                    inputHtml = `<input type="date" class="form-input" name="${col.key}">`;
                } else if (col.type === 'number') {
                    inputHtml = `<input type="number" class="form-input" name="${col.key}" step="0.5">`;
                } else if (col.type === 'point_ranges') {
                    inputHtml = buildPointRangesEditor(null);
                } else {
                    const required = col.key === config.primaryKey || col.key.includes('name') ? 'required' : '';
                    inputHtml = `<input type="text" class="form-input" name="${col.key}" ${required}>`;
                }

                const isFullWidth = col.type === 'textarea' || col.key.includes('link') || col.key.includes('folder') || col.type === 'point_ranges';

                return `
                    <div class="form-group ${isFullWidth ? 'full-width' : ''}">
                        <label class="form-label">${col.label}</label>
                        ${inputHtml}
                    </div>
                `;
            }).join('');

            document.getElementById('editFormFields').innerHTML = fieldsHtml;
            document.getElementById('editModal').classList.add('active');

            // Override save button for add
            editingRecord = null; // Indicate this is a new record
        }

        // Modified saveRecord to handle both add and update
        const originalSaveRecord = saveRecord;
        saveRecord = async function() {
            const config = tableConfig[currentTable];
            const form = document.getElementById('editForm');
            const formData = new FormData(form);

            const isNewRecord = !editingRecord;

            const data = {
                action: isNewRecord ? 'add' : 'update',
                table: currentTable
            };

            if (!isNewRecord) {
                data.id = editingRecord[config.primaryKey];
            }

            config.columns.forEach(col => {
                if (isNewRecord || col.editable) {
                    if (col.type === 'point_ranges') {
                        // Collect point ranges data from the editor
                        const pointRanges = collectPointRangesData();
                        data[col.key] = pointRanges ? JSON.stringify(pointRanges) : null;
                    } else {
                        data[col.key] = formData.get(col.key) || null;
                    }
                }
            });

            try {
                const response = await fetch(API_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (result.success) {
                    showToast(isNewRecord ? 'Record added successfully' : 'Record updated successfully', 'success');
                    closeEditModal();
                    loadData();
                } else {
                    showToast(result.message || 'Error saving record', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('Network error', 'error');
            }
        };

        // Export data
        function exportData() {
            const config = tableConfig[currentTable];
            const visibleCols = config.columns.filter(col => visibleColumns[currentTable][col.key]);

            // Create CSV content
            const headers = visibleCols.map(col => col.label).join(',');
            const rows = filteredRecords.map(record => {
                return visibleCols.map(col => {
                    const value = record[col.key] || '';
                    // Escape quotes and wrap in quotes if contains comma
                    const escaped = String(value).replace(/"/g, '""');
                    return escaped.includes(',') ? `"${escaped}"` : escaped;
                }).join(',');
            }).join('\n');

            const csv = headers + '\n' + rows;
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = URL.createObjectURL(blob);

            const link = document.createElement('a');
            link.href = url;
            link.download = `${currentTable}_export_${new Date().toISOString().split('T')[0]}.csv`;
            link.click();

            URL.revokeObjectURL(url);
            showToast('Data exported successfully', 'success');
        }

        // Refresh data
        function refreshData() {
            loadData();
        }

        // Utility functions
        function showLoading(show) {
            document.getElementById('loadingOverlay').classList.toggle('hidden', !show);
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
            } else if (type === 'warning') {
                toastIcon.className = 'fas fa-exclamation-triangle toast-icon';
                toast.style.borderLeftColor = 'var(--warning-color)';
                toastIcon.style.color = 'var(--warning-color)';
            } else {
                toastIcon.className = 'fas fa-check-circle toast-icon';
                toast.style.borderLeftColor = 'var(--success-color)';
                toastIcon.style.color = 'var(--success-color)';
            }

            toast.classList.add('show');
            setTimeout(() => toast.classList.remove('show'), 3000);
        }

        // Point Ranges Editor Functions
        let pointRangesData = { entries: [] };

        function buildPointRangesEditor(value) {
            pointRangesData = parsePointRanges(value);

            let entriesHtml = '';
            if (pointRangesData.entries && pointRangesData.entries.length > 0) {
                entriesHtml = pointRangesData.entries.map((entry, index) => buildPointRangeEntryHtml(entry, index)).join('');
            }

            return `
                <div class="point-ranges-container" id="pointRangesContainer">
                    <div id="pointRangesEntries">
                        ${entriesHtml || '<p style="color: var(--gray-500); text-align: center; padding: 1rem;">No point range entries yet. Click the button below to add one.</p>'}
                    </div>
                    <button type="button" class="add-point-range-btn" onclick="addPointRangeEntry()">
                        <i class="fas fa-plus"></i> Add Job File Entry
                    </button>
                </div>
            `;
        }

        function buildPointRangeEntryHtml(entry = {}, index) {
            return `
                <div class="point-range-entry" data-index="${index}">
                    <div class="point-range-header">
                        <span><i class="fas fa-file-alt"></i> Job File #${index + 1}</span>
                        <button type="button" class="remove-entry-btn" onclick="removePointRangeEntry(${index})">
                            <i class="fas fa-trash"></i> Remove
                        </button>
                    </div>
                    <div class="point-range-grid">
                        <div class="form-group">
                            <label>Job File Name</label>
                            <input type="text" class="form-input pr-job-file" value="${escapeHtml(entry.job_file_name || '')}" placeholder="e.g., 01232026JM">
                        </div>
                        <div class="form-group">
                            <label>Point Numbers Used</label>
                            <input type="text" class="form-input pr-points-used" value="${escapeHtml(entry.point_number_used || '')}" placeholder="e.g., 1-10, 100-378, 5489-1000">
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
                </div>
            `;
        }

        function addPointRangeEntry() {
            const container = document.getElementById('pointRangesEntries');
            const entries = container.querySelectorAll('.point-range-entry');
            const newIndex = entries.length;

            // Remove "no entries" message if present
            const noEntriesMsg = container.querySelector('p');
            if (noEntriesMsg) {
                noEntriesMsg.remove();
            }

            const newEntryHtml = buildPointRangeEntryHtml({}, newIndex);
            container.insertAdjacentHTML('beforeend', newEntryHtml);
        }

        function removePointRangeEntry(index) {
            const container = document.getElementById('pointRangesEntries');
            const entry = container.querySelector(`.point-range-entry[data-index="${index}"]`);
            if (entry) {
                entry.remove();

                // Reindex remaining entries
                const entries = container.querySelectorAll('.point-range-entry');
                entries.forEach((e, i) => {
                    e.dataset.index = i;
                    e.querySelector('.point-range-header span').innerHTML = `<i class="fas fa-file-alt"></i> Job File #${i + 1}`;
                    e.querySelector('.remove-entry-btn').setAttribute('onclick', `removePointRangeEntry(${i})`);
                });

                // Show "no entries" message if empty
                if (entries.length === 0) {
                    container.innerHTML = '<p style="color: var(--gray-500); text-align: center; padding: 1rem;">No point range entries yet. Click the button below to add one.</p>';
                }
            }
        }

        function collectPointRangesData() {
            const container = document.getElementById('pointRangesEntries');
            if (!container) return null;

            const entries = container.querySelectorAll('.point-range-entry');
            if (entries.length === 0) return null;

            const data = {
                entries: Array.from(entries).map(entry => ({
                    job_file_name: entry.querySelector('.pr-job-file')?.value || '',
                    point_number_used: entry.querySelector('.pr-points-used')?.value || '',
                    converted: entry.querySelector('.pr-converted')?.checked ? 'yes' : 'no',
                    imported: entry.querySelector('.pr-imported')?.checked ? 'yes' : 'no',
                    checked: entry.querySelector('.pr-checked')?.checked ? 'yes' : 'no',
                    notes: entry.querySelector('.pr-notes')?.value || ''
                }))
            };

            return data;
        }

        function escapeHtml(text) {
            if (text === null || text === undefined) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('open');
        }

        // Close modals on outside click
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('quick-edit-modal')) {
                e.target.classList.remove('active');
            }
        });
    </script>
</body>
</html>
