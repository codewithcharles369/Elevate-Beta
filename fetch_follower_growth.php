<?php
session_start();
include "includes/db.php";

$user_id = $_SESSION['user_id'];

// Get follower count by date
$stmt = $pdo->prepare("
    SELECT DATE(created_at) AS date, COUNT(*) AS count 
    FROM follows 
    WHERE following_id = ? 
    GROUP BY DATE(created_at)
    ORDER BY date ASC
");
$stmt->execute([$user_id]);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

$dates = array_column($data, 'date');
$counts = array_column($data, 'count');

echo json_encode(['dates' => $dates, 'counts' => $counts]);
?>