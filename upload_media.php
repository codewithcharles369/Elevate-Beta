<?php
session_start();
require 'includes/db.php';

$response = ['success' => false];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sender_id = $_SESSION['user_id'];
    $receiver_id = $_POST['receiver_id'];
    $message = $_POST['message'] ?? '';
    $reply_to = $_POST['reply_to'] ?? null; // Get the ID of the message being replied to
    $uploaded_files = []; // To store file URLs

    // Handle file uploads as usual
    if (isset($_FILES['media']) && count(array_filter($_FILES['media']['name'])) > 0) {
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'mp4', 'mov', 'avi', 'mkv', 'pdf', 'doc', 'docx', 'txt'];
        $upload_dir = 'uploads/';
        foreach ($_FILES['media']['name'] as $index => $file_name) {
            $file_tmp = $_FILES['media']['tmp_name'][$index];
            $file_size = $_FILES['media']['size'][$index];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            if (in_array($file_ext, $allowed_extensions)) {
                if ($file_size <= 1024 * 1024 * 1024) { // Limit file size to 10MB
                    $new_file_name = uniqid('media_', true) . '.' . $file_ext;
                    $upload_path = $upload_dir . $new_file_name;

                    if (move_uploaded_file($file_tmp, $upload_path)) {
                        $uploaded_files[] = $upload_path;
                    } else {
                        $response['message'] = 'Failed to upload one or more files.';
                        echo json_encode($response);
                        exit;
                    }
                } else {
                    $response['message'] = 'One or more files exceed the 10MB limit.';
                    echo json_encode($response);
                    exit;
                }
            } else {
                $response['message'] = 'Invalid file type in one or more files.';
                echo json_encode($response);
                exit;
            }
        }
    }

    // Save the message, file URLs, and reply_to in the database
    $media_urls = implode(',', $uploaded_files); // Save as a comma-separated string
    $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message, media, reply_to, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->execute([$sender_id, $receiver_id, $message, $media_urls, $reply_to]);

    $response['success'] = true;
    $response['message'] = 'Message sent successfully.';
}

echo json_encode($response);