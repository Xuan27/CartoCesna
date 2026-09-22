<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

function sendJsonResponse($data) {
    header('Content-Type: application/json');
    echo json_encode($data, JSON_UNESCAPED_SLASHES);
    exit;
}

// Project/task folder-path templates, editable from the Settings panel.
// Live in a flat JSON file — same pattern as Private/crews.json.php — so
// they can be changed without a migration. project-crud.js (new project
// auto-fill) and field_data_qc.php (raw data path guess) both read this
// instead of hardcoding their own copy.
define('PATH_TEMPLATES_FILE', __DIR__ . '/../../Private/path_templates.json.php');
define('PATH_TEMPLATES_GUARD', "<?php exit; // data file — the guard makes direct web requests return nothing ?>\n");

const PATH_TEMPLATE_KEYS = [
    'projectFolderLink',
    'surveyFolderLink',
    'drawingFolderLink',
    'contractLink',
    'qaQcFolderLink',
    'researchFolderLink',
    'rawDataPathGuess',
];

// Defaults mirror what was previously hardcoded in project-crud.js and
// field_data_qc.php, so behavior is unchanged until someone edits a template.
const DEFAULT_PATH_TEMPLATES = [
    'projectFolderLink'  => 'N:\\[project_id]',
    'surveyFolderLink'   => 'N:\\[project_id]\\05 Service Groups\\Survey',
    'drawingFolderLink'  => 'N:\\[project_id]\\06 CAD\\DWG\\Survey C3D',
    'contractLink'       => 'N:\\[project_id]\\01 Administration\\Contracts',
    'qaQcFolderLink'     => 'N:\\[project_id]\\07 QA-QC\\5 - Plan and Report Markups\\Land Surveying',
    'researchFolderLink' => 'N:\\[project_id]\\09 Research\\Survey Research',
    'rawDataPathGuess'   => 'N:\\[project_id]\\05 Service Groups\\Survey\\Downloads',
];

function readPathTemplates() {
    $templates = DEFAULT_PATH_TEMPLATES;
    if (is_file(PATH_TEMPLATES_FILE)) {
        $raw = (string)file_get_contents(PATH_TEMPLATES_FILE);
        $raw = preg_replace('/^<\?php.*?\?>\s*/s', '', $raw);
        $data = json_decode($raw, true);
        if (is_array($data)) {
            foreach (PATH_TEMPLATE_KEYS as $key) {
                if (isset($data[$key]) && is_string($data[$key]) && $data[$key] !== '') {
                    $templates[$key] = $data[$key];
                }
            }
        }
    }
    return $templates;
}

function writePathTemplates(array $templates) {
    $json = json_encode($templates, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    return file_put_contents(PATH_TEMPLATES_FILE, PATH_TEMPLATES_GUARD . $json, LOCK_EX) !== false;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['action'])) {
    sendJsonResponse([
        'success' => false,
        'message' => 'Invalid request method or missing action parameter'
    ]);
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$currentUser = trim($_SESSION['username'] ?? '');
session_write_close();

if ($currentUser === '') {
    sendJsonResponse(['success' => false, 'message' => 'Not logged in']);
}

$action = $_POST['action'];

if ($action === 'get_templates') {
    sendJsonResponse([
        'success'  => true,
        'templates' => readPathTemplates(),
        'defaults'  => DEFAULT_PATH_TEMPLATES,
    ]);

} elseif ($action === 'save_templates') {
    $decoded = json_decode($_POST['templates_json'] ?? '', true);
    if (!is_array($decoded)) {
        sendJsonResponse(['success' => false, 'message' => 'templates_json must be a JSON object']);
    }

    $templates = readPathTemplates();
    foreach (PATH_TEMPLATE_KEYS as $key) {
        if (!array_key_exists($key, $decoded)) continue;
        $value = trim((string)$decoded[$key]);
        if (mb_strlen($value) > 500) {
            sendJsonResponse(['success' => false, 'message' => "$key is too long (max 500 characters)"]);
        }
        // Empty means "use the default" rather than storing a blank template.
        $templates[$key] = $value !== '' ? $value : DEFAULT_PATH_TEMPLATES[$key];
    }

    if (!writePathTemplates($templates)) {
        sendJsonResponse(['success' => false, 'message' => 'Could not write path_templates.json.php — check file permissions']);
    }
    sendJsonResponse(['success' => true, 'templates' => $templates]);

} else {
    sendJsonResponse(['success' => false, 'message' => 'Invalid action parameter']);
}
