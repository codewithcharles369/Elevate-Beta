<?php
include 'includes/db.php';
session_start();

$admin_id = $_SESSION['user_id'];

// Fetch notifications for the admin
$stmt = $pdo->prepare("
    SELECT notifications.id, notifications.message, notifications.is_read, notifications.created_at, 
           users.id AS sender_id, users.name AS sender_name
    FROM notifications
    JOIN users ON notifications.sender_id = users.id
    WHERE notifications.user_id = ?
    ORDER BY notifications.created_at DESC
");
$stmt->execute([$admin_id]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['notifications' => $notifications]);
?>