<?php
header('Content-Type: application/json');
require_once '../../Private/db_config.php';
$db = new Database();
$conn = $db->getConnection();

try {
    // Support both GET and POST
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $action = $_GET['action'] ?? '';
    } else {
        // Try JSON body first, then form POST
        $input = json_decode(file_get_contents('php://input'), true);
        if ($input) {
            $action = $input['action'] ?? '';
        } else {
            $input = $_POST;
            $action = $_POST['action'] ?? '';
        }
    }

    // ========== TEMPLATE CRUD ==========

    if ($action === 'get_templates') {
        $stmt = $conn->query("
            SELECT ct.*,
                   GROUP_CONCAT(ctt.task_type ORDER BY ctt.task_type) as task_types,
                   (SELECT COUNT(*) FROM checklist_template_items WHERE template_id = ct.template_id AND parent_item_id IS NULL) as item_count
            FROM checklist_templates ct
            LEFT JOIN checklist_template_types ctt ON ct.template_id = ctt.template_id
            GROUP BY ct.template_id
            ORDER BY ct.created_date DESC
        ");
        $templates = $stmt->fetchAll();
        foreach ($templates as &$t) {
            $t['task_types'] = $t['task_types'] ? explode(',', $t['task_types']) : [];
        }
        echo json_encode(['success' => true, 'templates' => $templates]);

    } elseif ($action === 'get_template') {
        $templateId = $_GET['template_id'] ?? ($input['template_id'] ?? null);
        if (!$templateId) {
            echo json_encode(['success' => false, 'message' => 'Template ID required']);
            exit;
        }
        $stmt = $conn->prepare("SELECT * FROM checklist_templates WHERE template_id = :id");
        $stmt->execute(['id' => $templateId]);
        $template = $stmt->fetch();
        if (!$template) {
            echo json_encode(['success' => false, 'message' => 'Template not found']);
            exit;
        }
        // Get linked task types
        $stmt2 = $conn->prepare("SELECT task_type FROM checklist_template_types WHERE template_id = :id");
        $stmt2->execute(['id' => $templateId]);
        $template['task_types'] = $stmt2->fetchAll(PDO::FETCH_COLUMN);
        // Get items — roots first, then children grouped by parent
        $stmt3 = $conn->prepare("
            SELECT * FROM checklist_template_items
            WHERE template_id = :id
            ORDER BY CASE WHEN parent_item_id IS NULL THEN 0 ELSE 1 END, COALESCE(parent_item_id, 0), sort_order, item_id
        ");
        $stmt3->execute(['id' => $templateId]);
        $template['items'] = $stmt3->fetchAll();

        echo json_encode(['success' => true, 'template' => $template]);

    } elseif ($action === 'add_template') {
        $name = $input['template_name'] ?? '';
        $description = $input['description'] ?? '';
        $createdBy = $input['created_by'] ?? '';
        $taskTypes = $input['task_types'] ?? [];
        $items = $input['items'] ?? [];

        if (empty($name)) {
            echo json_encode(['success' => false, 'message' => 'Template name is required']);
            exit;
        }

        $conn->beginTransaction();

        $stmt = $conn->prepare("INSERT INTO checklist_templates (template_name, description, created_by) VALUES (:name, :desc, :created_by)");
        $stmt->execute(['name' => $name, 'desc' => $description, 'created_by' => $createdBy]);
        $templateId = $conn->lastInsertId();

        // Insert task types
        if (!empty($taskTypes)) {
            $stmt2 = $conn->prepare("INSERT INTO checklist_template_types (template_id, task_type) VALUES (:tid, :ttype)");
            foreach ($taskTypes as $type) {
                $stmt2->execute(['tid' => $templateId, 'ttype' => $type]);
            }
        }

        // Two-pass insert: roots first, then children (need parent IDs resolved)
        if (!empty($items)) {
            $tempIdMap = []; // temp_id => real item_id

            $stmtInsert = $conn->prepare("
                INSERT INTO checklist_template_items (template_id, item_text, item_type, parent_item_id, branch, category, sort_order)
                VALUES (:tid, :text, :type, :pid, :branch, :cat, :sort)
            ");

            // Pass 1: root items (no parent_temp_id and no parent_item_id)
            foreach ($items as $i => $item) {
                if (!empty($item['parent_temp_id']) || !empty($item['parent_item_id'])) continue;
                $stmtInsert->execute([
                    'tid'    => $templateId,
                    'text'   => $item['item_text'] ?? '',
                    'type'   => $item['item_type'] ?? 'standard',
                    'pid'    => null,
                    'branch' => null,
                    'cat'    => $item['category'] ?? null,
                    'sort'   => $item['sort_order'] ?? $i
                ]);
                $newId = $conn->lastInsertId();
                if (!empty($item['temp_id'])) $tempIdMap[$item['temp_id']] = $newId;
            }

            // Pass 2: child items
            foreach ($items as $i => $item) {
                if (empty($item['parent_temp_id']) && empty($item['parent_item_id'])) continue;
                $parentId = $item['parent_item_id'] ?? ($tempIdMap[$item['parent_temp_id'] ?? ''] ?? null);
                if (!$parentId) continue;
                $stmtInsert->execute([
                    'tid'    => $templateId,
                    'text'   => $item['item_text'] ?? '',
                    'type'   => 'standard',
                    'pid'    => $parentId,
                    'branch' => $item['branch'] ?? null,
                    'cat'    => $item['category'] ?? null,
                    'sort'   => $item['sort_order'] ?? $i
                ]);
                $newId = $conn->lastInsertId();
                if (!empty($item['temp_id'])) $tempIdMap[$item['temp_id']] = $newId;
            }
        }

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Template created', 'template_id' => $templateId]);

    } elseif ($action === 'update_template') {
        $templateId = $input['template_id'] ?? null;
        $name = $input['template_name'] ?? '';
        $description = $input['description'] ?? '';
        $taskTypes = $input['task_types'] ?? [];
        $items = $input['items'] ?? [];

        if (!$templateId || empty($name)) {
            echo json_encode(['success' => false, 'message' => 'Template ID and name are required']);
            exit;
        }

        $conn->beginTransaction();

        $stmt = $conn->prepare("UPDATE checklist_templates SET template_name = :name, description = :desc WHERE template_id = :id");
        $stmt->execute(['name' => $name, 'desc' => $description, 'id' => $templateId]);

        // Replace task types
        $conn->prepare("DELETE FROM checklist_template_types WHERE template_id = :id")->execute(['id' => $templateId]);
        if (!empty($taskTypes)) {
            $stmt2 = $conn->prepare("INSERT INTO checklist_template_types (template_id, task_type) VALUES (:tid, :ttype)");
            foreach ($taskTypes as $type) {
                $stmt2->execute(['tid' => $templateId, 'ttype' => $type]);
            }
        }

        // Detect and delete removed items
        $existingIds = $conn->prepare("SELECT item_id FROM checklist_template_items WHERE template_id = :id");
        $existingIds->execute(['id' => $templateId]);
        $oldIds = $existingIds->fetchAll(PDO::FETCH_COLUMN);
        $newIds = array_filter(array_map(fn($item) => $item['item_id'] ?? null, $items));
        $removedIds = array_diff($oldIds, $newIds);
        if (!empty($removedIds)) {
            $placeholders = implode(',', array_fill(0, count($removedIds), '?'));
            $conn->prepare("DELETE FROM checklist_template_items WHERE item_id IN ($placeholders)")->execute(array_values($removedIds));
        }

        $tempIdMap = [];

        $stmtUpdate = $conn->prepare("
            UPDATE checklist_template_items
            SET item_text = :text, item_type = :type, category = :cat, sort_order = :sort
            WHERE item_id = :id AND template_id = :tid
        ");
        $stmtInsert = $conn->prepare("
            INSERT INTO checklist_template_items (template_id, item_text, item_type, parent_item_id, branch, category, sort_order)
            VALUES (:tid, :text, :type, :pid, :branch, :cat, :sort)
        ");

        // Pass 1: root items
        foreach ($items as $i => $item) {
            if (!empty($item['parent_temp_id']) || !empty($item['parent_item_id'])) continue;
            if (!empty($item['item_id'])) {
                $stmtUpdate->execute([
                    'text' => $item['item_text'] ?? '',
                    'type' => $item['item_type'] ?? 'standard',
                    'cat'  => $item['category'] ?? null,
                    'sort' => $item['sort_order'] ?? $i,
                    'id'   => $item['item_id'],
                    'tid'  => $templateId
                ]);
                if (!empty($item['temp_id'])) $tempIdMap[$item['temp_id']] = $item['item_id'];
            } else {
                $stmtInsert->execute([
                    'tid'    => $templateId,
                    'text'   => $item['item_text'] ?? '',
                    'type'   => $item['item_type'] ?? 'standard',
                    'pid'    => null,
                    'branch' => null,
                    'cat'    => $item['category'] ?? null,
                    'sort'   => $item['sort_order'] ?? $i
                ]);
                $newId = $conn->lastInsertId();
                if (!empty($item['temp_id'])) $tempIdMap[$item['temp_id']] = $newId;
            }
        }

        // Pass 2: child items
        $stmtUpdateChild = $conn->prepare("
            UPDATE checklist_template_items
            SET item_text = :text, item_type = 'standard', parent_item_id = :pid, branch = :branch, category = :cat, sort_order = :sort
            WHERE item_id = :id AND template_id = :tid
        ");
        foreach ($items as $i => $item) {
            if (empty($item['parent_temp_id']) && empty($item['parent_item_id'])) continue;
            $parentId = $item['parent_item_id'] ?? ($tempIdMap[$item['parent_temp_id'] ?? ''] ?? null);
            if (!$parentId) continue;
            if (!empty($item['item_id'])) {
                $stmtUpdateChild->execute([
                    'text'   => $item['item_text'] ?? '',
                    'pid'    => $parentId,
                    'branch' => $item['branch'] ?? null,
                    'cat'    => $item['category'] ?? null,
                    'sort'   => $item['sort_order'] ?? $i,
                    'id'     => $item['item_id'],
                    'tid'    => $templateId
                ]);
            } else {
                $stmtInsert->execute([
                    'tid'    => $templateId,
                    'text'   => $item['item_text'] ?? '',
                    'type'   => 'standard',
                    'pid'    => $parentId,
                    'branch' => $item['branch'] ?? null,
                    'cat'    => $item['category'] ?? null,
                    'sort'   => $item['sort_order'] ?? $i
                ]);
            }
        }

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Template updated']);

    } elseif ($action === 'delete_template') {
        $templateId = $input['template_id'] ?? null;
        if (!$templateId) {
            echo json_encode(['success' => false, 'message' => 'Template ID required']);
            exit;
        }
        $stmt = $conn->prepare("DELETE FROM checklist_templates WHERE template_id = :id");
        $stmt->execute(['id' => $templateId]);
        echo json_encode(['success' => true, 'message' => 'Template deleted']);

    // ========== ITEM CRUD ==========

    } elseif ($action === 'get_template_items') {
        $templateId = $_GET['template_id'] ?? ($input['template_id'] ?? null);
        if (!$templateId) {
            echo json_encode(['success' => false, 'message' => 'Template ID required']);
            exit;
        }
        $stmt = $conn->prepare("SELECT * FROM checklist_template_items WHERE template_id = :id ORDER BY sort_order, item_id");
        $stmt->execute(['id' => $templateId]);
        echo json_encode(['success' => true, 'items' => $stmt->fetchAll()]);

    } elseif ($action === 'add_template_item') {
        $templateId = $input['template_id'] ?? null;
        $itemText = $input['item_text'] ?? '';
        $category = $input['category'] ?? null;
        $sortOrder = $input['sort_order'] ?? 0;

        if (!$templateId || empty($itemText)) {
            echo json_encode(['success' => false, 'message' => 'Template ID and item text required']);
            exit;
        }

        $stmt = $conn->prepare("INSERT INTO checklist_template_items (template_id, item_text, category, sort_order) VALUES (:tid, :text, :cat, :sort)");
        $stmt->execute(['tid' => $templateId, 'text' => $itemText, 'cat' => $category, 'sort' => $sortOrder]);
        echo json_encode(['success' => true, 'message' => 'Item added', 'item_id' => $conn->lastInsertId()]);

    } elseif ($action === 'update_template_item') {
        $itemId = $input['item_id'] ?? null;
        $itemText = $input['item_text'] ?? '';
        $category = $input['category'] ?? null;

        if (!$itemId || empty($itemText)) {
            echo json_encode(['success' => false, 'message' => 'Item ID and text required']);
            exit;
        }

        $stmt = $conn->prepare("UPDATE checklist_template_items SET item_text = :text, category = :cat WHERE item_id = :id");
        $stmt->execute(['text' => $itemText, 'cat' => $category, 'id' => $itemId]);
        echo json_encode(['success' => true, 'message' => 'Item updated']);

    } elseif ($action === 'delete_template_item') {
        $itemId = $input['item_id'] ?? null;
        if (!$itemId) {
            echo json_encode(['success' => false, 'message' => 'Item ID required']);
            exit;
        }
        $stmt = $conn->prepare("DELETE FROM checklist_template_items WHERE item_id = :id");
        $stmt->execute(['id' => $itemId]);
        echo json_encode(['success' => true, 'message' => 'Item deleted']);

    } elseif ($action === 'reorder_items') {
        $items = $input['items'] ?? [];
        if (empty($items)) {
            echo json_encode(['success' => false, 'message' => 'Items array required']);
            exit;
        }
        $stmt = $conn->prepare("UPDATE checklist_template_items SET sort_order = :sort WHERE item_id = :id");
        foreach ($items as $item) {
            $stmt->execute(['sort' => $item['sort_order'], 'id' => $item['item_id']]);
        }
        echo json_encode(['success' => true, 'message' => 'Items reordered']);

    // ========== TASK CHECKLIST ==========

    } elseif ($action === 'get_task_checklist') {
        $taskId = $_GET['task_id'] ?? ($input['task_id'] ?? null);
        if (!$taskId) {
            echo json_encode(['success' => false, 'message' => 'Task ID required']);
            exit;
        }

        // Check if progress records exist for this task
        $stmt = $conn->prepare("SELECT COUNT(*) FROM task_checklist_progress WHERE task_id = :tid");
        $stmt->execute(['tid' => $taskId]);
        $hasProgress = $stmt->fetchColumn() > 0;

        if (!$hasProgress) {
            // Get task type to find matching template
            $stmt2 = $conn->prepare("SELECT task_type FROM tasks WHERE task_id = :tid");
            $stmt2->execute(['tid' => $taskId]);
            $taskType = $stmt2->fetchColumn();

            if (!$taskType) {
                echo json_encode(['success' => false, 'message' => 'Task not found']);
                exit;
            }

            // Find templates for this task type
            $stmt3 = $conn->prepare("
                SELECT ct.template_id, ct.template_name
                FROM checklist_templates ct
                JOIN checklist_template_types ctt ON ct.template_id = ctt.template_id
                WHERE ctt.task_type = :ttype AND ct.is_active = 1
            ");
            $stmt3->execute(['ttype' => $taskType]);
            $templates = $stmt3->fetchAll();

            if (count($templates) === 0) {
                echo json_encode(['success' => true, 'checklist' => null, 'message' => 'No template for this task type']);
                exit;
            }

            if (count($templates) > 1) {
                echo json_encode(['success' => true, 'checklist' => null, 'pick_template' => true, 'templates' => $templates]);
                exit;
            }

            $templateId = $templates[0]['template_id'];
            initializeProgress($conn, $taskId, $templateId);
        }

        // Fetch checklist with progress — include all new fields
        $stmt4 = $conn->prepare("
            SELECT tcp.progress_id, tcp.item_id, tcp.is_completed, tcp.item_status,
                   tcp.completed_by, tcp.completed_date, tcp.notes,
                   cti.item_text, cti.item_type, cti.parent_item_id, cti.branch,
                   cti.category, cti.sort_order,
                   ct.template_id, ct.template_name
            FROM task_checklist_progress tcp
            JOIN checklist_template_items cti ON tcp.item_id = cti.item_id
            JOIN checklist_templates ct ON tcp.template_id = ct.template_id
            WHERE tcp.task_id = :tid
            ORDER BY CASE WHEN cti.parent_item_id IS NULL THEN 0 ELSE 1 END,
                     COALESCE(cti.parent_item_id, 0), cti.sort_order, cti.item_id
        ");
        $stmt4->execute(['tid' => $taskId]);
        $items = $stmt4->fetchAll();

        // Rough counts (client recomputes accurate totals for conditional visibility)
        $total = count(array_filter($items, fn($i) => $i['parent_item_id'] === null));
        $completed = count(array_filter($items, fn($i) => $i['parent_item_id'] === null && in_array($i['item_status'], ['completed','yes','no','na'])));

        $checklist = [
            'template_id'   => $items[0]['template_id'] ?? null,
            'template_name' => $items[0]['template_name'] ?? '',
            'total'         => $total,
            'completed'     => $completed,
            'items'         => $items
        ];

        echo json_encode(['success' => true, 'checklist' => $checklist]);

    } elseif ($action === 'get_templates_for_task') {
        $taskId = $_GET['task_id'] ?? ($input['task_id'] ?? null);
        if (!$taskId) {
            echo json_encode(['success' => false, 'message' => 'Task ID required']);
            exit;
        }
        $stmt = $conn->prepare("SELECT task_type FROM tasks WHERE task_id = :tid");
        $stmt->execute(['tid' => $taskId]);
        $taskType = $stmt->fetchColumn();

        $stmt2 = $conn->prepare("
            SELECT ct.template_id, ct.template_name, ct.description
            FROM checklist_templates ct
            JOIN checklist_template_types ctt ON ct.template_id = ctt.template_id
            WHERE ctt.task_type = :ttype AND ct.is_active = 1
        ");
        $stmt2->execute(['ttype' => $taskType]);
        echo json_encode(['success' => true, 'templates' => $stmt2->fetchAll()]);

    } elseif ($action === 'assign_template') {
        $taskId = $input['task_id'] ?? null;
        $templateId = $input['template_id'] ?? null;

        if (!$taskId || !$templateId) {
            echo json_encode(['success' => false, 'message' => 'Task ID and Template ID required']);
            exit;
        }

        $conn->prepare("DELETE FROM task_checklist_progress WHERE task_id = :tid")->execute(['tid' => $taskId]);
        initializeProgress($conn, $taskId, $templateId);
        echo json_encode(['success' => true, 'message' => 'Template assigned']);

    } elseif ($action === 'set_item_status') {
        // New action: set a specific status ('unchecked','completed','yes','no','na')
        $taskId  = $input['task_id'] ?? null;
        $itemId  = $input['item_id'] ?? null;
        $status  = $input['status'] ?? 'unchecked';
        $validStatuses = ['unchecked', 'completed', 'yes', 'no', 'na'];

        if (!$taskId || !$itemId || !in_array($status, $validStatuses)) {
            echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
            exit;
        }

        $isCompleted = in_array($status, ['completed', 'yes', 'no', 'na']) ? 1 : 0;

        $stmt = $conn->prepare("
            UPDATE task_checklist_progress
            SET item_status  = :status,
                is_completed = :ic,
                completed_by   = CASE WHEN :ic2 = 1 THEN 'User' ELSE NULL END,
                completed_date = CASE WHEN :ic3 = 1 THEN NOW() ELSE NULL END
            WHERE task_id = :tid AND item_id = :iid
        ");
        $stmt->execute([
            'status' => $status, 'ic' => $isCompleted,
            'ic2' => $isCompleted, 'ic3' => $isCompleted,
            'tid' => $taskId, 'iid' => $itemId
        ]);

        echo json_encode(['success' => true, 'status' => $status, 'is_completed' => $isCompleted]);

    } elseif ($action === 'toggle_item') {
        // Legacy toggle action — kept for backward compatibility
        $taskId = $input['task_id'] ?? null;
        $itemId = $input['item_id'] ?? null;
        $completedBy = $input['completed_by'] ?? 'User';

        if (!$taskId || !$itemId) {
            echo json_encode(['success' => false, 'message' => 'Task ID and Item ID required']);
            exit;
        }

        $stmt = $conn->prepare("SELECT is_completed FROM task_checklist_progress WHERE task_id = :tid AND item_id = :iid");
        $stmt->execute(['tid' => $taskId, 'iid' => $itemId]);
        $current = $stmt->fetchColumn();

        if ($current === false) {
            echo json_encode(['success' => false, 'message' => 'Progress record not found']);
            exit;
        }

        $newState  = $current ? 0 : 1;
        $newStatus = $newState ? 'completed' : 'unchecked';
        $stmt2 = $conn->prepare("
            UPDATE task_checklist_progress
            SET is_completed  = :state,
                item_status   = :status,
                completed_by  = CASE WHEN :state2 = 1 THEN :by ELSE NULL END,
                completed_date = CASE WHEN :state3 = 1 THEN NOW() ELSE NULL END
            WHERE task_id = :tid AND item_id = :iid
        ");
        $stmt2->execute([
            'state' => $newState, 'status' => $newStatus,
            'state2' => $newState, 'by' => $completedBy,
            'state3' => $newState,
            'tid' => $taskId, 'iid' => $itemId
        ]);

        $stmt3 = $conn->prepare("SELECT COUNT(*) as total, SUM(is_completed) as completed FROM task_checklist_progress WHERE task_id = :tid");
        $stmt3->execute(['tid' => $taskId]);
        $counts = $stmt3->fetch();

        echo json_encode([
            'success' => true,
            'is_completed' => $newState,
            'total' => (int)$counts['total'],
            'completed' => (int)$counts['completed']
        ]);

    } elseif ($action === 'get_checklist_summaries') {
        $taskIds = $_GET['task_ids'] ?? ($input['task_ids'] ?? '');
        if (is_string($taskIds)) {
            $taskIds = array_filter(explode(',', $taskIds));
        }

        if (empty($taskIds)) {
            echo json_encode(['success' => true, 'summaries' => []]);
            exit;
        }

        $placeholders = implode(',', array_fill(0, count($taskIds), '?'));
        $stmt = $conn->prepare("
            SELECT tcp.task_id,
                   COUNT(*) as total,
                   SUM(tcp.is_completed) as completed,
                   tcp.template_id
            FROM task_checklist_progress tcp
            JOIN checklist_template_items cti ON tcp.item_id = cti.item_id
            WHERE tcp.task_id IN ($placeholders) AND cti.parent_item_id IS NULL
            GROUP BY tcp.task_id, tcp.template_id
        ");
        $stmt->execute(array_values($taskIds));
        $rows = $stmt->fetchAll();

        $summaries = [];
        foreach ($rows as $row) {
            $summaries[$row['task_id']] = [
                'total' => (int)$row['total'],
                'completed' => (int)$row['completed'],
                'template_id' => (int)$row['template_id']
            ];
        }

        // For tasks without progress, check if templates exist for their types
        $missingIds = array_diff($taskIds, array_keys($summaries));
        if (!empty($missingIds)) {
            $placeholders2 = implode(',', array_fill(0, count($missingIds), '?'));
            $stmt2 = $conn->prepare("
                SELECT t.task_id, t.task_type, COUNT(DISTINCT ct.template_id) as template_count
                FROM tasks t
                LEFT JOIN checklist_template_types ctt ON t.task_type = ctt.task_type
                LEFT JOIN checklist_templates ct ON ctt.template_id = ct.template_id AND ct.is_active = 1
                WHERE t.task_id IN ($placeholders2)
                GROUP BY t.task_id, t.task_type
            ");
            $stmt2->execute(array_values($missingIds));
            $missingRows = $stmt2->fetchAll();
            foreach ($missingRows as $row) {
                $summaries[$row['task_id']] = [
                    'total' => 0,
                    'completed' => 0,
                    'template_count' => (int)$row['template_count'],
                    'has_template' => (int)$row['template_count'] > 0
                ];
            }
        }

        echo json_encode(['success' => true, 'summaries' => $summaries]);

    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action: ' . $action]);
    }

} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

// Helper: Initialize progress records from all template items (roots + children)
function initializeProgress($conn, $taskId, $templateId) {
    $stmt = $conn->prepare("SELECT item_id FROM checklist_template_items WHERE template_id = :tid ORDER BY sort_order, item_id");
    $stmt->execute(['tid' => $templateId]);
    $items = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $insert = $conn->prepare("INSERT IGNORE INTO task_checklist_progress (task_id, template_id, item_id, item_status) VALUES (:task, :tmpl, :item, 'unchecked')");
    foreach ($items as $itemId) {
        $insert->execute(['task' => $taskId, 'tmpl' => $templateId, 'item' => $itemId]);
    }
}
