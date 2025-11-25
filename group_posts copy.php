<?php
include 'includes/db.php'; // Database connection
session_start();
$userId = $_SESSION['user_id'];

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

// Fetch posts with their comments
$postsStmt = $pdo->prepare("
    SELECT p.*, u.name AS author_name, u.profile_picture,
           (SELECT COUNT(*) FROM group_post_likes WHERE post_id = p.id) AS like_count,
           (SELECT COUNT(*) FROM group_post_comments WHERE post_id = p.id) AS comment_count
    FROM group_posts p
    JOIN users u ON p.user_id = u.id
    WHERE p.group_id = ?
    ORDER BY p.created_at DESC
");
$postsStmt->execute([$groupId]);
$posts = $postsStmt->fetchAll();

// Fetch all comments grouped by post_id
$commentsStmt = $pdo->prepare("
    SELECT c.*, u.name AS commenter_name, u.profile_picture AS commenter_picture
    FROM group_post_comments c
    JOIN users u ON c.user_id = u.id
    WHERE c.post_id IN (
        SELECT id FROM group_posts WHERE group_id = ?
    )
    ORDER BY c.post_id ASC, c.created_at ASC
");
$commentsStmt->execute([$groupId]);

// Fetch grouped comments by post_id
$comments = [];
while ($comment = $commentsStmt->fetch(PDO::FETCH_ASSOC)) {
    $comments[$comment['post_id']][] = $comment;
}
// Fetch the current user's role in the group
$userRoleStmt = $pdo->prepare("SELECT role FROM group_members WHERE group_id = ? AND user_id = ?");
$userRoleStmt->execute([$groupId, $userId]);
$userRole = $userRoleStmt->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="assets/elevate.jpg" type="image/x-icon">
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <title><?= htmlspecialchars($group['name']) ?> - Posts</title>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f6f9;
            color: #333;
        }

        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
        }

        h2 {
            text-align: center;
        }

        .post-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            padding: 20px;
            transition: transform 0.3s ease-in-out;
        }

        .post-card:hover {
            transform: translateY(-5px);
        }

        .post-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }

        .post-header img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
        }

        .post-header h3 {
            margin: 0;
            font-size: 1rem;
            color: #2c3e50;
        }

        .post-content {
            font-size: 1rem;
            color: black;
        }

        .post-content p {
            font-size: 1rem;
            color: #7f8c8d;
        }

        .create-post-btn {
            display: inline-block;
            margin-bottom: 20px;
            padding: 12px 25px;
            background-color: #6c63ff;
            color: white;
            border-radius: 25px;
            text-decoration: none;
            font-size: 1rem;
            font-weight: bold;
            transition: background-color 0.3s ease-in-out, transform 0.2s ease-in-out;
        }

        .create-post-btn:hover {
            background-color: #4a4aad;
            transform: scale(1.05);
        }
        .post-actions {
    justify-content: space-between;
    margin-top: 15px;
    padding: 0 10px;
}

.like-btn, .comment-btn, .edit-btn, .delete-btn {
    padding: 10px 20px;
    font-size: 1rem;
    font-weight: bold;
    color: white;
    border: none;
    border-radius: 25px;
    margin-right: 10px;
    cursor: pointer;
    transition: background-color 0.3s ease, transform 0.2s ease;
}

@media screen and (max-width: 600px){
    .like-btn, .comment-btn, .edit-btn, .delete-btn {
    margin-top: 10px;
    display: block;
    }
}
@media screen and (min-width: 600px){
    .post-actions {
        display: flex;
}
}

.like-btn {
    background-color: #6c63ff;
}

.like-btn:hover {
    background-color: #4a4aad;
    transform: scale(1.05);
}

.comment-btn {
    background-color: #3498db;
}

.comment-btn:hover {
    background-color: #2980b9;
    transform: scale(1.05);
}

.post-actions button span {
    font-weight: normal;
    color: #f1f1f1;
}
.post-comments {
    margin-top: 20px;
    padding: 15px;
    background-color: #f9f9f9;
    border-radius: 10px;
    border: 1px solid #ddd;
}

