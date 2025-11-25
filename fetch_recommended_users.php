<?php
session_start();
include "includes/db.php";

$current_user_id = $_SESSION['user_id'];

// Fetch users who are not followed yet, with mutual groups and profile picture
$stmt = $pdo->prepare("
    SELECT u.id, u.name, u.profile_picture,
        (SELECT COUNT(*) FROM group_members gm1
         JOIN group_members gm2 ON gm1.group_id = gm2.group_id
         WHERE gm1.user_id = u.id AND gm2.user_id = ?) AS mutual_groups
    FROM users u
    WHERE u.id != ? 
      AND u.id NOT IN (SELECT following_id FROM follows WHERE follower_id = ?)
    ORDER BY mutual_groups DESC
    LIMIT 5
");
$stmt->execute([$current_user_id, $current_user_id, $current_user_id]);
$recommendedUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($recommendedUsers);
?>