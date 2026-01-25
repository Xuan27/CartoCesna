<?php
session_start();
header('Content-Type: application/json');

// Determine file system path for includes
$base_path = dirname(__DIR__, 3); // Go up 3 levels: 00_002 -> Professional -> Projects -> CartoCesna

// Include Auth class to get user role
require_once $base_path . '/classes/Auth.php';
require_once $base_path . '/Private/db_config.php';

// Check if user is logged in and is admin for write operations
$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'];
$userRole = '';
if ($isLoggedIn && isset($_SESSION['user_id'])) {
    $db = new Database();
    $auth = new Auth($db->getConnection());
    $userRole = $auth->getUserRole($_SESSION['user_id']);
}
$isAdmin = $isLoggedIn && $userRole === 'admin';

// Data file path
$dataFile = __DIR__ . '/data.json';

// Load data
function loadData($dataFile) {
    if (!file_exists($dataFile)) {
        return ['sections' => []];
    }
    $content = file_get_contents($dataFile);
    return json_decode($content, true) ?: ['sections' => []];
}

// Save data
function saveData($dataFile, $data) {
    return file_put_contents($dataFile, json_encode($data, JSON_PRETTY_PRINT));
}

// Generate unique ID
function generateId() {
    return uniqid() . '_' . bin2hex(random_bytes(4));
}

// Get action
$action = $_GET['action'] ?? null;

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? null;

    // Check authentication for write operations - admin only
    if (!$isAdmin) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized. Admin access required.']);
        exit;
    }

    $data = loadData($dataFile);

    switch ($action) {
        case 'add_section':
            $name = trim($input['name'] ?? '');
            if (empty($name)) {
                echo json_encode(['success' => false, 'message' => 'Section name is required']);
                exit;
            }

            $newSection = [
                'id' => generateId(),
                'name' => $name,
                'items' => []
            ];
            $data['sections'][] = $newSection;
            saveData($dataFile, $data);
            echo json_encode(['success' => true, 'section' => $newSection]);
            break;

        case 'update_section':
            $sectionId = $input['section_id'] ?? null;
            $name = trim($input['name'] ?? '');

            if (empty($sectionId) || empty($name)) {
                echo json_encode(['success' => false, 'message' => 'Section ID and name are required']);
                exit;
            }

            $found = false;
            foreach ($data['sections'] as &$section) {
                if ($section['id'] === $sectionId) {
                    $section['name'] = $name;
                    $found = true;
                    break;
                }
            }

            if ($found) {
                saveData($dataFile, $data);
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Section not found']);
            }
            break;

        case 'delete_section':
            $sectionId = $input['section_id'] ?? null;

            if (empty($sectionId)) {
                echo json_encode(['success' => false, 'message' => 'Section ID is required']);
                exit;
            }

            $data['sections'] = array_values(array_filter($data['sections'], function($s) use ($sectionId) {
                return $s['id'] !== $sectionId;
            }));
            saveData($dataFile, $data);
            echo json_encode(['success' => true]);
            break;

        case 'add_item':
            $sectionId = $input['section_id'] ?? null;
            $title = trim($input['title'] ?? '');
            $url = trim($input['url'] ?? '');
            $type = $input['type'] ?? 'reference';
            $notes = trim($input['notes'] ?? '');
            $bookReference = trim($input['book_reference'] ?? '');

            if (empty($sectionId) || empty($title)) {
                echo json_encode(['success' => false, 'message' => 'Section ID and title are required']);
                exit;
            }

            $found = false;
            foreach ($data['sections'] as &$section) {
                if ($section['id'] === $sectionId) {
                    $newItem = [
                        'id' => generateId(),
                        'title' => $title,
                        'url' => $url ?: null,
                        'type' => $type,
                        'notes' => $notes ?: null,
                        'book_reference' => $bookReference ?: null
                    ];
                    $section['items'][] = $newItem;
                    $found = true;
                    break;
                }
            }

            if ($found) {
                saveData($dataFile, $data);
                echo json_encode(['success' => true, 'item' => $newItem]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Section not found']);
            }
            break;

        case 'update_item':
            $sectionId = $input['section_id'] ?? null;
            $itemId = $input['item_id'] ?? null;
            $title = trim($input['title'] ?? '');
            $url = trim($input['url'] ?? '');
            $type = $input['type'] ?? 'reference';
            $notes = trim($input['notes'] ?? '');
            $bookReference = trim($input['book_reference'] ?? '');

            if (empty($sectionId) || empty($itemId) || empty($title)) {
                echo json_encode(['success' => false, 'message' => 'Section ID, item ID, and title are required']);
                exit;
            }

            $found = false;
            foreach ($data['sections'] as &$section) {
                if ($section['id'] === $sectionId) {
                    foreach ($section['items'] as &$item) {
                        if ($item['id'] === $itemId) {
                            $item['title'] = $title;
                            $item['url'] = $url ?: null;
                            $item['type'] = $type;
                            $item['notes'] = $notes ?: null;
                            $item['book_reference'] = $bookReference ?: null;
                            $found = true;
                            break 2;
                        }
                    }
                }
            }

            if ($found) {
                saveData($dataFile, $data);
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Item not found']);
            }
            break;

        case 'delete_item':
            $sectionId = $input['section_id'] ?? null;
            $itemId = $input['item_id'] ?? null;

            if (empty($sectionId) || empty($itemId)) {
                echo json_encode(['success' => false, 'message' => 'Section ID and item ID are required']);
                exit;
            }

            $found = false;
            foreach ($data['sections'] as &$section) {
                if ($section['id'] === $sectionId) {
                    $section['items'] = array_values(array_filter($section['items'], function($i) use ($itemId) {
                        return $i['id'] !== $itemId;
                    }));
                    $found = true;
                    break;
                }
            }

            if ($found) {
                saveData($dataFile, $data);
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Section not found']);
            }
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    exit;
}

// Handle GET requests
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'get') {
    $data = loadData($dataFile);
    echo json_encode(['success' => true, 'data' => $data]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid request']);
