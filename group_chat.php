<?php
session_start();
include 'includes/db.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'];

if ($action === 'send_message') {
    // Handle sending a message
    $group_id = $data['group_id'];
    $message = $data['message'];
    $user_id = $_SESSION['user_id'];

    // Validate input
    if (empty($message)) {
        echo json_encode(['success' => false, 'error' => 'Message cannot be empty.']);
        exit;
    }

    // Insert the message into the database
    $stmt = $pdo->prepare("INSERT INTO group_messages (group_id, user_id, message) VALUES (?, ?, ?)");
    $stmt->execute([$group_id, $user_id, $message]);

    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'fetch_messages') {
    // Handle fetching messages
    $group_id = $data['group_id'];

    $stmt = $pdo->prepare("
        SELECT gm.message, gm.created_at, u.name, u.profile_picture, gm.user_id
        FROM group_messages gm
        JOIN users u ON gm.user_id = u.id
        WHERE gm.group_id = ?
        ORDER BY gm.created_at ASC
    ");
    $stmt->execute([$group_id]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'messages' => $messages]);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Invalid action.']);