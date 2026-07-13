<?php
/**
 * Grocery & Meal Planning API — backs Projects/Personal/00_004.
 * Tables (gm_*) are created on first use so no separate migration step is needed.
 */
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json');

require_once __DIR__ . '/../../classes/Security.php';
require_once __DIR__ . '/scrapers/ScraperFactory.php';

function sendJsonResponse($data) {
    echo json_encode($data, JSON_UNESCAPED_SLASHES);
    exit;
}

Security::secureSession();
if (empty($_SESSION['logged_in'])) {
    sendJsonResponse(['success' => false, 'message' => 'Not authenticated']);
}
$currentUser = trim($_SESSION['username'] ?? '');
session_write_close();

// Support both GET (reads) and POST with a JSON or form body (writes)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $input  = $_GET;
    $action = $_GET['action'] ?? '';
} else {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        $input = $_POST;
    }
    $action = $input['action'] ?? '';
}

try {
    require_once __DIR__ . '/../../Private/db_config.php';
    $db  = new Database();
    $pdo = $db->getConnection();

    ensureSchema($pdo);

    switch ($action) {
        case 'get_stores':            getStores($pdo); break;

        case 'get_products':          getProducts($pdo); break;
        case 'save_product':          saveProduct($pdo, $input); break;
        case 'delete_product':        deleteProduct($pdo, $input); break;

        case 'get_prices':            getPrices($pdo, $input); break;
        case 'save_price':            savePrice($pdo, $input, $currentUser); break;
        case 'delete_price':          deletePrice($pdo, $input); break;
        case 'scrape_price':          scrapePrice($pdo, $input); break;

        case 'get_meals':             getMeals($pdo, $input); break;
        case 'get_meal':              getMeal($pdo, $input); break;
        case 'save_meal':             saveMeal($pdo, $input); break;
        case 'delete_meal':           deleteMeal($pdo, $input); break;

        case 'get_health_profile':    getHealthProfile($pdo, $currentUser); break;
        case 'save_health_profile':   saveHealthProfile($pdo, $input, $currentUser); break;

        case 'get_meal_plan':         getMealPlan($pdo, $input, $currentUser); break;
        case 'save_meal_plan_entry':  saveMealPlanEntry($pdo, $input, $currentUser); break;
        case 'delete_meal_plan_entry':deleteMealPlanEntry($pdo, $input, $currentUser); break;
        case 'generate_meal_plan':    generateMealPlan($pdo, $input, $currentUser); break;

        case 'get_shopping_list':     getShoppingList($pdo, $input, $currentUser); break;

        default:
            sendJsonResponse(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    error_log('Grocery/Meal API error: ' . $e->getMessage());
    sendJsonResponse(['success' => false, 'message' => 'Server error occurred']);
}

// ─────────────────────────────────────────────────────────────────────────
// Schema
// ─────────────────────────────────────────────────────────────────────────

function ensureSchema(PDO $pdo) {
    $pdo->exec("CREATE TABLE IF NOT EXISTS gm_stores (
        store_id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");

    $pdo->exec("CREATE TABLE IF NOT EXISTS gm_products (
        product_id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(150) NOT NULL,
        category VARCHAR(80) NULL,
        unit VARCHAR(30) NOT NULL DEFAULT 'each',
        notes TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_product_name (name)
    ) ENGINE=InnoDB");

    $pdo->exec("CREATE TABLE IF NOT EXISTS gm_product_prices (
        price_id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NOT NULL,
        store_id INT NOT NULL,
        price DECIMAL(8,2) NOT NULL,
        observed_date DATE NOT NULL,
        source ENUM('manual','scraped') NOT NULL DEFAULT 'manual',
        needs_review TINYINT(1) NOT NULL DEFAULT 0,
        raw_snippet VARCHAR(500) NULL,
        created_by VARCHAR(100) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_product_store_date (product_id, store_id, observed_date),
        CONSTRAINT fk_gm_price_product FOREIGN KEY (product_id) REFERENCES gm_products(product_id) ON DELETE CASCADE,
        CONSTRAINT fk_gm_price_store FOREIGN KEY (store_id) REFERENCES gm_stores(store_id) ON DELETE CASCADE
    ) ENGINE=InnoDB");

    $pdo->exec("CREATE TABLE IF NOT EXISTS gm_meals (
        meal_id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(150) NOT NULL,
        meal_type ENUM('breakfast','lunch','dinner','snack') NOT NULL DEFAULT 'dinner',
        servings INT NOT NULL DEFAULT 4,
        calories INT NULL,
        protein_g INT NULL,
        carbs_g INT NULL,
        fat_g INT NULL,
        tags VARCHAR(300) NULL,
        instructions TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");

    $pdo->exec("CREATE TABLE IF NOT EXISTS gm_meal_ingredients (
        ingredient_id INT AUTO_INCREMENT PRIMARY KEY,
        meal_id INT NOT NULL,
        product_id INT NOT NULL,
        quantity DECIMAL(8,2) NOT NULL DEFAULT 1,
        unit VARCHAR(30) NULL,
        CONSTRAINT fk_gm_ingredient_meal FOREIGN KEY (meal_id) REFERENCES gm_meals(meal_id) ON DELETE CASCADE,
        CONSTRAINT fk_gm_ingredient_product FOREIGN KEY (product_id) REFERENCES gm_products(product_id) ON DELETE CASCADE
    ) ENGINE=InnoDB");

    $pdo->exec("CREATE TABLE IF NOT EXISTS gm_meal_plan (
        plan_id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(100) NOT NULL,
        plan_date DATE NOT NULL,
        meal_type ENUM('breakfast','lunch','dinner','snack') NOT NULL,
        meal_id INT NOT NULL,
        servings_planned INT NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_username_date (username, plan_date),
        CONSTRAINT fk_gm_plan_meal FOREIGN KEY (meal_id) REFERENCES gm_meals(meal_id) ON DELETE CASCADE
    ) ENGINE=InnoDB");

    $pdo->exec("CREATE TABLE IF NOT EXISTS gm_health_profile (
        username VARCHAR(100) PRIMARY KEY,
        calorie_target INT NULL,
        protein_target_g INT NULL,
        carbs_target_g INT NULL,
        fat_target_g INT NULL,
        allergies VARCHAR(300) NULL,
        restrictions VARCHAR(300) NULL,
        notes TEXT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");

    // Seed the three stores the user cares about (idempotent)
    $stmt = $pdo->prepare("INSERT IGNORE INTO gm_stores (name) VALUES (:name)");
    foreach (['HEB', 'Sprouts', 'Costco'] as $storeName) {
        $stmt->execute([':name' => $storeName]);
    }
}

// ─────────────────────────────────────────────────────────────────────────
// Stores
// ─────────────────────────────────────────────────────────────────────────

function getStores(PDO $pdo) {
    $stores = $pdo->query("SELECT * FROM gm_stores ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
    sendJsonResponse(['success' => true, 'stores' => $stores]);
}

// ─────────────────────────────────────────────────────────────────────────
// Products
// ─────────────────────────────────────────────────────────────────────────

function getProducts(PDO $pdo) {
    $products = $pdo->query("SELECT * FROM gm_products ORDER BY category, name")->fetchAll(PDO::FETCH_ASSOC);
    sendJsonResponse(['success' => true, 'products' => $products]);
}

function saveProduct(PDO $pdo, array $input) {
    $name = trim($input['name'] ?? '');
    if ($name === '') {
        sendJsonResponse(['success' => false, 'message' => 'Product name is required']);
    }

    $productId = (int) ($input['product_id'] ?? 0);
    $category  = trim($input['category'] ?? '') ?: null;
    $unit      = trim($input['unit'] ?? '') ?: 'each';
    $notes     = trim($input['notes'] ?? '') ?: null;

    if ($productId > 0) {
        $stmt = $pdo->prepare(
            "UPDATE gm_products SET name = :name, category = :category, unit = :unit, notes = :notes
             WHERE product_id = :id"
        );
        $stmt->execute([':name' => $name, ':category' => $category, ':unit' => $unit, ':notes' => $notes, ':id' => $productId]);
    } else {
        $stmt = $pdo->prepare(
            "INSERT INTO gm_products (name, category, unit, notes) VALUES (:name, :category, :unit, :notes)
             ON DUPLICATE KEY UPDATE category = VALUES(category), unit = VALUES(unit), notes = VALUES(notes)"
        );
        $stmt->execute([':name' => $name, ':category' => $category, ':unit' => $unit, ':notes' => $notes]);
        $productId = (int) $pdo->lastInsertId();
        if ($productId === 0) {
            $lookup = $pdo->prepare("SELECT product_id FROM gm_products WHERE name = :name");
            $lookup->execute([':name' => $name]);
            $productId = (int) $lookup->fetchColumn();
        }
    }

    sendJsonResponse(['success' => true, 'product_id' => $productId]);
}

function deleteProduct(PDO $pdo, array $input) {
    $productId = (int) ($input['product_id'] ?? 0);
    if (!$productId) {
        sendJsonResponse(['success' => false, 'message' => 'product_id is required']);
    }
    $pdo->prepare("DELETE FROM gm_products WHERE product_id = :id")->execute([':id' => $productId]);
    sendJsonResponse(['success' => true]);
}

// ─────────────────────────────────────────────────────────────────────────
// Prices
// ─────────────────────────────────────────────────────────────────────────

/** Latest observed price per store for a product, plus recent history. */
function getPrices(PDO $pdo, array $input) {
    $productId = (int) ($input['product_id'] ?? 0);

    if ($productId) {
        $latest = $pdo->prepare(
            "SELECT pp.*, s.name AS store_name
             FROM gm_product_prices pp
             JOIN gm_stores s ON s.store_id = pp.store_id
             WHERE pp.product_id = :pid
             AND pp.price_id IN (
                 SELECT MAX(price_id) FROM gm_product_prices WHERE product_id = :pid2 GROUP BY store_id
             )
             ORDER BY pp.price ASC"
        );
        $latest->execute([':pid' => $productId, ':pid2' => $productId]);

        $history = $pdo->prepare(
            "SELECT pp.*, s.name AS store_name
             FROM gm_product_prices pp
             JOIN gm_stores s ON s.store_id = pp.store_id
             WHERE pp.product_id = :pid
             ORDER BY pp.observed_date DESC, pp.price_id DESC
             LIMIT 50"
        );
        $history->execute([':pid' => $productId]);

        sendJsonResponse([
            'success' => true,
            'latest'  => $latest->fetchAll(PDO::FETCH_ASSOC),
            'history' => $history->fetchAll(PDO::FETCH_ASSOC),
        ]);
    }

    // No product filter: cheapest current price per product, across all products
    $rows = $pdo->query(
        "SELECT pp.product_id, p.name AS product_name, p.unit, pp.store_id, s.name AS store_name,
                pp.price, pp.observed_date, pp.source, pp.needs_review
         FROM gm_product_prices pp
         JOIN gm_products p ON p.product_id = pp.product_id
         JOIN gm_stores s ON s.store_id = pp.store_id
         WHERE pp.price_id IN (
             SELECT MAX(price_id) FROM gm_product_prices GROUP BY product_id, store_id
         )
         ORDER BY p.name, pp.price ASC"
    )->fetchAll(PDO::FETCH_ASSOC);

    sendJsonResponse(['success' => true, 'prices' => $rows]);
}

function savePrice(PDO $pdo, array $input, string $currentUser) {
    $productId = (int) ($input['product_id'] ?? 0);
    $storeId   = (int) ($input['store_id'] ?? 0);
    $price     = $input['price'] ?? null;

    if (!$productId || !$storeId || !is_numeric($price) || (float) $price < 0) {
        sendJsonResponse(['success' => false, 'message' => 'product_id, store_id, and a non-negative price are required']);
    }

    $observedDate = trim($input['observed_date'] ?? '') ?: date('Y-m-d');
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $observedDate)) {
        sendJsonResponse(['success' => false, 'message' => 'Invalid observed_date']);
    }

    $stmt = $pdo->prepare(
        "INSERT INTO gm_product_prices (product_id, store_id, price, observed_date, source, needs_review, raw_snippet, created_by)
         VALUES (:product_id, :store_id, :price, :observed_date, 'manual', 0, NULL, :created_by)"
    );
    $stmt->execute([
        ':product_id'    => $productId,
        ':store_id'      => $storeId,
        ':price'         => (float) $price,
        ':observed_date' => $observedDate,
        ':created_by'    => $currentUser,
    ]);

    sendJsonResponse(['success' => true, 'price_id' => (int) $pdo->lastInsertId()]);
}

function deletePrice(PDO $pdo, array $input) {
    $priceId = (int) ($input['price_id'] ?? 0);
    if (!$priceId) {
        sendJsonResponse(['success' => false, 'message' => 'price_id is required']);
    }
    $pdo->prepare("DELETE FROM gm_product_prices WHERE price_id = :id")->execute([':id' => $priceId]);
    sendJsonResponse(['success' => true]);
}

/**
 * Best-effort scrape: looks up a price via the store's scraper and returns
 * it for review — it is NOT saved automatically. The UI should show the
 * result with a clear "unverified" label and let the user save it via
 * save_price (or discard it) after eyeballing it against the real store.
 */
function scrapePrice(PDO $pdo, array $input) {
    $storeId     = (int) ($input['store_id'] ?? 0);
    $productName = trim($input['product_name'] ?? '');

    if (!$storeId || $productName === '') {
        sendJsonResponse(['success' => false, 'message' => 'store_id and product_name are required']);
    }

    $storeStmt = $pdo->prepare("SELECT name FROM gm_stores WHERE store_id = :id");
    $storeStmt->execute([':id' => $storeId]);
    $storeName = $storeStmt->fetchColumn();
    if (!$storeName) {
        sendJsonResponse(['success' => false, 'message' => 'Unknown store']);
    }

    $scraper = ScraperFactory::forStore($storeName);
    if (!$scraper) {
        sendJsonResponse(['success' => false, 'message' => "No scraper available for $storeName"]);
    }

    $result = $scraper->lookupPrice($productName);
    if ($result === null) {
        $reason = $scraper->getLastError() ?: 'No confident match found.';
        sendJsonResponse([
            'success' => false,
            'message' => "Couldn't get a price from $storeName: $reason Enter the price manually.",
            'debug'   => buildScrapeDebugInfo($scraper->getLastHtml()),
        ]);
    }

    sendJsonResponse([
        'success'      => true,
        'needs_review' => true,
        'store_id'     => $storeId,
        'store_name'   => $storeName,
        'price'        => $result['price'],
        'matched_name' => $result['matched_name'],
        'raw_snippet'  => $result['raw_snippet'],
    ]);
}

// ─────────────────────────────────────────────────────────────────────────
// Meals
// ─────────────────────────────────────────────────────────────────────────

function getMeals(PDO $pdo, array $input) {
    $mealType = trim($input['meal_type'] ?? '');
    if ($mealType !== '') {
        $stmt = $pdo->prepare("SELECT * FROM gm_meals WHERE meal_type = :type ORDER BY name");
        $stmt->execute([':type' => $mealType]);
        $meals = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $meals = $pdo->query("SELECT * FROM gm_meals ORDER BY meal_type, name")->fetchAll(PDO::FETCH_ASSOC);
    }
    sendJsonResponse(['success' => true, 'meals' => $meals]);
}

function getMeal(PDO $pdo, array $input) {
    $mealId = (int) ($input['meal_id'] ?? 0);
    if (!$mealId) {
        sendJsonResponse(['success' => false, 'message' => 'meal_id is required']);
    }

    $stmt = $pdo->prepare("SELECT * FROM gm_meals WHERE meal_id = :id");
    $stmt->execute([':id' => $mealId]);
    $meal = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$meal) {
        sendJsonResponse(['success' => false, 'message' => 'Meal not found']);
    }

    $ingredientsStmt = $pdo->prepare(
        "SELECT mi.*, p.name AS product_name, p.unit AS product_unit
         FROM gm_meal_ingredients mi
         JOIN gm_products p ON p.product_id = mi.product_id
         WHERE mi.meal_id = :id
         ORDER BY p.name"
    );
    $ingredientsStmt->execute([':id' => $mealId]);
    $meal['ingredients'] = $ingredientsStmt->fetchAll(PDO::FETCH_ASSOC);

    sendJsonResponse(['success' => true, 'meal' => $meal]);
}

function saveMeal(PDO $pdo, array $input) {
    $name = trim($input['name'] ?? '');
    if ($name === '') {
        sendJsonResponse(['success' => false, 'message' => 'Meal name is required']);
    }

    $mealId    = (int) ($input['meal_id'] ?? 0);
    $mealType  = in_array($input['meal_type'] ?? '', ['breakfast', 'lunch', 'dinner', 'snack'], true)
        ? $input['meal_type'] : 'dinner';
    $servings  = max(1, (int) ($input['servings'] ?? 4));
    $calories  = is_numeric($input['calories'] ?? null) ? (int) $input['calories'] : null;
    $protein   = is_numeric($input['protein_g'] ?? null) ? (int) $input['protein_g'] : null;
    $carbs     = is_numeric($input['carbs_g'] ?? null) ? (int) $input['carbs_g'] : null;
    $fat       = is_numeric($input['fat_g'] ?? null) ? (int) $input['fat_g'] : null;
    $tags      = trim($input['tags'] ?? '') ?: null;
    $instructions = trim($input['instructions'] ?? '') ?: null;
    $ingredients  = is_array($input['ingredients'] ?? null) ? $input['ingredients'] : [];

    $pdo->beginTransaction();
    try {
        if ($mealId > 0) {
            $stmt = $pdo->prepare(
                "UPDATE gm_meals SET name = :name, meal_type = :type, servings = :servings, calories = :calories,
                 protein_g = :protein, carbs_g = :carbs, fat_g = :fat, tags = :tags, instructions = :instructions
                 WHERE meal_id = :id"
            );
            $stmt->execute([
                ':name' => $name, ':type' => $mealType, ':servings' => $servings, ':calories' => $calories,
                ':protein' => $protein, ':carbs' => $carbs, ':fat' => $fat, ':tags' => $tags,
                ':instructions' => $instructions, ':id' => $mealId,
            ]);
            $pdo->prepare("DELETE FROM gm_meal_ingredients WHERE meal_id = :id")->execute([':id' => $mealId]);
        } else {
            $stmt = $pdo->prepare(
                "INSERT INTO gm_meals (name, meal_type, servings, calories, protein_g, carbs_g, fat_g, tags, instructions)
                 VALUES (:name, :type, :servings, :calories, :protein, :carbs, :fat, :tags, :instructions)"
            );
            $stmt->execute([
                ':name' => $name, ':type' => $mealType, ':servings' => $servings, ':calories' => $calories,
                ':protein' => $protein, ':carbs' => $carbs, ':fat' => $fat, ':tags' => $tags,
                ':instructions' => $instructions,
            ]);
            $mealId = (int) $pdo->lastInsertId();
        }

        $ingredientStmt = $pdo->prepare(
            "INSERT INTO gm_meal_ingredients (meal_id, product_id, quantity, unit) VALUES (:meal_id, :product_id, :quantity, :unit)"
        );
        foreach ($ingredients as $ingredient) {
            $productId = (int) ($ingredient['product_id'] ?? 0);
            if (!$productId) {
                continue;
            }
            $ingredientStmt->execute([
                ':meal_id'    => $mealId,
                ':product_id' => $productId,
                ':quantity'   => is_numeric($ingredient['quantity'] ?? null) ? (float) $ingredient['quantity'] : 1,
                ':unit'       => trim($ingredient['unit'] ?? '') ?: null,
            ]);
        }

        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }

    sendJsonResponse(['success' => true, 'meal_id' => $mealId]);
}

function deleteMeal(PDO $pdo, array $input) {
    $mealId = (int) ($input['meal_id'] ?? 0);
    if (!$mealId) {
        sendJsonResponse(['success' => false, 'message' => 'meal_id is required']);
    }
    $pdo->prepare("DELETE FROM gm_meals WHERE meal_id = :id")->execute([':id' => $mealId]);
    sendJsonResponse(['success' => true]);
}

// ─────────────────────────────────────────────────────────────────────────
// Health profile (manual entry — tailors meal plan generation)
// ─────────────────────────────────────────────────────────────────────────

function getHealthProfile(PDO $pdo, string $currentUser) {
    $stmt = $pdo->prepare("SELECT * FROM gm_health_profile WHERE username = :username");
    $stmt->execute([':username' => $currentUser]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);
    sendJsonResponse(['success' => true, 'profile' => $profile ?: null]);
}

function saveHealthProfile(PDO $pdo, array $input, string $currentUser) {
    $stmt = $pdo->prepare(
        "INSERT INTO gm_health_profile (username, calorie_target, protein_target_g, carbs_target_g, fat_target_g, allergies, restrictions, notes)
         VALUES (:username, :calorie, :protein, :carbs, :fat, :allergies, :restrictions, :notes)
         ON DUPLICATE KEY UPDATE calorie_target = VALUES(calorie_target), protein_target_g = VALUES(protein_target_g),
             carbs_target_g = VALUES(carbs_target_g), fat_target_g = VALUES(fat_target_g),
             allergies = VALUES(allergies), restrictions = VALUES(restrictions), notes = VALUES(notes)"
    );
    $stmt->execute([
        ':username'     => $currentUser,
        ':calorie'      => is_numeric($input['calorie_target'] ?? null) ? (int) $input['calorie_target'] : null,
        ':protein'      => is_numeric($input['protein_target_g'] ?? null) ? (int) $input['protein_target_g'] : null,
        ':carbs'        => is_numeric($input['carbs_target_g'] ?? null) ? (int) $input['carbs_target_g'] : null,
        ':fat'          => is_numeric($input['fat_target_g'] ?? null) ? (int) $input['fat_target_g'] : null,
        ':allergies'    => trim($input['allergies'] ?? '') ?: null,
        ':restrictions' => trim($input['restrictions'] ?? '') ?: null,
        ':notes'        => trim($input['notes'] ?? '') ?: null,
    ]);
    sendJsonResponse(['success' => true]);
}

// ─────────────────────────────────────────────────────────────────────────
// Meal plan
// ─────────────────────────────────────────────────────────────────────────

function getMealPlan(PDO $pdo, array $input, string $currentUser) {
    [$startDate, $endDate] = resolveDateRange($input);

    $stmt = $pdo->prepare(
        "SELECT mp.*, m.name AS meal_name, m.calories, m.protein_g, m.carbs_g, m.fat_g, m.tags
         FROM gm_meal_plan mp
         JOIN gm_meals m ON m.meal_id = mp.meal_id
         WHERE mp.username = :username AND mp.plan_date BETWEEN :start AND :end
         ORDER BY mp.plan_date, FIELD(mp.meal_type, 'breakfast','lunch','dinner','snack')"
    );
    $stmt->execute([':username' => $currentUser, ':start' => $startDate, ':end' => $endDate]);

    sendJsonResponse([
        'success'    => true,
        'plan'       => $stmt->fetchAll(PDO::FETCH_ASSOC),
        'start_date' => $startDate,
        'end_date'   => $endDate,
    ]);
}

function saveMealPlanEntry(PDO $pdo, array $input, string $currentUser) {
    $planDate = trim($input['plan_date'] ?? '');
    $mealType = trim($input['meal_type'] ?? '');
    $mealId   = (int) ($input['meal_id'] ?? 0);
    $servings = max(1, (int) ($input['servings_planned'] ?? 1));

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $planDate) || !in_array($mealType, ['breakfast', 'lunch', 'dinner', 'snack'], true) || !$mealId) {
        sendJsonResponse(['success' => false, 'message' => 'plan_date, meal_type, and meal_id are required']);
    }

    $stmt = $pdo->prepare(
        "INSERT INTO gm_meal_plan (username, plan_date, meal_type, meal_id, servings_planned)
         VALUES (:username, :date, :type, :meal_id, :servings)"
    );
    $stmt->execute([
        ':username' => $currentUser, ':date' => $planDate, ':type' => $mealType,
        ':meal_id' => $mealId, ':servings' => $servings,
    ]);

    sendJsonResponse(['success' => true, 'plan_id' => (int) $pdo->lastInsertId()]);
}

function deleteMealPlanEntry(PDO $pdo, array $input, string $currentUser) {
    $planId = (int) ($input['plan_id'] ?? 0);
    if (!$planId) {
        sendJsonResponse(['success' => false, 'message' => 'plan_id is required']);
    }
    $stmt = $pdo->prepare("DELETE FROM gm_meal_plan WHERE plan_id = :id AND username = :username");
    $stmt->execute([':id' => $planId, ':username' => $currentUser]);
    sendJsonResponse(['success' => true]);
}

/**
 * Auto-fills empty slots in the date range with meals from the library,
 * respecting the user's health profile (excludes meals tagged with a
 * logged allergy/restriction) and avoiding repeats within the last 3 days.
 * Slots that already have a plan entry are left untouched.
 */
function generateMealPlan(PDO $pdo, array $input, string $currentUser) {
    [$startDate, $endDate] = resolveDateRange($input);
    $slots = ['breakfast', 'lunch', 'dinner'];

    $profileStmt = $pdo->prepare("SELECT * FROM gm_health_profile WHERE username = :username");
    $profileStmt->execute([':username' => $currentUser]);
    $profile = $profileStmt->fetch(PDO::FETCH_ASSOC) ?: [];

    $excludedTags = array_filter(array_map('trim', explode(',',
        strtolower(($profile['allergies'] ?? '') . ',' . ($profile['restrictions'] ?? '')))));

    $mealsByType = [];
    foreach ($slots as $type) {
        $stmt = $pdo->prepare("SELECT * FROM gm_meals WHERE meal_type = :type");
        $stmt->execute([':type' => $type]);
        $mealsByType[$type] = array_values(array_filter(
            $stmt->fetchAll(PDO::FETCH_ASSOC),
            function ($meal) use ($excludedTags) {
                if (empty($excludedTags)) {
                    return true;
                }
                $mealTags = array_map('trim', explode(',', strtolower($meal['tags'] ?? '')));
                return !array_intersect($excludedTags, $mealTags);
            }
        ));
    }

    $existingStmt = $pdo->prepare(
        "SELECT plan_date, meal_type, meal_id FROM gm_meal_plan WHERE username = :username AND plan_date BETWEEN :start AND :end"
    );
    $existingStmt->execute([':username' => $currentUser, ':start' => $startDate, ':end' => $endDate]);
    $existing = $existingStmt->fetchAll(PDO::FETCH_ASSOC);

    $filled = [];
    $recentMealIds = [];
    foreach ($existing as $row) {
        $filled[$row['plan_date'] . '|' . $row['meal_type']] = true;
        $recentMealIds[] = (int) $row['meal_id'];
    }

    $insertStmt = $pdo->prepare(
        "INSERT INTO gm_meal_plan (username, plan_date, meal_type, meal_id, servings_planned)
         VALUES (:username, :date, :type, :meal_id, 1)"
    );

    $inserted = 0;
    $date = new DateTime($startDate);
    $end  = new DateTime($endDate);
    $calorieBudgetPerMeal = !empty($profile['calorie_target']) ? (int) ($profile['calorie_target'] / count($slots)) : null;

    while ($date <= $end) {
        $dateStr = $date->format('Y-m-d');
        foreach ($slots as $type) {
            if (isset($filled[$dateStr . '|' . $type]) || empty($mealsByType[$type])) {
                continue;
            }

            $candidates = array_filter($mealsByType[$type], function ($meal) use ($recentMealIds) {
                return !in_array((int) $meal['meal_id'], array_slice($recentMealIds, -3 * 3), true);
            });
            if (empty($candidates)) {
                $candidates = $mealsByType[$type];
            }

            if ($calorieBudgetPerMeal) {
                usort($candidates, function ($a, $b) use ($calorieBudgetPerMeal) {
                    return abs(($a['calories'] ?? $calorieBudgetPerMeal) - $calorieBudgetPerMeal)
                        <=> abs(($b['calories'] ?? $calorieBudgetPerMeal) - $calorieBudgetPerMeal);
                });
                $chosen = $candidates[array_rand(array_slice($candidates, 0, max(1, (int) (count($candidates) / 2)), true))];
            } else {
                $chosen = $candidates[array_rand($candidates)];
            }

            $insertStmt->execute([
                ':username' => $currentUser, ':date' => $dateStr, ':type' => $type, ':meal_id' => $chosen['meal_id'],
            ]);
            $recentMealIds[] = (int) $chosen['meal_id'];
            $inserted++;
        }
        $date->modify('+1 day');
    }

    sendJsonResponse(['success' => true, 'entries_created' => $inserted, 'start_date' => $startDate, 'end_date' => $endDate]);
}

// ─────────────────────────────────────────────────────────────────────────
// Shopping list
// ─────────────────────────────────────────────────────────────────────────

/**
 * Aggregates ingredient quantities across the meal plan for the date range,
 * scaling by planned servings, and pairs each product with its cheapest
 * currently-known store price.
 */
function getShoppingList(PDO $pdo, array $input, string $currentUser) {
    [$startDate, $endDate] = resolveDateRange($input);

    $stmt = $pdo->prepare(
        "SELECT p.product_id, p.name AS product_name, p.unit, p.category,
                SUM(mi.quantity * (mp.servings_planned / m.servings)) AS total_quantity
         FROM gm_meal_plan mp
         JOIN gm_meals m ON m.meal_id = mp.meal_id
         JOIN gm_meal_ingredients mi ON mi.meal_id = m.meal_id
         JOIN gm_products p ON p.product_id = mi.product_id
         WHERE mp.username = :username AND mp.plan_date BETWEEN :start AND :end
         GROUP BY p.product_id, p.name, p.unit, p.category
         ORDER BY p.category, p.name"
    );
    $stmt->execute([':username' => $currentUser, ':start' => $startDate, ':end' => $endDate]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $priceStmt = $pdo->prepare(
        "SELECT pp.store_id, s.name AS store_name, pp.price
         FROM gm_product_prices pp
         JOIN gm_stores s ON s.store_id = pp.store_id
         WHERE pp.product_id = :pid
         AND pp.price_id IN (SELECT MAX(price_id) FROM gm_product_prices WHERE product_id = :pid2 GROUP BY store_id)
         ORDER BY pp.price ASC"
    );

    $total = 0.0;
    foreach ($items as &$item) {
        $priceStmt->execute([':pid' => $item['product_id'], ':pid2' => $item['product_id']]);
        $prices = $priceStmt->fetchAll(PDO::FETCH_ASSOC);
        $item['store_prices'] = $prices;
        $item['best_store']   = $prices[0]['store_name'] ?? null;
        $item['best_price']   = $prices[0]['price'] ?? null;
        $item['estimated_cost'] = $item['best_price'] !== null
            ? round($item['best_price'] * (float) $item['total_quantity'], 2)
            : null;
        if ($item['estimated_cost'] !== null) {
            $total += $item['estimated_cost'];
        }
    }
    unset($item);

    sendJsonResponse([
        'success'        => true,
        'items'          => $items,
        'estimated_total'=> round($total, 2),
        'start_date'     => $startDate,
        'end_date'       => $endDate,
    ]);
}

// ─────────────────────────────────────────────────────────────────────────
// Helpers
// ─────────────────────────────────────────────────────────────────────────

/**
 * Cheap, human-readable summary of what a scraper actually received, so a failed
 * scrape_price can be diagnosed from the UI without pasting raw page HTML by hand.
 */
function buildScrapeDebugInfo(?string $rawHtml): ?array {
    if ($rawHtml === null || $rawHtml === '') {
        return null;
    }

    preg_match('/<title[^>]*>(.*?)<\/title>/is', $rawHtml, $titleMatch);
    $lowerHtml = strtolower($rawHtml);

    $suspiciousMarkers = [
        'captcha', 'access denied', 'are you a human', 'unusual traffic',
        'px-captcha', 'cloudflare', 'request blocked', 'verify you are human',
    ];
    $foundMarkers = array_values(array_filter(
        $suspiciousMarkers,
        function ($marker) use ($lowerHtml) { return strpos($lowerHtml, $marker) !== false; }
    ));

    $textOnly = trim(preg_replace('/\s+/', ' ', strip_tags($rawHtml)));

    return [
        'html_length'           => strlen($rawHtml),
        'page_title'            => trim($titleMatch[1] ?? ''),
        'contains_product_card' => strpos($rawHtml, 'data-component="product-card"') !== false,
        'suspicious_markers'    => $foundMarkers,
        'text_snippet'          => mb_substr($textOnly, 0, 400),
    ];
}

/** @return array{0: string, 1: string} */
function resolveDateRange(array $input): array {
    $startDate = trim($input['start_date'] ?? '');
    $endDate   = trim($input['end_date'] ?? '');

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate)) {
        $startDate = date('Y-m-d', strtotime('monday this week'));
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
        $endDate = date('Y-m-d', strtotime($startDate . ' +6 days'));
    }

    return [$startDate, $endDate];
}
