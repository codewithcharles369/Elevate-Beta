<?php
include 'includes/db.php';
session_start();

$user_id = $_SESSION['user_id']; // Logged-in user

// Query to fetch unread notifications for the user
$stmt = $pdo->prepare("
    SELECT notifications.id, notifications.message, notifications.is_read,
    notifications.created_at, notifications.link, 
    users.id AS sender_id, users.name AS sender_name, users.profile_picture
    FROM notifications
    JOIN users ON notifications.sender_id = users.id
    WHERE notifications.user_id = ? AND notifications.is_read = 0
    ORDER BY notifications.created_at DESC
");
$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['notifications' => $notifications]);
?>