<?php
session_start();
require 'includes/db.php';

$group_id = intval($_POST['group_id']);
$user_id = $_SESSION['user_id'];

// Check if the user is the group admin
$stmt = $pdo->prepare("SELECT created_by FROM groups WHERE id = ?");
$stmt->execute([$group_id]);
$group = $stmt->fetch();

if (!$group || $group['created_by'] != $user_id) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized action.']);
    exit;
}

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_FILES['group_image']['name'])) {
    $targetDir = "uploads/";
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    $fileName = time() . "_" . basename($_FILES['group_image']['name']);
    $targetFilePath = $targetDir . $fileName;

    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    if (in_array($_FILES['group_image']['type'], $allowedTypes)) {
        if (move_uploaded_file($_FILES['group_image']['tmp_name'], $targetFilePath)) {
            // Update database
            $stmt = $pdo->prepare("UPDATE groups SET image = ? WHERE id = ?");
            $stmt->execute([$targetFilePath, $group_id]);

            echo json_encode(['success' => true, 'new_image' => $targetFilePath]);
            exit;
        } else {
            echo json_encode(['success' => false, 'message' => 'Upload failed.']);
            exit;
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid file type.']);
        exit;
    }
}
?>