.post-comments h4 {
    margin: 0 0 10px;
    font-size: 1.2rem;
    color: #34495e;
}

.comment {
    display: flex;
    align-items: flex-start;
    margin-bottom: 15px;
}

.comment img {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    margin-right: 10px;
}

.comment-content {
    background: white;
    padding: 10px;
    border-radius: 10px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    width: 100%;
}

.comment-content strong {
    font-size: 1rem;
    color: #2c3e50;
}

.comment-content p {
    font-size: 0.9rem;
    color: #7f8c8d;
    margin: 5px 0;
}

.comment-content small {
    font-size: 0.8rem;
}
.post-content img, .post-content video {
    width: 100%;
    max-height: 400px;
    border-radius: 10px;
    margin-top: 15px;
    object-fit: cover;
    box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
}
.delete-btn {
    padding: 10px 15px;
    font-size: 0.9rem;
    font-weight: bold;
    color: white;
    background-color: #e74c3c;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.delete-btn:hover {
    background-color: #c0392b;
}
/* 🔷 Modal Overlay */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.4);
    backdrop-filter: blur(5px); /* Subtle blur effect */
    color: black;
    overflow-y: auto;
}

/* 🔹 Modal Box */
.modal-content {
    background: #fff;
    padding: 20px;
    border-radius: 12px;
    width: 80%;
    margin: 10% auto;
    box-shadow: 0px 10px 20px rgba(0, 0, 0, 0.2);
    position: relative;
    animation: fadeIn 0.3s ease-in-out;
}

/* ✖ Close Button */
.close-modal {
    position: absolute;
    right: 15px;
    top: 10px;
    font-size: 18px;
    cursor: pointer;
    background: none;
    border: none;
    color: #555;
}

.close-modal:hover {
    color: #ff5f5f;
}

/* 📝 Form Inputs */
#editPostForm {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

#editPostForm label {
    font-weight: bold;
    font-size: 14px;
    color: #333;
    margin-bottom: 5px;
}

#editPostForm input[type="text"],
#editPostForm textarea {
    width: 100%;
    padding: 10px;
    border-radius: 8px;
    border: 1px solid #ccc;
    font-size: 14px;
    transition: border-color 0.3s;
    outline: none;
}

#editPostForm input[type="text"]:focus,
#editPostForm textarea:focus {
    border-color: #6a11cb;
    box-shadow: 0px 0px 5px rgba(106, 17, 203, 0.3);
}

/* 📂 File Upload */
#editPostForm input[type="file"] {
    padding: 5px;
    font-size: 14px;
}

/* ✅ Save Button */
#editPostForm button {
    background: linear-gradient(135deg, #6a11cb, #2575fc);
    color: white;
    border: none;
    padding: 12px;
    border-radius: 8px;
    font-size: 16px;
    cursor: pointer;
    transition: background 0.3s;
    margin-top: 10px;
}

#editPostForm button:hover {
    background: linear-gradient(135deg, #4a4aad, #1b5fd1);
}

/* 🔄 Animation */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
#edit-media-preview-container {
    margin-top: 10px;
    padding: 10px;
    background: #f9f9fb;
    border-radius: 8px;
    text-align: center;
    display: flex;
    flex-direction: column;
    align-items: center;
}

#edit-media-preview img,
#edit-media-preview video {
    max-width: 100%;
    max-height: 200px;
    border-radius: 8px;
    margin-top: 5px;
    box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.2);
}

#remove-media-btn {
    background: #ff4444;
    color: white;
    border: none;
    padding: 8px 12px;
    border-radius: 6px;
    margin-top: 10px;
    cursor: pointer;
    font-size: 14px;
}

#remove-media-btn:hover {
    background: #cc0000;
}
/* 📌 Group Posts Container */
.group-posts {
    max-width: 800px;
    margin: 20px auto;
}

/* 🎭 Group Post Card */
.post-card {
    background: white;
    border-radius: 10px;
    padding: 15px;
    box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
    margin-bottom: 20px;
    transition: 0.3s ease-in-out;
    display: flex;
    flex-direction: column;
}

.post-card:hover {
    box-shadow: 0px 6px 15px rgba(0, 0, 0, 0.15);
}

