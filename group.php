<?php
include 'includes/db.php'; // Database pdoection
session_start();

$userId = $_SESSION['user_id']; // Assume the user is logged in

// Fetch user's theme preference
$stmt = $pdo->prepare("SELECT theme FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$theme = $user['theme'] ?? 'light';

// Fetch user details
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch the count of unresolved reports
$stmt = $pdo->prepare("SELECT COUNT(*) AS report_count FROM reports WHERE status = 'unresolved'");
$stmt->execute();
$report = $stmt->fetch();
$report_count = $report['report_count'];
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;

    $user_id = $_GET['id'] ?? $_SESSION['user_id'];

}


// Validate group ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Group ID is required.");
}

$groupId = intval($_GET['id']);
$userId = $_SESSION['user_id'] ?? null; // Check if the user is logged in

// Fetch group details
$stmt = $pdo->prepare("SELECT * FROM groups WHERE id = ?");
$stmt->execute([$groupId]);
$group = $stmt->fetch();

if (!$group) {
    die("Group not found.");
}

// Check if the user is a member
$membershipStmt = $pdo->prepare("SELECT role FROM group_members WHERE group_id = ? AND user_id = ?");
$membershipStmt->execute([$groupId, $userId]);
$membership = $membershipStmt->fetch();
$isMember = $membership ? true : false;
$userRole = $membership['role'] ?? null;

// Fetch group members
$searchMember = $_GET['search_member'] ?? '';
$membersStmt = $pdo->prepare("
    SELECT u.id, u.name, u.profile_picture, gm.role 
    FROM group_members gm 
    JOIN users u ON gm.user_id = u.id 
    WHERE gm.group_id = ? 
    AND u.name LIKE ? 
    ORDER BY gm.role ASC, u.name ASC
");
$membersStmt->execute([$groupId, "%$searchMember%"]);
$members = $membersStmt->fetchAll();

$stmt = $pdo->prepare("
    SELECT g.*, 
           (SELECT COUNT(*) FROM group_members gm WHERE gm.group_id = g.id) AS member_count 
    FROM groups g
    WHERE g.id = ?
");
$stmt->execute([$groupId]);
$group = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_group'])) {
    $newName = trim($_POST['group_name']);
    $newDescription = trim($_POST['group_description']);
    $newImage = null;

    // Validate inputs
    if (empty($newName) || empty($newDescription)) {
        $error = "Group name and description are required.";
    } else {
        // Handle image upload
        if (!empty($_FILES['group_image']['name'])) {
            $targetDir = "uploads/";
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0777, true);
            }
            $fileName = time() . "_" . basename($_FILES['group_image']['name']);
            $targetFilePath = $targetDir . $fileName;

            // Validate file type
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            if (in_array($_FILES['group_image']['type'], $allowedTypes)) {
                if (move_uploaded_file($_FILES['group_image']['tmp_name'], $targetFilePath)) {
                    $newImage = $targetFilePath;
                } else {
                    $error = "Failed to upload image.";
                }
            } else {
                $error = "Invalid file type. Only JPG, PNG, and GIF are allowed.";
            }
        }

        // Update group details in the database
        if (empty($error)) {
            $updateStmt = $pdo->prepare("UPDATE groups SET name = ?, description = ?, image = ? WHERE id = ?");
            $updateStmt->execute([$newName, $newDescription, $newImage ?? $group['image'], $groupId]);

            $success = "Group details updated successfully.";
            header("Refresh:0"); // Refresh the page to show updated details
            exit;
        }
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_group'])) {
    // Delete group posts and associated media
    $postsStmt = $pdo->prepare("SELECT media FROM group_posts WHERE group_id = ?");
    $postsStmt->execute([$groupId]);
    $posts = $postsStmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($posts as $post) {
        if (!empty($post['media']) && file_exists($post['media'])) {
            unlink($post['media']); // Delete media file
        }
    }
    $pdo->prepare("DELETE FROM group_posts WHERE group_id = ?")->execute([$groupId]);

    // Delete group members
    $pdo->prepare("DELETE FROM group_members WHERE group_id = ?")->execute([$groupId]);

    // Delete the group itself
    $pdo->prepare("DELETE FROM groups WHERE id = ?")->execute([$groupId]);

    // Redirect to the groups page
    header("Location: groups.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM group_posts WHERE group_id = ? ORDER BY pinned DESC, created_at DESC");
$stmt->execute([$group['id']]);
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<?php
        $stmt = $pdo->prepare("SELECT * FROM group_events WHERE group_id = ? AND event_date > NOW() ORDER BY event_date ASC");
        $stmt->execute([$group['id']]);
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
        ?>
<?php
$stmt = $pdo->prepare("SELECT * FROM group_polls WHERE group_id = ? ORDER BY created_at DESC");
$stmt->execute([$group['id']]);
$polls = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<?php
$stmtChallenges = $pdo->prepare("SELECT * FROM group_challenges WHERE group_id = ? ORDER BY created_at DESC");
$stmtChallenges->execute([$group['id']]);
$challenges = $stmtChallenges->fetchAll(PDO::FETCH_ASSOC);

$stmtCheckRequest = $pdo->prepare("SELECT id FROM group_join_requests WHERE group_id = ? AND user_id = ?");
$stmtCheckRequest->execute([$groupId, $userId]);
$hasRequested = $stmtCheckRequest->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($group['name']) ?> - Group Details</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="icon" href="assets/elevate.jpg" type="image/x-icon">
    <style>
        *{
            color: back;
        }
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f6f9;
            color: #333;
        }

        .group-container {
            width: 100%;
        }

        .group-header {
    position: relative;
    text-align: center;
    padding: 80px 20px;
    background: linear-gradient(to right, #6a11cb, #2575fc);
    color: white;
    border-radius: 12px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
}

.group-header img {
    width: 130px;
    height: 130px;
    border-radius: 50%;
    border: 4px solid white;
    margin-top: -80px;
    transition: transform 0.3s ease;
    object-fit: cover;
}

.group-header img:hover {
    transform: scale(1.08);
}

.group-header h1 {
    font-size: 2rem;
    font-weight: bold;
    margin-top: 10px;
}

.group-header p {
    font-size: 1rem;
    opacity: 0.9;
    margin: 5px 0;
}

.group-content {
    background: #ffffff;
    margin-top: -40px;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.05);
}

.group-content h2 {
    font-size: 1.8rem;
    margin-bottom: 12px;
    color: #6a0dad;
}

.join-group-btn, .group-posts-btn, .group-admin-btn {
    background-color: #6a0dad;
    color: white;
    padding: 12px 20px;
    border-radius: 8px;
    font-weight: bold;
    border: none;
    transition: all 0.3s ease;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
}

.join-group-btn:hover, .group-posts-btn:hover, .group-admin-btn:hover {
    background-color: #4a0072;
    transform: translateY(-2px);
}

.leave-group-btn {
    background-color: #ff4d4d;
    color: white;
    padding: 12px 20px;
    border-radius: 8px;
    font-weight: bold;
    border: none;
    transition: all 0.3s ease;
    cursor: pointer;
    display: inline-block;
}

.leave-group-btn:hover {
    background-color: #c0392b;
    transform: translateY(-2px);
}

.group-members {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 16px;
    margin-top: 20px;
}

.member {
    background: #ffffff;
    padding: 15px;
    border-radius: 12px;
    text-align: center;
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.04);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.member:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 24px rgba(0, 0, 0, 0.08);
}

.member img {
    width: 70px;
    height: 70px;
    object-fit: cover;
    border-radius: 50%;
    margin-bottom: 8px;
}

.search-container {
    display: flex;
    align-items: center;
    justify-content: center;
    margin-top: 16px;
}

.search-container input {
    width: 60%;
    padding: 10px;
    border-radius: 20px;
    border: 1px solid #ddd;
    font-size: 1rem;
    outline: none;
}

.search-container button {
    background-color: #6a0dad;
    color: white;
    padding: 10px 18px;
    border: none;
    border-radius: 20px;
    cursor: pointer;
    transition: background-color 0.3s ease;
    margin-left: 8px;
}

.search-container button:hover {
    background-color: #4a0072;
}

.group-members {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 16px;
    margin-top: 20px;
}

.member {
    background: #ffffff;
    padding: 15px;
    border-radius: 12px;
    text-align: center;
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.04);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.member:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 24px rgba(0, 0, 0, 0.08);
}

.member img {
    width: 70px;
    height: 70px;
    object-fit: cover;
    border-radius: 50%;
    margin-bottom: 8px;
}

body.dark-mode .group-header {
    background: linear-gradient(to right, #3a3a3a, #1e1e1e);
}

body.dark-mode .group-content,
body.dark-mode .member {
    background-color: #1e1e1e;
    color: #ffffff;
}

body.dark-mode .search-container input {
    background-color: #2a2a2a;
    color: #ffffff;
    border-color: #555;
}

body.dark-mode .search-container button {
    background-color: #6a0dad;
}

/* 🔲 Modal Background */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(5px);
    overflow-y: auto;
    color: black;
}

/* 📦 Modal Content */
.modal-content {
    background: white;
    padding: 20px;
    border-radius: 12px;
    width: 90%;
    margin: 10% auto;
    text-align: center;
    box-shadow: 0px 10px 20px rgba(0, 0, 0, 0.2);
    position: relative;
    
   
}

/* ❌ Close Button */
.close-modal {
    position: absolute;
    right: 15px;
    top: 10px;
    font-size: 20px;
    cursor: pointer;
    background: none;
    border: none;
    color: #555;
}

.close-modal:hover {
    color: #ff5f5f;
}

/* 🖼️ Group Image in Modal */
#modalGroupImage {
    width: 100%;
    height: 100%;
    border-radius: 10px;
    border: 4px solid #ddd;
    margin: 15px 0;
}

/* 📂 File Input */
#groupImageInput {
    margin: 15px 0;
    color: grey;
}

