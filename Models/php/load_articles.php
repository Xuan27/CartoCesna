<?php
header('Content-Type: application/json');
//Config file from the private folder
require_once '../../Private/db_config.php';

try {
    $stmt = $pdo->query("SELECT * FROM articles ORDER BY date_added DESC");
    $articles = $stmt->fetchAll();
    echo json_encode($articles);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>