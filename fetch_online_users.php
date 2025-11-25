<?php
include 'includes/db.php';

// Calculate the threshold time for being "online"
$threshold_time = date('Y-m-d H:i:s', time() - 300); // 5 minutes ago

// Fetch online users
$stmt = $pdo->prepare("
    SELECT id, name, profile_picture 
    FROM users 
    WHERE last_active >= ?
    ORDER BY name ASC
");
$stmt->execute([$threshold_time]);
$online_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['online_users' => $online_users]);
?>