/* ✅ Upload Button */
button {
    background: linear-gradient(135deg, #6a11cb, #2575fc);
    color: white;
    border: none;
    padding: 10px 15px;
    border-radius: 8px;
    font-size: 16px;
    cursor: pointer;
    transition: background 0.3s;
}

button:hover {
    background: linear-gradient(135deg, #4a4aad, #1b5fd1);
}

.group-update-section {
    margin-top: 20px;
    padding: 20px;
    background: #f9f9f9;
    border-radius: 10px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.group-update-section h3 {
    margin-bottom: 15px;
    font-size: 1.5rem;
    color: #34495e;
}

.group-update-section label {
    display: block;
    margin-bottom: 5px;
    font-size: 1rem;
    color: #7f8c8d;
}

.group-update-section input, .group-update-section textarea {
    width: 100%;
    padding: 10px;
    margin-bottom: 15px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 1rem;
}

.group-update-section button {
    display: inline-block;
    background-color: #6c63ff;
    color: white;
    border: none;
    border-radius: 5px;
    padding: 10px 20px;
    font-size: 1rem;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.group-update-section button:hover {
    background-color: #4a4aad;
}

.group-info-card {
    background-color: #ffffff;
    border-radius: 14px;
    padding: 24px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.06);
    margin-top: -30px;
    margin-bottom: 24px;
    text-align: center;
    transition: background-color 0.3s ease, color 0.3s ease;
}

.group-info-card h2 {
    font-size: 1.8rem;
    color: #6a0dad;
    margin-bottom: 8px;
}

.group-info-card .group-description {
    font-size: 1rem;
    color: #444;
    margin-bottom: 12px;
    line-height: 1.6;
}

.group-info-card .group-meta {
    font-size: 0.95rem;
    color: #777;
}

.group-info-card span#member-count {
    font-weight: bold;
    color: #333;
}

/* Dark Mode */
body.dark-mode .group-info-card {
    background-color: #1e1e1e;
    color: #f5f5f5;
}

body.dark-mode .group-info-card h2 {
    color: #bb86fc;
}

body.dark-mode .group-info-card .group-description {
    color: #ddd;
}

body.dark-mode .group-info-card .group-meta {
    color: #aaa;
}

body.dark-mode .group-info-card span#member-count {
    color: #ffffff;
}

.group-info-card {
    opacity: 0;
    transform: translateY(15px);
    animation: fadeInGroupCard 0.7s ease-out forwards;
}

@keyframes fadeInGroupCard {
    0% { opacity: 0; transform: translateY(15px); }
    100% { opacity: 1; transform: translateY(0); }
}

/* Member Cards Container */
.group-members {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 16px;
    margin-top: 20px;
}

/* Individual Member Card */
.member-card {
    background-color: #ffffff;
    border-radius: 14px;
    padding: 16px;
    text-align: center;
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.05);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    position: relative;
    overflow: hidden;
}

.member-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 12px 28px rgba(0, 0, 0, 0.1);
}

/* Profile Picture */
.member-card img {
    width: 70px;
    height: 70px;
    object-fit: cover;
    border-radius: 50%;
    margin-bottom: 8px;
    border: 3px solid #f3f3f3;
    transition: transform 0.3s ease;
}

.member-card:hover img {
    transform: scale(1.1);
}

/* Member Name */
.member-card h4 {
    font-size: 1rem;
    color: #6a0dad;
    margin-bottom: 4px;
}

/* Member Role */
.member-card .member-role {
    font-size: 0.85rem;
    color: #777;
    margin-bottom: 10px;
}

/* View Profile Button */
.view-profile-btn {
    background-color: #6a0dad;
    color: white;
    text-decoration: none;
    padding: 8px 14px;
    border-radius: 8px;
    font-size: 0.85rem;
    transition: background-color 0.3s ease, transform 0.2s ease;
    display: inline-block;
}

.view-profile-btn:hover {
    background-color: #4a0072;
    transform: translateY(-2px);
}

/* Animation on load */
.member-card {
    opacity: 0;
    transform: translateY(15px);
    animation: fadeInMemberCard 0.8s ease-out forwards;
}

@keyframes fadeInMemberCard {
    0% { opacity: 0; transform: translateY(15px); }
    100% { opacity: 1; transform: translateY(0); }
}

/* Dark Mode Styles */
body.dark-mode .member-card {
    background-color: #1e1e1e;
    color: #f5f5f5;
}

body.dark-mode .member-card h4 {
    color: #bb86fc;
}

body.dark-mode .member-card .member-role {
    color: #aaa;
}

body.dark-mode .view-profile-btn {
    background-color: #bb86fc;
    color: #1e1e1e;
}

body.dark-mode .view-profile-btn:hover {
    background-color: #d8b4fe;
    color: #1e1e1e;
}
/* Floating Action Button (FAB) */
.fab-container {
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 1000;
    display: flex;
    flex-direction: column;
    align-items: flex-end;
}

.fab-main-btn {
    background-color: #6a0dad;
    color: white;
    border: none;
    border-radius: 50%;
    width: 56px;
    height: 56px;
    font-size: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
    transition: transform 0.3s ease, background-color 0.3s ease;
}

.fab-main-btn:hover {
    background-color: #4a0072;
    transform: rotate(45deg);
}

/* Hidden by default */
.fab-options {
    display: none;
    flex-direction: column;
    align-items: flex-end;
    gap: 12px;
    margin-bottom: 8px;
}

.fab-option-btn {
    background-color: #ffffff;
    color: #6a0dad;
    border: none;
    border-radius: 50px;
    padding: 12px 18px;
    font-size: 14px;
    text-decoration: none;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    display: flex;
    align-items: center;
    gap: 8px;
    transition: background-color 0.3s ease, transform 0.2s ease;
}

.fab-option-btn i {
    font-size: 16px;
    color: #6a0dad;
}

.fab-option-btn:hover {
    background-color: #f3e5f5;
    transform: translateY(-2px);
}

/* Dark Mode Styles */
body.dark-mode .fab-main-btn {
    background-color: #bb86fc;
    color: #121212;
}

body.dark-mode .fab-main-btn:hover {
    background-color: #9c6eff;
}

body.dark-mode .fab-option-btn {
    background-color: #2a2a2a;
    color: #bb86fc;
}

body.dark-mode .fab-option-btn i {
    color: #bb86fc;
}

body.dark-mode .fab-option-btn:hover {
    background-color: #3a3a3a;
}
.group-announcement {
    background-color: #f4f3ff;
    padding: 16px;
    border-radius: 8px;
    margin-top: 16px;
    font-size: 0.95rem;
    border-left: 4px solid #6a0dad;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}

.announcement-edit textarea {
    width: 100%;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 10px;
    font-size: 0.9rem;
}

.announcement-edit button {
    background-color: #6a0dad;
    color: white;
    border: none;
    padding: 8px 14px;
    border-radius: 6px;
    cursor: pointer;
    margin-top: 8px;
    transition: background-color 0.3s ease;
}

.announcement-edit button:hover {
    background-color: #4a0072;
}

/* Dark Mode */
body.dark-mode .group-announcement {
    background-color: #2a2a2a;
    color: #f5f5f5;
    border-left-color: #bb86fc;
}

body.dark-mode .announcement-edit textarea {
    background-color: #2a2a2a;
    color: white;
    border-color: #444;
}
.group-event-form, .group-events {
    background-color: #ffffff;
    padding: 16px;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    margin-bottom: 20px;
}

.group-event-form input, .group-event-form textarea {
    width: 100%;
    padding: 10px;
    margin-bottom: 10px;
    border-radius: 8px;
    border: 1px solid #ddd;
}

.event-card {
    background-color: #f9f9f9;
    padding: 12px;
    border-radius: 8px;
    margin-bottom: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.03);
}

/* Dark Mode */
body.dark-mode .group-event-form, body.dark-mode .group-events, body.dark-mode .event-card {
    background-color: #1e1e1e;
    color: white;
}

.group-poll-form, .group-polls {
    background-color: #ffffff;
    padding: 16px;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    margin-bottom: 20px;
}

.poll-card {
    background-color: #f9f9f9;
    padding: 12px;
    border-radius: 8px;
    margin-bottom: 12px;
}

.poll-card .poll-question{

}

.group-polls h3 {
    font-size: 1.5rem;
    color: #6a0dad;
    margin-bottom: 16px;
    text-align: center;
}

.poll-card button {
    background-color: #6a0dad;
    color: white;
    border: none;
    padding: 8px 14px;
    border-radius: 6px;
    margin: 4px;
    cursor: pointer;
}

/* Dark Mode */
body.dark-mode .group-poll-form, body.dark-mode .group-polls, body.dark-mode .poll-card {
    background-color: #1e1e1e;
    color: white;
}

/* Poll Creation Form Container */
.group-poll-form {
    background-color: #ffffff;
    padding: 24px;
    border-radius: 12px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.06);
    margin-bottom: 24px;
    transition: background-color 0.3s ease, color 0.3s ease;
}

.group-poll-form h3 {
    font-size: 1.5rem;
    color: #6a0dad;
    margin-bottom: 16px;
    text-align: center;
}

.group-poll-form .input-group {
    margin-bottom: 14px;
}

.group-poll-form label {
    font-weight: bold;
    font-size: 0.95rem;
    margin-bottom: 6px;
    display: block;
    color: #333;
}

.group-poll-form input[type="text"],
.group-poll-form textarea {
    width: 100%;
    padding: 12px;
    font-size: 0.95rem;
    border: 1px solid #ddd;
    border-radius: 8px;
    background-color: #f9f9f9;
    transition: border-color 0.3s ease;
}

.group-poll-form input[type="text"]:focus,
.group-poll-form textarea:focus {
    outline: none;
    border-color: #6a0dad;
    background-color: #fff;
    box-shadow: 0 4px 12px rgba(106, 13, 173, 0.1);
}