/* 🏷️ Post Header (User Info) */
.post-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 10px;
}

.post-header img {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    object-fit: cover;
}

.post-header .user-info {
    display: flex;
    flex-direction: column;
}

.post-header .user-info a {
    font-weight: bold;
    color: #333;
    text-decoration: none;
}


.post-header .user-info small {
    color: black;
}

.post-header .user-info a:hover {
    color: #6c63ff;
}
/* 🎨 Edit & Delete Buttons */
.edit-comment-btn, .delete-comment-btn, .reply-comment-btn {
    background: none;
    border: none;
    font-size: 14px;
    font-weight: bold;
    cursor: pointer;
    margin-left: 10px;
    transition: 0.3s ease-in-out;
}

/* ✏ Edit Button */
.edit-comment-btn {
    color: #f1c40f;
}

/* ✏ Edit Button */
.reply-comment-btn {
    color: royalblue;
}


.edit-comment-btn:hover {
    color: #d4ac0d;
    text-decoration: underline;
}

/* 🗑 Delete Button */
.delete-comment-btn {
    color: #e74c3c;
}

.delete-comment-btn:hover {
    color: #c0392b;
    text-decoration: underline;
}

/* ✨ Edited Indicator */
.comment-edited {
    font-size: 12px;
    color: #7f8c8d;
    font-style: italic;
    margin-left: 5px;
}
/* 📝 Reply Form */
.reply-form {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-top: 10px;
    padding: 10px;
    background: #f7f7f7;
    border-radius: 8px;
    border: 1px solid #ddd;
}

.reply-input {
    width: 100%;
    padding: 10px;
    border-radius: 6px;
    border: 1px solid #ccc;
    font-size: 14px;
    resize: none;
}

.reply-input:focus {
    border-color: #6c63ff;
    box-shadow: 0px 0px 5px rgba(108, 99, 255, 0.3);
    outline: none;
}

.submit-reply-btn {
    background: #6c63ff;
    color: white;
    border: none;
    padding: 8px 12px;
    border-radius: 6px;
    font-size: 14px;
    cursor: pointer;
    transition: background 0.3s ease-in-out;
}

.submit-reply-btn:hover {
    background: #4a4aad;
}

/* 💬 Replies Section */
.replies {
    margin-top: 10px;
    padding-left: 20px;
    border-left: 3px solid #ddd;
}

.reply {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    margin-bottom: 10px;
}

.reply img {
    width: 35px;
    height: 35px;
    border-radius: 50%;
}

.reply-content {
    background: white;
    padding: 8px 12px;
    border-radius: 8px;
    box-shadow: 0px 2px 4px rgba(0, 0, 0, 0.1);
    width: 100%;
}

.reply-content strong {
    font-size: 14px;
    color: #2c3e50;
}

.reply-content p {
    font-size: 13px;
    color: #555;
    margin: 5px 0;
}

.reply-content small {
    font-size: 12px;
    color: #999;
}
/* 🔲 Image Modal */
.image-modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.8);
    backdrop-filter: blur(5px);
    display: flex;
    justify-content: center;
    align-items: center;
}

/* 📦 Modal Content */
.image-modal-content {
    position: relative;
    max-width: 90%;
    max-height: 90%;
    overflow: auto;
    border-radius: 10px;
}

