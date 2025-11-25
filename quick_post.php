<?php
session_start();
include "includes/db.php"; // Adjust if your DB connection file is different

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'You must be logged in to post.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);

    // Validate input
    if (empty($title) || empty($content)) {
        echo json_encode(['success' => false, 'message' => 'Both title and content are required.']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO posts (user_id, title, content, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$user_id, $title, $content]);

        echo json_encode(['success' => true, 'message' => 'Post created successfully.']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
}
?>