.group-poll-form textarea {
    resize: none;
    height: 80px;
}

.create-poll-btn {
    background-color: #6a0dad;
    color: white;
    border: none;
    border-radius: 8px;
    padding: 12px 18px;
    font-size: 1rem;
    font-weight: bold;
    cursor: pointer;
    transition: background-color 0.3s ease, transform 0.2s ease;
    width: 100%;
}

.create-poll-btn:hover {
    background-color: #4a0072;
    transform: translateY(-2px);
}

/* Dark Mode */
body.dark-mode .group-poll-form {
    background-color: #1e1e1e;
    color: #f5f5f5;
}

body.dark-mode .group-poll-form label {
    color: #f5f5f5;
}

body.dark-mode .group-poll-form input[type="text"],
body.dark-mode .group-poll-form textarea {
    background-color: #2a2a2a;
    border-color: #444;
    color: white;
}

body.dark-mode .group-poll-form input[type="text"]:focus,
body.dark-mode .group-poll-form textarea:focus {
    border-color: #bb86fc;
    box-shadow: 0 4px 12px rgba(187, 134, 252, 0.2);
}
.poll-option {
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}

.vote-btn {
    background-color: #6a0dad;
    color: white;
    border: none;
    border-radius: 6px;
    padding: 8px 12px;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.vote-btn:hover {
    background-color: #4a0072;
}

.vote-count {
    font-size: 0.9rem;
    color: #555;
}

.vote-bar {
    height: 8px;
    background-color: #e0e0e0;
    border-radius: 4px;
    overflow: hidden;
    width: 100%;
    margin-top: 4px;
}

.vote-bar-fill {
    height: 100%;
    background-color: #6a0dad;
    transition: width 0.3s ease;
}

.total-votes {
    font-size: 0.9rem;
    color: #777;
    margin-top: 8px;
}

/* Dark Mode */
body.dark-mode .vote-count,
body.dark-mode .total-votes {
    color: #ddd;
}

body.dark-mode .vote-bar {
    background-color: #444;
}

body.dark-mode .vote-bar-fill {
    background-color: #bb86fc;
}

.challenge-card {
    background-color: #ffffff;
    padding: 16px;
    border-radius: 10px;
    margin-bottom: 12px;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
    color: initial;
}

body.dark-mode .challenge-card {
    background-color: #1e1e1e;
    color: white;
}


/* Group Challenge Form Container */
.challenge-form {
    background-color: #ffffff;
    padding: 24px;
    border-radius: 12px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.06);
    margin-bottom: 24px;
    transition: background-color 0.3s ease, color 0.3s ease;
}

.challenge-form h3 {
    font-size: 1.5rem;
    color: #6a0dad;
    margin-bottom: 18px;
    text-align: center;
}

.challenge-form .input-group {
    margin-bottom: 16px;
}

.challenge-form label {
    font-weight: 600;
    font-size: 0.95rem;
    margin-bottom: 6px;
    display: block;
    color: #333;
}

.challenge-form input[type="text"],
.challenge-form textarea,
.challenge-form input[type="datetime-local"] {
    width: 100%;
    padding: 12px;
    font-size: 0.95rem;
    border: 1px solid #ddd;
    border-radius: 8px;
    background-color: #f9f9f9;
    transition: border-color 0.3s ease;
}

.challenge-form input:focus,
.challenge-form textarea:focus {
    outline: none;
    border-color: #6a0dad;
    background-color: #fff;
    box-shadow: 0 4px 12px rgba(106, 13, 173, 0.1);
}

.challenge-form textarea {
    height: 100px;
    resize: none;
}

.create-challenge-btn {
    background-color: #6a0dad;
    color: white;
    border: none;
    border-radius: 8px;
    padding: 12px 18px;
    font-size: 1rem;
    font-weight: bold;
    cursor: pointer;
    transition: background-color 0.3s ease, transform 0.2s ease;
    width: 100%;
}

.create-challenge-btn:hover {
    background-color: #4a0072;
    transform: translateY(-2px);
}

/* Dark Mode Support */
body.dark-mode .challenge-form {
    background-color: #1e1e1e;
    color: #f5f5f5;
}

body.dark-mode .challenge-form label {
    color: #f5f5f5;
}

body.dark-mode .challenge-form input,
body.dark-mode .challenge-form textarea {
    background-color: #2a2a2a;
    border-color: #444;
    color: white;
}

body.dark-mode .challenge-form input:focus,
body.dark-mode .challenge-form textarea:focus {
    border-color: #bb86fc;
    box-shadow: 0 4px 12px rgba(187, 134, 252, 0.2);
}

body.dark-mode .create-challenge-btn {
    background-color: #bb86fc;
}

body.dark-mode .create-challenge-btn:hover {
    background-color: #9b59b6;
}

/* Modal */
.modal-overlay {
    display: none;
    position: fixed;
    top: 0; left: 0;
    width: 100%; height: 100%;
    background-color: rgba(0, 0, 0, 0.7);
    justify-content: center;
    align-items: center;
    z-index: 9999;
}

.modal-content {
    background: white;
    padding: 20px;
    border-radius: 12px;
    width: 90%;
    max-width: 400px;
    text-align: center;
}

.close-modal {
    position: absolute;
    top: 10px; right: 15px;
    font-size: 20px;
    cursor: pointer;
}

/* Followers List */
.follower-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin: 8px 0;
    padding: 10px;
    border-radius: 8px;
    background-color: #f9f9f9;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
}

.follower-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    margin-right: 10px;
}

