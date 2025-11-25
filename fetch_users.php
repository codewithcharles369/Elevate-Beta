<?php
include 'includes/db.php';

$search = isset($_GET['search']) ? $_GET['search'] : '';

$stmt = $pdo->prepare("
    SELECT id, name, profile_picture 
    FROM users 
    WHERE name LIKE ? 
    ORDER BY name ASC
");
$stmt->execute(['%' . $search . '%']);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($users);
?>