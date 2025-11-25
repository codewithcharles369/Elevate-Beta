<?php
include 'includes/db.php';
session_start();

$user_id = $_SESSION['user_id'];

// Get form data
$name = $_POST['name'];
$bio = $_POST['bio'];
$social_links = $_POST['social_links'];
$email = $_POST['email'];
$password = $_POST['password'];

// Handle profile picture upload
$profile_picture_path = null;
if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
    $target_dir = "uploads/";
    $file_name = basename($_FILES['profile_picture']['name']);
    $target_file = $target_dir . time() . "_" . $file_name;

    // Move uploaded file
    if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $target_file)) {
        $profile_picture_path = $target_file;
    }
}

// Update user profile
$stmt = $pdo->prepare("
    UPDATE users SET 
        name = ?, 
        bio = ?, 
        social_links = ?, 
        email = ?, 
        password = IF(? != '', ?, password),
        profile_picture = IF(? IS NOT NULL, ?, profile_picture)
    WHERE id = ?
");
$stmt->execute([
    $name,
    $bio,
    $social_links,
    $email,
    $password ? password_hash($password, PASSWORD_BCRYPT) : null,
    $password ? password_hash($password, PASSWORD_BCRYPT) : null,
    $profile_picture_path,
    $profile_picture_path,
    $user_id
]);

if ($stmt->rowCount() > 0) {
    echo json_encode(['success' => true, 'message' => 'Profile updated successfully, re-login for updates to load correctly']);
} else {
    echo json_encode(['success' => false, 'message' => 'No changes were made.']);
}
?>