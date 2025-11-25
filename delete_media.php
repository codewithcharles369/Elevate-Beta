<?php
session_start();
require_once 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postId = intval($_POST['post_id']);
    $mediaName = trim($_POST['media_name']);
    $userId = $_SESSION['user_id'];

    // Fetch the post to verify ownership
    $stmt = $pdo->prepare("SELECT media FROM posts WHERE id = ? AND user_id = ?");
    $stmt->execute([$postId, $userId]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($post) {
        $mediaFiles = json_decode($post['media'], true);
        if (($key = array_search($mediaName, $mediaFiles)) !== false) {
            // Remove the media file
            unset($mediaFiles[$key]);
            $updatedMediaJson = json_encode(array_values($mediaFiles));

            // Update the post in the database
            $stmt = $pdo->prepare("UPDATE posts SET media = ? WHERE id = ? AND user_id = ?");
            if ($stmt->execute([$updatedMediaJson, $postId, $userId])) {
                // Delete the file from the server
                $filePath = 'uploads/' . $mediaName;
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
                echo json_encode(['success' => true, 'message' => 'Media deleted successfully.']);
                exit;
            }
        }
    }

    echo json_encode(['success' => false, 'message' => 'Failed to delete media.']);
    exit;
}
?>