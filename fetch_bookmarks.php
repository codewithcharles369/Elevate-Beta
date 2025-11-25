<?php
include 'includes/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$sort = $_GET['sort'] ?? 'date_desc';

// Sorting logic
$orderBy = "bookmarks.created_at DESC"; // Default sort
switch ($sort) {
    case 'date_asc':
        $orderBy = "bookmarks.created_at ASC";
        break;
    case 'title_asc':
        $orderBy = "posts.title ASC";
        break;
    case 'title_desc':
        $orderBy = "posts.title DESC";
        break;
}

// Fetch sorted bookmarks
$stmt = $pdo->prepare("
    SELECT posts.id, posts.title, posts.content, posts.media, posts.created_at, users.name AS name,
    posts.share_count AS share_count,
    (SELECT COUNT(*) FROM likes WHERE likes.post_id = posts.id) AS like_count,
    (SELECT COUNT(*) FROM bookmarks WHERE bookmarks.post_id = posts.id) AS bookmark_count,
    (SELECT COUNT(*) FROM comments WHERE post_id = posts.id) AS comment_count
    FROM bookmarks
    LEFT JOIN posts ON bookmarks.post_id = posts.id
    LEFT JOIN users ON posts.user_id = users.id
    WHERE bookmarks.user_id = ?
    ORDER BY $orderBy
");
$stmt->execute([$user_id]);
$bookmarks = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Send JSON response
echo json_encode(['success' => true, 'bookmarks' => $bookmarks]);
exit;
?>