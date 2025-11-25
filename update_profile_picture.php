<?php
session_start();
require 'includes/db.php';

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_FILES['profile_picture']['name'])) {
        $targetDir = "uploads/";
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $fileName = time() . "_" . basename($_FILES['profile_picture']['name']);
        $targetFilePath = $targetDir . $fileName;

        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (in_array($_FILES['profile_picture']['type'], $allowedTypes)) {
            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $targetFilePath)) {
                // Update database
                $stmt = $pdo->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
                $stmt->execute([$targetFilePath, $user_id]);

                echo json_encode(['success' => true, 'new_picture' => $targetFilePath]);
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
}
?>