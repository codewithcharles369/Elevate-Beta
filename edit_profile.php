<?php
require 'includes/db.php';
session_start();

$user_id = $_SESSION['user_id'];

// Fetch user's theme preference
$stmt = $pdo->prepare("SELECT theme FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$theme = $user['theme'] ?? 'light';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bio = $_POST['bio'];
    $social_links = $_POST['social_links'];
    $user_id = $_SESSION['user_id'];

    // Handle profile picture upload
    $profile_picture = $_FILES['profile_picture']['name'];
    if (!empty($profile_picture)) {
        $profile_picture = 'uploads/' . basename($_FILES['profile_picture']['name']);
        move_uploaded_file($_FILES['profile_picture']['tmp_name'], $profile_picture);
    }else {
        $profile_picture = 'assets/default-photo.png';
    }
    if (empty($bio)) {
        $bio = 'Hey there, I create blogs';
    }

    $stmt = $pdo->prepare("UPDATE users SET bio = ?, social_links = ?, profile_picture = ? WHERE id = ?");
    $stmt->execute([$bio, $social_links, $profile_picture, $user_id]);

    header('Location: dashboard.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <style>
        
        body {
            font-family: 'Arial', sans-serif;
            background: linear-gradient(to bottom right, #6a11cb, #2575fc);
            display: flex;
            justify-content: center;
            align-items: center;
            color: #fff;
        }
        
        /* Form Container */
form {
    width: 100%;
    background: rgba(255, 255, 255, 0.1);
    padding: 30px;
    border-radius: 10px;
    box-shadow: 0 4px 30px rgba(0, 0, 0, 0.2);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
}

/* Form Header */
h2 {
    text-align: center;
    margin-bottom: 20px;
    font-weight: 700;
    color: #fff;
}

/* Input Fields */
form label {
    display: block;
    margin-bottom: 8px;
    font-weight: bold;
    font-size: 14px;
}

form input {
    width: 100%;
    padding: 12px 15px;
    margin-bottom: 15px;
    border: none;
    border-radius: 5px;
    font-size: 14px;
}

form select {
    width: 100%;
    padding: 16px 20px;
    border: none;
    border-radius: 4px;
    background-color: #f1f1f1;
}

 
form button {
    width: 100%;
    padding: 12px 15px;
    border: none;
    border-radius: 5px;
    background: #2575fc;
    color: white;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.3s ease;
}



form button:hover {
    background: #1e63d4;
}

/* Links */
form a {
    display: block;
    margin-top: 15px;
    text-align: center;
    color: #ddd;
    font-size: 14px;
    text-decoration: none;
}

form a:hover {
    color: white;
    text-decoration: underline;
}

/* Error Message */
.error {
    color: red;
    font-size: 14px;
    margin-bottom: 15px;
    text-align: center;
}

/* Textarea */
form textarea {
    width: 100%;
    padding: 12px 15px;
    border: none;
    border-radius: 5px;
    font-size: 14px;
    resize: vertical;
    margin-bottom: 15px;
}
    </style>
</head>
<body style="height: 100vh; max-width">
    <form style=" max-width: 600px" action="edit_profile.php" method="POST" enctype="multipart/form-data">
        <button style="background-color: initial; width: 20%; color: whitesmoke; float: right;" type="submit">Skip</button><br>
        <h2 style='text-align: left;'>Edit Profile</h2>
        <textarea name="bio" placeholder="Your bio here..."></textarea>
        <input type="text" name="social_links" placeholder="Social links (e.g., Twitter, Instagram)">
        <input type="file" name="profile_picture">
        <button type="submit">Update Profile</button>
    </form>
</body>
</html>