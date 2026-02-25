<?php
/**
 * tour_viewer.php — Public tour entry point for CartoCesna
 *
 * URL: /CartoCesna/tour_viewer.php?token=<embed_token>
 *
 * Looks up the token in tour_configurations → gets scan_id →
 * redirects to the pre-generated Three.js viewer for that scan.
 */

require_once __DIR__ . '/Private/db_config.php';

// Compute base URL dynamically so it works on any deployment path
$_doc_root = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/');
$_app_root = str_replace('\\', '/', __DIR__);
$base_url  = '/' . ltrim(substr($_app_root, strlen($_doc_root)), '/');
$base_url  = rtrim($base_url, '/') . '/';

$token = isset($_GET['token']) ? trim($_GET['token']) : '';

// Basic token format validation (hex string, 32–64 chars)
if (!preg_match('/^[a-f0-9]{32,64}$/', $token)) {
    http_response_code(400);
    showError('Invalid tour token.');
}

$db   = new Database();
$conn = $db->getConnection();

$stmt = $conn->prepare("
    SELECT tc.scan_id, tc.published_date, sp.scan_name, sp.status
    FROM   tour_configurations tc
    JOIN   scan_projects sp ON sp.scan_id = tc.scan_id
    WHERE  tc.embed_token = :token
    LIMIT  1
");
$stmt->execute([':token' => $token]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    http_response_code(404);
    showError('Tour not found. The link may be invalid or the tour has not been published yet.');
}

if (strtolower($row['status']) !== 'published') {
    http_response_code(403);
    showError('This tour is not yet published.');
}

$scan_id   = (int) $row['scan_id'];
$scan_name = htmlspecialchars($row['scan_name']);
$viewer    = $base_url . "uploads/scans/{$scan_id}/index.html";

// Verify the viewer file actually exists on disk
$viewer_path = __DIR__ . "/uploads/scans/{$scan_id}/index.html";
if (!file_exists($viewer_path)) {
    http_response_code(404);
    showError("Tour viewer for \"{$scan_name}\" has not been generated yet. Upload a GLB model from the dashboard first.");
}

// All good — redirect to the Three.js viewer
header("Location: {$viewer}");
exit;

// ── Helper ────────────────────────────────────────────────────────────────────
function showError(string $message): never {
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tour Not Found — CartoCesna</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #0f172a;
            color: #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 2rem;
        }
        .card {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 12px;
            padding: 2.5rem 3rem;
            max-width: 480px;
            text-align: center;
        }
        .icon { font-size: 3rem; margin-bottom: 1rem; }
        h1 { font-size: 1.25rem; font-weight: 600; margin-bottom: 0.75rem; color: #f8fafc; }
        p  { font-size: 0.9375rem; color: #94a3b8; line-height: 1.6; }
        a  {
            display: inline-block;
            margin-top: 1.5rem;
            padding: 0.5rem 1.25rem;
            background: #3b82f6;
            color: #fff;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.875rem;
        }
        a:hover { background: #2563eb; }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">🏠</div>
        <h1>Tour Unavailable</h1>
        <p><?= $message ?></p>
        <a href="<?= htmlspecialchars($base_url) ?>">Back to CartoCesna</a>
    </div>
</body>
</html>
    <?php
    exit;
}