.invite-btn {
    background-color: #6a0dad;
    color: white;
    border: none;
    padding: 8px 12px;
    border-radius: 6px;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.invite-btn:hover {
    background-color: #4a0072;
}

/* Group Announcement */
.group-announcement {
    background: linear-gradient(135deg, #6a0dad, #9b59b6);
    color: #ffffff;
    padding: 16px 20px;
    border-radius: 12px;
    margin-bottom: 20px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    animation: fadeInSlide 0.8s ease-out;
    position: relative;
    font-size: 1rem;
    line-height: 1.6;
}

.group-announcement strong {
    font-size: 1.2rem;
    display: block;
    margin-bottom: 5px;
}

.group-announcement p {
    margin: 0;
}

.group-announcement button {
    position: absolute;
    top: 10px;
    right: 10px;
    color: #ffffff;
    font-size: 1.2rem;
    transition: transform 0.2s ease, color 0.2s ease;
}

.group-announcement button:hover {
    transform: scale(1.2);
    color: #ffdddd;
}

/* Group Events Section */
.group-events {
    background: #ffffff;
    border-radius: 14px;
    padding: 24px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.group-events h3 {
    font-size: 1.8rem;
    color: #6a0dad;
    margin-bottom: 16px;
    text-align: center;
}

.group-events p, .group-polls p {
    font-size: 1rem;
    color: #555;
    text-align: center;
}

/* Event Card */
.event-card {
    background: #f9f8ff;
    border-left: 5px solid #6a0dad;
    border-radius: 12px;
    padding: 16px;
    margin-bottom: 16px;
    box-shadow: 0 4px 8px rgba(106, 13, 173, 0.05);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.event-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 16px rgba(106, 13, 173, 0.1);
}

.event-card h4 {
    font-size: 1.3rem;
    color: #6a0dad;
    margin-bottom: 8px;
}

.event-card p {
    font-size: 1rem;
    color: #333;
    margin: 6px 0;
}

.event-card i {
    color: #6a0dad;
    margin-right: 8px;
}

/* Delete Button */
.delete-btn {
    background: #e74c3c;
    color: #ffffff;
    border: none;
    padding: 8px 14px;
    border-radius: 8px;
    font-weight: bold;
    cursor: pointer;
    transition: background-color 0.3s ease, transform 0.2s ease;
}

.delete-btn:hover {
    background: #c0392b;
    transform: translateY(-2px);
}

/* Animation */
@keyframes fadeInSlide {
    0% {
        opacity: 0;
        transform: translateY(-10px);
    }
    100% {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Dark Mode Compatibility */
.dark-mode .group-announcement {
    background: #2a2a3f;
    color: #e4e4e4;
}

.dark-mode .group-events {
    background: #1e1e2f;
    color: #e4e4e4;
}

.dark-mode .event-card {
    background: #2a2a3f;
    color: #e4e4e4;
}

/* Group Challenges Section */
.group-challenges {
    background: #ffffff;
    border-radius: 14px;
    padding: 24px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    margin-bottom: 30px;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.group-challenges h3 {
    font-size: 1.8rem;
    color: #6a0dad;
    text-align: center;
    margin-bottom: 16px;
}

.group-challenges p {
    font-size: 1rem;
    color: #555;
    text-align: center;
}

/* Challenge Card */
.challenge-card {
    background: #f9f8ff;
    border-left: 5px solid #6a0dad;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 16px;
    box-shadow: 0 4px 8px rgba(106, 13, 173, 0.05);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    position: relative;
}

.challenge-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 16px rgba(106, 13, 173, 0.1);
}

.challenge-card h4 {
    font-size: 1.3rem;
    color: #6a0dad;
    margin-bottom: 10px;
}

.challenge-card p {
    font-size: 1rem;
    color: #333;
    line-height: 1.6;
    margin-bottom: 8px;
}

.challenge-card strong {
    color: #6a0dad;
}

.challenge-card a {
    display: inline-block;
    background: #6a0dad;
    color: #ffffff;
    padding: 8px 14px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: bold;
    transition: background-color 0.3s ease, transform 0.2s ease;
}

.challenge-card a:hover {
    background: #4a0072;
    transform: translateY(-2px);
}

/* Animation */
@keyframes fadeInSlide {
    0% {
        opacity: 0;
        transform: translateY(-10px);
    }
    100% {
        opacity: 1;
        transform: translateY(0);
    }
}

.group-challenges .challenge-card {
    animation: fadeInSlide 0.8s ease-out;
}

/* Dark Mode Compatibility */
.dark-mode .group-challenges {
    background: #1e1e2f;
    color: #e4e4e4;
}

.dark-mode .challenge-card {
    background: #2a2a3f;
    color: #e4e4e4;
}

.dark-mode .challenge-card a {
    background: #9b59b6;
}

.dark-mode .challenge-card a:hover {
    background: #8e44ad;
}

/* Group Polls Section */
.group-polls {
    background: #ffffff;
    border-radius: 14px;
    padding: 24px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    margin-bottom: 30px;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.group-polls h3 {
    font-size: 1.8rem;
    color: #6a0dad;
    text-align: center;
    margin-bottom: 16px;
}

.poll-card {
    background: #f9f8ff;
    border-left: 5px solid #6a0dad;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 16px;
    box-shadow: 0 4px 8px rgba(106, 13, 173, 0.05);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    position: relative;
}

.poll-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 16px rgba(106, 13, 173, 0.1);
}

.poll-question {
    font-size: 1.2rem;
    font-weight: bold;
    color: #6a0dad;
    margin-bottom: 12px;
}

/* Poll Option */
.poll-option {
    background: #ffffff;
    border-radius: 8px;
    padding: 12px;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
    transition: background-color 0.3s ease, box-shadow 0.3s ease;
}

.poll-option:hover {
    background: #f5f0ff;
    box-shadow: 0 4px 12px rgba(106, 13, 173, 0.08);
}

/* Vote Button */
.vote-btn {
    background: #6a0dad;
    color: #ffffff;
    border: none;
    padding: 8px 14px;
    border-radius: 8px;
    font-weight: bold;
    cursor: pointer;
    transition: background-color 0.3s ease, transform 0.2s ease;
}

.vote-btn:hover {
    background: #4a0072;
    transform: translateY(-2px);
}

/* Vote Count and Percentage */
.vote-count {
    font-size: 0.9rem;
    color: #333;
}

/* Progress Bar */
.vote-bar {
    background: #e0e0e0;
    border-radius: 20px;
    width: 100%;
    height: 8px;
    margin-top: 8px;
    overflow: hidden;
    position: relative;
}

.vote-bar-fill {
    background: #6a0dad;
    height: 100%;
    width: 0%;
    transition: width 0.8s ease-in-out;
}

/* Total Votes */
.total-votes {
    font-size: 1rem;
    font-weight: bold;
    text-align: center;
    margin-top: 12px;
    color: #6a0dad;
}

/* Delete Button */
.delete-btn {
    background: #e74c3c;
    color: #ffffff;
    border: none;
    padding: 8px 14px;
    border-radius: 8px;
    font-weight: bold;
    cursor: pointer;
    transition: background-color 0.3s ease, transform 0.2s ease;
}

.delete-btn:hover {
    background: #c0392b;
    transform: translateY(-2px);
}

/* Animation */
@keyframes fadeInSlide {
    0% {
        opacity: 0;
        transform: translateY(-10px);
    }
    100% {
        opacity: 1;
        transform: translateY(0);
    }
}

.group-polls .poll-card {
    animation: fadeInSlide 0.8s ease-out;
}

/* Dark Mode Compatibility */
.dark-mode .group-polls {
    background: #1e1e2f;
    color: #e4e4e4;
}

.dark-mode .poll-card {
    background: #2a2a3f;
    color: #e4e4e4;
}

.dark-mode .vote-btn {
    background: #9b59b6;
}

.dark-mode .vote-btn:hover {
    background: #8e44ad;
}

/* Join Requests Section */
.join-request {
    display: flex;
    align-items: center;
    background: #f9f8ff;
    border-left: 5px solid #6a0dad;
    padding: 12px 18px;
    border-radius: 12px;
    margin-bottom: 12px;
    box-shadow: 0 4px 8px rgba(106, 13, 173, 0.05);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.join-request:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 16px rgba(106, 13, 173, 0.1);
}

.join-request img {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    object-fit: cover;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
    margin-right: 12px;
}

.join-request span {
    flex-grow: 1;
    font-weight: bold;
    color: #6a0dad;
    font-size: 1rem;
}

.join-request button {
    background: #4caf50;
    color: #ffffff;
    border: none;
    padding: 8px 14px;
    border-radius: 8px;
    font-weight: bold;
    cursor: pointer;
    transition: background-color 0.3s ease, transform 0.2s ease;
}

.join-request button:hover {
    background: #388e3c;
    transform: translateY(-2px);
}

/* Announcement Edit Form */
.announcement-edit {
    background: #ffffff;
    border-radius: 14px;
    padding: 24px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    margin-top: 20px;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.announcement-edit form {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.announcement-edit textarea {
    width: 100%;
    border: 1px solid #ddd;
    padding: 12px;
    border-radius: 8px;
    font-size: 14px;
    background-color: #fafafa;
    transition: border-color 0.3s ease;
    resize: none;
}

.announcement-edit textarea:focus {
    outline: none;
    border-color: #6a0dad;
}

.announcement-edit button {
    background: #6a0dad;
    color: white;
    border: none;
    padding: 10px 16px;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: background-color 0.3s ease, transform 0.2s ease;
}

.announcement-edit button:hover {
    background: #4a0072;
    transform: translateY(-2px);
}

/* Animation */
@keyframes fadeInSlide {
    0% {
        opacity: 0;
        transform: translateY(-10px);
    }
    100% {
        opacity: 1;
        transform: translateY(0);
    }
}

.join-request,
.announcement-edit {
    animation: fadeInSlide 0.8s ease-out;
}

/* Dark Mode Compatibility */
.dark-mode .join-request {
    background: #2a2a3f;
    color: #e4e4e4;
}

.dark-mode .announcement-edit {
    background: #1e1e2f;
    color: #e4e4e4;
}

.dark-mode .announcement-edit textarea {
    background: #2a2a3f;
    color: #e4e4e4;
    border: 1px solid #555;
}

.dark-mode .announcement-edit button {
    background: #9b59b6;
}

.dark-mode .announcement-edit button:hover {
    background: #8e44ad;
}

/* Group Update Section */
.group-update-section {
    background: #ffffff;
    border-radius: 14px;
    padding: 24px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    margin-top: 30px;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.group-update-section h3 {
    font-size: 1.8rem;
    color: #6a0dad;
    text-align: center;
    margin-bottom: 16px;
}

/* Form Layout */
#updateGroupForm {
    display: flex;
    flex-direction: column;
    gap: 14px;
}

#updateGroupForm label {
    font-size: 1rem;
    font-weight: bold;
    color: #4a0072;
    margin-bottom: 4px;
    display: block;
}

#updateGroupForm input[type="text"],
#updateGroupForm textarea,
#updateGroupForm input[type="file"] {
    width: 100%;
    border: 1px solid #ddd;
    padding: 12px;
    border-radius: 8px;
    font-size: 1rem;
    background-color: #fafafa;
    transition: border-color 0.3s ease, background-color 0.3s ease;
}

#updateGroupForm input[type="text"]:focus,
#updateGroupForm textarea:focus {
    outline: none;
    border-color: #6a0dad;
    background-color: #ffffff;
}

#updateGroupForm textarea {
    resize: none;
    height: 100px;
}

/* Group Image Input */
#group-image {
    padding: 8px;
    background-color: #f9f9f9;
    border: 1px solid #ddd;
    border-radius: 8px;
    cursor: pointer;
}

#group-image:hover {
    background-color: #f1f1f1;
}

/* Submit Button */
#updateGroupForm button {
    background: #6a0dad;
    color: white;
    border: none;
    padding: 12px;
    border-radius: 8px;
    font-weight: bold;
    font-size: 1rem;
    cursor: pointer;
    transition: background-color 0.3s ease, transform 0.2s ease;
}

#updateGroupForm button:hover {
    background: #4a0072;
    transform: translateY(-2px);
}

/* Animation */
@keyframes fadeInSlide {
    0% {
        opacity: 0;
        transform: translateY(-10px);
    }
    100% {
        opacity: 1;
        transform: translateY(0);
    }
}

.group-update-section {
    animation: fadeInSlide 0.8s ease-out;
}

/* Dark Mode Compatibility */
.dark-mode .group-update-section {
    background: #1e1e2f;
    color: #e4e4e4;
}

.dark-mode #updateGroupForm input[type="text"],
.dark-mode #updateGroupForm textarea,
.dark-mode #updateGroupForm input[type="file"] {
    background: #2a2a3f;
    color: #e4e4e4;
    border: 1px solid #555;
}

.dark-mode #updateGroupForm button {
    background: #9b59b6;
}

.dark-mode #updateGroupForm button:hover {
    background: #8e44ad;
}

/* Modal Overlay */
.modal-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7);
    backdrop-filter: blur(8px);
    z-index: 1000;
    justify-content: center;
    align-items: center;
    transition: opacity 0.3s ease;
}

/* Modal Content */
.modal-content {
    background: #ffffff;
    padding: 24px;
    border-radius: 14px;
    width: 90%;
    max-width: 400px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
    text-align: center;
    position: relative;
    transform: translateY(-20px);
    opacity: 0;
    animation: slideInModal 0.4s ease-out forwards;
}

/* Modal Title */
.modal-content h3 {
    font-size: 1.8rem;
    color: #6a0dad;
    margin-bottom: 16px;
}

/* Followers List */
#followersList {
    max-height: 300px;
    overflow-y: auto;
    padding: 10px;
    background: #f9f8ff;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(106, 13, 173, 0.05);
}

#followersList p {
    color: #555;
    font-size: 1rem;
}

/* Close Modal Button */
.close-modal {
    position: absolute;
    top: 10px;
    right: 16px;
    font-size: 24px;
    color: #6a0dad;
    cursor: pointer;
    transition: transform 0.3s ease, color 0.3s ease;
}