/* 🖼️ Zoomable Image */
#modalImage {
    max-width: 100%;
    max-height: 100%;
    transition: transform 0.2s ease-in-out;
    cursor: grab;
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
                    <li><a href="public_posts.php" ><i class="fas fa-file-alt"></i>  All Posts</a></li>
                    <li><a href="create_post.php"><i class="fas fa-pen"></i>Create Post</a></li>
                    <li><a href="groups.php" class="active"><i class="fas fa-users"></i>Groups</a></li>
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
                    <li><a href="public_posts.php" class="active"><i class="fas fa-file-alt"></i> All Posts</a></li>
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


    
        <h2 class="animate-on-scroll" >Posts in <?= htmlspecialchars($group['name']) ?></h2>

        <!-- Create Post Button -->
        <?php if ($isMember): ?>
            <a href="create_group_post.php?group_id=<?= $groupId ?>" class="create-post-btn"><i class="fas fa-pen"></i> Create New Post</a>
        <?php endif; ?>
        
            
        
        <!-- Posts Section -->
        <?php if (count($posts) > 0): ?>
            <?php foreach ($posts as $post): ?>
                <div class="post-card" id="<?= htmlspecialchars($post['id']) ?>">
                    <div class="post-header">
                        <a href="user_profile.php?id=<?= htmlspecialchars($post['user_id']) ?>"><img src="<?= htmlspecialchars($post['profile_picture']) ?: 'default-user.jpg' ?>" alt="Author Picture"></a>
                        <div class="user-info">
                        <a href="user_profile.php?id=<?= htmlspecialchars($post['user_id']) ?>"><?= htmlspecialchars($post['author_name']) ?></a>
                            <small><?= date('F j, Y - H:i A', strtotime($post['created_at'])) ?></small>
                        </div>
                    </div>
                    <div class="post-content" id="post-content">
                        <h2><?= htmlspecialchars($post['title']) ?></h2>
                        <?php if (!empty($post['media'])): ?>
                            <?php
                            $mediaPath = htmlspecialchars($post['media']);
                            $mediaExtension = pathinfo($mediaPath, PATHINFO_EXTENSION);
                            $videoExtensions = ['mp4', 'webm', 'ogg']; // Add more video formats if needed

                            if (in_array($mediaExtension, $videoExtensions)): ?>
                                <video controls style="width: 100%; max-height: 400px;">
                                    <source src="<?= $mediaPath ?>" type="video/<?= $mediaExtension ?>">
                                    Your browser does not support the video tag.
                                </video>
                            <?php else: ?>
                                <img src="<?= $mediaPath ?>" alt="Post Media" style="width: 100%; max-height: 400px; object-fit: cover;">
                            <?php endif; ?>
                        <?php endif; ?>
                        <p style="text-align: center; margin-top: 16px;"><?= nl2br(htmlspecialchars($post['content'])) ?></p>
                    </div>
                    <div class="post-actions">
                        <button class="like-btn" data-post-id="<?= $post['id'] ?>">❤ Like (<span><?= $post['like_count'] ?></span>)</button>
                        <a href="#comment-input-<?= $post['id'] ?>" class="comment-btn" data-post-id="<?= $post['id'] ?>"><i class="fas fa-message"></i> Comment (<span><?= $post['comment_count'] ?></span>)</a>

                        <?php if ($post['user_id'] == $userId): // Only show edit button if the user is the owner ?>
                            <button class="edit-btn" data-post-id="<?= $post['id'] ?>" data-title="<?= htmlspecialchars($post['title']) ?>" data-content="<?= htmlspecialchars($post['content']) ?>" data-media="<?= htmlspecialchars($post['media']) ?>">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                        <?php endif; ?>

                        <?php if ($userRole === 'admin' || $userRole === 'moderator' || $post['user_id'] == $userId): ?>
                            <button class="delete-btn" data-post-id="<?= $post['id'] ?>"><i class="fas fa-trash"></i> Delete</button>
                        <?php endif; ?>
                        <?php if ($userRole === 'admin' || $userRole === 'moderator'): ?>
                        <form action="pin_post.php" method="POST" style="display:inline;">
                            <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                            <input type="hidden" name="group_id" value="<?php echo $group['id']; ?>">
                            <button type="submit"><?php echo $post['pinned'] ? 'Unpin' : 'Pin'; ?> Post</button>
                        </form>
                    <?php endif; ?>
                    </div>

                

        <!-- Comments Section -->
