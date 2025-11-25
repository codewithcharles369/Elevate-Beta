<?php
include 'includes/db.php';

$user_id = $_GET['user_id']; // The profile owner ID

    // Fetch followers
    $stmt = $pdo->prepare("SELECT COUNT(*) AS followers_count FROM follows WHERE following_id = ?");
    $stmt->execute([$user_id]);
    $followers_count = $stmt->fetch(PDO::FETCH_ASSOC)['followers_count'];

    // Fetch following
    $stmt = $pdo->prepare("SELECT COUNT(*) AS following_count FROM follows WHERE follower_id = ?");
    $stmt->execute([$user_id]);
    $following_count = $stmt->fetch(PDO::FETCH_ASSOC)['following_count'];

// Fetch followers
$stmt = $pdo->prepare("
    SELECT u.id, u.name, u.profile_picture 
    FROM follows f 
    JOIN users u ON f.follower_id = u.id 
    WHERE f.following_id = ?
");
$stmt->execute([$user_id]);
$followers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch following
$stmt = $pdo->prepare("
    SELECT u.id, u.name, u.profile_picture 
    FROM follows f 
    JOIN users u ON f.following_id = u.id 
    WHERE f.follower_id = ?
");
$stmt->execute([$user_id]);
$following = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'followers_count' => $followers_count,
    'following_count' => $following_count,
    'followers' => $followers,
    'following' => $following
]);
?>