.close-modal:hover {
    transform: scale(1.2);
    color: #4a0072;
}

/* Invite Button */
.invite-btn {
    background: #6a0dad;
    color: #ffffff;
    border: none;
    padding: 8px 16px;
    border-radius: 8px;
    font-weight: bold;
    cursor: pointer;
    transition: background-color 0.3s ease, transform 0.2s ease;
}

.invite-btn:hover {
    background: #4a0072;
    transform: translateY(-2px);
}

/* Undo Invite Button */
.undo-btn {
    background: #e74c3c;
    color: #ffffff;
    border: none;
    padding: 8px 16px;
    border-radius: 8px;
    font-weight: bold;
    cursor: pointer;
    transition: background-color 0.3s ease, transform 0.2s ease;
}

.undo-btn:hover {
    background: #c0392b;
    transform: translateY(-2px);
}

/* Animation */
@keyframes slideInModal {
    0% {
        opacity: 0;
        transform: translateY(-20px);
    }
    100% {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Dark Mode Compatibility */
.dark-mode .modal-content {
    background: #1e1e2f;
    color: #e4e4e4;
}

.dark-mode #followersList {
    background: #2a2a3f;
}

.dark-mode .close-modal {
    color: #9b59b6;
}

.dark-mode .invite-btn {
    background: #9b59b6;
}

.dark-mode .invite-btn:hover {
    background: #8e44ad;
}
    </style>
</head>
<body class="<?php echo htmlspecialchars($theme); ?>">


<?php if ($user['role'] === 'User'): ?>
    <aside style="height: 100%;  overflow-y: scroll" class="sidebar">
            <img class="animate-on-scroll count" src="<?php echo $user['profile_picture']; ?>" width="100px" height="100px" style="border-radius: 50%;">
            <h2 class="animate-on-scroll count"><?php echo $_SESSION['name']; ?></h2>
            <nav>
                <ul>
                <li><a href="dashboard.php"><i class="fas fa-home"></i> Home</a></li>
                    <li><a href="my_profile.php?id=<?php echo $_SESSION['user_id']; ?>"><i class="fas fa-user"></i> My Profile</a></li>
                    <li><a href="search_page.php"><i class="fas fa-search"></i>  Search User</a></li>
                    <li><a href="public_posts.php" class="active"><i class="fas fa-file-alt"></i>  All Posts</a></li>
                    <li><a href="create_post.php"><i class="fas fa-pen"></i>Create Post</a></li>
                    <li><a href="groups.php"><i class="fas fa-users"></i>Groups</a></li>
                    <li><a href="my_posts.php"><i class="fas fa-file"></i>My Posts</a></li>
                    <li><a href="bookmarked_posts.php"><i class="fas fa-bookmark"></i> Bookmarks</a></li>
                    <li><a href="leaderboards.php"><i class="fas fa-trophy"></i> Leaderboards</a></li>
                    <li><a href="settings.php"><i class="fas fa-cog"></i>Settings</a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i>Logout</a></li>
                </ul>
            </nav>
        </aside><!-- Main Content -->
            <?php elseif ($user['role'] === 'Admin'): ?>
                       <!-- Sidebar -->
        <aside class="sidebar" style="overflow-y: scroll;">
        <img class="animate-on-scroll" src="<?php echo $user['profile_picture']; ?>" width="100px" height="100px" style="border-radius: 50%;">
        <h2 class="animate-on-scroll"><?php echo $_SESSION['name']; ?></h2>
        <nav>
            <ul>
                <li><a href="admin_dashboard.php"><i class="fas fa-home"></i>Home</a></li>
                <li><a href="admin_profile.php?id=<?php echo $_SESSION['user_id']; ?>"><i class="fas fa-user"></i>My Profile</a></li>
                <li><a href="search_page.php"><i class="fas fa-search"></i>  Search User</a></li>
                <li><a href="admin_users.php"><i class="fas fa-user-cog"></i>Manage Users</a></li>
                <li><a href="admin_groups.php"><i class="fas fa-users"></i>Manage Groups</a></li>
                <li><a href="admin_posts.php"><i class="fas fa-file-alt"></i>Manage Posts</a></li>
                <li><a href="admin_comments.php"><i class="fas fa-comments"></i>Manage Comments</a></li>
                <li><a href="admin_reports.php"><i class="fas fa-chart-line"></i>View Reports </a></li>
                <li><a href="admin_filters.php"><i class="fas fa-folder-open"></i>Manage Filters</a></li>
                    <li><a href="public_posts.php"><i class="fas fa-file-alt"></i> All Posts</a></li>
                    <li><a href="create_post.php"><i class="fas fa-pen"></i>Create Post</a></li>
                    <li><a href="groups.php" class="active"><i class="fas fa-users"></i>Groups</a></li>
                    <li><a href="my_posts.php"><i class="fas fa-file"></i>My Posts</a></li>
                    <li><a href="bookmarked_posts.php"><i class="fas fa-bookmark"></i>Bookmarked Posts</a></li>
                    <li><a href="leaderboards.php"><i class="fas fa-trophy"></i> Leaderboards</a></li>
                    <li><a href="settings.php"><i class="fas fa-cog"></i>Settings</a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i>Logout</a></li>
            </ul>
        </nav>
       
        </aside>
            <?php endif; ?>
         <!-- Sidebar -->
         
        <main class="content">
            <ul class="nav">
              <?php if ($_SESSION['role'] === 'User'): ?>
                <li class="animate-on-scroll icon"><a href="dashboard.php"><i class="fas fa-home"></i></a></li>
                <li class="animate-on-scroll icon"><a href="my_profile.php?id=<?php echo $_SESSION['user_id']; ?>"><i class="fas fa-user"></i></a></li>
                <li class="animate-on-scroll icon"><a href="dashboard.php#notifications-container"><i class="fas fa-bell"></i><span id="notification-count" class="count-badge">0</span></a></li>
                <li class="animate-on-scroll icon"><a href="dashboard.php#unread-messages-container"><i class="fas fa-envelope"></i><span id="unread-message-count" class="count-badge">0</span></a></li>
              <?php elseif ($_SESSION['role'] === 'Admin'): ?>
                <li class="animate-on-scroll icon"><a href="admin_dashboard.php"><i class="fas fa-home"></i></a></li>
                <li class="animate-on-scroll icon"><a href="admin_profile.php?id=<?php echo $_SESSION['user_id']; ?>"><i class="fas fa-user"></i></a></li>
                <li class="animate-on-scroll icon"><a href="admin_dashboard.php#notifications-container"><i class="fas fa-bell"></i><span id="notification-count" class="count-badge">0</span></a></li>
                <li class="animate-on-scroll icon"><a href="admin_dashboard.php#unread-messages-container"><i class="fas fa-envelope"></i><span id="unread-message-count" class="count-badge">0</span></a></li>
                <li  class="animate-on-scroll icon"><a href="admin_users.php"><i class="fas fa-user-cog"></i></a></li>
                <li  class="animate-on-scroll icon"><a href="admin_groups.php"><i class="fas fa-users"></i></a></li>
                <li  class="animate-on-scroll icon"><a href="admin_posts.php"><i class="fas fa-file-alt"></i></a></li>
                <li class="animate-on-scroll icon"><a href="admin_comments.php"><i class="fas fa-comments"></i></a></li>
                <li  class="animate-on-scroll icon"><a href="admin_reports.php"><i class="fas fa-chart-line"></i> <?php if ($report_count > 0): ?><span class="count-badge"><?= $report_count ?></span><?php endif; ?></a></li>
                <li class="animate-on-scroll icon"><a href="admin_filters.php"><i class="fas fa-folder-open"></i></a></li>
            <?php endif; ?>
              <li class="animate-on-scroll icon"><a href="search_page.php"><i class="fas fa-search"></i></a></li>
              <li class="animate-on-scroll icon"><a href="public_posts.php"><i class="fas fa-file-alt"></i></a></li>
              <li class="animate-on-scroll icon"><a href="create_post.php"><i class="fas fa-pen"></i></a></li>
              <a href="#"><li class="animate-on-scroll icon"><a href="groups.php"><i class="fas fa-users"></i></a></li>
              <li class="animate-on-scroll icon"><a href="my_posts.php"><i class="fas fa-file"></i></a></li>
              <li class="animate-on-scroll icon"><a href="bookmarked_posts.php"><i class="fas fa-bookmark"></i></a></li>
              <li class="animate-on-scroll icon"><a href="leaderboards.php"><i class="fas fa-trophy"></i></a></li>
              <li class="animate-on-scroll icon"><a href="settings.php"><i class="fas fa-cog"></i></a></li>
              <li class="animate-on-scroll icon"><a href="logout.php"><i class="fas fa-sign-out-alt"></i></a></li>
            </ul><br>


    <div class="group-container">
        <!-- Group Header -->
        <div class="group-header">
            <?php if ($group['image']): ?>
                <img id="groupImage" class="animate-on-scroll"  src="<?= htmlspecialchars($group['image']) ?>" alt="Group Image">
            <?php else: ?>
                <img  class="animate-on-scroll"  src="assets/default-group-image.jpg" alt="Default Group Image">
            <?php endif; ?>
            <h1 class="animate-on-scroll" ><?= htmlspecialchars($group['name']) ?></h1>
            <p class="animate-on-scroll" ><?= htmlspecialchars($group['description']) ?></p>
            <p class="animate-on-scroll" ><strong><?= $group['member_count'] ?> Members</strong></p>
        </div>

        <div class="group-info-card animate-on-scroll">
    <h2><?php echo htmlspecialchars($group['name']); ?></h2>
    <p class="group-description"><?php echo htmlspecialchars($group['description']); ?></p>
    <p class="group-meta">
        <span id="member-count"><?php echo htmlspecialchars($group['member_count']); ?></span> Members • 
        <?php echo ($group['privacy'] === 'public') ? 'Public Group' : 'Private Group'; ?>
    </p>
</div>

        <!-- Group Image Modal -->
        <div id="groupImageModal" class="modal">
            <div class="modal-content">
                <span class="close-modal">&times;</span>
                <h2>Group Image</h2>
                <img id="modalGroupImage" src="<?= htmlspecialchars($group['image']) ?: 'assets/default-group.jpg' ?>" alt="Group Image">

                 <?php if ($userRole === 'admin'): ?>
                    Change Group Image Form (Only Visible to Admin) 
                    <form id="changeGroupImageForm" enctype="multipart/form-data">
                        <input type="file" id="groupImageInput" name="group_image" accept="image/*">
                        <button type="submit">Upload</button>
                    </form> 
                <?php endif; ?>
            </div>
        </div>

        <!-- Group Content -->
        <br><div class="group-content">
            <h2  class="animate-on-scroll" >About the Group</h2>
            <p  class="animate-on-scroll"  style="color: black"><?= htmlspecialchars($group['description']) ?></p>

            <?php if ($userId): ?>
    <!-- If user is a group member -->
    <?php if ($isMember): ?>
        <?php if ($userRole === 'admin'): ?>
            <button class="animate-on-scroll join-group-btn" style="background-color: grey; border-radius: 5px; cursor: not-allowed;" disabled>
                You are the Admin
            </button>
        <?php else: ?>
            <a href="#" class="animate-on-scroll join-group-btn" id="leaveGroupBtn" data-group-id="<?= $groupId ?>" onclick="playButtonSound(); updateMemberCount(<?= $groupId ?>)"><i class="fas fa-sign-out-alt"></i> Leave Group</a>
        <?php endif; ?>

        <!-- Invite Followers Button -->
        <button id="invite-followers-btn" class="invite-followers-btn" onclick="playButtonSound();"><i class="fas fa-user-plus"></i> Invite Followers</button>

    <!-- If user is NOT a member -->
    <?php else: ?>
        <!-- Private Group - Show Request to Join -->
        <?php if ($group['privacy'] === 'private' && !$isMember && !$hasRequested): ?>
    <button id="requestJoinBtn" onclick="sendJoinRequest(<?php echo $groupId; ?>)" class="join-group-btn">Request to Join</button>
<?php elseif ($group['privacy'] === 'private' && !$isMember && $hasRequested): ?>
    <button class="join-group-btn" style="background-color: grey; cursor: not-allowed;" disabled>Request Sent</button>
        <!-- Public Group - Show Join Group -->
        <?php else: ?>
            <a href="#" class="animate-on-scroll join-group-btn" id="joinGroupBtn" data-group-id="<?= $groupId ?>" onclick="playButtonSound(); updateMemberCount(<?= $groupId ?>)">Join Group</a>
        <?php endif; ?>
    <?php endif; ?>

    <!-- View Group Posts -->
    <?php if ($group['privacy'] === 'public' || $isMember): ?>
        <a onclick="playButtonSound()" href="group_posts.php?id=<?= $groupId ?>" class="animate-on-scroll group-posts-btn">View Group Posts</a>
    <?php else: ?>
        <strong class="animate-on-scroll" style="color: gray;">This is a private group. Join to view posts.</strong>
    <?php endif; ?>

    <!-- Admin Actions -->
    <?php if ($userRole === 'admin'): ?>
        <div class="group-admin-actions">
            <form method="POST" onsubmit="return confirm('Are you sure you want to delete this group? This action cannot be undone.')">
                <button type="submit" name="delete_group" class="animate-on-scroll btn delete-btn">Delete Group</button>
            </form>
        </div>
    <?php endif; ?>
<?php endif; ?>
        

        
       
        </div>

        <?php if (!empty($group['announcement'])): ?>
            <div class="group-announcement animate-on-scroll" id="announcement">
            <button onclick="dismissAnnouncement()" style="float:right; background: none; border: none; cursor:pointer;">✖</button>
            <strong>📢 Announcement:</strong>
            <p><?php echo nl2br(htmlspecialchars($group['announcement'])); ?></p>
        </div>
        <script>
        function dismissAnnouncement() {
            document.getElementById('announcement').style.display = 'none';
        }
        </script>
        <?php endif; ?><br>

        <div class="group-events animate-on-scroll">
            <h3>Upcoming Events</h3>
            <?php if (empty($events)): ?>
                <p>No upcoming events.</p>
            <?php else: ?>
                <?php foreach ($events as $event): ?>
                    <div class="event-card">
                        <h4><i class="fas fa-calendar-alt"></i> <?php echo htmlspecialchars($event['title']); ?></h4>
                        <p><i class="fas fa-info-circle"></i> <?php echo htmlspecialchars($event['description']); ?></p>
                        <p><i class="fas fa-clock"></i> Date: <?php echo date('F j, Y g:i A', strtotime($event['event_date'])); ?></p>
                        <p><i class="fas fa-map-marker-alt"></i> Location: <?php echo htmlspecialchars($event['location'] ?? 'Online'); ?></p>
                    </div>
                    <!-- Delete Button for Admins/Moderators -->
            <?php if ($userRole === 'admin' || $userRole === 'moderator'): ?>
                <form action="delete_group_event.php" method="POST" style="display:inline;">
                    <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                    <input type="hidden" name="group_id" value="<?php echo $group['id']; ?>">
                    <button type="submit" class="delete-btn">Delete Event</button>
                </form>
            <?php endif; ?>
                <?php endforeach; ?>
            
            <?php endif; ?>
        </div>
        <br>

        <div class="group-polls animate-on-scroll">
    <h3>Group Polls</h3>
    <?php if (empty($polls)): ?>
                <p>No group polls.</p>
            <?php else: ?>
    <?php foreach ($polls as $poll): ?>
        <?php
        // Fetch votes for the current poll ONCE per poll, outside of the options loop
        $stmtVotes = $pdo->prepare("SELECT selected_option, COUNT(*) as vote_count FROM group_poll_votes WHERE poll_id = ? GROUP BY selected_option");
        $stmtVotes->execute([$poll['id']]);
        $voteResults = $stmtVotes->fetchAll(PDO::FETCH_ASSOC);

        $voteCounts = [];
        $totalVotes = 0;
        foreach ($voteResults as $vote) {
            $voteCounts[$vote['selected_option']] = $vote['vote_count'];
            $totalVotes += $vote['vote_count'];
        }

        $options = json_decode($poll['options'], true);
        ?>

        <div class="poll-card">
            <p class="poll-question"><?php echo htmlspecialchars($poll['question']); ?></p>

            <?php foreach ($options as $option): ?>
                <div class="poll-option">
                    <!-- Voting Form -->
                    <form action="vote_poll.php" method="POST">
                        <input type="hidden" name="group_id" value="<?php echo $group['id']; ?>">
                        <input type="hidden" name="poll_id" value="<?php echo $poll['id']; ?>">
                        <input type="hidden" name="option" value="<?php echo htmlspecialchars($option); ?>">
                        <button type="submit" class="vote-btn"><?php echo htmlspecialchars($option); ?></button>
                    </form>

                    <!-- Vote Count and Percentage -->
                    <?php
                    $voteCount = $voteCounts[$option] ?? 0;
                    $percentage = ($totalVotes > 0) ? round(($voteCount / $totalVotes) * 100, 1) : 0;
                    ?>
                    <span class="vote-count"><?php echo $voteCount; ?> Votes (<?php echo $percentage; ?>%)</span>

                    <!-- Progress Bar -->
                    <div class="vote-bar">
                        <div class="vote-bar-fill" style="width: <?php echo $percentage; ?>%;"></div>
                    </div>
                </div>
            <?php endforeach; ?>

            <!-- Total Votes -->
            <p class="total-votes">Total Votes: <?php echo $totalVotes; ?></p>
         <!-- Delete Button for Admins/Moderators -->
            <?php if ($userRole === 'admin' || $userRole === 'moderator'): ?>
                <form action="delete_group_poll.php" method="POST" style="display:inline;">
                    <input type="hidden" name="poll_id" value="<?php echo $poll['id']; ?>">
                    <input type="hidden" name="group_id" value="<?php echo $group['id']; ?>">
                    <button type="submit" class="delete-btn">Delete Poll</button>
                </form>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<div class="group-challenges animate-on-scroll">
    <h3>Group Challenges</h3>
    <?php if (empty($challenges)): ?>
        <p>No active challenges.</p>
    <?php else: ?>
        <?php foreach ($challenges as $challenge): ?>
            <div class="challenge-card">
                <h4><?php echo htmlspecialchars($challenge['title']); ?></h4>
                <p><?php echo nl2br(htmlspecialchars($challenge['description'])); ?></p>
                <?php if (!empty($challenge['deadline'])): ?>
                    <p><strong>Deadline:</strong> <?php echo date('F j, Y g:i A', strtotime($challenge['deadline'])); ?></p>
                <?php endif; ?>
                <a href="view_challenge.php?id=<?php echo $challenge['id']; ?>&group_id=<?php echo $group['id']; ?>">View Challenge</a>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

        <?php if ($userRole === 'admin' || $userRole === 'moderator'): ?>
    <div class="group-update-section">
        <h3>Update Group Details</h3>
        <form  method="POST" enctype="multipart/form-data" id="updateGroupForm">
            <label  class="animate-on-scroll"  for="group-name">Group Name</label>
            <input type="text" id="group-name" name="group_name" value="<?= htmlspecialchars($group['name']) ?>" required>

            <label  class="animate-on-scroll"  for="group-description">Group Description</label>
            <textarea id="group-description" name="group_description" required><?= htmlspecialchars($group['description']) ?></textarea>

            <label class="animate-on-scroll" for="group-image">Group Image</label>
            <input style="color: black" type="file" id="group-image" name="group_image" accept="image/*">

            <button class="animate-on-scroll" type="submit" name="update_group">Update Group</button>
        </form>
    </div>
<?php endif; ?>

<br>

<?php if ($userRole === 'admin' || $userRole === 'moderator'): ?>
    <h3>Join Requests</h3>
    <?php
    $stmtRequests = $pdo->prepare("
        SELECT gr.*, u.name, u.profile_picture 
        FROM group_join_requests gr 
        JOIN users u ON gr.user_id = u.id 
        WHERE gr.group_id = ?
    ");
    $stmtRequests->execute([$groupId]);
    $requests = $stmtRequests->fetchAll();
    ?>
    <?php if (!empty($requests)): ?>
        <?php foreach ($requests as $request): ?>
            <div class="join-request">
                <img src="<?= htmlspecialchars($request['profile_picture']) ?>" width="50" height="50" style="border-radius: 50%;">
                <span><?= htmlspecialchars($request['name']) ?></span>
                <button onclick="approveRequest(<?= $request['user_id'] ?>, <?= $groupId ?>)">Approve</button>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>No pending requests.</p>
    <?php endif; ?>
<?php endif; ?>

<?php if ($userRole === 'admin' || $userRole === 'moderator'): ?>
    <div class="announcement-edit animate-on-scroll">
        <form action="update_announcement.php" method="POST">
            <input type="hidden" name="group_id" value="<?php echo $group['id']; ?>">
            <textarea name="announcement" rows="3" placeholder="Enter group announcement..."><?php echo htmlspecialchars($group['announcement'] ?? ''); ?></textarea>
            <button type="submit">Update Announcement</button>
        </form>
    </div>
<?php endif; ?>

<br>

<?php if ($userRole === 'admin' || $userRole === 'moderator'): ?>
<div class="group-event-form animate-on-scroll">
    <h3>Create Group Event</h3>
    <form action="add_group_event.php" method="POST">
        <input type="hidden" name="group_id" value="<?php echo $group['id']; ?>">
        <input type="text" name="title" placeholder="Event Title" required>
        <textarea name="description" placeholder="Event Description" required></textarea>
        <input type="datetime-local" name="event_date" required>
        <input type="text" name="location" placeholder="Location (Optional)">
        <button type="submit">Create Event</button>
    </form>
</div>
<?php endif; ?>


<?php if ($userRole === 'admin' || $userRole === 'moderator'): ?>
<div class="group-poll-form animate-on-scroll">
    <h3>Create a Poll</h3>
    <form action="add_group_poll.php" method="POST">
        <input type="hidden" name="group_id" value="<?php echo $group['id']; ?>">

        <div class="input-group">
            <label for="poll-question">Poll Question:</label>
            <input type="text" id="poll-question" name="question" placeholder="Enter your poll question..." required>
        </div>

        <div class="input-group">
            <label for="poll-options">Poll Options (comma-separated):</label>
            <textarea id="poll-options" name="options" placeholder="Option 1, Option 2, Option 3..." required></textarea>
        </div>

        <button type="submit" class="create-poll-btn">Create Poll</button>
    </form>
</div>
<?php endif; ?>

<?php if ($userRole === 'admin' || $userRole === 'moderator'): ?>
        <div class="challenge-form animate-on-scroll">
    <h3>Create a Group Challenge</h3>
    <form action="add_group_challenge.php" method="POST">
        <input type="hidden" name="group_id" value="<?php echo $group['id']; ?>">

        <div class="input-group">
            <label for="title">Title</label>
            <input type="text" id="title" name="title" placeholder="Enter Challenge Title" required>
        </div>

        <div class="input-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" placeholder="Describe the challenge..." required></textarea>
        </div>

        <div class="input-group">
            <label for="deadline">Deadline (Optional)</label>
            <input type="datetime-local" id="deadline" name="deadline">
        </div>

        <button type="submit" class="create-challenge-btn">Create Challenge</button>
    </form>
</div>
<?php endif; ?>

            <!-- Search Members -->
            <form action="" method="get" class="search-container">
                <input type="hidden" name="id" value="<?= htmlspecialchars($groupId) ?>">
                <input 
                    type="text" 
                    name="search_member" 
                    placeholder="Search members..." 
                    value="<?= htmlspecialchars($searchMember) ?>"
                >
                <button  class="animate-on-scroll"  type="submit">Search</button>
            </form>

            <h2  class="animate-on-scroll" >Group Members</h2>
            <div class="group-members" id="group-members">
    <?php if (count($members) > 0): ?>
        <?php foreach ($members as $member): ?>
            <div class="member-card animate-on-scroll">
            <a href="user_profile.php?id=<?php echo htmlspecialchars($member['id']); ?>">
                <img src="<?php echo htmlspecialchars($member['profile_picture']); ?>" alt="<?php echo htmlspecialchars($member['name']); ?> Profile">
            </a>
            <h4><?php echo htmlspecialchars($member['name']); ?></h4>
            <p class="member-role">
                <?php echo ucfirst($member['role']); ?>
            </p>
            <?php if ($member['id'] != $_SESSION['user_id']): ?>
                <a href="user_profile.php?id=<?php echo htmlspecialchars($member['id']); ?>" class="view-profile-btn">View Profile</a>
            <?php endif; ?>

                <?php if ($userRole === 'admin' && $member['id'] !== $userId): ?>
                    <div  class="animate-on-scroll" >
                        <?php if ($member['role'] === 'member'): ?>
                            <button onclick="playButtonSound();" class="action-btn promote-btn" data-user-id="<?= $member['id'] ?>">Promote to Moderator</button>
                        <?php elseif ($member['role'] === 'moderator'): ?>
                            <button onclick="playButtonSound();" class="action-btn demote-btn" data-user-id="<?= $member['id'] ?>">Demote to Member</button>
                        <?php endif; ?>
                        <button onclick="playButtonSound();" class="action-btn remove-btn" data-user-id="<?= $member['id'] ?>">Remove</button>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>No members in this group yet.</p>
    <?php endif; ?>
</div>
        </div>
    </div>



    <div class="fab-container">
    <button class="fab-main-btn" onclick="toggleFabOptions()">
        <i class="fas fa-plus"></i>
    </button>

    <div class="fab-options">
        <a href="group_posts.php?id=<?php echo $group['id']; ?>" class="fab-option-btn" onclick="playButtonSound()">
            <i class="fas fa-comments"></i> Posts
        </a>

        <?php if ($userRole === 'admin' || $userRole === 'moderator'): ?>
        <a href="group_admin.php?id=<?php echo $group['id']; ?>" class="fab-option-btn" onclick="playButtonSound()">
            <i class="fas fa-cog"></i> Admin
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- Invite Followers Modal -->
<div id="inviteFollowersModal" class="modal-overlay">
    <div class="modal-content">
        <span class="close-modal" onclick="closeInviteModal()">&times;</span>
        <h3>Invite Your Followers</h3>
        <div id="followersList">
            <p>Loading followers...</p>
        </div>
    </div>
</div>
    <ul class="nav">
              <?php if ($_SESSION['role'] === 'User'): ?>
              <li class="animate-on-scroll icon"><a href="dashboard.php"><i class="fas fa-home"></i></a></li>
                <li class="animate-on-scroll icon"><a href="my_profile.php?id=<?php echo $_SESSION['user_id']; ?>"><i class="fas fa-user"></i></a></li>
                <li class="animate-on-scroll icon"><a href="dashboard.php#notifications-container"><i class="fas fa-bell"></i></a></li>
                <li class="animate-on-scroll icon"><a href="dashboard.php#unread-messages-container"><i class="fas fa-envelope"></i></a></li>
              <?php elseif ($_SESSION['role'] === 'Admin'): ?>
              <li class="animate-on-scroll icon"><a href="admin_dashboard.php"><i class="fas fa-home"></i></a></li>
                <li class="animate-on-scroll icon"><a href="admin_profile.php?id=<?php echo $_SESSION['user_id']; ?>"><i class="fas fa-user"></i></a></li>
                <li class="animate-on-scroll icon"><a href="admin_dashboard.php#notifications-container"><i class="fas fa-bell"></i></a></li>
                <li class="animate-on-scroll icon"><a href="admin_dashboard.php#unread-messages-container"><i class="fas fa-envelope"></i></a></li>
                <li  class="animate-on-scroll icon"><a href="admin_users.php"><i class="fas fa-user-cog"></i></a></li>
                <li  class="animate-on-scroll icon"><a href="admin_groups.php"><i class="fas fa-users"></i></a></li>
                <li  class="animate-on-scroll icon"><a href="admin_posts.php"><i class="fas fa-file-alt"></i></a></li>
                <li class="animate-on-scroll icon"><a href="admin_comments.php"><i class="fas fa-comments"></i></a></li>
                <li  class="animate-on-scroll icon"><a href="admin_reports.php"><i class="fas fa-chart-line"></i></a></li>
                <li class="animate-on-scroll icon"><a href="admin_filters.php"><i class="fas fa-folder-open"></i></a></li>
            <?php endif; ?>
              <li class="animate-on-scroll icon"><a href="search_page.php"><i class="fas fa-search"></i></a></li>
              <li class="animate-on-scroll icon"><a href="public_posts.php"><i class="fas fa-file-alt"></i></a></li>
              <li class="animate-on-scroll icon"><a href="create_post.php"><i class="fas fa-pen"></i></a></li>
              <li class="animate-on-scroll icon"><a href="groups.php"><i class="fas fa-users"></i></a></li>
              <li class="animate-on-scroll icon"><a href="my_posts.php"><i class="fas fa-file"></i></a></li>
              <li class="animate-on-scroll icon"><a href="bookmarked_posts.php"><i class="fas fa-bookmark"></i></a></li>
              <li class="animate-on-scroll icon"><a href="leaderboards.php"><i class="fas fa-trophy"></i></a></li>
              <li class="animate-on-scroll icon"><a href="settings.php"><i class="fas fa-cog"></i></a></li>
              <li class="animate-on-scroll icon"><a href="logout.php"><i class="fas fa-sign-out-alt"></i></a></li>
            </ul><br><br><br>
    <script>
    document.addEventListener("DOMContentLoaded", function () {
        const joinGroupBtn = document.getElementById("joinGroupBtn");
        const leaveGroupBtn = document.getElementById("leaveGroupBtn");

        if (joinGroupBtn) {
            joinGroupBtn.addEventListener("click", function (e) {
                e.preventDefault();
                const groupId = this.dataset.groupId;

                fetch("join_group.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ groupId: groupId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert("You have joined the group!");
                        location.reload();
                    } else {
                        alert("Error: " + data.message);
                    }
                });
            });
        }

        if (leaveGroupBtn) {
            leaveGroupBtn.addEventListener("click", function (e) {
                e.preventDefault();
                const groupId = this.dataset.groupId;

                fetch("leave_group.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ groupId: groupId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert("You have left the group!");
                        location.reload();
                    } else {
                        alert("Error: " + data.message);
                    }
                });
            });
        }
    });
    function updateMemberCount(groupId) {
        fetch(`get_member_count.php?group_id=${groupId}`)
            .then(response => response.json())
            .then(data => {
                const countElement = document.getElementById(`member-count-${groupId}`);
                if (countElement) {
                    countElement.textContent = `${data.member_count} Members`;
                }
            })
            .catch(error => console.error('Error updating member count:', error));
    }
    document.addEventListener("DOMContentLoaded", function () {
        // Promote to Moderator
        document.querySelectorAll('.promote-btn').forEach(button => {
            button.addEventListener('click', function () {
                const userId = this.dataset.userId;
                manageMember('promote', userId);
            });
        });

        // Demote to Member
        document.querySelectorAll('.demote-btn').forEach(button => {
            button.addEventListener('click', function () {
                const userId = this.dataset.userId;
                manageMember('demote', userId);
            });
        });

        // Remove Member
        document.querySelectorAll('.remove-btn').forEach(button => {
            button.addEventListener('click', function () {
                const userId = this.dataset.userId;
                manageMember('remove', userId);
            });
        });

        // Manage Member Function
        function manageMember(action, userId) {
            fetch('manage_member.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action, userId, groupId: <?= $groupId ?> })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => console.error('Error:', error));
        }
    });
    document.addEventListener("DOMContentLoaded", function () {
  const elements = document.querySelectorAll(".animate-on-scroll");

  function handleScroll() {
    elements.forEach((el) => {
      const rect = el.getBoundingClientRect();
      if (rect.top < window.innerHeight - 100) {
        el.classList.add("visible");
      }
    });
  }

  window.addEventListener("scroll", handleScroll);
  handleScroll(); // Trigger once on page load
});
document.addEventListener('DOMContentLoaded', function () {
    const notificationCountElement = document.getElementById('notification-count');
    const unreadMessageCountElement = document.getElementById('unread-message-count');
    const notificationsList = document.getElementById('notifications-list');
    const unreadMessagesList = document.getElementById('unread-messages-list');

    // Fetch unread notifications
    function fetchNotifications() {
        fetch('fetch_notifications.php')
            .then(response => response.json())
            .then(data => {
                const notifications = data.notifications;
                notificationCountElement.textContent = notifications.length;

                notificationsList.innerHTML = ''; // Clear previous list
                if (notifications.length > 0) {
                    notifications.forEach(notification => {
                        const listItem = document.createElement('li');
                        listItem.className = 'notification-item';
                        listItem.innerHTML = `
                            <img src="${notification.profile_picture || 'default-profile.png'}" alt="${notification.sender_name}" class="sender-pic">
                            <span>${notification.message} - <a href="user_profile.php?id=${notification.sender_id}">${notification.sender_name}</a></span>
                            <button class="mark-as-read-btn" data-notification-id="${notification.id}">Mark as Read</button>
                        `;
                        notificationsList.appendChild(listItem);
                    });
                } else {
                    notificationsList.innerHTML = '<li>No new notifications</li>';
                }
            })
            .catch(error => console.error('Error fetching notifications:', error));
    }

    // Fetch unread messages
    function fetchUnreadMessages() {
        fetch('fetch_unread_messages.php')
            .then(response => response.json())
            .then(data => {
                const unreadMessages = data.unread_messages;
                unreadMessageCountElement.textContent = unreadMessages.length;

                unreadMessagesList.innerHTML = ''; // Clear previous list
                if (unreadMessages.length > 0) {
                    unreadMessages.forEach(msg => {
                        const listItem = document.createElement('li');
                        listItem.className = 'unread-message-item';
                        listItem.innerHTML = `
                            <img src="${msg.profile_picture || 'default-profile.png'}" alt="${msg.sender_name}" class="sender-pic">
                            <span>${msg.sender_name} (${msg.unread_count} unread)</span>
                            <a href="chat.php?user_id=${msg.sender_id}" class="view-chat-link">View Chat</a>
                        `;
                        unreadMessagesList.appendChild(listItem);
                    });
                } else {
                    unreadMessagesList.innerHTML = '<li>No unread messages</li>';
                }
            })
            .catch(error => console.error('Error fetching unread messages:', error));
    }

    // Initial fetch
    fetchNotifications();
    fetchUnreadMessages();

    // Optionally refresh the lists periodically
    setInterval(() => {
        fetchNotifications();
        fetchUnreadMessages();
    }, 2000); // Refresh every 60 seconds
});
document.addEventListener('DOMContentLoaded', function () {
    const groupImage = document.getElementById('groupImage');
    const modal = document.getElementById('groupImageModal');
    const closeModal = document.querySelector('.close-modal');
    const modalGroupImage = document.getElementById('modalGroupImage');
    const groupImageInput = document.getElementById('groupImageInput');
    const changeGroupImageForm = document.getElementById('changeGroupImageForm');

    // Open Modal on Click
    groupImage.addEventListener('click', function () {
        modalGroupImage.src = this.src;
        modal.style.display = 'block';
    });

    // Close Modal
    closeModal.addEventListener('click', function () {
        modal.style.display = 'none';
    });

    // Handle Group Image Change (Admin Only)
    if (changeGroupImageForm) {
        changeGroupImageForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(this);

            fetch('update_group_image.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    modalGroupImage.src = data.new_image;
                    groupImage.src = data.new_image;
                    alert('Group image updated successfully!');
                    modal.style.display = 'none';
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => console.error('Error:', error));
        });
    }
});