<div class="post-comments" id="comments-container-<?= $post['id'] ?>">
    <h4>Comments</h4>
    <?php if (!empty($comments[$post['id']])): ?>
        <?php foreach ($comments[$post['id']] as $comment): ?>
            <div class="comment" id="comment-<?= $comment['id'] ?>">
                <a href="user_profile.php?id=<?= htmlspecialchars($comment['user_id']) ?>">
                    <img src="<?= htmlspecialchars($comment['commenter_picture']) ?: 'default-user.jpg' ?>" alt="Commenter Picture">
                </a>
                <div class="comment-content">
                    <strong><?= htmlspecialchars($comment['commenter_name']) ?></strong>
                    <p class="comment-text"><?= nl2br(htmlspecialchars($comment['comment'])) ?></p>
                    <small style="color: #7f8c8d;">
                        <?= date('F j, Y, g:i a', strtotime($comment['created_at'])) ?>
                        <?php if (!empty($comment['edited']) && $comment['edited'] == 1): ?>
                            <span class="comment-edited"> (Edited)</span>
                        <?php endif; ?>
                    </small>

                    <!-- Reply Button -->
                    <button class="reply-comment-btn" data-comment-id="<?= $comment['id'] ?>">↩ Reply</button>

                    <?php if ($comment['user_id'] == $userId): ?>
                        <button class="edit-comment-btn" data-comment-id="<?= $comment['id'] ?>">✏ Edit</button>
                        <button class="delete-comment-btn" data-comment-id="<?= $comment['id'] ?>">🗑 Delete</button>
                    <?php endif; ?>

                    <!-- Reply Form (Initially Hidden) -->
                    <div class="reply-form" id="reply-form-<?= $comment['id'] ?>" style="display: none;">
                        <textarea class="reply-input" id="reply-input-<?= $comment['id'] ?>" placeholder="Write a reply..."></textarea>
                        <button class="submit-reply-btn" data-comment-id="<?= $comment['id'] ?>">Reply</button>
                    </div>

                    <!-- Replies Container -->
                    <div class="replies" id="replies-container-<?= $comment['id'] ?>">
                        <!-- Replies will be inserted here -->
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p style="color: gray; font-size: 16px;">No comments yet. Be the first to comment!</p>
    <?php endif; ?>
</div>

<!-- Add New Comment -->
<div id="comment-div">
        <textarea id="comment-input-<?= $post['id'] ?>" placeholder="Write a comment..." style="width: 100%; padding: 10px; margin-top: 10px; border-radius: 5px; border: 1px solid #ddd;"></textarea>
        <button class="submit-comment-btn" data-post-id="<?= $post['id'] ?>" style="margin-top: 10px; padding: 10px 20px; background-color: #6c63ff; color: white; border: none; border-radius: 5px; cursor: pointer;">Submit Comment</button><br>
    </div>
    </div>
<?php endforeach; ?>
    <?php else: ?>
    <p style="text-align: center;">No posts in this group yet. Be the first to post!</p>
<?php endif; ?>
    </div>

    <div id="editPostModal" class="modal">
    <div class="modal-content">
        <button class="close-modal">&times;</button>
        <h2>Edit Post</h2>
        <form id="editPostForm">
            <input type="hidden" id="edit-post-id">
            
            <label for="edit-title">Title</label>
            <input type="text" id="edit-title" name="title" required>

            <label for="edit-content">Content</label>
            <textarea id="edit-content" name="content" rows="4" required></textarea>

            <!-- Media Preview Section -->
            <div id="edit-media-preview-container" style="display: none;">
                <p>Current Media:</p>
                <div id="edit-media-preview"></div>
                <button type="button" id="remove-media-btn" style="display: none;">Remove Media</button>
            </div>

            <label for="edit-media">Change Media (Optional)</label>
            <input type="file" id="edit-media" name="media" accept="image/*,video/*">

            <button type="submit">Save Changes</button>
        </form>
    </div>
</div>
      <!-- Image Modal -->
      <div id="imageModal" style="display: none" class="image-modal">
   
    <div class="image-modal-content">
        <img id="modalImage" src="" alt="Post Image">
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

document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener("click", function (e) {
        e.preventDefault();
        document.querySelector(this.getAttribute("href")).scrollIntoView({
            behavior: "smooth",
            block: "start"
        });
    });
});

