<?php
/**
 * Regenerates the printable recipe book (or a single recipe) straight from
 * the live database and streams it back as a PDF, using WeasyPrint.
 *
 * GET params:
 *   id           - optional. If set, export just that one recipe.
 *   category_id  - optional. If set (and id is not), export just that category.
 */
require_once __DIR__ . '/../../../../../classes/Env.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/pdf_render.php';

$pdo = RecipeDB::connect();

$singleId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$catId    = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;

if ($singleId) {
    $stmt = $pdo->prepare(
        "SELECT r.*, c.name_en AS category_name_en, c.name_es AS category_name_es
         FROM recipes r JOIN categories c ON c.id = r.category_id WHERE r.id = :id"
    );
    $stmt->execute([':id' => $singleId]);
    $recipe = $stmt->fetch();
    if (!$recipe) {
        http_response_code(404);
        echo 'Recipe not found';
        exit;
    }
    $inner = $recipe['format'] === 'page' ? pr_renderPage($recipe) : pr_renderCard($recipe);
    $html = '<!DOCTYPE html><html><head><meta charset="utf-8"><style>' . pr_css() . '</style></head><body>' . $inner . '</body></html>';
    $filenameBase = preg_replace('/[^A-Za-z0-9]+/', '-', $recipe['title']);
} else {
    $catStmt = $pdo->query('SELECT id, name_en, name_es, sort_order FROM categories ORDER BY sort_order');
    $categories = $catStmt->fetchAll();
    if ($catId) {
        $categories = array_values(array_filter($categories, fn($c) => (int)$c['id'] === $catId));
    }

    $recipesByCategory = [];
    $recStmt = $pdo->query('SELECT * FROM recipes');
    foreach ($recStmt->fetchAll() as $row) {
        $recipesByCategory[$row['category_id']][] = $row;
    }

    $html = pr_buildBookHtml($categories, $recipesByCategory);
    $filenameBase = $catId ? preg_replace('/[^A-Za-z0-9]+/', '-', $categories[0]['name_en'] ?? 'Category') : "Vanessa's-Recipe-Book";
}

$tmpHtml = tempnam(sys_get_temp_dir(), 'recipebook_') . '.html';
$tmpPdf  = tempnam(sys_get_temp_dir(), 'recipebook_') . '.pdf';
file_put_contents($tmpHtml, $html);

$pythonPath = '/home/juan/.local/lib/python3.12/site-packages';
$cmd = 'PYTHONPATH=' . escapeshellarg($pythonPath) . ' python3 -m weasyprint '
     . escapeshellarg($tmpHtml) . ' ' . escapeshellarg($tmpPdf) . ' 2>&1';
exec($cmd, $outputLines, $exitCode);

if ($exitCode !== 0 || !file_exists($tmpPdf)) {
    error_log('export_pdf weasyprint failed: ' . implode("\n", $outputLines));
    http_response_code(500);
    echo 'Failed to generate PDF';
    @unlink($tmpHtml);
    exit;
}

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . $filenameBase . '.pdf"');
header('Content-Length: ' . filesize($tmpPdf));
readfile($tmpPdf);

@unlink($tmpHtml);
@unlink($tmpPdf);
