<?php
session_start();
include "includes/db.php";

$user_id = $_SESSION['user_id'];

// Get posts grouped by day and hour
$stmt = $pdo->prepare("
    SELECT DAYNAME(created_at) AS day, HOUR(created_at) AS hour, COUNT(*) AS count 
    FROM posts 
    WHERE user_id = ? 
    GROUP BY day, hour
");
$stmt->execute([$user_id]);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Format data for the heatmap
$days = ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];
$hours = range(0, 23);
$heatmapData = array_fill(0, 24, array_fill(0, 7, 0)); // 24 hours x 7 days

foreach ($data as $row) {
    $dayIndex = array_search($row['day'], $days);
    $hour = (int)$row['hour'];
    $heatmapData[$hour][$dayIndex] = (int)$row['count'];
}

echo json_encode(['days' => $days, 'hours' => $heatmapData]);
?>