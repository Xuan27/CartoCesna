<?php
session_start();
header('Content-Type: application/json');

$base_path = dirname(__DIR__, 3);

require_once $base_path . '/classes/Auth.php';
require_once $base_path . '/Private/db_config.php';

$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'];
$userRole = '';
if ($isLoggedIn && isset($_SESSION['user_id'])) {
    $db = new Database();
    $auth = new Auth($db->getConnection());
    $userRole = $auth->getUserRole($_SESSION['user_id']);
}
$isAdmin = $isLoggedIn && $userRole === 'admin';

$dataFile = __DIR__ . '/data.json';

function loadData($dataFile) {
    if (!file_exists($dataFile)) {
        return ['cases' => []];
    }
    $content = file_get_contents($dataFile);
    return json_decode($content, true) ?: ['cases' => []];
}

function saveData($dataFile, $data) {
    return file_put_contents($dataFile, json_encode($data, JSON_PRETTY_PRINT));
}

$action = $_GET['action'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'get') {
    echo json_encode(['success' => true, 'data' => loadData($dataFile), 'isAdmin' => $isAdmin]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? null;

    if (!$isAdmin) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized. Admin access required.']);
        exit;
    }

    $data = loadData($dataFile);

    switch ($action) {
        case 'add_case':
            $required = ['name', 'topic', 'facts', 'issue', 'decision', 'rule'];
            foreach ($required as $field) {
                if (empty(trim($input[$field] ?? ''))) {
                    echo json_encode(['success' => false, 'message' => "Field '$field' is required."]);
                    exit;
                }
            }
            $newCase = [
                'id'       => 'c_' . uniqid() . '_' . bin2hex(random_bytes(3)),
                'name'     => trim($input['name']),
                'topic'    => trim($input['topic']),
                'facts'    => trim($input['facts']),
                'issue'    => trim($input['issue']),
                'decision' => trim($input['decision']),
                'rule'     => trim($input['rule']),
                'apply'    => trim($input['apply'] ?? ''),
                'notes'    => trim($input['notes'] ?? ''),
                'created'  => date('c'),
            ];
            array_unshift($data['cases'], $newCase);
            saveData($dataFile, $data);
            echo json_encode(['success' => true, 'case' => $newCase]);
            break;

        case 'update_case':
            $caseId = $input['id'] ?? null;
            if (!$caseId) {
                echo json_encode(['success' => false, 'message' => 'Case ID required.']);
                exit;
            }
            $found = false;
            foreach ($data['cases'] as &$c) {
                if ($c['id'] === $caseId) {
                    $c['name']     = trim($input['name']     ?? $c['name']);
                    $c['topic']    = trim($input['topic']    ?? $c['topic']);
                    $c['facts']    = trim($input['facts']    ?? $c['facts']);
                    $c['issue']    = trim($input['issue']    ?? $c['issue']);
                    $c['decision'] = trim($input['decision'] ?? $c['decision']);
                    $c['rule']     = trim($input['rule']     ?? $c['rule']);
                    $c['apply']    = trim($input['apply']    ?? $c['apply']);
                    $c['notes']    = trim($input['notes']    ?? $c['notes']);
                    $found = true;
                    break;
                }
            }
            if ($found) {
                saveData($dataFile, $data);
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Case not found.']);
            }
            break;

        case 'delete_case':
            $caseId = $input['id'] ?? null;
            if (!$caseId) {
                echo json_encode(['success' => false, 'message' => 'Case ID required.']);
                exit;
            }
            $before = count($data['cases']);
            $data['cases'] = array_values(array_filter($data['cases'], fn($c) => $c['id'] !== $caseId));
            if (count($data['cases']) < $before) {
                saveData($dataFile, $data);
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Case not found.']);
            }
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action.']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid request.']);
