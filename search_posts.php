<?php
include 'includes/db.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$query = $data['query'] ?? '';

// Debugging
file_put_contents('debug.log', "Search query: " . $query . PHP_EOL, FILE_APPEND);

if (empty($query)) {
    echo json_encode([]);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM posts WHERE title LIKE ? OR content LIKE ? OR hashtags LIKE ?");
$searchTerm = "%$query%";
$stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($results);