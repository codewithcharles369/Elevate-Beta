<?php
include 'includes/db.php';
session_start();

$user_id = $_SESSION['user_id'];

// Set the account as deactivated and store the timestamp
$stmt = $pdo->prepare("UPDATE users SET is_active = 0, deactivated_at = NOW() WHERE id = ?");
$stmt->execute([$user_id]);

if ($stmt->rowCount() > 0) {
    echo json_encode(['success' => true, 'message' => 'Account deactivated successfully. You can log in within 7 days to reactivate it.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to deactivate account.']);
}
?>