const buttonClickSound = new Audio('assets/sounds/group_click.mp3');

function playButtonSound() {
    buttonClickSound.currentTime = 0; // Restart sound if clicked multiple times
    buttonClickSound.play();
    buttonClickSound.volume = 1; // 50% volume
}

function toggleFabOptions() {
    const options = document.querySelector('.fab-options');
    options.style.display = (options.style.display === 'flex') ? 'none' : 'flex';
}

document.getElementById('invite-followers-btn').addEventListener('click', openInviteModal);

function openInviteModal() {
    const modal = document.getElementById('inviteFollowersModal');
    modal.style.display = 'flex';

    const groupId = <?php echo $group['id']; ?>;
    const followersListDiv = document.getElementById('followersList');
    followersListDiv.innerHTML = `<p>Loading followers...</p>`;

    fetch(`fetch_non_member_followers.php?group_id=${groupId}`)
        .then(response => response.json())
        .then(data => {
            if (data.length === 0) {
                followersListDiv.innerHTML = `<p>No followers to invite.</p>`;
                return;
            }

            followersListDiv.innerHTML = '';
            data.forEach(follower => {
                const followerItem = document.createElement('div');
                followerItem.className = 'follower-item';
                followerItem.innerHTML = `
                    <img src="${follower.profile_picture}" alt="${follower.name}" class="follower-avatar">
                    <span>${follower.name}</span>
                    <button class="invite-btn" data-user-id="${follower.id}" data-group-id="${groupId}" data-invited="false">Invite</button>
                `;
                followersListDiv.appendChild(followerItem);
            });

            setupInviteButtons();
        })
        .catch(error => {
            followersListDiv.innerHTML = `<p>Error loading followers.</p>`;
            console.error(error);
        });
}

