<?php
header('Content-Type: application/json');
require_once '../../Private/db_config.php';//Replace to server db config file

// Get JSON POST data
$data = json_decode(file_get_contents("php://input"), true);

// Validate required fields
if (
    empty($data['id']) ||
    empty($data['title']) ||
    empty($data['url']) ||
    empty($data['category']) ||
    empty($data['dateAdded'])
) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing required fields.']);
    exit;
}

try {
    $sql = "INSERT INTO articles (
                id, title, url, author, publication, category, description, rating, date_added
            ) VALUES (
                :id, :title, :url, :author, :publication, :category, :description, :rating, :date_added
            )";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ':id' => $data['id'],
        ':title' => $data['title'],
        ':url' => $data['url'],
        ':author' => $data['author'] ?? null,
        ':publication' => $data['date'] ?? null,
        ':category' => $data['category'],
        ':description' => $data['description'] ?? null,
        ':rating' => $data['rating'] ?? null,
        ':date_added' => $data['dateAdded'],
    ]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
