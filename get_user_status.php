<?php
include 'includes/db.php';

$user_id = $_GET['user_id'];

// Retrieve the user's last_active timestamp
$stmt = $pdo->prepare("SELECT last_active FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$last_active = $stmt->fetchColumn();

if ($last_active) {
    $current_time = new DateTime();
    $last_active_time = new DateTime($last_active);
    $interval = $current_time->getTimestamp() - $last_active_time->getTimestamp();

    if ($interval <= 10) { // 300 seconds = 5 minutes
        echo json_encode(['status' => 'online']);
    } else {
        // Format the last active time
        echo json_encode([
            'status' => 'offline',
            'last_online' => $last_active_time->format('Y-m-d H:i:s')
        ]);
    }
} else {
    echo json_encode([
        'status' => 'offline',
        'last_online' => null
    ]);
}
?>