function closeInviteModal() {
    document.getElementById('inviteFollowersModal').style.display = 'none';
}

function setupInviteButtons() {
    document.querySelectorAll('.invite-btn').forEach(button => {
        button.addEventListener('click', function () {
            const userId = this.getAttribute('data-user-id');
            const groupId = this.getAttribute('data-group-id');
            const isInvited = this.getAttribute('data-invited') === 'true';

            const action = isInvited ? 'undo' : 'invite';

            fetch('send_group_invite.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `user_id=${userId}&group_id=${groupId}&action=${action}`
            })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        this.innerText = isInvited ? 'Invite' : 'Undo';
                        this.setAttribute('data-invited', isInvited ? 'false' : 'true');
                    } else {
                        alert('Failed to send invite.');
                    }
                })
                .catch(error => console.error('Error:', error));
        });
    });
}
function sendJoinRequest(groupId) {
    const button = document.getElementById('requestJoinBtn');

    fetch('send_group_request.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'groupId=' + groupId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            button.textContent = 'Request Sent';
            button.disabled = true;
            button.style.backgroundColor = 'grey';
            button.style.cursor = 'not-allowed';
        } else {
            alert('Failed to send request.');
        }
    })
    .catch(error => console.error('Error:', error));
}

function approveRequest(userId, groupId) {
    fetch('approve_group_request.php', {
        method: 'POST',
        body: JSON.stringify({ userId, groupId }),
        headers: { 'Content-Type': 'application/json' },
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Request approved');
            location.reload();
        }
    })
    .catch(error => console.error('Error:', error));
}
</script>
</body>
</html>