document.addEventListener('DOMContentLoaded', function () {
    const editButtons = document.querySelectorAll('.edit-btn');
    const editPostModal = document.getElementById('editPostModal');
    const closeModal = document.querySelector('.close-modal');
    const editPostForm = document.getElementById('editPostForm');

    const editPostId = document.getElementById('edit-post-id');
    const editTitle = document.getElementById('edit-title');
    const editContent = document.getElementById('edit-content');
    const editMedia = document.getElementById('edit-media');

    const editMediaPreviewContainer = document.getElementById('edit-media-preview-container');
    const editMediaPreview = document.getElementById('edit-media-preview');
    const removeMediaBtn = document.getElementById('remove-media-btn');

    let originalMedia = null; // Store original media for reference

    // Open Modal with Post Data
    editButtons.forEach(button => {
        button.addEventListener('click', function () {
            editPostId.value = this.dataset.postId;
            editTitle.value = this.dataset.title;
            editContent.value = this.dataset.content;

            originalMedia = this.dataset.media; // Store original media

            if (originalMedia) {
                editMediaPreviewContainer.style.display = 'block';
                removeMediaBtn.style.display = 'block';
                displayMediaPreview(originalMedia);
            } else {
                editMediaPreviewContainer.style.display = 'none';
                removeMediaBtn.style.display = 'none';
            }

            editPostModal.style.display = 'block';
        });
    });

    // Close Modal
    closeModal.addEventListener('click', function () {
        editPostModal.style.display = 'none';
        resetPreview();
    });

    // Show Preview for New Uploaded Media
    editMedia.addEventListener('change', function () {
        const file = this.files[0];
        if (file) {
            editMediaPreviewContainer.style.display = 'block';
            removeMediaBtn.style.display = 'block';

            const reader = new FileReader();
            reader.onload = function (e) {
                displayMediaPreview(e.target.result);
            };
            reader.readAsDataURL(file);
        }
    });

    // Remove Media Button
    removeMediaBtn.addEventListener('click', function () {
        editMediaPreview.innerHTML = "";
        editMediaPreviewContainer.style.display = 'none';
        editMedia.value = ""; // Clear file input
        originalMedia = null;
    });

    // Function to Display Media Preview
    function displayMediaPreview(mediaSrc) {
        editMediaPreview.innerHTML = "";
        if (mediaSrc.endsWith(".mp4") || mediaSrc.endsWith(".mov")) {
            editMediaPreview.innerHTML = `<video controls><source src="${mediaSrc}" type="video/mp4"></video>`;
        } else {
            editMediaPreview.innerHTML = `<img src="${mediaSrc}" alt="Post to view Media">`;
        }
    }

    // Reset Media Preview
    function resetPreview() {
        editMediaPreview.innerHTML = "";
        editMedia.value = "";
        editMediaPreviewContainer.style.display = 'none';
        removeMediaBtn.style.display = 'none';
    }

    // Submit Edited Post
    editPostForm.addEventListener('submit', function (e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('post_id', editPostId.value);
        formData.append('title', editTitle.value);
        formData.append('content', editContent.value);

        if (editMedia.files[0]) {
            formData.append('media', editMedia.files[0]); // New media
        } else if (!originalMedia) {
            formData.append('remove_media', true); // Remove existing media
        }

        fetch('edit_group_post.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert("Post updated successfully!");
                location.reload();
            } else {
                alert("Error: " + data.message);
            }
        })
        .catch(error => console.error('Error:', error));
    });
});

document.addEventListener('DOMContentLoaded', function () {
    const likeButtons = document.querySelectorAll('.like-btn');

    likeButtons.forEach(button => {
        button.addEventListener('click', function () {
            const postId = this.dataset.postId;
            const likeCountElement = this.querySelector('span');

            fetch('like_group_post.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ post_id: postId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    likeCountElement.textContent = data.likes;
                } else {
                    alert(data.message);
                }
            })
            .catch(error => console.error('Error:', error));
        });
    });
});
document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll('.submit-comment-btn').forEach(button => {
        button.addEventListener('click', function () {
            const postId = this.dataset.postId;
            const commentInput = document.getElementById(`comment-input-${postId}`);
            const commentText = commentInput.value.trim();

            if (!commentText) {
                alert("Comment cannot be empty!");
                return;
            }

            // Send comment via AJAX
            fetch('comment_group_post.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ post_id: postId, comment: commentText })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const commentsContainer = document.getElementById(`comments-container-${postId}`);
                    const newComment = `
                        <div class="comment" id="comment-${data.comment_id}">
                            <a href="user_profile.php?id=${data.commenter_id}">
                                <img src="${data.commenter_picture || 'default-user.jpg'}" alt="User">
                            </a>
                            <div class="comment-content">
                                <strong>${data.commenter_name}</strong>
                                <p>${data.comment}</p>
                                <small style="color: gray">${data.created_at}</small>
                            </div>
                        </div>
                    `;
                    commentsContainer.insertAdjacentHTML('beforeend', newComment);
                    commentInput.value = ''; // Clear input field
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => console.error('Error:', error));
        });
    });

    // Fetch new comments every 5 seconds
    setInterval(() => {
        document.querySelectorAll('.comments-container').forEach(container => {
            const postId = container.dataset.postId;
            fetchNewComments(postId);
        });
    }, 2000);
});

