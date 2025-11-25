<?php
include 'includes/db.php';

// Get the current date
$current_date = date('Y-m-d');

// Fetch the number of sign-ups for the current day
$stmt = $pdo->prepare("
    SELECT COUNT(*) AS daily_signups 
    FROM users 
    WHERE DATE(created_at) = ?
");
$stmt->execute([$current_date]);
$daily_signups = $stmt->fetchColumn();

echo json_encode(['daily_signups' => $daily_signups]);
?>