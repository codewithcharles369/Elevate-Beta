<?php
require 'includes/db.php';
session_start();

$user_id = $_SESSION['user_id'];
$group_id = $_GET['group_id'];

// 1. Fetch the user's followers
$stmtFollowers = $pdo->prepare("
    SELECT u.id, u.name, u.profile_picture
    FROM follows f
    JOIN users u ON f.follower_id = u.id
    WHERE f.following_id = ?
");
$stmtFollowers->execute([$user_id]);
$followers = $stmtFollowers->fetchAll(PDO::FETCH_ASSOC);

// 2. Fetch group members
$stmtGroupMembers = $pdo->prepare("
    SELECT user_id FROM group_members WHERE group_id = ?
");
$stmtGroupMembers->execute([$group_id]);
$groupMembers = $stmtGroupMembers->fetchAll(PDO::FETCH_COLUMN);

// 3. Exclude followers who are already group members
$nonMembers = [];
foreach ($followers as $follower) {
    if (!in_array($follower['id'], $groupMembers)) {
        $nonMembers[] = $follower;
    }
}

header('Content-Type: application/json');
echo json_encode($nonMembers);