// Fetch latest comments for a post
function fetchNewComments(postId) {
    fetch(`fetch_group_comments.php?post_id=${postId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const commentsContainer = document.getElementById(`comments-container-${postId}`);
                commentsContainer.innerHTML = ''; // Clear existing comments

                data.comments.forEach(comment => {
                    const commentHtml = `
                        <div class="comment" id="comment-${comment.id}">
                            <a href="user_profile.php?id=${comment.commenter_id}">
                                <img src="${comment.commenter_picture || 'default-user.jpg'}" alt="User">
                            </a>
                            <div class="comment-content">
                                <strong>${comment.commenter_name}</strong>
                                <p>${comment.comment}</p>
                                <small>${comment.created_at}</small>
                            </div>
                        </div>
                    `;
                    commentsContainer.insertAdjacentHTML('beforeend', commentHtml);
                });
            }
        })
        .catch(error => console.error('Error fetching comments:', error));
}
    document.addEventListener("DOMContentLoaded", function () {
        document.querySelectorAll('.delete-btn').forEach(button => {
            button.addEventListener('click', function () {
                const postId = this.dataset.postId;
                if (confirm("Are you sure you want to delete this post?")) {
                    fetch('delete_group_post.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ post_id: postId })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert("Post deleted successfully.");
                            location.reload(); // Reload the page to update the posts
                        } else {
                            alert("Error: " + data.message);
                        }
                    })
                    .catch(error => console.error('Error:', error));
                }
            });
        });
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
document.addEventListener("DOMContentLoaded", function () {
    // Edit Comment
    document.querySelectorAll('.edit-comment-btn').forEach(button => {
        button.addEventListener('click', function () {
            const commentId = this.dataset.commentId;
            const commentText = document.querySelector(`#comment-${commentId} .comment-text`);

            const newComment = prompt("Edit your comment:", commentText.textContent.trim());
            if (newComment && newComment !== commentText.textContent.trim()) {
                fetch('edit_group_comment.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ comment_id: commentId, comment: newComment })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        commentText.textContent = data.updated_comment;

                        // Check if "Edited" indicator exists, if not, add it
                        let editedIndicator = document.querySelector(`#comment-${commentId} .comment-edited`);
                        if (!editedIndicator) {
                            editedIndicator = document.createElement("span");
                            editedIndicator.classList.add("comment-edited");
                            editedIndicator.textContent = " (Edited)";
                            commentText.parentNode.appendChild(editedIndicator);
                        }
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => console.error('Error:', error));
            }
        });
    });

    // Delete Comment
    document.querySelectorAll('.delete-comment-btn').forEach(button => {
        button.addEventListener('click', function () {
            const commentId = this.dataset.commentId;
            if (confirm("Are you sure you want to delete this comment?")) {
                fetch('delete_group_comment.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ comment_id: commentId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById(`comment-${commentId}`).remove();
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => console.error('Error:', error));
            }
        });
    });
});
document.addEventListener("DOMContentLoaded", function () {
    // Show Reply Form When Reply Button is Clicked
    document.querySelectorAll('.reply-comment-btn').forEach(button => {
        button.addEventListener('click', function () {
            const commentId = this.dataset.commentId;
            const replyForm = document.getElementById(`reply-form-${commentId}`);
            replyForm.style.display = replyForm.style.display === 'block' ? 'none' : 'block';
        });
    });

    // Submit Reply
    document.querySelectorAll('.submit-reply-btn').forEach(button => {
        button.addEventListener('click', function () {
            const commentId = this.dataset.commentId;
            const replyInput = document.getElementById(`reply-input-${commentId}`);
            const replyText = replyInput.value.trim();

            if (!replyText) {
                alert("Reply cannot be empty!");
                return;
            }

            fetch('reply_group_comment.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ comment_id: commentId, reply: replyText })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const repliesContainer = document.getElementById(`replies-container-${commentId}`);
                    const newReply = `
                        <div class="reply">
                            <a href="user_profile.php?id=${data.replier_id}">
                                <img src="${data.replier_picture || 'default-user.jpg'}" alt="User">
                            </a>
                            <div class="reply-content">
                                <strong>${data.replier_name}</strong>
                                <p>${data.reply}</p>
                                <small>${data.created_at}</small>
                            </div>
                        </div>
                    `;
                    repliesContainer.insertAdjacentHTML('beforeend', newReply);
                    replyInput.value = ''; // Clear input field
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => console.error('Error:', error));
        });
    });

    // Fetch new replies every 5 seconds
    setInterval(() => {
        document.querySelectorAll('.replies').forEach(container => {
            const commentId = container.id.replace("replies-container-", "");
            fetchNewReplies(commentId);
        });
    }, 1000);
});

