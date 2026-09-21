<?php
require_once __DIR__ . '/../../../../../classes/Env.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/helpers.php';

$action = $_REQUEST['action'] ?? null;
if (!$action) {
    sendJsonResponse(['success' => false, 'message' => 'Missing action parameter'], 400);
}

try {
    $pdo = RecipeDB::connect();

    switch ($action) {

        // ---------------------------------------------------------------
        case 'categories': {
            $stmt = $pdo->query(
                "SELECT c.id, c.name_en, c.name_es, c.sort_order, COUNT(r.id) AS recipe_count
                 FROM categories c
                 LEFT JOIN recipes r ON r.category_id = c.id
                 GROUP BY c.id
                 ORDER BY c.sort_order"
            );
            sendJsonResponse(['success' => true, 'categories' => $stmt->fetchAll()]);
        }

        // ---------------------------------------------------------------
        case 'list': {
            $q        = trim($_GET['q'] ?? '');
            $catId    = $_GET['category_id'] ?? '';
            $lang     = $_GET['lang'] ?? '';
            $format   = $_GET['format'] ?? '';
            $favOnly  = ($_GET['favorite'] ?? '') === '1';

            $where  = [];
            $params = [];

            if ($catId !== '' && $catId !== 'all') {
                $where[] = 'r.category_id = :category_id';
                $params[':category_id'] = (int)$catId;
            }
            if ($lang !== '' && $lang !== 'all') {
                $where[] = 'r.lang = :lang';
                $params[':lang'] = $lang;
            }
            if ($format !== '' && $format !== 'all') {
                $where[] = 'r.format = :format';
                $params[':format'] = $format;
            }
            if ($favOnly) {
                $where[] = 'r.is_favorite = 1';
            }
            if ($q !== '') {
                // Require every whitespace-separated term to appear somewhere
                // in the title or the flattened search text (AND semantics).
                // Note: PDO here runs with EMULATE_PREPARES=false, so the same
                // named placeholder cannot be reused twice in one query --
                // each occurrence needs its own distinct name.
                $terms = preg_split('/\s+/', $q, -1, PREG_SPLIT_NO_EMPTY);
                foreach ($terms as $i => $term) {
                    $phTitle = ":term{$i}_title";
                    $phText  = ":term{$i}_text";
                    $where[] = "(r.title LIKE {$phTitle} OR r.search_text LIKE {$phText})";
                    $params[$phTitle] = '%' . $term . '%';
                    $params[$phText]  = '%' . $term . '%';
                }
            }

            $sql = "SELECT r.id, r.category_id, c.name_en AS category_name_en, c.name_es AS category_name_es,
                           r.title, r.format, r.lang, r.serves, r.prep_time, r.cook_time,
                           r.photo_path, r.is_favorite, r.updated_at,
                           JSON_LENGTH(r.ingredients_json) AS section_count
                    FROM recipes r
                    JOIN categories c ON c.id = r.category_id";
            if ($where) {
                $sql .= ' WHERE ' . implode(' AND ', $where);
            }
            $sql .= ' ORDER BY c.sort_order, r.title';

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll();
            foreach ($rows as &$row) {
                $row['is_favorite'] = (bool)$row['is_favorite'];
                if ($row['photo_path']) {
                    $row['photo_url'] = 'uploads/' . rawurlencode(basename($row['photo_path']));
                } else {
                    $row['photo_url'] = null;
                }
                unset($row['photo_path']);
            }
            sendJsonResponse(['success' => true, 'recipes' => $rows, 'count' => count($rows)]);
        }

        // ---------------------------------------------------------------
        case 'get': {
            $id = (int)($_GET['id'] ?? 0);
            if (!$id) sendJsonResponse(['success' => false, 'message' => 'Missing id'], 400);
            $stmt = $pdo->prepare(
                "SELECT r.*, c.name_en AS category_name_en, c.name_es AS category_name_es
                 FROM recipes r JOIN categories c ON c.id = r.category_id
                 WHERE r.id = :id"
            );
            $stmt->execute([':id' => $id]);
            $r = $stmt->fetch();
            if (!$r) sendJsonResponse(['success' => false, 'message' => 'Recipe not found'], 404);
            $r['ingredients'] = normalizeSections($r['ingredients_json']);
            $r['directions'] = normalizeSections($r['directions_json']);
            unset($r['ingredients_json'], $r['directions_json'], $r['search_text']);
            $r['is_favorite'] = (bool)$r['is_favorite'];
            $r['photo_url'] = $r['photo_path'] ? ('uploads/' . rawurlencode(basename($r['photo_path']))) : null;
            unset($r['photo_path']);
            sendJsonResponse(['success' => true, 'recipe' => $r]);
        }

        // ---------------------------------------------------------------
        case 'create':
        case 'update': {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                sendJsonResponse(['success' => false, 'message' => 'POST required'], 405);
            }
            $isUpdate = $action === 'update';
            $id = (int)($_POST['id'] ?? 0);
            if ($isUpdate && !$id) {
                sendJsonResponse(['success' => false, 'message' => 'Missing id'], 400);
            }

            $err = requireFields($_POST, ['title', 'category_id', 'format', 'lang']);
            if ($err) sendJsonResponse(['success' => false, 'message' => $err], 400);

            $title    = trim($_POST['title']);
            $catId    = (int)$_POST['category_id'];
            $format   = $_POST['format'] === 'page' ? 'page' : 'card';
            $lang     = $_POST['lang'] === 'es' ? 'es' : 'en';
            $serves   = trim($_POST['serves'] ?? '') ?: null;
            $prep     = trim($_POST['prep_time'] ?? '') ?: null;
            $cook     = trim($_POST['cook_time'] ?? '') ?: null;
            $note     = trim($_POST['note'] ?? '') ?: null;
            $source   = trim($_POST['source'] ?? '') ?: null;

            $ingredients = normalizeSections($_POST['ingredients_json'] ?? '[]');
            $directions  = normalizeSections($_POST['directions_json'] ?? '[]');
            if (empty($ingredients)) {
                sendJsonResponse(['success' => false, 'message' => 'At least one ingredient is required'], 400);
            }
            if (empty($directions)) {
                sendJsonResponse(['success' => false, 'message' => 'At least one direction step is required'], 400);
            }

            // Verify category exists
            $catCheck = $pdo->prepare('SELECT id FROM categories WHERE id = :id');
            $catCheck->execute([':id' => $catId]);
            if (!$catCheck->fetch()) {
                sendJsonResponse(['success' => false, 'message' => 'Invalid category'], 400);
            }

            $searchText = buildSearchText(
                ['title' => $title, 'serves' => $serves, 'prep_time' => $prep, 'cook_time' => $cook, 'note' => $note, 'source' => $source],
                $ingredients, $directions
            );

            $uploadDir = __DIR__ . '/../uploads/';
            $photoPath = null;
            $removePhoto = ($_POST['remove_photo'] ?? '') === '1';

            if ($isUpdate) {
                $existing = $pdo->prepare('SELECT photo_path FROM recipes WHERE id = :id');
                $existing->execute([':id' => $id]);
                $row = $existing->fetch();
                if (!$row) sendJsonResponse(['success' => false, 'message' => 'Recipe not found'], 404);
                $photoPath = $row['photo_path'];
                if ($removePhoto && $photoPath && file_exists($photoPath)) {
                    @unlink($photoPath);
                    $photoPath = null;
                }
            }

            if (!empty($_FILES['photo']['tmp_name']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($_FILES['photo']['tmp_name']);
                if (!isset($allowed[$mime])) {
                    sendJsonResponse(['success' => false, 'message' => 'Photo must be a JPEG, PNG, or WEBP image'], 400);
                }
                if ($_FILES['photo']['size'] > 8 * 1024 * 1024) {
                    sendJsonResponse(['success' => false, 'message' => 'Photo must be under 8MB'], 400);
                }
                if ($photoPath && file_exists($photoPath)) {
                    @unlink($photoPath);
                }
                $ext = $allowed[$mime];
                $filename = 'recipe_' . ($isUpdate ? $id : 'new') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                $dest = $uploadDir . $filename;
                if (!move_uploaded_file($_FILES['photo']['tmp_name'], $dest)) {
                    sendJsonResponse(['success' => false, 'message' => 'Failed to save uploaded photo'], 500);
                }
                $photoPath = $dest;
            }

            $ingJson = json_encode($ingredients, JSON_UNESCAPED_UNICODE);
            $dirJson = json_encode($directions, JSON_UNESCAPED_UNICODE);

            if ($isUpdate) {
                $stmt = $pdo->prepare(
                    "UPDATE recipes SET category_id=:cat, title=:title, format=:format, lang=:lang,
                        serves=:serves, prep_time=:prep, cook_time=:cook, note=:note, source=:source,
                        ingredients_json=:ing, directions_json=:dir, search_text=:stext, photo_path=:photo
                     WHERE id=:id"
                );
                $stmt->execute([
                    ':cat' => $catId, ':title' => $title, ':format' => $format, ':lang' => $lang,
                    ':serves' => $serves, ':prep' => $prep, ':cook' => $cook, ':note' => $note, ':source' => $source,
                    ':ing' => $ingJson, ':dir' => $dirJson, ':stext' => $searchText, ':photo' => $photoPath,
                    ':id' => $id,
                ]);
                sendJsonResponse(['success' => true, 'id' => $id, 'message' => 'Recipe updated']);
            } else {
                $stmt = $pdo->prepare(
                    "INSERT INTO recipes (category_id, title, format, lang, serves, prep_time, cook_time, note, source,
                        ingredients_json, directions_json, search_text, photo_path)
                     VALUES (:cat, :title, :format, :lang, :serves, :prep, :cook, :note, :source, :ing, :dir, :stext, :photo)"
                );
                $stmt->execute([
                    ':cat' => $catId, ':title' => $title, ':format' => $format, ':lang' => $lang,
                    ':serves' => $serves, ':prep' => $prep, ':cook' => $cook, ':note' => $note, ':source' => $source,
                    ':ing' => $ingJson, ':dir' => $dirJson, ':stext' => $searchText, ':photo' => $photoPath,
                ]);
                $newId = (int)$pdo->lastInsertId();
                sendJsonResponse(['success' => true, 'id' => $newId, 'message' => 'Recipe created']);
            }
        }

        // ---------------------------------------------------------------
        case 'delete': {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                sendJsonResponse(['success' => false, 'message' => 'POST required'], 405);
            }
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) sendJsonResponse(['success' => false, 'message' => 'Missing id'], 400);
            $stmt = $pdo->prepare('SELECT photo_path FROM recipes WHERE id = :id');
            $stmt->execute([':id' => $id]);
            $row = $stmt->fetch();
            if (!$row) sendJsonResponse(['success' => false, 'message' => 'Recipe not found'], 404);
            $del = $pdo->prepare('DELETE FROM recipes WHERE id = :id');
            $del->execute([':id' => $id]);
            if ($row['photo_path'] && file_exists($row['photo_path'])) {
                @unlink($row['photo_path']);
            }
            sendJsonResponse(['success' => true, 'message' => 'Recipe deleted']);
        }

        // ---------------------------------------------------------------
        case 'toggle_favorite': {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                sendJsonResponse(['success' => false, 'message' => 'POST required'], 405);
            }
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) sendJsonResponse(['success' => false, 'message' => 'Missing id'], 400);
            $stmt = $pdo->prepare('UPDATE recipes SET is_favorite = 1 - is_favorite WHERE id = :id');
            $stmt->execute([':id' => $id]);
            $check = $pdo->prepare('SELECT is_favorite FROM recipes WHERE id = :id');
            $check->execute([':id' => $id]);
            $row = $check->fetch();
            if (!$row) sendJsonResponse(['success' => false, 'message' => 'Recipe not found'], 404);
            sendJsonResponse(['success' => true, 'is_favorite' => (bool)$row['is_favorite']]);
        }

        // ---------------------------------------------------------------
        default:
            sendJsonResponse(['success' => false, 'message' => 'Unknown action'], 400);
    }
} catch (Exception $e) {
    error_log('recipes_api error: ' . $e->getMessage());
    sendJsonResponse(['success' => false, 'message' => 'Server error'], 500);
}
