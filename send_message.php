<?php
include 'includes/db.php';

$data = json_decode(file_get_contents('php://input'), true);

$sender_id = $data['sender_id'];
$receiver_id = $data['receiver_id'];
$message = $data['message'];

if (!empty($sender_id) && !empty($receiver_id) && !empty($message)) {
    $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
    $stmt->execute([$sender_id, $receiver_id, $message]);


    // Add notification
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, sender_id, message) VALUES (?, ?, ?)");
    $stmt->execute([$receiver_id, $sender_id, $message]);

    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "error" => "Invalid input"]);
}
?>
