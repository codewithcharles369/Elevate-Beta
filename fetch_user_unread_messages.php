<?php
require_once 'includes/db.php';
session_start();

if (!isset($_SESSION['user_id']) || !isset($_GET['profile_user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$currentUserId = $_SESSION['user_id'];
$profileUserId = intval($_GET['profile_user_id']);

try {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) AS unread_count
        FROM messages
        WHERE sender_id = ? AND receiver_id = ? AND is_read = 0
    ");
    $stmt->execute([$profileUserId, $currentUserId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'unread_count' => $result['unread_count']]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>