// Fetch latest replies for a comment
function fetchNewReplies(commentId) {
    fetch(`fetch_group_replies.php?comment_id=${commentId}`)
        .then(response => response.json())
        .then(data => {
            const repliesContainer = document.getElementById(`replies-container-${commentId}`);
            repliesContainer.innerHTML = ''; // Clear existing replies

            data.replies.forEach(reply => {
                const replyHtml = `
                    <div class="reply">
                        <a href="user_profile.php?id=${reply.replier_id}">
                            <img src="${reply.replier_picture || 'default-user.jpg'}" alt="User">
                        </a>
                        <div class="reply-content">
                            <strong>${reply.replier_name}</strong>
                            <p>${reply.reply}</p>
                            <small>${reply.created_at}</small>
                        </div>
                    </div>
                `;
                repliesContainer.insertAdjacentHTML('beforeend', replyHtml);
            });
        })
        .catch(error => console.error('Error fetching replies:', error));
}

document.addEventListener("DOMContentLoaded", function () {
    const modal = document.getElementById("imageModal");
    const modalImg = document.getElementById("modalImage");
    const closeModal = document.querySelector(".close-modal");

    // Open Image Modal
    document.querySelectorAll(".post-content img").forEach(image => {
        image.addEventListener("click", function () {
            modalImg.src = this.src;
            modal.style.display = "flex";
            modalImg.style.transform = "scale(1)"; // Reset zoom
        });
    });

    // Close Modal
    closeModal.addEventListener("click", function () {
        modal.style.display = "none";
    });

    // Close Modal on Outside Click
    modal.addEventListener("click", function (event) {
        if (event.target === modal) {
            modal.style.display = "none";
        }
    });

    // Enable Zoom In and Out
    let scale = 1;
    modalImg.addEventListener("wheel", function (event) {
        event.preventDefault();
        scale += event.deltaY * -0.01;
        scale = Math.min(Math.max(1, scale), 3); // Zoom range (1x to 3x)
        modalImg.style.transform = `scale(${scale})`;
    });
});
document.addEventListener("DOMContentLoaded", function () {
    const readAloudBtn = document.getElementById("readAloudBtn");

    readAloudBtn.addEventListener("click", () => {
        const selectedText = window.getSelection().toString().trim();

        if (selectedText) {
            const utterance = new SpeechSynthesisUtterance(selectedText);
            utterance.lang = "en-US"; // Language setting
            utterance.rate = 1;       // Speed of speech (1 is normal)
            utterance.pitch = 1;      // Pitch of voice

            speechSynthesis.speak(utterance);
        } else {
            alert("Please select some text to read aloud.");
        }
    });
});
</script>
</body>
</html>