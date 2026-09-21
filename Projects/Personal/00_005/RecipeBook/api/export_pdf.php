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


/**
 * Browser-print fallback: the same template as a web page that opens the
 * print dialog ("Save as PDF"). Needs nothing installed on the server.
 */
function serveBrowserPrint(string $html, string $title): void {
    $banner = '<div class="no-print" style="font:14px Arial,sans-serif;background:#fff8e1;border-bottom:1px solid #e0c36a;padding:10px 16px;">'
        . 'In the print dialog choose <b>Save as PDF</b>, paper size <b>Letter</b>, and turn <b>Headers and footers</b> off. '
        . '<button onclick="window.print()" style="margin-left:8px;padding:4px 12px;">Print / Save as PDF</button></div>';
    $extra = '<style>@media print{.no-print{display:none!important}} @media screen{body{background:#eee}'
        . '.recipe-page,.card-sheet,.divider-page{background:#fff;width:8.5in;margin:12px auto;padding:0.45in 0.55in;box-shadow:0 1px 6px rgba(0,0,0,.2)}}</style>';
    $html = str_replace('</head>', '<title>' . pr_esc($title) . '</title>' . $extra . '</head>', $html);
    $html = str_replace('<body>', '<body>' . $banner, $html);
    $html = str_replace('</body>', '<script>window.addEventListener("load",function(){setTimeout(function(){window.print()},400)})</script></body>', $html);
    header('Content-Type: text/html; charset=utf-8');
    echo $html;
    exit;
}

// Can we run WeasyPrint here? (Many shared hosts disable exec or lack it.)
function weasyprintUsable(): bool {
    if (!function_exists('exec')) return false;
    $disabled = array_map('trim', explode(',', (string)ini_get('disable_functions')));
    return !in_array('exec', $disabled, true);
}

$forcePrint = ($_GET['mode'] ?? '') === 'print';
if ($forcePrint || !weasyprintUsable()) {
    pr_setPhotoMode('web');
    // Rebuild HTML with web-relative photo URLs
    if ($singleId) {
        $inner = $recipe['format'] === 'page' ? pr_renderPage($recipe) : pr_renderCard($recipe);
        $html = '<!DOCTYPE html><html><head><meta charset="utf-8"><style>' . pr_css() . '</style></head><body>' . $inner . '</body></html>';
    } else {
        $html = pr_buildBookHtml($categories, $recipesByCategory);
    }
    serveBrowserPrint($html, $filenameBase);
}

$tmpHtml = tempnam(sys_get_temp_dir(), 'recipebook_') . '.html';
$tmpPdf  = tempnam(sys_get_temp_dir(), 'recipebook_') . '.pdf';
file_put_contents($tmpHtml, $html);

$pythonPath = '/home/juan/.local/lib/python3.12/site-packages';
$cmd = 'PYTHONPATH=' . escapeshellarg($pythonPath) . ' python3 -m weasyprint '
     . escapeshellarg($tmpHtml) . ' ' . escapeshellarg($tmpPdf) . ' 2>&1';
exec($cmd, $outputLines, $exitCode);

if ($exitCode !== 0 || !file_exists($tmpPdf) || filesize($tmpPdf) === 0) {
    error_log('export_pdf weasyprint failed: ' . implode("\n", $outputLines));
    @unlink($tmpHtml);
    @unlink($tmpPdf);
    // Fall back to the browser print page instead of an error.
    pr_setPhotoMode('web');
    if ($singleId) {
        $inner = $recipe['format'] === 'page' ? pr_renderPage($recipe) : pr_renderCard($recipe);
        $html = '<!DOCTYPE html><html><head><meta charset="utf-8"><style>' . pr_css() . '</style></head><body>' . $inner . '</body></html>';
    } else {
        $html = pr_buildBookHtml($categories, $recipesByCategory);
    }
    serveBrowserPrint($html, $filenameBase);
}

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . $filenameBase . '.pdf"');
header('Content-Length: ' . filesize($tmpPdf));
readfile($tmpPdf);

@unlink($tmpHtml);
@unlink($tmpPdf);
